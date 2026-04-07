<?php
// models/Slider.php
require_once __DIR__ . '/../core/Model.php';

class Slider extends Model {
    
    /**
     * Fetch active slides for a specific page path.
     * Uses URI matching (e.g. '/' or '/shop').
     */
    public function getSlidesByPage($path = '/') {
        // Fallback for empty/root path
        if (empty($path)) $path = '/';
        
        try {
            $sql = "SELECT * FROM sliders 
                    WHERE is_active = 1 
                    AND (page_path = :path OR page_path = '*')
                    ORDER BY order_priority ASC, id DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':path', $path, PDO::PARAM_STR);
            $stmt->execute();
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            // Defensive Fail-Safe: Return empty if table is missing or DB error
            return [];
        }
    }

    /**
     * Get all slides for admin panel listing.
     */
    public function getAllRecords() {
        $stmt = $this->db->query("SELECT * FROM sliders ORDER BY is_active DESC, order_priority ASC, id DESC");
        return $stmt->fetchAll();
    }

    /**
     * Get a single slide by ID.
     */
    public function findById($id) {
        $stmt = $this->db->prepare("SELECT * FROM sliders WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    /**
     * Create a new slide record.
     */
    public function create($data) {
        $sql = "INSERT INTO sliders (heading, subheading, image_url, cta_text, cta_link, page_path, template_type, is_active, order_priority) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $data['heading'] ?? '',
            $data['subheading'] ?? '',
            $data['image_url'] ?? '',
            $data['cta_text'] ?? 'Shop Now',
            $data['cta_link'] ?? '/shop',
            $data['page_path'] ?? '/',
            $data['template_type'] ?? 'split',
            $data['is_active'] ?? 1,
            $data['order_priority'] ?? 0
        ]);
    }

    /**
     * Update an existing slide record.
     */
    public function update($id, $data) {
        $fields = [];
        $params = [];
        
        foreach ($data as $key => $value) {
            $fields[] = "$key = ?";
            $params[] = $value;
        }
        
        $params[] = $id;
        $sql = "UPDATE sliders SET " . implode(', ', $fields) . " WHERE id = ?";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Delete a slide record.
     */
    public function delete($id) {
        $stmt = $this->db->prepare("DELETE FROM sliders WHERE id = ?");
        return $stmt->execute([$id]);
    }
}
