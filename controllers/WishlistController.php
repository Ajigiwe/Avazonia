<?php
// controllers/WishlistController.php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../models/Wishlist.php';
require_once __DIR__ . '/../core/Session.php';

class WishlistController extends Controller {
    public function index() {
        if (!Session::get('user_id')) {
            $this->redirect(APP_URL . '/login');
        }

        $wishModel = new Wishlist();
        $items = $wishModel->findByUserId(Session::get('user_id'));

        $this->view('account/wishlist', [
            'items' => $items
        ]);
    }

    public function toggle() {
        if (!Session::get('user_id')) {
            echo json_encode(['success' => false, 'message' => 'Please login to save favorites', 'redirect' => APP_URL . '/login']);
            return;
        }

        $productId = $_POST['product_id'] ?? null;
        if (!$productId) {
            echo json_encode(['success' => false, 'message' => 'Invalid product']);
            return;
        }

        $wishModel = new Wishlist();
        $result = $wishModel->toggle(Session::get('user_id'), $productId);

        echo json_encode([
            'success' => true,
            'status' => $result['status'],
            'message' => $result['status'] === 'added' ? 'Added to favorites' : 'Removed from favorites'
        ]);
    }
}
