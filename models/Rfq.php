<?php
// models/Rfq.php — Request for Quote
require_once __DIR__ . '/../core/Model.php';

class Rfq extends Model {
    public function create(array $data): int|false {
        $stmt=$this->db->prepare("INSERT INTO rfqs (buyer_user_id,product_id,seller_id,store_id,qty,specs,destination,message,status) VALUES (?,?,?,?,?,?,?,?,?)");
        $ok=$stmt->execute([
            $data['buyer_user_id'],
            $data['product_id'] ?? null,
            $data['seller_id'],
            $data['store_id'] ?? null,
            $data['qty'] ?? 1,
            $data['specs'] ?? null,
            $data['destination'] ?? null,
            $data['message'] ?? null,
            'pending'
        ]);
        return $ok ? (int)$this->db->lastInsertId() : false;
    }
    public function getBySeller(int $sellerId, int $limit=20): array {
        $stmt=$this->db->prepare("SELECT r.*, u.full_name as buyer_name, u.email as buyer_email, p.name as product_name FROM rfqs r LEFT JOIN users u ON r.buyer_user_id=u.id LEFT JOIN products p ON r.product_id=p.id WHERE r.seller_id=? ORDER BY r.created_at DESC LIMIT ".(int)$limit);
        $stmt->execute([$sellerId]);
        return $stmt->fetchAll();
    }
    public function getByBuyer(int $buyerId, int $limit=20): array {
        $stmt=$this->db->prepare("SELECT r.*, s.business_name, p.name as product_name FROM rfqs r LEFT JOIN sellers s ON r.seller_id=s.id LEFT JOIN products p ON r.product_id=p.id WHERE r.buyer_user_id=? ORDER BY r.created_at DESC LIMIT ".(int)$limit);
        $stmt->execute([$buyerId]);
        return $stmt->fetchAll();
    }
    public function findById(int $id): array|false {
        $stmt=$this->db->prepare("SELECT * FROM rfqs WHERE id=? LIMIT 1");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    public function updateStatus(int $id,string $status): bool {
        $stmt=$this->db->prepare("UPDATE rfqs SET status=?, updated_at=CURRENT_TIMESTAMP WHERE id=?");
        return $stmt->execute([$status,$id]);
    }
}
