<?php
// controllers/ReviewController.php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../models/Review.php';
require_once __DIR__ . '/../core/Session.php';

class ReviewController extends Controller {
    public function add() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect(APP_URL);
        }

        $productId = (int)($_POST['product_id'] ?? 0);
        $userId = Session::get('user_id');
        $name = $_POST['name'] ?? '';
        $rating = (int)($_POST['rating'] ?? 0);
        $comment = $_POST['comment'] ?? '';

        if (!$productId || !$rating || !$name) {
            $this->redirect(APP_URL . '/product/' . ($_POST['slug'] ?? ''));
        }

        $reviewModel = new Review();
        $reviewModel->create([
            'product_id' => $productId,
            'user_id' => $userId,
            'reviewer_name' => $name,
            'reviewer_location' => $_POST['location'] ?? 'Accra',
            'rating' => $rating,
            'body' => $comment,
            'is_approved' => 1 // Auto-approve for this project as requested "functional"
        ]);

        $this->redirect(APP_URL . '/product/' . $_POST['slug'] . '?msg=review_success#reviews');
    }
}
