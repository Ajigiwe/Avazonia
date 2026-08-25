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
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 24;
        $offset = ($page - 1) * $perPage;
        $wishlistIds = Session::get('user_id') ? $wishModel->getProductIds(Session::get('user_id')) : [];

        if ($catSlug === 'deals-offers') {
            $products = $productModel->getDiscounted($perPage, $offset);
            $total = $productModel->countDiscounted();
            $title = "Best Deals & Offers — Avazonia";
        } elseif ($catSlug === 'new-arrivals') {
            $products = $productModel->getNewArrivals($perPage, $offset);
            $total = $productModel->countAll();
            $title = "New Arrivals — Avazonia";
        } elseif ($catSlug === 'top-selling') {
            $products = $productModel->getTopSelling($perPage, $offset);
            $total = $productModel->countTopSelling();
            $title = "Top Selling Gadgets — Avazonia";
        } elseif ($catSlug) {
            $category = $categoryModel->findBySlug($catSlug);
            $catId = $category['id'] ?? 0;

            // If this category has subcategories, show the subcategory list (Jiji-style)
            // instead of product grid. Pass ?view=all to force product listing.
            $forceProducts = isset($_GET['view']) && $_GET['view'] === 'all';
            if ($category && !$forceProducts) {
                $children = $categoryModel->getChildrenWithCounts((int)$category['id']);
                if (!empty($children)) {
                    $breadcrumbs = $categoryModel->getBreadcrumbs((int)$category['id']);
                    $totalInSubtree = $categoryModel->countProductsInSubtree((int)$category['id']);
                    $this->view('shop/category', [
                        'category' => $category,
                        'children' => $children,
                        'breadcrumbs' => $breadcrumbs,
                        'totalInSubtree' => $totalInSubtree,
                        'categories' => $categoryModel->getAll(),
                    ]);
                    return;
                }
            }

            $products = $productModel->getByCategory($catId, $perPage, $offset);
            $total = $productModel->countByCategory($catId);
            $title = ($category['name'] ?? 'Shop') . " — Avazonia";
        } elseif ($search) {
            $catId = $_GET['cat_id'] ?? null;
            $products = $productModel->search($search, $catId, $perPage, $offset);
            $total = $productModel->countSearch($search, $catId);
            $title = "Search results for '$search' — Avazonia";
        } else {
            $products = $productModel->getAll($perPage, $offset);
            $total = $productModel->countAll();
            $title = "All Products — Avazonia";
        }

        $totalPages = (int)ceil($total / $perPage);
        $categories = $categoryModel->getAll();

        $pagination = [
            'page'      => $page,
            'perPage'   => $perPage,
            'total'     => $total,
            'totalPages' => $totalPages,
            'hasPrev'   => $page > 1,
            'hasNext'   => $page < $totalPages,
        ];

        $this->view('shop/index', [
            'products'   => $products,
            'categories' => $categories,
            'title'      => $title,
            'currentCat' => $catSlug,
            'wishlistIds' => $wishlistIds,
            'pagination' => $pagination,
        ]);
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
