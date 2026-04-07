<?php
// controllers/ShopController.php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../models/Product.php';
require_once __DIR__ . '/../models/Category.php';
require_once __DIR__ . '/../models/Wishlist.php';

class ShopController extends Controller {
    public function index() {
        $productModel = new Product();
        $categoryModel = new Category();
        $wishModel = new Wishlist();

        $catSlug = $_GET['cat'] ?? null;
        $search = $_GET['q'] ?? null;
        $wishlistIds = Session::get('user_id') ? $wishModel->getProductIds(Session::get('user_id')) : [];

        if ($catSlug === 'deals-offers') {
            $products = $productModel->getDiscounted();
            $title = "Best Deals & Offers — Avazonia";
        } elseif ($catSlug === 'new-arrivals') {
            $products = $productModel->getNewArrivals();
            $title = "New Arrivals — Avazonia";
        } elseif ($catSlug === 'top-selling') {
            $products = $productModel->getTopSelling();
            $title = "Top Selling Gadgets — Avazonia";
        } elseif ($catSlug) {
            $category = $categoryModel->findBySlug($catSlug);
            $products = $productModel->getByCategory($category['id'] ?? 0);
            $title = ($category['name'] ?? 'Shop') . " — Avazonia";
        } elseif ($search) {
            $catId = $_GET['cat_id'] ?? null;
            $products = $productModel->search($search, $catId);
            $title = "Search results for '$search' — Avazonia";
        } else {
            $products = $productModel->getAll(24);
            $title = "All Products — Avazonia";
        }

        $categories = $categoryModel->getAll();

        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
            $this->view('shop/grid', [
                'products' => $products,
                'wishlistIds' => $wishlistIds
            ]);
        } else {
            $this->view('shop/index', [
                'products' => $products,
                'categories' => $categories,
                'title' => $title,
                'currentCat' => $catSlug,
                'wishlistIds' => $wishlistIds
            ]);
        }
    }

    public function suggestions() {
        header('Content-Type: application/json');
        
        $query = $_GET['q'] ?? '';
        $catId = $_GET['cat_id'] ?? null;

        if (strlen(trim($query)) < 2) {
            echo json_encode([]);
            return;
        }

        $productModel = new Product();
        $suggestions = $productModel->getSuggestions(trim($query), $catId, 5);

        $results = [];
        foreach ($suggestions as $s) {
            $results[] = [
                'name' => $s['name'],
                'url' => APP_URL . '/product/' . $s['slug'],
                'image' => APP_URL . '/' . ltrim($s['primary_image'] ?? 'assets/placeholder', '/')
            ];
        }

        echo json_encode($results);
    }
}
