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

    public function getGridCategories($limit = 7) {
        $stmt = $this->db->prepare("SELECT * FROM categories WHERE is_active = 1 ORDER BY sort_order ASC, name ASC LIMIT :limit");
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
}
