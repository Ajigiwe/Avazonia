<?php
// controllers/DealsController.php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../models/Product.php';
require_once __DIR__ . '/../models/Category.php';

require_once __DIR__ . '/../models/Wishlist.php';

class DealsController extends Controller {
    public function index() {
        $productModel = new Product();
        $categoryModel = new Category();
        $wishModel = new Wishlist();

        $wishlistIds = Session::get('user_id') ? $wishModel->getProductIds(Session::get('user_id')) : [];
        
        // Fetch specialized items
        $deals = $productModel->getDiscounted(12);
        $preorders = $productModel->getPreOrders(12);
        $dropshipping = $productModel->getDropshipping(12);

        $this->view('shop/deals', [
            'deals' => $deals,
            'preorders' => $preorders,
            'dropshipping' => $dropshipping,
            'title' => 'Exclusive Deals & Pre-Orders — Avazonia',
            'wishlistIds' => $wishlistIds
        ]);
    }
}
