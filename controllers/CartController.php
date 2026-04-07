<?php
// controllers/CartController.php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../models/Product.php';
require_once __DIR__ . '/../core/Session.php';

class CartController extends Controller {
    public function index() {
        $cart = Session::get('cart', []);
        $total = array_sum(array_map(fn($i) => $i['price_ghs'] * $i['qty'], $cart));

        $this->view('cart/index', [
            'cart' => $cart,
            'total' => $total
        ]);
    }

    public function add() {
        if (!Session::get('user_id')) {
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                return $this->json([
                    'success' => false, 
                    'redirect' => APP_URL . '/login?error=' . urlencode('Please login to add items to your cart.')
                ]);
            }
            $this->redirect(APP_URL . '/login?error=' . urlencode('Please login to add items to your cart.'));
        }

        $productId = (int)($_POST['product_id'] ?? 0);
        $qty = (int)($_POST['qty'] ?? 1);
        $variantId = isset($_POST['variant_id']) ? (int)$_POST['variant_id'] : null;

        if ($productId <= 0) {
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                return $this->json(['success' => false, 'message' => 'Invalid product.']);
            }
            $this->redirect(APP_URL . '/shop');
        }

        $productModel = new Product();
        $product = $productModel->findById($productId);

        if (!$product) {
            if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
                return $this->json(['success' => false, 'message' => 'Product not found.']);
            }
            $this->redirect(APP_URL . '/shop');
        }

        $cart = Session::get('cart', []);
        $key = $productId . '-' . ($variantId ?? '0');

        $price = (float)$product['price_ghs'];
        $name = $product['name'];
        $image = $product['primary_image'] ?? '';

        if ($variantId) {
            $variant = $productModel->getVariantById($variantId);
            if ($variant && $variant['product_id'] == $productId) { // ensure it matches the product
                if ($variant['price_override_ghs']) {
                    $price = (float)$variant['price_override_ghs'];
                }
                $labelParts = [];
                if ($variant['color']) $labelParts[] = $variant['color'];
                if ($variant['size']) $labelParts[] = $variant['size'];
                if (!empty($labelParts)) {
                    $name .= ' (' . implode(', ', $labelParts) . ')';
                }
                if ($variant['image_url']) {
                    $image = $variant['image_url'];
                }
            } else {
                $variantId = null; // invalid variant
                $key = $productId . '-0';
            }
        }

        if (isset($cart[$key])) {
            $cart[$key]['qty'] += $qty;
        } else {
            $cart[$key] = [
                'product_id' => $productId,
                'variant_id' => $variantId,
                'name' => $name,
                'price_ghs' => $price,
                'qty' => $qty,
                'image' => $image,
                'is_preorder' => (int)($product['is_preorder'] ?? 0)
            ];
        }

        Session::set('cart', $cart);

        if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
            return $this->json([
                'success' => true,
                'cart_count' => array_sum(array_column($cart, 'qty')),
                'message' => 'Added to cart'
            ]);
        }

        $this->redirect(APP_URL . '/cart');
    }

    public function update() {
        $key = $_POST['key'] ?? '';
        $qty = (int)($_POST['qty'] ?? 1);
        $cart = Session::get('cart', []);

        if (isset($cart[$key])) {
            if ($qty > 0) {
                $cart[$key]['qty'] = $qty;
            } else {
                unset($cart[$key]);
            }
        }

        Session::set('cart', $cart);
        $this->redirect(APP_URL . '/cart');
    }

    public function remove() {
        $key = $_POST['key'] ?? '';
        $cart = Session::get('cart', []);

        if (isset($cart[$key])) {
            unset($cart[$key]);
        }

        Session::set('cart', $cart);
        $this->redirect(APP_URL . '/cart');
    }
}
