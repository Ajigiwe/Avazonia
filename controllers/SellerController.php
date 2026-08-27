<?php
// controllers/SellerController.php — seller dashboard (V1 simple)
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../models/Seller.php';
require_once __DIR__ . '/../models/Store.php';
require_once __DIR__ . '/../models/Product.php';
require_once __DIR__ . '/../models/Rfq.php';
require_once __DIR__ . '/../core/Session.php';

class SellerController extends Controller {
    private function requireSeller(): array|false {
        if (!Session::get('user_id')) { $this->redirect(APP_URL.'/login'); return false; }
        $s=new Seller(); $seller=$s->findByUserId((int)Session::get('user_id'));
        if (!$seller) { $this->view('seller/apply'); return false; }
        return $seller;
    }
    public function dashboard() {
        $seller=$this->requireSeller(); if (!$seller) return;
        $store=(new Store())->findBySellerId((int)$seller['id']);
        $products=(new Product())->getBySeller((int)$seller['id'],12,0);
        $rfqs=(new Rfq())->getBySeller((int)$seller['id'],10);
        $this->view('seller/dashboard', ['seller'=>$seller,'store'=>$store,'products'=>$products,'rfqs'=>$rfqs]);
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
            // handle Ghana Card + Face ID (both required)
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
        $seller=$this->requireSeller(); if (!$seller) return;
        // Gate: until verified, can't list and sell
        if (empty($seller['is_verified'])) {
            $this->view('seller/dashboard', ['seller'=>$seller,'store'=>(new Store())->findBySellerId((int)$seller['id']),'products'=>(new Product())->getBySeller((int)$seller['id'],12,0),'rfqs'=>(new Rfq())->getBySeller((int)$seller['id'],10),'error'=>'Verification required: your Ghana Card + Face ID is under review. You can list products after Admin verifies you.']);
            return;
        }
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
            if (!$name || !$price) { $this->view('seller/new_product',['seller'=>$seller,'store'=>$store,'error'=>'Name and price required','categories'=>(new \Category())->getAll()]); return; }
            $slug=strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/','-',$name))).'-'.time();
            require_once __DIR__.'/../config/database.php';
            $db=db();
            $stmt=$db->prepare("INSERT INTO products (name,slug,category_id,seller_id,store_id,listing_type,condition_type,moq,price_ghs,currency,stock_qty,description,is_active,status_market) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
            $stmt->execute([$name,$slug,$catId,$seller['id'],$store['id']??null,$listing,$cond,$moq,$price,'GHS',$stock,trim($_POST['description']??''),1,'pending_review']);
            $pid=$db->lastInsertId();
            // Optional image URL
            if (!empty($_POST['image_url'])) {
                $db->prepare("INSERT INTO product_images (product_id,url,is_primary) VALUES (?,?,1)")->execute([$pid, trim($_POST['image_url'])]);
            }
            $this->redirect(APP_URL.'/seller/dashboard?success=1');
            return;
        }
        $this->view('seller/new_product',['seller'=>$seller,'store'=>$store,'categories'=>(new \Category())->getAll()]);
    }
}
