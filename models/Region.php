<?php
// models/Region.php
require_once __DIR__ . '/../core/Model.php';

class Region extends Model {
    public function getAll($onlyActive = false) {
        $sql = "SELECT * FROM regions" . ($onlyActive ? " WHERE is_active = 1" : "") . " ORDER BY sort_order ASC, name ASC";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    public function findById($id) {
        $stmt = $this->db->prepare("SELECT * FROM regions WHERE id = ?");
        $stmt->execute([(int)$id]);
        return $stmt->fetch();
    }

    public function findBySlug($slug) {
        $stmt = $this->db->prepare("SELECT * FROM regions WHERE slug = ? AND is_active = 1");
        $stmt->execute([$slug]);
        return $stmt->fetch();
    }

    public function create($data) {
        $stmt = $this->db->prepare("INSERT INTO regions (name, slug, is_active, sort_order) VALUES (?, ?, ?, ?)");
        return $stmt->execute([
            trim($data['name']),
            $this->slugify($data['name']),
            $data['is_active'] ?? 1,
            (int)($data['sort_order'] ?? 0)
        ]);
    }

    public function update($id, $data) {
        $stmt = $this->db->prepare("UPDATE regions SET name = ?, slug = ?, is_active = ?, sort_order = ? WHERE id = ?");
        return $stmt->execute([
            trim($data['name']),
            $this->slugify($data['name']),
            $data['is_active'] ?? 1,
            (int)($data['sort_order'] ?? 0),
            (int)$id
        ]);
    }

    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM regions WHERE id = ?");
        return $stmt->execute([(int)$id]);
    }

    public function countSellers($name) {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM sellers WHERE region = ?");
        $stmt->execute([trim($name)]);
        return (int)$stmt->fetchColumn();
    }

    public function productCount($name) {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM products p
            LEFT JOIN sellers s ON p.seller_id = s.id
            WHERE s.region = ? AND p.is_active = 1");
        $stmt->execute([trim($name)]);
        return (int)$stmt->fetchColumn();
    }

    private function slugify(string $s): string {
        $s = strtolower(trim($s));
        $s = preg_replace('/[^a-z0-9]+/', '-', $s);
        return trim($s, '-') ?: 'region';
    }
}
