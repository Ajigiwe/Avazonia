<?php
// controllers/HomeController.php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../core/Cache.php';
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

        // All product rails on the home page (New Drops, per-category rows,
        // pre-orders, wholesale, export) come from one cached payload.
        // Cache carries the 'products' tag: any product/seller write flushes it
        // (see Cache::flushTags callers), so edits show up immediately.
        $rails = Cache::remember('home.product_rails', 60, function () use ($productModel, $categoryModel) {
            // NEW DROPS — fetch a deeper pool than the 10 shown on the rail so
            // the "View more" footer can reveal the rest in place (10 shown +
            // up to 10 preloaded extras; extras cost nothing extra to query).
            $newDrops = $productModel->getAll(20, 0);
            $preorderProducts = $productModel->getPreorderProducts(8);

            // TOP 10 PER MAJOR CATEGORY — every top-level category that has products
            // gets a row of its 10 newest items (approved seller listings included).
            $categoryDrops = [];
            foreach ($categoryModel->getTopLevelsWithProducts() as $cat) {
                if ((int)$categoryModel->countProductsInSubtree((int)$cat['id']) === 0) continue;
                $catProducts = $productModel->getByCategory((int)$cat['id'], 10, 0);
                if (empty($catProducts)) continue;
                $categoryDrops[] = [
                    'category' => $cat,
                    'products' => $catProducts
                ];
            }

            // Marketplace blocks
            $wholesaleDeals = $productModel->getWholesaleDeals(8);
            $exportCars = $productModel->getExportListings(4);

            // Batch-fetch card slider images for every product at once.
            // This replaces one product_images query per rendered card.
            $newDrops = $productModel->attachCardImages($newDrops);
            foreach ($categoryDrops as &$drop) {
                $drop['products'] = $productModel->attachCardImages($drop['products']);
            }
            unset($drop);
            $preorderProducts = $productModel->attachCardImages($preorderProducts);
            $wholesaleDeals = $productModel->attachCardImages($wholesaleDeals);
            $exportCars = $productModel->attachCardImages($exportCars);

            return [
                'new_drops'      => $newDrops,
                'category_drops' => $categoryDrops,
                'preorders'      => $preorderProducts,
                'wholesale'      => $wholesaleDeals,
                'export_cars'    => $exportCars,
            ];
        }, ['products']);

        // Only show categories that contain visible inventory themselves or in a child category.
        $topCategoriesWithProducts = $categoryModel->getTopLevelsWithProducts();
        $mobileCategories = $topCategoriesWithProducts;
        
        $settings = $settingsModel->all();
        $gridIds = !empty($settings['home_mobile_category_grid'])
            ? array_filter(array_map('intval', explode(',', $settings['home_mobile_category_grid'])))
            : [];
        $categoryGrid = !empty($gridIds)
            ? $categoryModel->filterWithProducts($categoryModel->findByIds($gridIds))
            : $categoryModel->filterWithProducts($categoryModel->getGridCategories(7));

        // Homepage promo banners (admin-managed) shown between New Drops and the category rows
        $homeBanners = (new HomeBanner())->active();

        $wishlistIds = Session::get('user_id') ? $wishModel->getProductIds(Session::get('user_id')) : [];
        // Marketplace store rails (cheap queries, left uncached)
        $storeModel=new Store();
        $featuredBusinesses=$storeModel->getByType('business_retailer',4);
        $intlSuppliers=$storeModel->getByType('international_supplier',4);

        $this->view('home/index', [
            'newDrops' => $rails['new_drops'],
            'categoryDrops' => $rails['category_drops'],
            'homeBanners' => $homeBanners,
            'preorders' => $rails['preorders'],
            'mobileCategories' => $mobileCategories,
            'categoryGrid' => $categoryGrid,
            'wishlistIds' => $wishlistIds,
            'wholesaleDeals' => $rails['wholesale'],
            'exportCars' => $rails['export_cars'],
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
