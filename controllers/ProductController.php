<?php
// controllers/ProductController.php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../models/Product.php';

class ProductController extends Controller {
    public function show($slug) {
        $productModel = new Product();
        $product = $productModel->findBySlug($slug);

        if (!$product) {
            $this->redirect(APP_URL . '/404');
        }

        require_once __DIR__ . '/../models/Review.php';
        $reviewModel = new Review();
        $reviews = $reviewModel->findByProduct($product['id']);
        $ratingData = $reviewModel->getAverageRating($product['id']);

        $related = $productModel->getRelated($product['category_id'], $product['id'], 5);
        $images = $productModel->getImages($product['id']);
        $variants = $productModel->getVariants($product['id']);

        $this->view('product/detail', [
            'product' => $product,
            'images' => $images,
            'variants' => $variants,
            'reviews' => $reviews,
            'related' => $related,
            'avg_rating' => $ratingData['avg_rating'] ?? 0,
            'review_count' => $ratingData['count'] ?? 0
        ]);
    }
}
