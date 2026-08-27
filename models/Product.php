<?php
// models/Product.php
require_once __DIR__ . '/../core/Model.php';

class Product extends Model {
    private function createDeletedProductBackup($productId, $product, $ordersUsingProduct) {
        $backupDir = __DIR__ . '/../backups/deleted-products/';
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        $imagesStmt = $this->db->prepare("SELECT * FROM product_images WHERE product_id = ? ORDER BY id ASC");
        $imagesStmt->execute([(int)$productId]);

        $variantsStmt = $this->db->prepare("SELECT * FROM variants WHERE product_id = ? ORDER BY id ASC");
        $variantsStmt->execute([(int)$productId]);

        $reviewsStmt = $this->db->prepare("SELECT * FROM reviews WHERE product_id = ? ORDER BY id ASC");
        $reviewsStmt->execute([(int)$productId]);

        $wishlistStmt = $this->db->prepare("SELECT * FROM wishlist WHERE product_id = ? ORDER BY id ASC");
        $wishlistStmt->execute([(int)$productId]);

        $orderItemsStmt = $this->db->prepare("SELECT * FROM order_items WHERE product_id = ? ORDER BY id ASC");
        $orderItemsStmt->execute([(int)$productId]);

        $backupPayload = [
            'backup_type' => 'deleted_product',
            'created_at' => date('c'),
            'product_id' => (int)$productId,
            'orders_using_product' => (int)$ordersUsingProduct,
            'product' => $product,
            'product_images' => $imagesStmt->fetchAll(),
            'variants' => $variantsStmt->fetchAll(),
            'reviews' => $reviewsStmt->fetchAll(),
            'wishlist_entries' => $wishlistStmt->fetchAll(),
            'order_items' => $orderItemsStmt->fetchAll()
        ];

        $filename = 'product_' . (int)$productId . '_' . date('Y-m-d_His') . '.json';
        file_put_contents(
            $backupDir . $filename,
            json_encode($backupPayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );

        return $filename;
    }

    private function getMinStock() {
        static $minStock = null;
        if ($minStock === null) {
            require_once __DIR__ . '/Settings.php';
            $settings = new Settings();
            $minStock = (int)$settings->get('min_stock_threshold', 1);
        }
        return $minStock;
    }

    private function getStockSql() {
        return " AND (:min_stock = :min_stock)";
    }
    private function sellerSelect(): string {
        return ", s.business_name as seller_name, s.seller_type, s.verification_level, s.is_verified, st.slug as store_slug, st.name as store_name ";
    }
    private function sellerJoins(): string {
        return " LEFT JOIN sellers s ON p.seller_id=s.id LEFT JOIN stores st ON p.store_id=st.id ";
    }
    private function marketplaceWhere(): string {
        $extra = " AND (p.status_market IS NULL OR p.status_market='active') ";
        // Gate: until sellers are verified, their products are not sellable → hide unverified sellers' products
        // Use EXISTS subquery so queries without seller JOIN still work (fixes s.is_verified no such column)
        $extra .= " AND (p.seller_id IS NULL OR EXISTS (SELECT 1 FROM sellers _vs WHERE _vs.id=p.seller_id AND _vs.is_verified=1)) ";
        try {
            if (session_status()===PHP_SESSION_ACTIVE && class_exists('Session')) {
                $bt = \Session::get('buyer_type');
                if ($bt==='business') { $extra .= " AND p.visibility IN ('public','b2b_only','retail_only') "; }
                else { $extra .= " AND p.visibility IN ('public','retail_only') "; }
            } else {
                $extra .= " AND p.visibility IN ('public','retail_only') ";
            }
        } catch (Throwable $e) { $extra .= " AND p.visibility IN ('public','retail_only') "; }
        return $extra;
    }

    public function getAll($limit = 12, $offset = 0) {
        $sql = "SELECT p.*, b.name as brand_name, c.name as category_name, pi.url as primary_image, (SELECT AVG(rating) FROM reviews WHERE product_id = p.id AND is_approved = 1) as avg_rating ".$this->sellerSelect()."
                FROM products p 
                LEFT JOIN brands b ON p.brand_id = b.id
                LEFT JOIN categories c ON p.category_id = c.id
                LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1 ".$this->sellerJoins()."
                WHERE p.is_active = 1 " . $this->getStockSql() . $this->marketplaceWhere() . " 
                ORDER BY p.created_at DESC LIMIT :limit OFFSET :offset";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':min_stock', $this->getMinStock(), PDO::PARAM_INT);
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function countAll() {
        $sql = "SELECT COUNT(*) FROM products p LEFT JOIN sellers s ON p.seller_id=s.id WHERE p.is_active = 1 " . $this->getStockSql() . $this->marketplaceWhere();
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':min_stock', $this->getMinStock(), PDO::PARAM_INT);
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    public function findBySlug($slug) {
        $sql = "SELECT p.*, b.name as brand_name, c.name as category_name, pi.url as primary_image, (SELECT AVG(rating) FROM reviews WHERE product_id = p.id AND is_approved = 1) as avg_rating ".$this->sellerSelect()."
                FROM products p 
                LEFT JOIN brands b ON p.brand_id = b.id 
                LEFT JOIN categories c ON p.category_id = c.id 
                LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1 ".$this->sellerJoins()."
                WHERE p.slug = :slug AND p.is_active = 1 " . $this->getStockSql();
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':slug', $slug, PDO::PARAM_STR);
        $stmt->bindValue(':min_stock', $this->getMinStock(), PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch();
    }

    public function getFeatured() {
        $sql = "SELECT p.*, b.name as brand_name, c.name as category_name, pi.url as primary_image, (SELECT AVG(rating) FROM reviews WHERE product_id = p.id AND is_approved = 1) as avg_rating ".$this->sellerSelect()."
                FROM products p 
                LEFT JOIN brands b ON p.brand_id = b.id
                LEFT JOIN categories c ON p.category_id = c.id
                LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1 ".$this->sellerJoins()."
                WHERE p.is_active = 1 AND p.is_featured = 1 " . $this->getStockSql() . $this->marketplaceWhere() . " 
                ORDER BY p.created_at DESC LIMIT 8";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':min_stock', $this->getMinStock(), PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getBestsellers($limit = 8) {
        $sql = "SELECT p.*, b.name as brand_name, c.name as category_name, pi.url as primary_image, (SELECT AVG(rating) FROM reviews WHERE product_id = p.id AND is_approved = 1) as avg_rating ".$this->sellerSelect()."
                FROM products p 
                LEFT JOIN brands b ON p.brand_id = b.id 
                LEFT JOIN categories c ON p.category_id = c.id
                LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1 ".$this->sellerJoins()."
                WHERE p.is_active = 1 AND p.is_bestseller = 1 " . $this->getStockSql() . $this->marketplaceWhere() . " 
                ORDER BY p.created_at DESC LIMIT :limit";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':min_stock', $this->getMinStock(), PDO::PARAM_INT);
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getPreorderProducts($limit = 8) {
        $sql = "SELECT p.*, b.name as brand_name, c.name as category_name, pi.url as primary_image, (SELECT AVG(rating) FROM reviews WHERE product_id = p.id AND is_approved = 1) as avg_rating 
                FROM products p 
                LEFT JOIN brands b ON p.brand_id = b.id
                LEFT JOIN categories c ON p.category_id = c.id
                LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1 
                WHERE p.is_active = 1 AND p.is_preorder = 1 " . $this->getStockSql() . " 
                ORDER BY p.created_at DESC LIMIT :limit";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':min_stock', $this->getMinStock(), PDO::PARAM_INT);
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function findById($id) {
        $stmt = $this->db->prepare("SELECT p.*, pi.url as primary_image, (SELECT AVG(rating) FROM reviews WHERE product_id = p.id AND is_approved = 1) as avg_rating FROM products p LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1 WHERE p.id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function getImages($productId) {
        $stmt = $this->db->prepare("SELECT * FROM product_images WHERE product_id = ? ORDER BY is_primary DESC, sort_order ASC, id ASC");
        $stmt->execute([$productId]);
        return $stmt->fetchAll();
    }

    public function getVariants($productId) {
        $stmt = $this->db->prepare("SELECT * FROM variants WHERE product_id = ? ORDER BY id ASC");
        $stmt->execute([$productId]);
        return $stmt->fetchAll();
    }

    public function getVariantById($variantId) {
        $stmt = $this->db->prepare("SELECT * FROM variants WHERE id = ?");
        $stmt->execute([$variantId]);
        return $stmt->fetch();
    }

    public function getByCategory($categoryId, $limit = 24, $offset = 0) {
        $sql = "SELECT p.*, b.name as brand_name, c.name as category_name, pi.url as primary_image, (SELECT AVG(rating) FROM reviews WHERE product_id = p.id AND is_approved = 1) as avg_rating 
                FROM products p 
                LEFT JOIN brands b ON p.brand_id = b.id 
                LEFT JOIN categories c ON p.category_id = c.id
                LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1 
                WHERE (p.category_id = :cat OR p.category_id IN (SELECT id FROM categories WHERE parent_id = :cat)) AND p.is_active = 1 " . $this->getStockSql() . $this->marketplaceWhere() . " 
                ORDER BY p.created_at DESC LIMIT :limit OFFSET :offset";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':cat', (int)$categoryId, PDO::PARAM_INT);
        $stmt->bindValue(':min_stock', $this->getMinStock(), PDO::PARAM_INT);
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function countByCategory($categoryId) {
        $sql = "SELECT COUNT(*) FROM products p 
                WHERE (p.category_id = :cat OR p.category_id IN (SELECT id FROM categories WHERE parent_id = :cat)) 
                AND p.is_active = 1 " . $this->getStockSql() . $this->marketplaceWhere();
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':cat', (int)$categoryId, PDO::PARAM_INT);
        $stmt->bindValue(':min_stock', $this->getMinStock(), PDO::PARAM_INT);
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    public function getRelated($categoryId, $excludeId, $limit = 5) {
        $randFn = $this->db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite' ? 'RANDOM()' : 'RAND()';
        $sql = "SELECT p.*, b.name as brand_name, c.name as category_name, pi.url as primary_image, (SELECT AVG(rating) FROM reviews WHERE product_id = p.id AND is_approved = 1) as avg_rating 
                FROM products p 
                LEFT JOIN brands b ON p.brand_id = b.id 
                LEFT JOIN categories c ON p.category_id = c.id
                LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1 
                WHERE (p.category_id = :cat OR p.category_id IN (SELECT id FROM categories WHERE parent_id = :cat)) AND p.id != :exc AND p.is_active = 1 " . $this->getStockSql() . " 
                ORDER BY $randFn LIMIT :limit";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':cat', (int)$categoryId, PDO::PARAM_INT);
        $stmt->bindValue(':exc', (int)$excludeId, PDO::PARAM_INT);
        $stmt->bindValue(':min_stock', $this->getMinStock(), PDO::PARAM_INT);
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function search($query, $categoryId = null, $limit = 24, $offset = 0, array $filters=[]) {
        $catFilter = $categoryId ? " AND (p.category_id = :cat_id OR p.category_id IN (SELECT id FROM categories WHERE parent_id = :cat_id)) " : "";
        $mpFilter=""; $mpParams=[];
        if (!empty($filters['listing_type'])) { $mpFilter.=" AND p.listing_type=:lt "; $mpParams[':lt']=$filters['listing_type']; }
        if (!empty($filters['condition_type'])) { $mpFilter.=" AND p.condition_type=:ct "; $mpParams[':ct']=$filters['condition_type']; }
        if (!empty($filters['vehicle_origin'])) { $mpFilter.=" AND p.vehicle_origin=:vo "; $mpParams[':vo']=$filters['vehicle_origin']; }
        if (!empty($filters['location_country'])) { $mpFilter.=" AND p.location_country=:lc "; $mpParams[':lc']=$filters['location_country']; }
        $sql = "SELECT p.*, b.name as brand_name, c.name as category_name, pi.url as primary_image, (SELECT AVG(rating) FROM reviews WHERE product_id = p.id AND is_approved = 1) as avg_rating ".$this->sellerSelect()."
                FROM products p 
                LEFT JOIN brands b ON p.brand_id = b.id 
                LEFT JOIN categories c ON p.category_id = c.id
                LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1 ".$this->sellerJoins()."
                WHERE (p.name LIKE :q1 OR p.description LIKE :q2) AND p.is_active = 1 " . $this->getStockSql() . $catFilter . $mpFilter . $this->marketplaceWhere() . "
                ORDER BY p.created_at DESC LIMIT :limit OFFSET :offset";
        $stmt = $this->db->prepare($sql);
        $term = "%$query%";
        $stmt->bindValue(':q1', $term, PDO::PARAM_STR);
        $stmt->bindValue(':q2', $term, PDO::PARAM_STR);
        if ($categoryId) $stmt->bindValue(':cat_id', (int)$categoryId, PDO::PARAM_INT);
        foreach($mpParams as $k=>$v) $stmt->bindValue($k,$v);
        $stmt->bindValue(':min_stock', $this->getMinStock(), PDO::PARAM_INT);
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function countSearch($query, $categoryId = null, array $filters=[]) {
        $catFilter = $categoryId ? " AND (p.category_id = :cat_id OR p.category_id IN (SELECT id FROM categories WHERE parent_id = :cat_id)) " : "";
        $mpFilter=""; $mpParams=[];
        if (!empty($filters['listing_type'])) { $mpFilter.=" AND p.listing_type=:lt "; $mpParams[':lt']=$filters['listing_type']; }
        if (!empty($filters['condition_type'])) { $mpFilter.=" AND p.condition_type=:ct "; $mpParams[':ct']=$filters['condition_type']; }
        if (!empty($filters['vehicle_origin'])) { $mpFilter.=" AND p.vehicle_origin=:vo "; $mpParams[':vo']=$filters['vehicle_origin']; }
        if (!empty($filters['location_country'])) { $mpFilter.=" AND p.location_country=:lc "; $mpParams[':lc']=$filters['location_country']; }
        $sql = "SELECT COUNT(*) FROM products p ".$this->sellerJoins()."
                WHERE (p.name LIKE :q1 OR p.description LIKE :q2) AND p.is_active = 1 " . $this->getStockSql() . $catFilter . $mpFilter;
        $stmt = $this->db->prepare($sql);
        $term = "%$query%";
        $stmt->bindValue(':q1', $term, PDO::PARAM_STR);
        $stmt->bindValue(':q2', $term, PDO::PARAM_STR);
        if ($categoryId) $stmt->bindValue(':cat_id', (int)$categoryId, PDO::PARAM_INT);
        foreach($mpParams as $k=>$v) $stmt->bindValue($k,$v);
        $stmt->bindValue(':min_stock', $this->getMinStock(), PDO::PARAM_INT);
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    public function getSuggestions($query, $categoryId = null, $limit = 5) {
        $catFilter = $categoryId ? " AND (p.category_id = :cat_id OR p.category_id IN (SELECT id FROM categories WHERE parent_id = :cat_id)) " : "";
        $sql = "SELECT p.name, p.slug, pi.url as primary_image 
                FROM products p 
                LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1 
                WHERE p.name LIKE :q AND p.is_active = 1 " . $this->getStockSql() . $catFilter . " 
                ORDER BY p.name ASC LIMIT :limit";
        $stmt = $this->db->prepare($sql);
        $term = "%$query%";
        $stmt->bindValue(':q', $term, PDO::PARAM_STR);
        if ($categoryId) $stmt->bindValue(':cat_id', (int)$categoryId, PDO::PARAM_INT);
        $stmt->bindValue(':min_stock', $this->getMinStock(), PDO::PARAM_INT);
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getDiscounted($limit = 24, $offset = 0) {
        $sql = "SELECT p.*, b.name as brand_name, pi.url as primary_image, (SELECT AVG(rating) FROM reviews WHERE product_id = p.id AND is_approved = 1) as avg_rating 
                FROM products p 
                LEFT JOIN brands b ON p.brand_id = b.id 
                LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1 
                WHERE p.compare_at_price_ghs IS NOT NULL AND p.compare_at_price_ghs > p.price_ghs 
                AND p.is_active = 1 " . $this->getStockSql() . $this->marketplaceWhere() . " 
                ORDER BY (p.compare_at_price_ghs - p.price_ghs) DESC LIMIT :limit OFFSET :offset";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':min_stock', $this->getMinStock(), PDO::PARAM_INT);
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function countDiscounted() {
        $sql = "SELECT COUNT(*) FROM products p 
                WHERE p.compare_at_price_ghs IS NOT NULL AND p.compare_at_price_ghs > p.price_ghs 
                AND p.is_active = 1 " . $this->getStockSql();
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':min_stock', $this->getMinStock(), PDO::PARAM_INT);
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    public function getDealsPageItems($limit = 36) {
        $sql = "SELECT p.*, b.name as brand_name, pi.url as primary_image, (SELECT AVG(rating) FROM reviews WHERE product_id = p.id AND is_approved = 1) as avg_rating 
                FROM products p 
                LEFT JOIN brands b ON p.brand_id = b.id 
                LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1 
                WHERE (p.compare_at_price_ghs > 0 OR p.is_preorder = 1 OR p.is_dropshipping = 1) 
                AND p.is_active = 1 " . $this->getStockSql() . " 
                ORDER BY p.is_preorder DESC, p.created_at DESC LIMIT :limit";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':min_stock', $this->getMinStock(), PDO::PARAM_INT);
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getPreOrders($limit = 12) {
        $stmt = $this->db->prepare("SELECT p.*, b.name as brand_name, pi.url as primary_image FROM products p LEFT JOIN brands b ON p.brand_id = b.id LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1 WHERE p.is_preorder = 1 AND p.is_active = 1 LIMIT :limit");
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getDropshipping($limit = 12) {
        $stmt = $this->db->prepare("SELECT p.*, b.name as brand_name, pi.url as primary_image FROM products p LEFT JOIN brands b ON p.brand_id = b.id LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1 WHERE p.is_dropshipping = 1 AND p.is_active = 1 LIMIT :limit");
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getNewArrivals($limit = 24, $offset = 0) {
        return $this->getAll($limit, $offset);
    }

    public function getTopSelling($limit = 24, $offset = 0) {
        $sql = "SELECT p.*, b.name as brand_name, pi.url as primary_image, (SELECT AVG(rating) FROM reviews WHERE product_id = p.id AND is_approved = 1) as avg_rating 
                FROM products p 
                LEFT JOIN brands b ON p.brand_id = b.id 
                LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1 
                WHERE p.is_active = 1 " . $this->getStockSql() . " 
                ORDER BY p.stock_qty ASC LIMIT :limit OFFSET :offset";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':min_stock', $this->getMinStock(), PDO::PARAM_INT);
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function countTopSelling() {
        $sql = "SELECT COUNT(*) FROM products p WHERE p.is_active = 1 " . $this->getStockSql();
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':min_stock', $this->getMinStock(), PDO::PARAM_INT);
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    // ── Marketplace helpers ──────────────────────────────────────
    public function getByStore(int $storeId, int $limit=12, int $offset=0): array {
        $sql="SELECT p.*, pi.url as primary_image, (SELECT AVG(rating) FROM reviews WHERE product_id=p.id AND is_approved=1) as avg_rating ".$this->sellerSelect()." FROM products p LEFT JOIN product_images pi ON p.id=pi.product_id AND pi.is_primary=1 ".$this->sellerJoins()." WHERE p.store_id=:sid AND p.is_active=1 AND (p.status_market IS NULL OR p.status_market='active') ".$this->getStockSql()." ORDER BY p.created_at DESC LIMIT :limit OFFSET :offset";
        $stmt=$this->db->prepare($sql);
        $stmt->bindValue(':sid',(int)$storeId,PDO::PARAM_INT);
        $stmt->bindValue(':min_stock',$this->getMinStock(),PDO::PARAM_INT);
        $stmt->bindValue(':limit',(int)$limit,PDO::PARAM_INT);
        $stmt->bindValue(':offset',(int)$offset,PDO::PARAM_INT);
        $stmt->execute(); return $stmt->fetchAll();
    }
    public function countByStore(int $storeId): int {
        $stmt=$this->db->prepare("SELECT COUNT(*) FROM products WHERE store_id=? AND is_active=1 AND (status_market IS NULL OR status_market='active')");
        $stmt->execute([$storeId]); return (int)$stmt->fetchColumn();
    }
    public function getWholesaleDeals(int $limit=8): array {
        $sql="SELECT p.*, pi.url as primary_image ".$this->sellerSelect()." FROM products p LEFT JOIN product_images pi ON p.id=pi.product_id AND pi.is_primary=1 ".$this->sellerJoins()." WHERE p.listing_type='wholesale' AND p.is_active=1 AND (p.status_market IS NULL OR p.status_market='active') ".$this->getStockSql()." ORDER BY p.created_at DESC LIMIT :limit";
        $stmt=$this->db->prepare($sql);
        $stmt->bindValue(':min_stock',$this->getMinStock(),PDO::PARAM_INT);
        $stmt->bindValue(':limit',(int)$limit,PDO::PARAM_INT); $stmt->execute(); return $stmt->fetchAll();
    }
    public function getBySeller(int $sellerId, int $limit=12, int $offset=0): array {
        $sql="SELECT p.*, pi.url as primary_image ".$this->sellerSelect()." FROM products p LEFT JOIN product_images pi ON p.id=pi.product_id AND pi.is_primary=1 ".$this->sellerJoins()." WHERE p.seller_id=:sid AND p.is_active=1 ".$this->getStockSql()." ORDER BY p.created_at DESC LIMIT :limit OFFSET :offset";
        $stmt=$this->db->prepare($sql);
        $stmt->bindValue(':sid',(int)$sellerId,PDO::PARAM_INT);
        $stmt->bindValue(':min_stock',$this->getMinStock(),PDO::PARAM_INT);
        $stmt->bindValue(':limit',(int)$limit,PDO::PARAM_INT);
        $stmt->bindValue(':offset',(int)$offset,PDO::PARAM_INT);
        $stmt->execute(); return $stmt->fetchAll();
    }
    public function getExportListings(int $limit=8): array {
        $sql="SELECT p.*, pi.url as primary_image ".$this->sellerSelect()." FROM products p LEFT JOIN product_images pi ON p.id=pi.product_id AND pi.is_primary=1 ".$this->sellerJoins()." WHERE p.vehicle_origin='international_export' AND p.is_active=1 AND (p.status_market IS NULL OR p.status_market='active') ".$this->getStockSql()." ORDER BY p.created_at DESC LIMIT :limit";
        $stmt=$this->db->prepare($sql);
        $stmt->bindValue(':min_stock',$this->getMinStock(),PDO::PARAM_INT);
        $stmt->bindValue(':limit',(int)$limit,PDO::PARAM_INT); $stmt->execute(); return $stmt->fetchAll();
    }

    public function deleteById($id) {
        $product = $this->findById($id);
        if (!$product) {
            return ['success' => false, 'message' => 'Product not found.'];
        }

        $stmt = $this->db->prepare("SELECT COUNT(*) FROM order_items WHERE product_id = ?");
        $stmt->execute([(int)$id]);
        $ordersUsingProduct = (int)$stmt->fetchColumn();

        try {
            $backupFile = null;
            if ($ordersUsingProduct > 0) {
                $backupFile = $this->createDeletedProductBackup($id, $product, $ordersUsingProduct);
            }
            $this->db->beginTransaction();

            $deleteWishlist = $this->db->prepare("DELETE FROM wishlist WHERE product_id = ?");
            $deleteWishlist->execute([(int)$id]);

            $deleteReviews = $this->db->prepare("DELETE FROM reviews WHERE product_id = ?");
            $deleteReviews->execute([(int)$id]);

            $deleteImages = $this->db->prepare("DELETE FROM product_images WHERE product_id = ?");
            $deleteImages->execute([(int)$id]);

            $deleteVariants = $this->db->prepare("DELETE FROM variants WHERE product_id = ?");
            $deleteVariants->execute([(int)$id]);

            $deleteProduct = $this->db->prepare("DELETE FROM products WHERE id = ?");
            $deleteProduct->execute([(int)$id]);

            $this->db->commit();

            $message = 'Product deleted successfully.';
            if ($ordersUsingProduct > 0) {
                $message = 'Product deleted successfully. Order-linked data was backed up automatically before deletion (' . $backupFile . ').';
            }

            return ['success' => true, 'message' => $message];
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }

            return ['success' => false, 'message' => 'Delete failed: ' . $e->getMessage()];
        }
    }
}
