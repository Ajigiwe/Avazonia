<?php
// models/Seller.php — Marketplace seller profile
require_once __DIR__ . '/../core/Model.php';

class Seller extends Model {
    public function findByUserId(int $userId): array|false {
        $stmt = $this->db->prepare("SELECT * FROM sellers WHERE user_id = ? LIMIT 1");
        $stmt->execute([$userId]);
        return $stmt->fetch();
    }
    public function findById(int $id): array|false {
        $stmt = $this->db->prepare("SELECT s.*, u.email, u.full_name, u.phone FROM sellers s LEFT JOIN users u ON s.user_id=u.id WHERE s.id = ? LIMIT 1");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    public function findActiveByUserId(int $userId): array|false {
        $stmt = $this->db->prepare("SELECT * FROM sellers WHERE user_id = ? AND is_active = 1 LIMIT 1");
        $stmt->execute([$userId]);
        return $stmt->fetch();
    }
    public function suspend(int $id): bool {
        $stmt = $this->db->prepare("UPDATE sellers SET is_active = 0, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        return $stmt->execute([$id]);
    }
    public function reactivate(int $id): bool {
        $stmt = $this->db->prepare("UPDATE sellers SET is_active = 1, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        return $stmt->execute([$id]);
    }
    public function findBySlug(string $slug): array|false {
        $stmt = $this->db->prepare("SELECT s.*, u.email, u.full_name FROM sellers s LEFT JOIN users u ON s.user_id=u.id WHERE s.slug = ? LIMIT 1");
        $stmt->execute([$slug]);
        return $stmt->fetch();
    }
    public function getAll(int $limit=20, int $offset=0, string $type=null): array {
        $sql = "SELECT s.*, u.full_name, u.email FROM sellers s LEFT JOIN users u ON s.user_id=u.id WHERE s.is_active=1";
        $params=[];
        if ($type) { $sql.=" AND s.seller_type=?"; $params[]=$type; }
        $sql.=" ORDER BY s.is_verified DESC, s.created_at DESC LIMIT ".(int)$limit." OFFSET ".(int)$offset;
        $stmt=$this->db->prepare($sql); $stmt->execute($params); return $stmt->fetchAll();
    }
    public function create(int $userId, array $data): int|false {
        $slug = $this->slugify($data['business_name'] ?? $data['full_name'] ?? 'seller-'.$userId);
        // ensure unique slug
        $base=$slug; $i=1; while($this->findBySlug($slug)) { $slug=$base.'-'.$i++; }
        $stmt=$this->db->prepare("INSERT INTO sellers (user_id,seller_type,business_name,slug,country_code,city,verification_level,is_verified,description,docs) VALUES (?,?,?,?,?,?,?,?,?,?)");
        $ok=$stmt->execute([
            $userId,
            $data['seller_type'] ?? 'individual',
            $data['business_name'] ?? $data['full_name'] ?? null,
            $slug,
            $data['country_code'] ?? 'GH',
            $data['city'] ?? null,
            $data['verification_level'] ?? 'phone_verified',
            $data['is_verified'] ?? 0,
            $data['description'] ?? null,
            $data['docs'] ?? null
        ]);
        return $ok ? (int)$this->db->lastInsertId() : false;
    }
    public function updateVerification(int $id, string $level, int $verified): bool {
        $stmt=$this->db->prepare("UPDATE sellers SET verification_level=?, is_verified=?, updated_at=CURRENT_TIMESTAMP WHERE id=?");
        return $stmt->execute([$level,$verified,$id]);
    }
    private function slugify(string $s): string {
        $s=strtolower(trim($s)); $s=preg_replace('/[^a-z0-9]+/','-',$s); return trim($s,'-') ?: 'seller';
    }
    public function verificationBadge(array $seller): string {
        $map=[
            'unverified'=>['label'=>'Unverified','color'=>'#9ca3af'],
            'phone_verified'=>['label'=>'Phone Verified','color'=>'#0ea5e9'],
            'business_verified'=>['label'=>'Verified Business','color'=>'#0ea5e9'],
            'company_verified'=>['label'=>'Verified Supplier','color'=>'#f59e0b'],
            'avazonia_verified'=>['label'=>'Avazonia Verified','color'=>'#16a34a'],
        ];
        $lvl=$seller['verification_level'] ?? 'unverified';
        $info=$map[$lvl] ?? $map['unverified'];
        if (!empty($seller['is_verified']) && $lvl==='avazonia_verified') $info=$map['avazonia_verified'];
        return $info['label'];
    }
}
