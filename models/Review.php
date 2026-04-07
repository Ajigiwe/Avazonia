<?php
// models/Review.php
require_once __DIR__ . '/../core/Model.php';

class Review extends Model {
    protected $table = 'reviews';

    public function findByProduct($productId, $approvedOnly = true) {
        $sql = "SELECT r.*, u.full_name as user_name 
                FROM reviews r 
                LEFT JOIN users u ON r.user_id = u.id 
                WHERE r.product_id = ?";
        if ($approvedOnly) {
            $sql .= " AND r.is_approved = 1";
        }
        $sql .= " ORDER BY r.created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$productId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAverageRating($productId) {
        $stmt = $this->db->prepare("SELECT AVG(rating) as avg_rating, COUNT(*) as count 
                                   FROM reviews 
                                   WHERE product_id = ? AND is_approved = 1");
        $stmt->execute([$productId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function create($data) {
        $sql = "INSERT INTO reviews (product_id, user_id, reviewer_name, reviewer_location, rating, body, is_approved) 
                VALUES (:product_id, :user_id, :reviewer_name, :reviewer_location, :rating, :body, :is_approved)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($data);
    }
}
