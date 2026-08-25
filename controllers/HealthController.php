<?php
// controllers/HealthController.php — liveness probe for Docker & local dev

class HealthController {
    public function index() {
        header('Content-Type: application/json');
        $out = [
            'status' => 'ok',
            'app'    => defined('APP_NAME') ? APP_NAME : 'Avazonia',
            'time'   => date('c'),
        ];

        // Optional DB check: ?db=1
        if (isset($_GET['db'])) {
            try {
                require_once __DIR__ . '/../config/database.php';
                $db = db();
                $db->query('SELECT 1');
                // Count core tables if they exist
                $tables = ['users','products','categories','orders'];
                $counts = [];
                foreach ($tables as $t) {
                    try { $counts[$t] = (int)$db->query("SELECT COUNT(*) FROM `$t`")->fetchColumn(); }
                    catch (Throwable $e) { $counts[$t] = null; }
                }
                $out['db'] = 'ok';
                $out['counts'] = $counts;
            } catch (Throwable $e) {
                http_response_code(500);
                $out['status'] = 'error';
                $out['db'] = 'error';
                $out['error'] = $e->getMessage();
            }
        }

        echo json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }
}
