<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../models/Seller.php';
require_once __DIR__ . '/../models/Store.php';
require_once __DIR__ . '/../models/Product.php';
require_once __DIR__ . '/../models/Order.php';
require_once __DIR__ . '/../models/Rfq.php';
require_once __DIR__ . '/../models/Settings.php';
require_once __DIR__ . '/../models/Category.php';
require_once __DIR__ . '/../core/Session.php';

class SellerController extends Controller {
    private function requireSeller(): array|false {
        if (!Session::get('user_id')) { $this->redirect(APP_URL.'/login'); return false; }
        $s=new Seller(); $seller=$s->findByUserId((int)Session::get('user_id'));
        if (!$seller) { $this->view('seller/apply'); return false; }
        return $seller;
    }
    private function requireVerified(): array|false {
        $seller=$this->requireSeller(); if (!$seller) return false;
        if (empty($seller['is_verified'])) { $this->redirect(APP_URL.'/seller/dashboard'); return false; }
        return $seller;
    }
    private function getSellerStats(int $sellerId): array {
        $productModel=new Product(); $orderModel=new Order();
        require_once __DIR__ . '/../models/Settings.php';
        $settings=new Settings();
        $commissionPct=(float)$settings->get('commission_pct',5);
        $earnings=$orderModel->getSellerEarnings($sellerId);
        $commission=round($earnings['gross_sales']*($commissionPct/100),2);
        return [
            'total_products' => $productModel->countBySeller($sellerId),
            'active_products' => $productModel->countActiveBySeller($sellerId),
            'pending_products' => $productModel->countPendingBySeller($sellerId),
            'total_orders' => $orderModel->countSellerOrders($sellerId),
            'gross_sales' => $earnings['gross_sales'],
            'commission' => $commission,
            'net_earnings' => round($earnings['gross_sales'] - $commission, 2),
            'pending_payout' => $earnings['pending_payout'],
            'commission_pct' => $commissionPct,
        ];
    }

    public function dashboard() {
        $seller=$this->requireSeller(); if (!$seller) return;
        $store=(new Store())->findBySellerId((int)$seller['id']);
        $products=(new Product())->getBySeller((int)$seller['id'],8,0);
        $rfqs=(new Rfq())->getBySeller((int)$seller['id'],5);
        $orders=(new Order())->getSellerOrders((int)$seller['id'],5);
        $stats=$this->getSellerStats((int)$seller['id']);
        $this->view('seller/dashboard', ['seller'=>$seller,'store'=>$store,'products'=>$products,'rfqs'=>$rfqs,'orders'=>$orders,'stats'=>$stats,'page'=>'overview']);
    }

    public function products() {
        $seller=$this->requireSeller(); if (!$seller) return;
        $store=(new Store())->findBySellerId((int)$seller['id']);
        $products=(new Product())->getBySeller((int)$seller['id'],50,0);
        $stats=$this->getSellerStats((int)$seller['id']);
        $success=$_GET['success'] ?? null;
        $this->view('seller/products', ['seller'=>$seller,'store'=>$store,'products'=>$products,'stats'=>$stats,'success'=>$success,'page'=>'products']);
    }

    public function editProduct($id) {
        $seller=$this->requireVerified(); if (!$seller) return;
        $product=(new Product())->findByIdAndSeller((int)$id, (int)$seller['id']);
        if (!$product) { $this->redirect(APP_URL.'/seller/products'); return; }
        if ($_SERVER['REQUEST_METHOD']==='POST') {
            $data=[
                'name' => trim($_POST['name']??''),
                'price_ghs' => (float)($_POST['price_ghs']??0),
                'stock_qty' => (int)($_POST['stock_qty']??0),
                'description' => trim($_POST['description']??''),
                'listing_type' => $_POST['listing_type']??'retail',
                'condition_type' => $_POST['condition_type']??'new',
                'moq' => !empty($_POST['moq'])?(int)$_POST['moq']:null,
                'visibility' => $_POST['visibility']??'public',
                'wholesale_price_ghs' => !empty($_POST['wholesale_price_ghs'])?(float)$_POST['wholesale_price_ghs']:null,
                'category_id' => (int)($_POST['category_id']??0) ?: null,
            ];
            if (!$data['name'] || !$data['price_ghs']) {
                $this->view('seller/edit_product', ['seller'=>$seller,'product'=>$product,'categories'=>(new Category())->getAll(),'error'=>'Name and price required','page'=>'products']);
                return;
            }
            (new Product())->updateBySeller((int)$id, (int)$seller['id'], $data);
            $this->redirect(APP_URL.'/seller/products?success=1');
            return;
        }
        $stats=$this->getSellerStats((int)$seller['id']);
        $this->view('seller/edit_product', ['seller'=>$seller,'product'=>$product,'categories'=>(new Category())->getAll(),'stats'=>$stats,'page'=>'products']);
    }

    public function deleteProduct($id) {
        $seller=$this->requireVerified(); if (!$seller) return;
        (new Product())->deleteBySeller((int)$id, (int)$seller['id']);
        $this->redirect(APP_URL.'/seller/products?success=deleted');
    }

    public function orders() {
        $seller=$this->requireSeller(); if (!$seller) return;
        $orders=(new Order())->getSellerOrders((int)$seller['id'],100);
        $stats=$this->getSellerStats((int)$seller['id']);
        $store=(new Store())->findBySellerId((int)$seller['id']);
        $this->view('seller/orders', ['seller'=>$seller,'store'=>$store,'orders'=>$orders,'stats'=>$stats,'page'=>'orders']);
    }

    public function orderDetail($id) {
        $seller=$this->requireSeller(); if (!$seller) return;
        $orderModel=new Order();
        $order=$orderModel->findById((int)$id);
        if (!$order) { $this->redirect(APP_URL.'/seller/orders'); return; }
        $items=$orderModel->findSellerItems((int)$seller['id'], (int)$id);
        if (empty($items)) { $this->redirect(APP_URL.'/seller/orders'); return; }
        if ($_SERVER['REQUEST_METHOD']==='POST') {
            $itemIdx=(int)($_POST['item_idx']??-1);
            $status=$_POST['seller_status']??'';
            if ($itemIdx>=0 && isset($items[$itemIdx])) {
                $orderModel->updateSellerItemStatus((int)$id, (int)$seller['id'], $status);
            }
            $this->redirect(APP_URL.'/seller/orders/'.$id);
            return;
        }
        $stats=$this->getSellerStats((int)$seller['id']);
        $store=(new Store())->findBySellerId((int)$seller['id']);
        $this->view('seller/order_detail', ['seller'=>$seller,'store'=>$store,'order'=>$order,'items'=>$items,'stats'=>$stats,'page'=>'orders']);
    }

    public function finances() {
        $seller=$this->requireSeller(); if (!$seller) return;
        $orderModel=new Order();
        $earnings=$orderModel->getSellerEarnings((int)$seller['id']);
        $history=$orderModel->getSellerEarningsHistory((int)$seller['id'],100);
        $stats=$this->getSellerStats((int)$seller['id']);
        $store=(new Store())->findBySellerId((int)$seller['id']);
        $this->view('seller/finances', ['seller'=>$seller,'store'=>$store,'earnings'=>$earnings,'history'=>$history,'stats'=>$stats,'page'=>'finances']);
    }

    public function financesCsv() {
        $seller=$this->requireSeller(); if (!$seller) return;
        $history=(new Order())->getSellerEarningsHistory((int)$seller['id'],500);
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="avazonia-earnings-'.date('Y-m-d').'.csv"');
        $out=fopen('php://output','w');
        fputcsv($out,['Order Ref','Date','Product','Qty','Unit Price','Line Total','Seller Status','Order Status']);
        foreach($history as $h) {
            fputcsv($out,[$h['order_ref'],$h['order_date'],$h['product_name'],$h['qty'],$h['unit_price_ghs'],$h['line_total'],$h['seller_order_status'],$h['order_status']]);
        }
        fclose($out); exit;
    }

    public function settings() {
        $seller=$this->requireSeller(); if (!$seller) return;
        $store=(new Store())->findBySellerId((int)$seller['id']);
        if ($_SERVER['REQUEST_METHOD']==='POST') {
            $name=trim($_POST['store_name']??'');
            $tagline=trim($_POST['tagline']??'');
            $description=trim($_POST['description']??'');
            $city=trim($_POST['city']??'');
            if (!$name) {
                $stats=$this->getSellerStats((int)$seller['id']);
                $this->view('seller/settings', ['seller'=>$seller,'store'=>$store,'error'=>'Store name required','stats'=>$stats,'page'=>'settings']);
                return;
            }
            require_once __DIR__ . '/../config/database.php';
            $db=db();
            $fields=['name=?','tagline=?','description=?','city=?'];
            $params=[$name,$tagline,$description,$city];
            // Logo upload
            if (!empty($_FILES['logo']['name']) && $_FILES['logo']['error']===UPLOAD_ERR_OK) {
                $ext=strtolower(pathinfo($_FILES['logo']['name'],PATHINFO_EXTENSION));
                if (in_array($ext,['jpg','jpeg','png','webp'])) {
                    $fn='store_logo_'.time().'_'.bin2hex(random_bytes(3)).'.'.$ext;
                    $dir='public/uploads/stores/'; if(!is_dir($dir)) mkdir($dir,0777,true);
                    if (move_uploaded_file($_FILES['logo']['tmp_name'],$dir.$fn)) {
                        $fields[]='logo_url=?'; $params[]=$dir.$fn;
                    }
                }
            }
            // Banner upload
            if (!empty($_FILES['banner']['name']) && $_FILES['banner']['error']===UPLOAD_ERR_OK) {
                $ext=strtolower(pathinfo($_FILES['banner']['name'],PATHINFO_EXTENSION));
                if (in_array($ext,['jpg','jpeg','png','webp'])) {
                    $fn='store_banner_'.time().'_'.bin2hex(random_bytes(3)).'.'.$ext;
                    $dir='public/uploads/stores/'; if(!is_dir($dir)) mkdir($dir,0777,true);
                    if (move_uploaded_file($_FILES['banner']['tmp_name'],$dir.$fn)) {
                        $fields[]='banner_url=?'; $params[]=$dir.$fn;
                    }
                }
            }
            if ($store) {
                $params[]=(int)$store['id'];
                $stmt=$db->prepare("UPDATE stores SET ".implode(', ',$fields)." WHERE id=?");
                $stmt->execute($params);
            } else {
                $st=new Store();
                $sid=$st->create((int)$seller['id'],['name'=>$name,'tagline'=>$tagline,'city'=>$city,'country_code'=>'GH']);
            }
            // Update seller description
            $stmt=$db->prepare("UPDATE sellers SET description=? WHERE id=?");
            $stmt->execute([$description,(int)$seller['id']]);
            $this->redirect(APP_URL.'/seller/settings?success=1');
            return;
        }
        $stats=$this->getSellerStats((int)$seller['id']);
        $this->view('seller/settings', ['seller'=>$seller,'store'=>$store,'stats'=>$stats,'page'=>'settings']);
    }

    public function rfqs() {
        $seller=$this->requireSeller(); if (!$seller) return;
        $rfqs=(new Rfq())->getBySeller((int)$seller['id'],50);
        $stats=$this->getSellerStats((int)$seller['id']);
        $store=(new Store())->findBySellerId((int)$seller['id']);
        $this->view('seller/rfqs', ['seller'=>$seller,'store'=>$store,'rfqs'=>$rfqs,'stats'=>$stats,'page'=>'rfqs']);
    }

    public function respondRfq($id) {
        $seller=$this->requireSeller(); if (!$seller) return;
        if ($_SERVER['REQUEST_METHOD']==='POST') {
            $action=$_POST['rfq_action']??'';
            $statusMap=['accept'=>'accepted','reject'=>'rejected','quote'=>'quoted'];
            if (isset($statusMap[$action])) {
                (new Rfq())->updateStatus((int)$id, $statusMap[$action]);
            }
        }
        $this->redirect(APP_URL.'/seller/rfqs');
    }

    public function apply() {
        if (!Session::get('user_id')) { $this->redirect(APP_URL.'/login'); return; }
        if ($_SERVER['REQUEST_METHOD']==='POST') {
            $type=$_POST['seller_type']??'individual';
            $allowed=['individual','business_retailer','wholesaler','manufacturer','international_supplier'];
            if (!in_array($type,$allowed)) $type='individual';
            $biz=trim($_POST['business_name']??'');
            $city=trim($_POST['city']??'');
            $s=new Seller(); $st=new Store();
            if ($s->findByUserId((int)Session::get('user_id'))) { $this->redirect(APP_URL.'/seller/dashboard'); return; }
            $docsArr=[];
            $dir='public/uploads/sellers/'; if(!is_dir($dir)) mkdir($dir,0777,true);
            if (!empty($_FILES['ghana_card']['name']) && $_FILES['ghana_card']['error']===UPLOAD_ERR_OK) {
                $ext=strtolower(pathinfo($_FILES['ghana_card']['name'],PATHINFO_EXTENSION));
                if (in_array($ext,['jpg','jpeg','png','webp'])) {
                    $fn='ghana_'.time().'_'.bin2hex(random_bytes(3)).'.'.$ext;
                    if (move_uploaded_file($_FILES['ghana_card']['tmp_name'],$dir.$fn)) $docsArr['ghana_card']=$dir.$fn;
                }
            }
            $faceData=$_POST['face_data']??'';
            if ($faceData && str_starts_with($faceData,'data:image/')) {
                $parts=explode(',', $faceData, 2);
                if (count($parts)===2) {
                    $bin=base64_decode($parts[1]);
                    if ($bin && strlen($bin) > 1000 && strlen($bin) < 5*1024*1024) {
                        $fn='face_'.time().'_'.bin2hex(random_bytes(3)).'.jpg';
                        if (file_put_contents($dir.$fn,$bin)) $docsArr['face_id']=$dir.$fn;
                    }
                }
            }
            if (empty($docsArr['ghana_card']) || empty($docsArr['face_id'])) {
                $this->view('seller/apply', ['error'=>'Ghana Card + Face capture both required for verification','seller_type'=>$type,'business_name'=>$biz,'city'=>$city]);
                return;
            }
            $docs=json_encode($docsArr);
            $sid=$s->create((int)Session::get('user_id'), ['seller_type'=>$type,'business_name'=>$biz?:Session::get('user_name'),'full_name'=>Session::get('user_name'),'country_code'=>'GH','city'=>$city,'verification_level'=>'phone_verified','docs'=>$docs]);
            if ($sid) $st->create($sid, ['name'=>$biz?:Session::get('user_name'),'country_code'=>'GH','city'=>$city]);
            $this->redirect(APP_URL.'/seller/dashboard');
            return;
        }
        $this->view('seller/apply');
    }

    public function newProduct() {
        $seller=$this->requireVerified(); if (!$seller) return;
        $store=(new Store())->findBySellerId((int)$seller['id']);
        if ($_SERVER['REQUEST_METHOD']==='POST') {
            $name=trim($_POST['name']??'');
            $price=(float)($_POST['price_ghs']??0);
            $catId=(int)($_POST['category_id']??0) ?: null;
            $stock=(int)($_POST['stock_qty']??0);
            $listing=$_POST['listing_type']??'retail';
            $allowed=['retail','wholesale','rfq','export']; if(!in_array($listing,$allowed)) $listing='retail';
            $moq=!empty($_POST['moq'])?(int)$_POST['moq']:null;
            $cond=$_POST['condition_type']??'new';
            if (!$name || !$price) { $this->view('seller/new_product',['seller'=>$seller,'store'=>$store,'error'=>'Name and price required','categories'=>(new Category())->getAll()]); return; }
            $slug=strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/','-',$name))).'-'.time();
            require_once __DIR__.'/../config/database.php';
            $db=db();
            $stmt=$db->prepare("INSERT INTO products (name,slug,category_id,seller_id,store_id,listing_type,condition_type,moq,price_ghs,currency,stock_qty,description,is_active,status_market) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
            $stmt->execute([$name,$slug,$catId,$seller['id'],$store['id']??null,$listing,$cond,$moq,$price,'GHS',$stock,trim($_POST['description']??''),1,'pending_review']);
            $pid=$db->lastInsertId();
            if (!empty($_POST['image_url'])) {
                $db->prepare("INSERT INTO product_images (product_id,url,is_primary) VALUES (?,?,1)")->execute([$pid, trim($_POST['image_url'])]);
            }
            $this->redirect(APP_URL.'/seller/products?success=1');
            return;
        }
        $this->view('seller/new_product',['seller'=>$seller,'store'=>$store,'categories'=>(new Category())->getAll()]);
    }
}
