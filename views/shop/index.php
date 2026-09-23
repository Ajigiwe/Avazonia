<?php
// views/shop/index.php
require_once __DIR__ . '/../layout/head.php';
require_once __DIR__ . '/../layout/nav.php';
?>

<?php require_once __DIR__ . '/../layout/hero.php'; ?>

<?php
// Category filter state must be resolved BEFORE the page header renders,
// since the <h2> title reflects the active category.
$qsCat = $_GET;
$mkCat = function ($v) use ($qsCat) {
    $n = $qsCat;
    if ($v === null) unset($n['cat']); else $n['cat'] = $v;
    unset($n['page']);
    $q = http_build_query($n);
    return APP_URL . '/shop' . ($q ? "?$q" : '');
};
$currentCatName = $activeCategory['name'] ?? null;
$topLevel = array_values(array_filter($categories, fn($c) => empty($c['parent_id'])));
$subByParent = [];
foreach ($categories as $c) {
    if (!empty($c['parent_id'])) $subByParent[(int)$c['parent_id']][] = $c;
}
?>

<section class="shop-content" style="padding: 120px 0 80px;">
    <div class="container">
        <div style="display:flex; align-items:flex-end; justify-content:space-between; gap:16px; flex-wrap:wrap; margin-bottom:48px; border-bottom:1px solid var(--extra-light-gray); padding-bottom:16px;">
            <div class="sec-head reveal" style="margin-bottom:0; border-bottom:none; padding-bottom:0;">
                <div>
                    <div class="sec-over">THE DROP</div>
                    <h2 class="hero-heading" style="color: var(--ink); margin-bottom: 0; line-height: 0.85;">
                        <?= $currentCatName ? strtoupper(htmlspecialchars($currentCatName)) : 'ALL PRODUCTS' ?>
                    </h2>
                </div>
            </div>
            <div style="display:flex; align-items:center; gap:16px; flex-wrap:wrap;">
                <div class="view-toggle" role="group" aria-label="View toggle">
                    <button id="view-grid" class="view-btn active" data-view-mode="grid" aria-pressed="true" onclick="setProductView('grid')" title="Grid view">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
                    </button>
                    <button id="view-list" class="view-btn" data-view-mode="list" aria-pressed="false" onclick="setProductView('list')" title="List view">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="8" y1="6" x2="21" y2="6"></line><line x1="8" y1="12" x2="21" y2="12"></line><line x1="8" y1="18" x2="21" y2="18"></line><line x1="3" y1="6" x2="3.01" y2="6"></line><line x1="3" y1="12" x2="3.01" y2="12"></line><line x1="3" y1="18" x2="3.01" y2="18"></line></svg>
                    </button>
                </div>
                <div style="font-family: var(--f-semi); font-size: 11px; text-transform: uppercase; color: var(--mid-gray); font-weight: 700; letter-spacing: 0.1em;">
                    Showing <?= $pagination['total'] ?> items
                </div>
            </div>
        </div>

        <!-- Category filter -->
        <div class="shop-cat-filter" style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:12px;">
            <label for="shop-cat-select" style="font-family:var(--f-mono);font-size:10px;font-weight:800;letter-spacing:.12em;text-transform:uppercase;color:var(--mid-gray);">Category</label>
            <div style="position:relative;">
                <select id="shop-cat-select"
                        onchange="if(this.value){window.location.href=this.value;}"
                        style="appearance:none;-webkit-appearance:none;font-family:var(--f-semi);font-size:12px;font-weight:700;padding:9px 34px 9px 14px;border:2px solid var(--ink);background:#fff;color:var(--ink);cursor:pointer;min-width:220px;">
                    <option value="<?= $mkCat(null) ?>" <?= !$currentCatName ? 'selected' : '' ?>>All Categories</option>
                    <?php foreach ($topLevel as $t):
                        $subs = $subByParent[(int)$t['id']] ?? [];
                        $isCurTop = $currentCatName && (int)($activeCategory['id'] ?? 0) === (int)$t['id'];
                        $isCurChild = $currentCatName && (int)($activeCategory['parent_id'] ?? 0) === (int)$t['id'];
                    ?>
                        <option value="<?= $mkCat($t['slug']) ?>" <?= $isCurTop ? 'selected' : '' ?>>
                            <?= htmlspecialchars($t['icon'] ? $t['icon'] . ' ' : '') . htmlspecialchars($t['name']) ?>
                        </option>
                        <?php foreach ($subs as $s): ?>
                            <option value="<?= $mkCat($s['slug']) ?>" <?= $currentCatName && (int)($activeCategory['id'] ?? 0) === (int)$s['id'] ? 'selected' : '' ?>>
                                &nbsp;&nbsp;– <?= htmlspecialchars($s['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </select>
                <span aria-hidden="true" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);pointer-events:none;font-size:10px;color:var(--ink);">▼</span>
            </div>
            <?php if ($currentCatName): ?>
                <a href="<?= $mkCat(null) ?>" style="font-family:var(--f-mono);font-size:10px;font-weight:800;letter-spacing:.08em;text-transform:uppercase;color:var(--red);text-decoration:none;border-bottom:1px solid var(--red);padding-bottom:2px;">Clear ×</a>
            <?php endif; ?>

            <?php $shopRegions = $regions ?? []; if (!empty($shopRegions)): $qs=$_GET; $mkReg=function($v) use($qs){ $n=$qs; if($v===null){ unset($n['region']); } else { $n['region']=$v; } unset($n['page']); $q=http_build_query($n); return APP_URL.'/shop'.($q?"?$q":""); }; $curReg=$_GET['region']??''; ?>
            <label for="shop-region-select" style="font-family:var(--f-mono);font-size:10px;font-weight:800;letter-spacing:.12em;text-transform:uppercase;color:var(--mid-gray);margin-left:12px;">Region</label>
            <div style="position:relative;">
                <select id="shop-region-select"
                        onchange="if(this.value){window.location.href=this.value;}"
                        style="appearance:none;-webkit-appearance:none;font-family:var(--f-semi);font-size:12px;font-weight:700;padding:9px 34px 9px 14px;border:2px solid var(--ink);background:#fff;color:var(--ink);cursor:pointer;min-width:200px;">
                    <option value="<?= $mkReg(null) ?>" <?= $curReg==='' ? 'selected' : '' ?>>All Regions</option>
                    <?php foreach ($shopRegions as $rg): $rSlug=$rg['slug']; $rSel=($curReg===$rSlug || ($activeRegion['id'] ?? 0)===(int)$rg['id']) ? 'selected' : ''; ?>
                        <option value="<?= $mkReg($rSlug) ?>" <?= $rSel ?>>
                            <?= htmlspecialchars($rg['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <span aria-hidden="true" style="position:absolute;right:12px;top:50%;transform:translateY(-50%);pointer-events:none;font-size:10px;color:var(--ink);">▼</span>
            </div>
            <?php if ($curReg!==''): ?>
                <a href="<?= $mkReg(null) ?>" style="font-family:var(--f-mono);font-size:10px;font-weight:800;letter-spacing:.08em;text-transform:uppercase;color:var(--red);text-decoration:none;border-bottom:1px solid var(--red);padding-bottom:2px;">Clear ×</a>
            <?php endif; ?>
            <?php endif; ?>
        </div>

        <!-- Marketplace Filters -->
        <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:16px;">
            <?php $qs=$_GET; $base=APP_URL.'/shop'; $mk=function($k,$v) use($qs,$base){ $n=$qs; if($v===null) unset($n[$k]); else $n[$k]=$v; unset($n['page']); $q=http_build_query($n); return $base.($q?"?$q":""); };
                  $curLt=$_GET['listing_type']??''; $curCond=$_GET['condition']??''; $curOrig=$_GET['origin']??'';
            ?>
            <a href="<?= $mk('listing_type',null) ?>" style="padding:6px 12px;border:1px solid var(--ink);background:<?= $curLt==''?'var(--ink)':'#fff' ?>;color:<?= $curLt==''?'#fff':'var(--ink)' ?>;font-family:var(--f-mono);font-size:10px;text-decoration:none;">All</a>
            <a href="<?= $mk('listing_type','retail') ?>" style="padding:6px 12px;border:1px solid var(--ink);background:<?= $curLt==='retail'?'var(--ink)':'#fff' ?>;color:<?= $curLt==='retail'?'#fff':'var(--ink)' ?>;font-family:var(--f-mono);font-size:10px;text-decoration:none;">Retail</a>
            <a href="<?= $mk('listing_type','wholesale') ?>" style="padding:6px 12px;border:1px solid var(--ink);background:<?= $curLt==='wholesale'?'var(--ink)':'#fff' ?>;color:<?= $curLt==='wholesale'?'#fff':'var(--ink)' ?>;font-family:var(--f-mono);font-size:10px;text-decoration:none;">Wholesale · MOQ</a>
            <a href="<?= $mk('listing_type','export') ?>" style="padding:6px 12px;border:1px solid var(--ink);background:<?= $curLt==='export'?'var(--ink)':'#fff' ?>;color:<?= $curLt==='export'?'#fff':'var(--ink)' ?>;font-family:var(--f-mono);font-size:10px;text-decoration:none;">Export</a>
            <a href="<?= $mk('condition','used') ?>" style="padding:6px 12px;border:1px solid var(--ink);background:<?= $curCond==='used'?'var(--ink)':'#fff' ?>;color:<?= $curCond==='used'?'#fff':'var(--ink)' ?>;font-family:var(--f-mono);font-size:10px;text-decoration:none;">Used — C2C</a>
            <a href="<?= $mk('origin','local') ?>" style="padding:6px 12px;border:1px solid var(--ink);background:<?= $curOrig==='local'?'var(--ink)':'#fff' ?>;color:<?= $curOrig==='local'?'#fff':'var(--ink)' ?>;font-family:var(--f-mono);font-size:10px;text-decoration:none;">Local Ghana</a>
            <a href="<?= $mk('origin','international_export') ?>" style="padding:6px 12px;border:1px solid var(--ink);background:<?= $curOrig==='international_export'?'var(--ink)':'#fff' ?>;color:<?= $curOrig==='international_export'?'#fff':'var(--ink)' ?>;font-family:var(--f-mono);font-size:10px;text-decoration:none;">Export China</a>
            <a href="<?= APP_URL ?>/sourcing" style="padding:6px 12px;background:var(--red);color:#fff;font-family:var(--f-mono);font-size:10px;text-decoration:none;">🌍 B2B Sourcing →</a>
        </div>

        <!-- Product Grid -->
        <div class="products-grid">
            <?php require __DIR__ . '/grid.php'; ?>
        </div>

        <?php if ($pagination['totalPages'] > 1): ?>
        <div class="shop-pagination">
            <?php
            $queryParams = $_GET;
            unset($queryParams['page']);
            $baseQuery = http_build_query($queryParams);
            $baseUrl = APP_URL . '/shop' . ($baseQuery ? '?' . $baseQuery : '');
            $sep = $baseQuery ? '&' : '?';
            ?>
            <?php if ($pagination['hasPrev']): ?>
                <a href="<?= $baseUrl . $sep . 'page=' . ($pagination['page'] - 1) ?>" class="page-btn">&laquo; Prev</a>
            <?php endif; ?>
            
            <?php
            $start = max(1, $pagination['page'] - 2);
            $end = min($pagination['totalPages'], $pagination['page'] + 2);
            if ($start > 1) echo '<span class="page-dots">...</span>';
            for ($i = $start; $i <= $end; $i++):
                $isActive = $i === $pagination['page'];
            ?>
                <a href="<?= $baseUrl . $sep . 'page=' . $i ?>" class="page-btn <?= $isActive ? 'active' : '' ?>"><?= $i ?></a>
            <?php endfor; 
            if ($end < $pagination['totalPages']) echo '<span class="page-dots">...</span>';
            ?>

            <?php if ($pagination['hasNext']): ?>
                <a href="<?= $baseUrl . $sep . 'page=' . ($pagination['page'] + 1) ?>" class="page-btn">Next &raquo;</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
