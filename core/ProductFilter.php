<?php
// core/ProductFilter.php
//
// Declarative filter engine for the admin catalogue list (admin/products.php).
//
// The products screen reads its whole list state from the query string, so a
// filtered view can be bookmarked, shared with another admin, paginated, and
// survives the POST round-trips the page does for delete / moderate / bulk
// actions. Every filterable field is declared once in SPECS; from there the
// engine does four things:
//
//   1. VALIDATE  — each incoming value is trimmed, length-capped and checked
//                  against a whitelist for its type (text / int / float /
//                  enum / date). Anything invalid is silently dropped, so a
//                  hand-edited URL cannot smuggle junk into the query.
//   2. COMPILE   — validated values become a parameterised WHERE clause. Text
//                  search escapes LIKE wildcards and uses an explicit ESCAPE
//                  marker; request data never lands in the SQL string itself.
//   3. SORT      — ORDER BY cannot be bound as a placeholder (it needs an
//                  identifier, not a value), so sort keys go through the
//                  SORTS whitelist map instead.
//   4. DESCRIBE  — chips() renders the active filters as human-readable labels
//                  (with per-id names supplied by the caller via setLabels())
//                  and a URL that removes exactly that one filter.
//
// The engine is query-string driven and table-light (only the `p` alias is
// assumed), so it can be reused for other admin list screens with a new SPECS
// set and a new FROM clause.

class ProductFilter
{
    /**
     * Query-string key => field definition.
     *
     * Types:
     *   text — free text matched across `columns` with LIKE
     *   int  — integer id compared for equality against `column`
     *   enum — one of the `options` keys; either equals `column`, or runs the
     *          SQL fragment in `map` (for conditions that are not a plain
     *          equality, e.g. the three stock states)
     *   float— numeric range endpoint, `op` is >= or <=
     *   date — Y-m-d date compared against `column` (used for uploaded_at)
     */
    const SPECS = [
        'q' => [
            'label'   => 'Search',
            'type'    => 'text',
            // Name is the headline match; brand and SKU ride along so pasting
            // an order code or a brand into the same box still finds the row.
            'columns' => ['p.name', 'p.brand_name', 'p.sku'],
        ],
        'brand' => [
            'label'  => 'Brand',
            'type'   => 'int',
            'column' => 'p.brand_id',
        ],
        'category' => [
            'label'  => 'Category',
            'type'   => 'int',
            'column' => 'p.category_id',
        ],
        'seller' => [
            'label'  => 'Seller',
            'type'   => 'int',
            'column' => 'p.seller_id',
        ],
        'status' => [
            'label'   => 'Status',
            'type'    => 'enum',
            'column'  => 'p.status_market',
            'options' => [
                'active'         => 'Active',
                'pending_review' => 'Pending review',
                'draft'          => 'Draft',
                'rejected'       => 'Rejected',
            ],
        ],
        'vis' => [
            'label'   => 'Visibility',
            'type'    => 'enum',
            'column'  => 'p.is_active',
            'options' => [
                'active' => 'Live on site',
                'hidden' => 'Hidden',
            ],
            // p.is_active is a tinyint, so map the label to the stored flag.
            'map'     => ['active' => 'p.is_active = 1', 'hidden' => 'p.is_active = 0'],
        ],
        'listing' => [
            'label'   => 'Listing type',
            'type'    => 'enum',
            'column'  => 'p.listing_type',
            'options' => [
                'retail'    => 'Retail',
                'wholesale' => 'Wholesale',
                'rfq'       => 'RFQ',
                'export'    => 'Export',
            ],
        ],
        'stock' => [
            'label'   => 'Stock',
            'type'    => 'enum',
            'column'  => 'p.stock_qty',
            'options' => [
                'in'  => 'In stock',
                'low' => 'Low (1–4 units)',
                'out' => 'Out of stock',
            ],
            // Not an equality — each state is its own fragment.
            'map'     => [
                'in'  => 'p.stock_qty > 0',
                'low' => 'p.stock_qty > 0 AND p.stock_qty < 5',
                'out' => 'p.stock_qty = 0',
            ],
        ],
        'price_min' => [
            'label'  => 'Min price',
            'type'   => 'float',
            'column' => 'p.price_ghs',
            'op'     => '>=',
        ],
        'price_max' => [
            'label'  => 'Max price',
            'type'   => 'float',
            'column' => 'p.price_ghs',
            'op'     => '<=',
        ],
        'when' => [
            'label'   => 'Uploaded',
            'type'    => 'enum',
            'column'  => 'p.created_at',
            'options' => [], // filled from presets() below
        ],
        'from' => [
            'label'  => 'Uploaded from',
            'type'   => 'date',
            'column' => 'p.created_at',
            'op'     => '>=',
        ],
        'to' => [
            'label'  => 'Uploaded to',
            'type'   => 'date',
            'column' => 'p.created_at',
            'op'     => '<=',
        ],
    ];

    /** Whitelisted ORDER BY expressions, keyed by the `sort` query param. */
    const SORTS = [
        // Default: keep the moderation queue on top, then newest upload first.
        'newest'    => "p.status_market = 'pending_review' DESC, p.created_at DESC",
        'oldest'    => 'p.created_at ASC',
        'name'      => 'p.name ASC',
        'name_desc' => 'p.name DESC',
        'price'     => 'p.price_ghs ASC',
        'price_desc'=> 'p.price_ghs DESC',
        'stock'     => 'p.stock_qty ASC',
        'stock_desc'=> 'p.stock_qty DESC',
    ];

    /** Human labels for the sort dropdown. */
    const SORT_LABELS = [
        'newest'     => 'Pending first, newest',
        'oldest'     => 'Oldest upload first',
        'name'       => 'Name A → Z',
        'name_desc'  => 'Name Z → A',
        'price'      => 'Price low → high',
        'price_desc' => 'Price high → low',
        'stock'      => 'Stock low → high',
        'stock_desc' => 'Stock high → low',
    ];

    /** @var array Raw (but scalar-checked) query string, kept for url() building. */
    private $input = [];

    /** @var array Query-string key => validated value. */
    private $values = [];

    /** @var string[] Compiled WHERE fragments, in declaration order. */
    private $clauses = [];

    /** @var array Bind params, appended in the same order as $clauses. */
    private $params = [];

    /** @var array Query-string key => [id => display name] for chips(). */
    private $labels = [];

    /** @var string Final ORDER BY expression (never user data — SORTS map). */
    private $orderBy;

    public function __construct(array $get)
    {
        // Only scalar query params are considered; arrays (`?q[]=1`) are ignored.
        foreach ($get as $k => $v) {
            if (is_scalar($v)) $this->input[$k] = trim((string)$v);
        }

        foreach (self::SPECS as $key => $def) {
            $raw = $this->input[$key] ?? '';
            if ($raw === '') continue;
            $this->add($key, $def, $raw);
        }

        $sort = $this->input['sort'] ?? '';
        $this->orderBy = self::SORTS[$sort] ?? self::SORTS['newest'];
    }

    // ── Compilation ────────────────────────────────────────────────────────

    /** Validate one raw value and, if it survives, push its SQL into the engine. */
    private function add($key, array $def, $raw)
    {
        switch ($def['type']) {

            case 'text':
                // Cap the length and drop control characters — this is a search
                // box, not a data-entry field, and 100 chars is far past useful.
                $term = trim((string)preg_replace('/[\x00-\x1F\x7F]/', '', $raw));
                if (mb_strlen($term) > 100) $term = mb_substr($term, 0, 100);
                if ($term === '') return;
                $this->values[$key] = $term;
                // Escape LIKE wildcards in the user's term, then match loosely.
                // ESCAPE '!' keeps MySQL and SQLite in agreement (MySQL's default
                // is backslash, SQLite has no default escape character at all),
                // so the escape prefix must be '!' too — not a backslash.
                $like = '%' . str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $term) . '%';
                $parts = [];
                foreach ($def['columns'] as $col) {
                    $parts[] = "$col LIKE ? ESCAPE '!'";
                    $this->params[] = $like;
                }
                $this->clauses[] = '(' . implode(' OR ', $parts) . ')';
                return;

            case 'int':
                if (!preg_match('/^-?\d{1,12}$/', $raw)) return;
                $val = (int)$raw;
                if ($val <= 0) return;
                $this->values[$key] = $val;
                $this->clauses[] = $def['column'] . ' = ?';
                $this->params[] = $val;
                return;

            case 'float':
                if (!is_numeric($raw)) return;
                $val = (float)$raw;
                if ($val < 0) return;
                $this->values[$key] = $val;
                $this->clauses[] = $def['column'] . ' ' . $def['op'] . ' ?';
                $this->params[] = $val;
                return;

            case 'enum':
                // The `when` preset list is built at runtime, so merge it in.
                // presets() returns a list; re-key it by 'key' for the whitelist
                // check AND the from-date lookup below.
                $options = $def['options'];
                if ($key === 'when') $options = array_column(self::presets(), null, 'key');
                if (!array_key_exists($raw, $options)) return;
                $this->values[$key] = $raw;
                if (isset($def['map'][$raw])) {
                    // Whitelisted fragment (stock / visibility) — no user data in it.
                    $this->clauses[] = $def['map'][$raw];
                } elseif ($key === 'when') {
                    // Presets resolve to "from the start of that day".
                    $this->clauses[] = $def['column'] . ' >= ?';
                    $this->params[] = $options[$raw]['from'] . ' 00:00:00';
                } else {
                    $this->clauses[] = $def['column'] . ' = ?';
                    $this->params[] = $raw;
                }
                return;

            case 'date':
                // Reject anything that is not a real calendar date (a round-trip
                // through DateTime::format catches 2026-02-31, 2026-13-01, …).
                $dt = DateTime::createFromFormat('Y-m-d', $raw);
                if (!$dt || $dt->format('Y-m-d') !== $raw) return;
                $this->values[$key] = $raw;
                // created_at is a TIMESTAMP / 'Y-m-d H:i:s' TEXT on SQLite, so a
                // string comparison against the day boundary works on both.
                $boundary = $def['op'] === '>=' ? $raw . ' 00:00:00' : $raw . ' 23:59:59';
                $this->clauses[] = $def['column'] . ' ' . $def['op'] . ' ?';
                $this->params[] = $boundary;
                return;
        }
    }

    // ── Output ─────────────────────────────────────────────────────────────

    /** "WHERE … AND …" with a leading space, or an empty string when unfiltered. */
    public function whereSql()
    {
        return $this->clauses ? ' WHERE ' . implode(' AND ', $this->clauses) : '';
    }

    /** Bind params for the WHERE clause, in clause order. */
    public function params()
    {
        return $this->params;
    }

    /** Whitelisted ORDER BY expression (no trailing "ORDER BY"). */
    public function orderBy()
    {
        return $this->orderBy;
    }

    /** Validated value for a single field — used to re-populate the form. */
    public function value($key, $default = '')
    {
        return array_key_exists($key, $this->values) ? $this->values[$key] : $default;
    }

    /** Every validated value. */
    public function values()
    {
        return $this->values;
    }

    /** True when at least one filter is narrowing the list. */
    public function isActive()
    {
        return !empty($this->clauses);
    }

    /** Number of active filters — cheap guard for "show the chips row" logic. */
    public function count()
    {
        return count($this->clauses);
    }

    /** Row count for the current filter, run against the same WHERE clause. */
    public function rowCount(PDO $db)
    {
        $stmt = $db->prepare('SELECT COUNT(*) FROM products p' . $this->whereSql());
        $stmt->execute($this->params());
        return (int)$stmt->fetchColumn();
    }

    /**
     * Id => display-name maps so chips can say "Brand: Nike" instead of
     * "Brand: 7". Call with e.g. ['brand' => [7 => 'Nike'], …].
     */
    public function setLabels(array $maps)
    {
        $this->labels = $maps;
    }

    /** Quick "time uploaded" presets, resolved against today. */
    public static function presets()
    {
        $today = new DateTimeImmutable('today');
        $fmt   = function (DateTimeImmutable $d) { return $d->format('Y-m-d'); };
        return [
            ['key' => 'today', 'label' => 'Today',            'from' => $fmt($today)],
            ['key' => '7d',    'label' => 'Last 7 days',      'from' => $fmt($today->modify('-6 days'))],
            ['key' => '30d',   'label' => 'Last 30 days',     'from' => $fmt($today->modify('-29 days'))],
            ['key' => '90d',   'label' => 'Last 90 days',     'from' => $fmt($today->modify('-89 days'))],
            ['key' => 'month', 'label' => 'This month',       'from' => $fmt($today->modify('first day of this month'))],
            ['key' => 'year',  'label' => 'This year',        'from' => $today->format('Y') . '-01-01'],
        ];
    }

    /** Active "time uploaded" preset key, if any — used to highlight quick links. */
    public function activePreset()
    {
        return $this->value('when');
    }

    /**
     * Active filters as chips: ['label' => 'Status: Pending review', 'url' => '…'],
     * where each URL re-requests the page with exactly that filter removed.
     */
    public function chips()
    {
        $out = [];
        foreach (array_keys($this->values) as $key) {
            $def   = self::SPECS[$key];
            $value = $this->values[$key];
            $text  = $this->describe($key, $def, $value);
            if ($text === null) continue;
            $out[] = [
                'label' => $def['label'] . ': ' . $text,
                'url'   => $this->url([$key => null]),
            ];
        }
        // Sort is list state, not a filter — surface it last, styled the same.
        $sort = $this->input['sort'] ?? '';
        if ($sort !== '' && isset(self::SORT_LABELS[$sort])) {
            $out[] = [
                'label' => 'Sorted by: ' . self::SORT_LABELS[$sort],
                'url'   => $this->url(['sort' => null]),
            ];
        }
        return $out;
    }

    /** Display text for one active value, or null to skip the chip. */
    private function describe($key, array $def, $value)
    {
        if ($key === 'q') return '“' . $value . '”';
        if ($key === 'when') {
            foreach (self::presets() as $p) if ($p['key'] === $value) return $p['label'];
            return null;
        }
        if ($key === 'price_min') return CURRENCY_SYMBOL . number_format((float)$value, 2) . ' and up';
        if ($key === 'price_max') return CURRENCY_SYMBOL . number_format((float)$value, 2) . ' and under';
        if ($key === 'from') return 'since ' . date('M j, Y', strtotime($value));
        if ($key === 'to')   return 'up to ' . date('M j, Y', strtotime($value));

        // Enum / id fields: prefer a name supplied by setLabels(), then the
        // option label, and finally fall back to the raw id.
        if (isset($this->labels[$key][$value])) return (string)$this->labels[$key][$value];
        if (isset($def['options'][$value])) return $def['options'][$value];
        if ($def['type'] === 'int') return '#' . $value;
        return (string)$value;
    }

    // ── URL building ───────────────────────────────────────────────────────

    /**
     * products.php?… built from the current filters with $overrides applied.
     * A null override removes that key; unknown and empty keys are dropped so
     * pagination links never carry dead parameters or a stale page number.
     */
    public function url(array $overrides = [])
    {
        $q = [];
        foreach ($this->values as $k => $v) $q[$k] = $v;
        $sort = $this->input['sort'] ?? '';
        if ($sort !== '' && isset(self::SORTS[$sort])) $q['sort'] = $sort;

        foreach ($overrides as $k => $v) {
            if ($v === null || $v === '') unset($q[$k]);
            // Page 1 is the default URL, so never carry an explicit page=1.
            elseif ($k === 'page' && (int)$v <= 1) unset($q[$k]);
            else $q[$k] = $v;
        }

        $qs = http_build_query($q);
        return 'products.php' . ($qs !== '' ? '?' . $qs : '');
    }

    /** Link that clears every filter and the sort. */
    public function clearUrl()
    {
        return 'products.php';
    }
}
