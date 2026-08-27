<?php
// models/Store.php
require_once __DIR__ . '/../core/Model.php';

class Store extends Model {
    public function findBySlug(string $slug): array|false {
        $stmt=$this->db->prepare("SELECT st.*, s.seller_type, s.verification_level, s.is_verified, s.business_name, s.country_code as seller_country FROM stores st JOIN sellers s ON st.seller_id=s.id WHERE st.slug=? AND st.is_active=1 AND s.is_active=1 LIMIT 1");
        $stmt->execute([$slug]);
        return $stmt->fetch();
    }
    public function findBySellerId(int $sellerId): array|false {
        $stmt=$this->db->prepare("SELECT * FROM stores WHERE seller_id=? LIMIT 1");
        $stmt->execute([$sellerId]);
        return $stmt->fetch();
    }
    public function findById(int $id): array|false {
        $stmt=$this->db->prepare("SELECT st.*, s.seller_type, s.verification_level, s.is_verified FROM stores st JOIN sellers s ON st.seller_id=s.id WHERE st.id=? LIMIT 1");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    public function getFeatured(int $limit=6): array {
        $stmt=$this->db->prepare("SELECT st.*, s.seller_type, s.verification_level, s.is_verified, s.business_name FROM stores st JOIN sellers s ON st.seller_id=s.id WHERE st.is_active=1 AND s.is_active=1 AND st.is_featured=1 ORDER BY st.created_at DESC LIMIT ".(int)$limit);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    public function getByType(string $sellerType, int $limit=6): array {
        $stmt=$this->db->prepare("SELECT st.*, s.seller_type, s.verification_level, s.is_verified FROM stores st JOIN sellers s ON st.seller_id=s.id WHERE s.seller_type=? AND st.is_active=1 AND s.is_active=1 ORDER BY s.is_verified DESC, st.created_at DESC LIMIT ".(int)$limit);
        $stmt->execute([$sellerType]);
        return $stmt->fetchAll();
    }
    public function getAll(int $limit=20,int $offset=0): array {
        $stmt=$this->db->prepare("SELECT st.*, s.seller_type, s.verification_level, s.is_verified FROM stores st JOIN sellers s ON st.seller_id=s.id WHERE st.is_active=1 AND s.is_active=1 ORDER BY st.is_featured DESC, st.created_at DESC LIMIT ".(int)$limit." OFFSET ".(int)$offset);
        $stmt->execute();
        return $stmt->fetchAll();
    }
    public function create(int $sellerId, array $data): int|false {
        $slug=$data['slug'] ?? $this->slugify($data['name'] ?? 'store-'.$sellerId);
        $base=$slug; $i=1; while($this->findBySlug($slug)) { $slug=$base.'-'.$i++; }
        $stmt=$this->db->prepare("INSERT INTO stores (seller_id,slug,name,tagline,country_code,city,is_featured) VALUES (?,?,?,?,?,?,?)");
        $ok=$stmt->execute([$sellerId,$slug,$data['name'],$data['tagline']??null,$data['country_code']??'GH',$data['city']??null, $data['is_featured']??0]);
        return $ok ? (int)$this->db->lastInsertId() : false;
    }
    private function slugify(string $s): string { $s=strtolower(trim($s)); $s=preg_replace('/[^a-z0-9]+/','-',$s); return trim($s,'-') ?: 'store'; }
    public function getProducts(int $storeId, int $limit=12,int $offset=0): array {
        require_once __DIR__ . '/Product.php';
        $p=new Product();
        // delegate with store filter
        return $p->getByStore($storeId,$limit,$offset);
    }
}
