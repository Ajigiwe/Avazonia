<?php
// models/Brand.php
require_once __DIR__ . '/../core/Model.php';

class Brand extends Model {
    public function getAll() {
        $stmt = $this->db->query("SELECT * FROM brands ORDER BY name ASC");
        return $stmt->fetchAll();
    }

    public function create($data) {
        $stmt = $this->db->prepare("INSERT INTO brands (name, slug, logo_url, is_active, sort_order) VALUES (?, ?, ?, ?, ?)");
        return $stmt->execute([
            $data['name'],
            $data['slug'],
            $data['logo_url'] ?? null,
            $data['is_active'] ?? 1,
            $data['sort_order'] ?? 0
        ]);
    }

    public function update($id, $data) {
        $stmt = $this->db->prepare("UPDATE brands SET name = ?, slug = ?, logo_url = ?, is_active = ?, sort_order = ? WHERE id = ?");
        return $stmt->execute([
            $data['name'],
            $data['slug'],
            $data['logo_url'] ?? null,
            $data['is_active'] ?? 1,
            $data['sort_order'] ?? 0,
            $id
        ]);
    }

    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM brands WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function findById($id) {
        $stmt = $this->db->prepare("SELECT * FROM brands WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
}
