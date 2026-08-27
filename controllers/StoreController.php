<?php
// controllers/StoreController.php — handles storefronts + sourcing + RFQ
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../models/Store.php';
require_once __DIR__ . '/../models/Seller.php';
require_once __DIR__ . '/../models/Product.php';
require_once __DIR__ . '/../models/Rfq.php';
require_once __DIR__ . '/../models/Category.php';
require_once __DIR__ . '/../core/Session.php';

class StoreController extends Controller {
    public function storefront($slug) {
        $storeModel=new Store(); $productModel=new Product();
        $store=$storeModel->findBySlug($slug);
        if (!$store) { http_response_code(404); echo "Store not found"; return; }
        $page=max(1,(int)($_GET['page']??1)); $perPage=24; $offset=($page-1)*$perPage;
        $products=$storeModel->getProducts((int)$store['id'],$perPage,$offset);
        $total=$productModel->countByStore((int)$store['id']);
        $totalPages=(int)ceil($total/$perPage);
        $this->view('store/show', [
            'store'=>$store,
            'products'=>$products,
            'pagination'=>['page'=>$page,'totalPages'=>$totalPages,'total'=>$total,'hasPrev'=>$page>1,'hasNext'=>$page<$totalPages]
        ]);
    }
    public function sourcing() {
        $storeModel=new Store(); $productModel=new Product(); $catModel=new Category();
        $intlStores=$storeModel->getByType('international_supplier',6);
        $wholesaleStores=$storeModel->getByType('wholesaler',6);
        $wholesaleDeals=$productModel->getWholesaleDeals(8);
        $exportCars=$productModel->getExportListings(8);
        $categories=$catModel->getAll();
        $this->view('store/sourcing', [
            'intlStores'=>$intlStores,
            'wholesaleStores'=>$wholesaleStores,
            'wholesaleDeals'=>$wholesaleDeals,
            'exportCars'=>$exportCars,
            'categories'=>$categories
        ]);
    }
    public function supplier($slug) {
        // Alias to storefront but with B2B emphasis
        $this->storefront($slug);
    }
    // POST /api/rfq
    public function createRfq() {
        header('Content-Type: application/json');
        if (!Session::get('user_id')) { echo json_encode(['success'=>false,'message'=>'Please login']); return; }
        $productId = (int)($_POST['product_id'] ?? 0);
        $sellerId  = (int)($_POST['seller_id'] ?? 0);
        $qty       = max(1,(int)($_POST['qty'] ?? 1));
        $specs     = trim($_POST['specs'] ?? '');
        $dest      = trim($_POST['destination'] ?? '');
        $msg       = trim($_POST['message'] ?? '');
        if (!$sellerId && $productId) {
            $p=(new Product())->findById($productId);
            $sellerId=(int)($p['seller_id'] ?? 0);
        }
        if (!$sellerId) { echo json_encode(['success'=>false,'message'=>'Seller not found']); return; }
        $storeId=null;
        if ($productId) { $p=(new Product())->findById($productId); $storeId=$p['store_id'] ?? null; }
        else {
            $s=(new Seller())->findById($sellerId);
            $st=(new Store())->findBySellerId($sellerId);
            $storeId=$st['id'] ?? null;
        }
        $rfq=new Rfq();
        $id=$rfq->create(['buyer_user_id'=>Session::get('user_id'),'product_id'=>$productId?:null,'seller_id'=>$sellerId,'store_id'=>$storeId,'qty'=>$qty,'specs'=>$specs,'destination'=>$dest,'message'=>$msg]);
        if ($id) {
            // Email + notification (best-effort)
            try {
                require_once __DIR__.'/../core/Mailer.php';
                require_once __DIR__.'/../models/Notification.php';
                $buyerId=Session::get('user_id'); $buyerEmail=Session::get('user_email') ?? ''; // fallback
                $db=db(); $buyer=$db->prepare("SELECT email,full_name FROM users WHERE id=?"); $buyer->execute([$buyerId]); $b=$buyer->fetch();
                $seller=$db->prepare("SELECT s.*, u.email as seller_email FROM sellers s LEFT JOIN users u ON s.user_id=u.id WHERE s.id=?"); $seller->execute([$sellerId]); $srow=$seller->fetch();
                if ($b && $srow) {
                    // Notify seller via email (log mailer in dev)
                    @Mailer::sendTemplate($srow['seller_email'] ?? SITE_EMAIL, $srow['business_name'] ?? 'Seller', 'New B2B Enquiry #'.$id.' — Avazonia Sourcing', 'order_status_update', ['toEmail'=>$srow['seller_email']??SITE_EMAIL,'toName'=>$srow['business_name']??'Seller','order'=>['order_ref'=>'RFQ-'.$id,'status'=>'pending']]);
                    Notification::create('new_rfq', "New RFQ #$id from {$b['full_name']} qty $qty", ['rfq_id'=>$id,'seller_id'=>$sellerId]);
                }
            } catch (Throwable $e) { error_log('[RFQ email] '.$e->getMessage()); }
            echo json_encode(['success'=>true,'message'=>'Enquiry sent. Seller will contact you.','id'=>$id]);
        } else echo json_encode(['success'=>false,'message'=>'Failed to send enquiry']);
    }
}
