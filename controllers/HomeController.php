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
        $preorderProducts = $productModel->getPreorderProducts(8);
        $categories = $categoryModel->getAll();

        // Fetch products for a few main categories to showcase on homepage
        $categoryShowcase = [];
        $showcaseCount = 0;
        foreach ($categories as $cat) {
            if (empty($cat['parent_id']) && $showcaseCount < 3) {
                $catProducts = $productModel->getByCategory($cat['id'], 4);
                if (!empty($catProducts)) {
                    $categoryShowcase[] = [
                        'category' => $cat,
                        'products' => $catProducts
                    ];
                    $showcaseCount++;
                }
            }
        }

        $wishlistIds = Session::get('user_id') ? $wishModel->getProductIds(Session::get('user_id')) : [];
        $popupSettings = $settingsModel->all();

        $this->view('home/index', [
            'featured' => $featuredProducts,
            'all_products' => $allProducts,
            'bestsellers' => $bestsellers,
            'preorders' => $preorderProducts,
            'categories' => $categories,
            'categoryShowcase' => $categoryShowcase,
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
