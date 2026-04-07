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

    public function create($data) {
        $stmt = $this->db->prepare("INSERT INTO categories (name, slug, description, sort_order, is_active) VALUES (?, ?, ?, ?, ?)");
        return $stmt->execute([
            $data['name'],
            $data['slug'],
            $data['description'] ?? null,
            $data['sort_order'] ?? 0,
            $data['is_active'] ?? 1
        ]);
    }

    public function update($id, $data) {
        $stmt = $this->db->prepare("UPDATE categories SET name = ?, slug = ?, description = ?, sort_order = ?, is_active = ? WHERE id = ?");
        return $stmt->execute([
            $data['name'],
            $data['slug'],
            $data['description'] ?? null,
            $data['sort_order'] ?? 0,
            $data['is_active'] ?? 1,
            $id
        ]);
    }

    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM categories WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
