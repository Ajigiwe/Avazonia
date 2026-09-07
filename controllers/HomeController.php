<?php
// controllers/HomeController.php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../models/Product.php';
require_once __DIR__ . '/../models/Category.php';

require_once __DIR__ . '/../models/Wishlist.php';
require_once __DIR__ . '/../models/Settings.php';
require_once __DIR__ . '/../models/Store.php';
require_once __DIR__ . '/../models/HomeBanner.php';

class HomeController extends Controller {
    public function index() {
        $productModel = new Product();
        $categoryModel = new Category();
        $wishModel = new Wishlist();
        $settingsModel = new Settings();

        // NEW DROPS — the 10 most recently added items from any category
        $newDrops = $productModel->getAll(10, 0);

        $preorderProducts = $productModel->getPreorderProducts(8);
        // All top-level categories for the mobile marketplace launcher grid
        $mobileCategories = $categoryModel->getTopLevels();
        
        $settings = $settingsModel->all();
        $gridIds = !empty($settings['home_mobile_category_grid'])
            ? array_filter(array_map('intval', explode(',', $settings['home_mobile_category_grid'])))
            : [];
        $categoryGrid = !empty($gridIds)
            ? $categoryModel->findByIds($gridIds)
            : $categoryModel->getGridCategories(7);

        // Homepage promo banners (admin-managed) shown between New Drops and the category rows
        $homeBanners = (new HomeBanner())->active();

        // TOP 10 PER MAJOR CATEGORY — every top-level category that has products
        // gets a row of its 10 newest items (approved seller listings included).
        $categoryDrops = [];
        foreach ($categoryModel->getTopLevels() as $cat) {
            if ((int)$categoryModel->countProductsInSubtree((int)$cat['id']) === 0) continue;
            $catProducts = $productModel->getByCategory((int)$cat['id'], 10, 0);
            if (empty($catProducts)) continue;
            $categoryDrops[] = [
                'category' => $cat,
                'products' => $catProducts
            ];
        }

        $wishlistIds = Session::get('user_id') ? $wishModel->getProductIds(Session::get('user_id')) : [];
        // Marketplace blocks
        $storeModel=new Store();
        $wholesaleDeals=$productModel->getWholesaleDeals(8);
        $exportCars=$productModel->getExportListings(4);
        $featuredBusinesses=$storeModel->getByType('business_retailer',4);
        $intlSuppliers=$storeModel->getByType('international_supplier',4);

        $this->view('home/index', [
            'newDrops' => $newDrops,
            'categoryDrops' => $categoryDrops,
            'homeBanners' => $homeBanners,
            'preorders' => $preorderProducts,
            'mobileCategories' => $mobileCategories,
            'categoryGrid' => $categoryGrid,
            'wishlistIds' => $wishlistIds,
            'wholesaleDeals' => $wholesaleDeals,
            'exportCars' => $exportCars,
            'featuredBusinesses' => $featuredBusinesses,
            'intlSuppliers' => $intlSuppliers,
            'settings' => $settings,
            'popup' => [
                'enabled'   => $settings['home_popup_enabled']   ?? '0',
                'type'      => $settings['home_popup_type']      ?? 'promo',
                'title'     => $settings['home_popup_title']     ?? 'SAMSUNG EXPERIENCE',
                'desc'      => $settings['home_popup_desc']      ?? 'Experience the next generation of gadgets.',
                'image'     => $settings['home_popup_image']     ?? 'public/assets/img/s25_promo.png',
                'discount'  => $settings['home_popup_discount']  ?? 'AVAZONIA10',
                'link'      => $settings['home_popup_link']      ?? '/shop',
                'btn_text'  => $settings['home_popup_btn_text']  ?? 'Shop Now',
                'frequency' => (int)($settings['home_popup_frequency'] ?? 3)
            ]
        ]);
    }
}
