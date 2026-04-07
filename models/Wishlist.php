<?php
// models/Wishlist.php
require_once __DIR__ . '/../core/Model.php';

class Wishlist extends Model {
    protected $table = 'wishlist';

    public function toggle($userId, $productId) {
        $db = db();
        $stmt = $db->prepare("SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$userId, $productId]);
        if ($stmt->fetch()) {
            $stmt = $db->prepare("DELETE FROM wishlist WHERE user_id = ? AND product_id = ?");
            $stmt->execute([$userId, $productId]);
            return ['status' => 'removed'];
        } else {
            $stmt = $db->prepare("INSERT INTO wishlist (user_id, product_id) VALUES (?, ?)");
            $stmt->execute([$userId, $productId]);
            return ['status' => 'added'];
        }
    }

    public function findByUserId($userId) {
        $db = db();
        $stmt = $db->prepare("
            SELECT w.*, p.name, p.price_ghs, p.compare_at_price_ghs, p.slug, p.is_preorder, p.is_dropshipping, pi.url as primary_image 
            FROM wishlist w 
            JOIN products p ON w.product_id = p.id 
            LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
            WHERE w.user_id = ?
            ORDER BY w.created_at DESC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function getProductIds($userId) {
        $db = db();
        $stmt = $db->prepare("SELECT product_id FROM wishlist WHERE user_id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}
