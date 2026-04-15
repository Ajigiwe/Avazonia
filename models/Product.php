<?php
// models/Product.php
require_once __DIR__ . '/../core/Model.php';

class Product extends Model {
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
        return " AND (p.stock_qty >= :min_stock OR p.is_preorder = 1 OR p.is_dropshipping = 1)";
    }

    public function getAll($limit = 12) {
        $sql = "SELECT p.*, pi.url as primary_image, (SELECT AVG(rating) FROM reviews WHERE product_id = p.id AND is_approved = 1) as avg_rating 
                FROM products p 
                LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1 
                WHERE p.is_active = 1 " . $this->getStockSql() . " 
                ORDER BY p.created_at DESC LIMIT :limit";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':min_stock', $this->getMinStock(), PDO::PARAM_INT);
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function findBySlug($slug) {
        $sql = "SELECT p.*, b.name as brand_name, c.name as category_name, pi.url as primary_image, (SELECT AVG(rating) FROM reviews WHERE product_id = p.id AND is_approved = 1) as avg_rating 
                FROM products p 
                LEFT JOIN brands b ON p.brand_id = b.id 
                LEFT JOIN categories c ON p.category_id = c.id 
                LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1 
                WHERE p.slug = :slug AND p.is_active = 1 " . $this->getStockSql();
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':slug', $slug, PDO::PARAM_STR);
        $stmt->bindValue(':min_stock', $this->getMinStock(), PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch();
    }

    public function getFeatured() {
        $sql = "SELECT p.*, pi.url as primary_image, (SELECT AVG(rating) FROM reviews WHERE product_id = p.id AND is_approved = 1) as avg_rating 
                FROM products p 
                LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1 
                WHERE p.is_active = 1 AND p.is_featured = 1 " . $this->getStockSql() . " 
                ORDER BY p.created_at DESC LIMIT 8";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':min_stock', $this->getMinStock(), PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getBestsellers($limit = 8) {
        $sql = "SELECT p.*, b.name as brand_name, pi.url as primary_image, (SELECT AVG(rating) FROM reviews WHERE product_id = p.id AND is_approved = 1) as avg_rating 
                FROM products p 
                LEFT JOIN brands b ON p.brand_id = b.id 
                LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1 
                WHERE p.is_active = 1 AND p.is_bestseller = 1 " . $this->getStockSql() . " 
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

    public function getByCategory($categoryId, $limit = 24) {
        $sql = "SELECT p.*, b.name as brand_name, pi.url as primary_image, (SELECT AVG(rating) FROM reviews WHERE product_id = p.id AND is_approved = 1) as avg_rating 
                FROM products p 
                LEFT JOIN brands b ON p.brand_id = b.id 
                LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1 
                WHERE p.category_id = :cat AND p.is_active = 1 " . $this->getStockSql() . " 
                ORDER BY p.created_at DESC LIMIT :limit";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':cat', (int)$categoryId, PDO::PARAM_INT);
        $stmt->bindValue(':min_stock', $this->getMinStock(), PDO::PARAM_INT);
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getRelated($categoryId, $excludeId, $limit = 5) {
        $sql = "SELECT p.*, b.name as brand_name, pi.url as primary_image, (SELECT AVG(rating) FROM reviews WHERE product_id = p.id AND is_approved = 1) as avg_rating 
                FROM products p 
                LEFT JOIN brands b ON p.brand_id = b.id 
                LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1 
                WHERE p.category_id = :cat AND p.id != :exc AND p.is_active = 1 " . $this->getStockSql() . " 
                ORDER BY RAND() LIMIT :limit";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':cat', (int)$categoryId, PDO::PARAM_INT);
        $stmt->bindValue(':exc', (int)$excludeId, PDO::PARAM_INT);
        $stmt->bindValue(':min_stock', $this->getMinStock(), PDO::PARAM_INT);
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function search($query, $categoryId = null, $limit = 24) {
        $catFilter = $categoryId ? " AND p.category_id = :cat_id " : "";
        $sql = "SELECT p.*, b.name as brand_name, pi.url as primary_image, (SELECT AVG(rating) FROM reviews WHERE product_id = p.id AND is_approved = 1) as avg_rating 
                FROM products p 
                LEFT JOIN brands b ON p.brand_id = b.id 
                LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1 
                WHERE (p.name LIKE :q1 OR p.description LIKE :q2) AND p.is_active = 1 " . $this->getStockSql() . $catFilter . " 
                ORDER BY p.created_at DESC LIMIT :limit";
        $stmt = $this->db->prepare($sql);
        $term = "%$query%";
        $stmt->bindValue(':q1', $term, PDO::PARAM_STR);
        $stmt->bindValue(':q2', $term, PDO::PARAM_STR);
        if ($categoryId) $stmt->bindValue(':cat_id', (int)$categoryId, PDO::PARAM_INT);
        $stmt->bindValue(':min_stock', $this->getMinStock(), PDO::PARAM_INT);
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getSuggestions($query, $categoryId = null, $limit = 5) {
        $catFilter = $categoryId ? " AND p.category_id = :cat_id " : "";
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

    public function getDiscounted($limit = 24) {
        $sql = "SELECT p.*, b.name as brand_name, pi.url as primary_image, (SELECT AVG(rating) FROM reviews WHERE product_id = p.id AND is_approved = 1) as avg_rating 
                FROM products p 
                LEFT JOIN brands b ON p.brand_id = b.id 
                LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1 
                WHERE p.compare_at_price_ghs IS NOT NULL AND p.compare_at_price_ghs > p.price_ghs 
                AND p.is_active = 1 " . $this->getStockSql() . " 
                ORDER BY (p.compare_at_price_ghs - p.price_ghs) DESC LIMIT :limit";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':min_stock', $this->getMinStock(), PDO::PARAM_INT);
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
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

    public function getNewArrivals($limit = 24) {
        return $this->getAll($limit);
    }

    public function getTopSelling($limit = 24) {
        $sql = "SELECT p.*, b.name as brand_name, pi.url as primary_image, (SELECT AVG(rating) FROM reviews WHERE product_id = p.id AND is_approved = 1) as avg_rating 
                FROM products p 
                LEFT JOIN brands b ON p.brand_id = b.id 
                LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1 
                WHERE p.is_active = 1 " . $this->getStockSql() . " 
                ORDER BY p.stock_qty ASC LIMIT :limit";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':min_stock', $this->getMinStock(), PDO::PARAM_INT);
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
