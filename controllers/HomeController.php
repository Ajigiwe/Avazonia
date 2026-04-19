<?php
// controllers/HomeController.php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../models/Product.php';
require_once __DIR__ . '/../models/Category.php';

require_once __DIR__ . '/../models/Wishlist.php';
require_once __DIR__ . '/../models/Settings.php';

class HomeController extends Controller {
    public function index() {
        $productModel = new Product();
        $categoryModel = new Category();
        $wishModel = new Wishlist();
        $settingsModel = new Settings();

        $featuredProducts = $productModel->getFeatured();
        $allProducts = $productModel->getAll(24); // Fetch 24 latest products
        $bestsellers = $productModel->getBestsellers(8);
        $categories = $categoryModel->getAll();
        $wishlistIds = Session::get('user_id') ? $wishModel->getProductIds(Session::get('user_id')) : [];
        $popupSettings = $settingsModel->all();

        $this->view('home/index', [
            'featured' => $featuredProducts,
            'all_products' => $allProducts,
            'bestsellers' => $bestsellers,
            'categories' => $categories,
            'wishlistIds' => $wishlistIds,
            'settings' => $popupSettings,
            'popup' => [
                'enabled'   => $popupSettings['home_popup_enabled']   ?? '0',
                'type'      => $popupSettings['home_popup_type']      ?? 'promo',
                'title'     => $popupSettings['home_popup_title']     ?? 'SAMSUNG EXPERIENCE',
                'desc'      => $popupSettings['home_popup_desc']      ?? 'Experience the next generation of gadgets.',
                'image'     => $popupSettings['home_popup_image']     ?? 'public/assets/img/s25_promo.png',
                'discount'  => $popupSettings['home_popup_discount']  ?? 'AVAZONIA10',
                'link'      => $popupSettings['home_popup_link']      ?? '/shop',
                'btn_text'  => $popupSettings['home_popup_btn_text']  ?? 'Shop Now',
                'frequency' => (int)($popupSettings['home_popup_frequency'] ?? 3)
            ]
        ]);
    }
}
