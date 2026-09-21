<?php
// models/PageView.php
// Read-side aggregation over page_views for the admin traffic widget.
// Timestamps are stored as 'Y-m-d H:i:s' strings and compared lexically —
// identical semantics on MySQL and SQLite with no dialect-specific SQL.

require_once __DIR__ . '/../core/Model.php';

class PageView extends Model
{
    /** Overall summary: today (site day) + last 7 days. */
    public function summary(): array
    {
        $dayStart = date('Y-m-d') . ' 00:00:00';
        $weekAgo  = date('Y-m-d H:i:s', time() - 7 * 86400);

        $row = $this->db->query("
            SELECT COUNT(*) AS views, COUNT(DISTINCT visitor_id) AS visitors
            FROM page_views
            WHERE viewed_at >= " . $this->db->quote($dayStart) . "
        ")->fetch(PDO::FETCH_ASSOC) ?: [];

        $row7 = $this->db->query("
            SELECT COUNT(*) AS views, COUNT(DISTINCT visitor_id) AS visitors
            FROM page_views
            WHERE viewed_at >= " . $this->db->quote($weekAgo) . "
        ")->fetch(PDO::FETCH_ASSOC) ?: [];

        return [
            'today_views'    => (int)($row['views'] ?? 0),
            'today_visitors' => (int)($row['visitors'] ?? 0),
            'views_7d'       => (int)($row7['views'] ?? 0),
            'visitors_7d'    => (int)($row7['visitors'] ?? 0),
        ];
    }

    /** Visitors + views per day for the last $days days (no gap filling). */
    public function dailySeries(int $days = 7): array
    {
        $days = max(1, min(90, $days));
        $since = date('Y-m-d H:i:s', time() - $days * 86400);
        $rows = $this->db->query("
            SELECT substr(viewed_at, 1, 10) AS day,
                   COUNT(DISTINCT visitor_id) AS visitors,
                   COUNT(*) AS views
            FROM page_views
            WHERE viewed_at >= " . $this->db->quote($since) . "
            GROUP BY substr(viewed_at, 1, 10)
            ORDER BY day ASC
        ")->fetchAll(PDO::FETCH_ASSOC) ?: [];
        return array_map(fn($r) => [
            'day'      => (string)$r['day'],
            'visitors' => (int)$r['visitors'],
            'views'    => (int)$r['views'],
        ], $rows);
    }

    /** Top viewed pages over the window. */
    public function topPages(int $days = 7, int $limit = 5): array
    {
        $limit = max(1, min(50, $limit));
        $since = date('Y-m-d H:i:s', time() - $days * 86400);
        $rows = $this->db->query("
            SELECT path, COUNT(*) AS views, COUNT(DISTINCT visitor_id) AS visitors
            FROM page_views
            WHERE viewed_at >= " . $this->db->quote($since) . "
            GROUP BY path
            ORDER BY views DESC
            LIMIT {$limit}
        ")->fetchAll(PDO::FETCH_ASSOC) ?: [];
        return array_map(fn($r) => [
            'path'     => (string)$r['path'],
            'views'    => (int)$r['views'],
            'visitors' => (int)$r['visitors'],
        ], $rows);
    }

    /** Top external referrers over the window (same-site navigations excluded). */
    public function topReferrers(int $days = 7, int $limit = 5): array
    {
        $limit = max(1, min(50, $limit));
        $since = date('Y-m-d H:i:s', time() - $days * 86400);
        $rows = $this->db->query("
            SELECT referrer_host AS source, COUNT(*) AS visits
            FROM page_views
            WHERE viewed_at >= " . $this->db->quote($since) . "
              AND referrer_host IS NOT NULL AND referrer_host <> ''
            GROUP BY referrer_host
            ORDER BY visits DESC
            LIMIT {$limit}
        ")->fetchAll(PDO::FETCH_ASSOC) ?: [];
        return array_map(fn($r) => [
            'source' => (string)$r['source'],
            'visits' => (int)$r['visits'],
        ], $rows);
    }
}
