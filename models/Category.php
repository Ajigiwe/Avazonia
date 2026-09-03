<?php
// models/Category.php
require_once __DIR__ . '/../core/Model.php';

class Category extends Model {
    public function getAll() {
        $stmt = $this->db->query("SELECT * FROM categories WHERE is_active = 1 ORDER BY sort_order ASC, name ASC");
        return $stmt->fetchAll();
    }

    public function findBySlug($slug) {
        $stmt = $this->db->prepare("SELECT * FROM categories WHERE slug = ? AND is_active = 1");
        $stmt->execute([$slug]);
        return $stmt->fetch();
    }

    public function findById($id) {
        $stmt = $this->db->prepare("SELECT * FROM categories WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function getTopLevels() {
        $stmt = $this->db->query("SELECT * FROM categories WHERE parent_id IS NULL AND is_active = 1 ORDER BY sort_order ASC, name ASC");
        return $stmt->fetchAll();
    }

    public function getGridCategories($limit = 7) {
        $stmt = $this->db->prepare("SELECT * FROM categories WHERE parent_id IS NULL AND is_active = 1 ORDER BY sort_order ASC, name ASC LIMIT :limit");
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function findByIds(array $ids) {
        if (empty($ids)) return [];
        $ids = array_map('intval', $ids);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->db->prepare("SELECT * FROM categories WHERE id IN ($placeholders) AND is_active = 1");
        $stmt->execute(array_values($ids));
        $rows = $stmt->fetchAll();
        $byId = [];
        foreach ($rows as $row) {
            $byId[(int)$row['id']] = $row;
        }
        $ordered = [];
        foreach ($ids as $id) {
            if (isset($byId[$id])) {
                $ordered[] = $byId[$id];
            }
        }
        return $ordered;
    }

    public function create($data) {
        $stmt = $this->db->prepare("INSERT INTO categories (name, slug, icon, description, image_url, sort_order, is_active, parent_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        return $stmt->execute([
            $data['name'],
            $data['slug'],
            $data['icon'] ?? '📦',
            $data['description'] ?? null,
            $data['image_url'] ?? null,
            $data['sort_order'] ?? 0,
            $data['is_active'] ?? 1,
            !empty($data['parent_id']) ? (int)$data['parent_id'] : null
        ]);
    }

    public function update($id, $data) {
        $stmt = $this->db->prepare("UPDATE categories SET name = ?, slug = ?, icon = ?, description = ?, image_url = ?, sort_order = ?, is_active = ?, parent_id = ? WHERE id = ?");
        return $stmt->execute([
            $data['name'],
            $data['slug'],
            $data['icon'] ?? '📦',
            $data['description'] ?? null,
            $data['image_url'] ?? null,
            $data['sort_order'] ?? 0,
            $data['is_active'] ?? 1,
            !empty($data['parent_id']) ? (int)$data['parent_id'] : null,
            $id
        ]);
    }

    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM categories WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function getChildren(int $parentId): array {
        $stmt = $this->db->prepare("SELECT * FROM categories WHERE parent_id = ? AND is_active = 1 ORDER BY sort_order ASC, name ASC");
        $stmt->execute([$parentId]);
        return $stmt->fetchAll();
    }

    public function hasChildren(int $parentId): bool {
        $stmt = $this->db->prepare("SELECT 1 FROM categories WHERE parent_id = ? AND is_active = 1 LIMIT 1");
        $stmt->execute([$parentId]);
        return (bool)$stmt->fetch();
    }

    public function getChildrenWithCounts(int $parentId): array {
        $driver = $this->db->getAttribute(PDO::ATTR_DRIVER_NAME);
        // Aggregated count: products directly in child OR in its own children (grandchildren)
        // Works for both MySQL and SQLite (no MySQL-only functions)
        $sql = "SELECT c.*, 
                       (SELECT COUNT(*) FROM products p 
                        WHERE p.is_active = 1 
                          AND (p.category_id = c.id 
                               OR p.category_id IN (SELECT id FROM categories WHERE parent_id = c.id))
                       ) AS product_count
                FROM categories c
                WHERE c.parent_id = :pid AND c.is_active = 1
                ORDER BY c.sort_order ASC, c.name ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':pid', $parentId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function getBreadcrumbs(int $categoryId): array {
        $crumbs = [];
        $current = $this->findById($categoryId);
        while ($current) {
            array_unshift($crumbs, $current);
            if (empty($current['parent_id'])) break;
            $current = $this->findById((int)$current['parent_id']);
        }
        return $crumbs;
    }

    public function countProductsInSubtree(int $categoryId): int {
        $sql = "SELECT COUNT(*) FROM products p 
                WHERE p.is_active = 1 
                  AND (p.category_id = :cat 
                       OR p.category_id IN (SELECT id FROM categories WHERE parent_id = :cat)
                       OR p.category_id IN (SELECT id FROM categories WHERE parent_id IN (SELECT id FROM categories WHERE parent_id = :cat)))";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':cat', $categoryId, PDO::PARAM_INT);
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }
}
