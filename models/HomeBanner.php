<?php
// models/HomeBanner.php — Homepage promo banners
// Banners are stored as JSON under the existing `settings` table (key: home_banners),
// so this feature needs no schema migration on production.
require_once __DIR__ . '/../core/Model.php';
require_once __DIR__ . '/../models/Settings.php';

class HomeBanner extends Model {
    const KEY = 'home_banners';

    /** All banners in display order. */
    public function all(): array {
        $raw = (new Settings())->get(self::KEY, '[]');
        $rows = json_decode((string)$raw, true);
        return is_array($rows) ? array_values($rows) : [];
    }

    /** Only banners marked active, in display order. */
    public function active(): array {
        return array_values(array_filter($this->all(), static fn($b) => !empty($b['is_active'])));
    }

    /** Find one banner by id. */
    public function find($id): ?array {
        foreach ($this->all() as $b) {
            if ((string)($b['id'] ?? '') === (string)$id) return $b;
        }
        return null;
    }

    /** Persist the full banner list. */
    public function saveAll(array $banners): bool {
        return (new Settings())->set(self::KEY, json_encode(array_values($banners), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    /** Add a banner to the end of the list. */
    public function add(array $data): bool {
        $banners = $this->all();
        $banners[] = array_merge([
            'id'        => 'bnr_' . bin2hex(random_bytes(5)),
            'title'     => '',
            'image_url' => '',
            'link_url'  => '',
            'is_active' => 1,
        ], $data);
        return $this->saveAll($banners);
    }

    /** Update a banner by id (keeps its position). */
    public function update($id, array $data): bool {
        $banners = $this->all();
        foreach ($banners as &$b) {
            if ((string)($b['id'] ?? '') === (string)$id) {
                foreach ($data as $k => $v) $b[$k] = $v;
                break;
            }
        }
        unset($b);
        return $this->saveAll($banners);
    }

    /** Remove a banner by id. */
    public function delete($id): bool {
        $banners = array_values(array_filter($this->all(), static fn($b) => (string)($b['id'] ?? '') !== (string)$id));
        return $this->saveAll($banners);
    }

    /** Move a banner up (dir=-1) or down (dir=+1) in display order. */
    public function move($id, int $dir): bool {
        $banners = $this->all();
        $idx = null;
        foreach ($banners as $i => $b) {
            if ((string)($b['id'] ?? '') === (string)$id) { $idx = $i; break; }
        }
        if ($idx === null) return false;
        $target = $idx + $dir;
        if ($target < 0 || $target >= count($banners)) return false;
        $tmp = $banners[$idx];
        $banners[$idx] = $banners[$target];
        $banners[$target] = $tmp;
        return $this->saveAll($banners);
    }
}
