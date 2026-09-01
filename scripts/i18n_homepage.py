#!/usr/bin/env python3
"""Add homepage keys to lang files and refactor homepage strings."""
import pathlib

ROOT = pathlib.Path(__file__).resolve().parent.parent

# ── Add homepage keys to lang files ──
homepage_keys_en = {
    # Homepage sections
    'home.hero_tagline': "Buy. Sell. Source. Trade.",
    'home.hero_sub': "AVAZONIA — AFRICA'S MULTI-VENDOR MARKETPLACE",
    'home.hero_buy': 'Buy',
    'home.hero_sell': 'Sell',
    'home.hero_source': 'Source',
    'home.bestsellers_over': 'Hand-picked',
    'home.bestsellers_title': 'Bestsellers',
    'home.full_catalogue': 'Full catalogue',
    'home.wholesale_over': 'B2B · WHOLESALE',
    'home.wholesale_title': 'Wholesale Deals',
    'home.go_sourcing': 'Go to Sourcing',
    'home.featured_biz': 'Featured Businesses',
    'home.intl_suppliers': 'International Suppliers',
    'home.explore_category': 'EXPLORE CATEGORY',
    'home.shop_all': 'Shop All',
    'home.see_all_preorders': 'See all pre-orders',
    'home.vehicle_sourcing': 'INTERNATIONAL VEHICLE SOURCING — FOB / CIF',
    'home.special_offer': 'SPECIAL OFFER',
    'home.newsletter_email_ph': 'Email Address...',
}

homepage_keys_fr = {
    'home.hero_tagline': 'Acheter. Vendre. Sourcing. Commerce.',
    'home.hero_sub': "AVAZONIA — LA MARKETPLACE MULTI-VENDEURS D'AFRIQUE",
    'home.hero_buy': 'Acheter',
    'home.hero_sell': 'Vendre',
    'home.hero_source': 'Sourcing',
    'home.bestsellers_over': 'Sélection',
    'home.bestsellers_title': 'Meilleures ventes',
    'home.full_catalogue': 'Tout le catalogue',
    'home.wholesale_over': 'B2B · GROS',
    'home.wholesale_title': 'Offres en gros',
    'home.go_sourcing': 'Aller au Sourcing',
    'home.featured_biz': 'Entreprises en vedette',
    'home.intl_suppliers': 'Fournisseurs internationaux',
    'home.explore_category': 'EXPLORER LA CATÉGORIE',
    'home.shop_all': 'Tout acheter',
    'home.see_all_preorders': 'Voir les précommandes',
    'home.vehicle_sourcing': 'SORCING VÉHICULES INTERNATIONAUX — FOB / CIF',
    'home.special_offer': 'OFFRE SPÉCIALE',
    'home.newsletter_email_ph': 'Adresse email...',
}

homepage_keys_zh = {
    'home.hero_tagline': '买. 卖. 采购. 贸易.',
    'home.hero_sub': 'AVAZONIA — 非洲多供应商市场平台',
    'home.hero_buy': '购买',
    'home.hero_sell': '销售',
    'home.hero_source': '采购',
    'home.bestsellers_over': '精选推荐',
    'home.bestsellers_title': '热销排行',
    'home.full_catalogue': '完整目录',
    'home.wholesale_over': 'B2B · 批发',
    'home.wholesale_title': '批发优惠',
    'home.go_sourcing': '前往采购',
    'home.featured_biz': '优质商家',
    'home.intl_suppliers': '国际供应商',
    'home.explore_category': '探索分类',
    'home.shop_all': '查看全部',
    'home.see_all_preorders': '查看预售商品',
    'home.vehicle_sourcing': '国际车辆采购 — FOB / CIF',
    'home.special_offer': '特别优惠',
    'home.newsletter_email_ph': '邮箱地址...',
}

def merge_keys(filepath, new_keys):
    """Add new keys to a PHP return array file."""
    c = filepath.read_text(encoding='utf-8')
    # Find the last entry before the closing ];
    # Insert new keys before the last line
    lines = c.rstrip().split('\n')
    # Find the closing ];
    insert_idx = len(lines) - 1
    for i in range(len(lines) - 1, -1, -1):
        if lines[i].strip() == '];':
            insert_idx = i
            break

    new_lines = []
    for key, val in sorted(new_keys.items()):
        val_escaped = val.replace("'", "\\'")
        new_lines.append(f"    '{key}' => '{val_escaped}',")

    lines[insert_idx:insert_idx] = new_lines
    filepath.write_text('\n'.join(lines) + '\n', encoding='utf-8')
    print(f"Added {len(new_keys)} keys to {filepath.name}")

merge_keys(ROOT / 'lang/en.php', homepage_keys_en)
merge_keys(ROOT / 'lang/fr.php', homepage_keys_fr)
merge_keys(ROOT / 'lang/zh.php', homepage_keys_zh)

# ── Refactor homepage ──
p = ROOT / 'views/home/index.php'
c = p.read_text(encoding='utf-8')

home_replacements = [
    # Hero band
    ("AVAZONIA — AFRICA'S MULTI-VENDOR MARKETPLACE", "<?= t('home.hero_sub', \"AVAZONIA — AFRICA'S MULTI-VENDOR MARKETPLACE\") ?>"),
    ("Buy. Sell. Source. Trade.", "<?= t('home.hero_tagline', 'Buy. Sell. Source. Trade.') ?>"),
    (">Buy</a>", "><?= t('home.hero_buy', 'Buy') ?></a>"),
    (">Sell</a>", "><?= t('home.hero_sell', 'Sell') ?></a>"),
    (">Source</a>", "><?= t('home.hero_source', 'Source') ?></a>"),
    # Bestsellers
    (">Hand-picked</div>", "><?= t('home.bestsellers_over', 'Hand-picked') ?></div>"),
    (">Bestsellers</h2>", "><?= t('home.bestsellers_title', 'Bestsellers') ?></h2>"),
    ('>Full catalogue <span', "><?= t('home.full_catalogue', 'Full catalogue') ?> <span"),
    # Wholesale
    ('>B2B · WHOLESALE</div>', "><?= t('home.wholesale_over', 'B2B · WHOLESALE') ?></div>"),
    ('>Wholesale Deals</h2>', "><?= t('home.wholesale_title', 'Wholesale Deals') ?></h2>"),
    ('>Go to Sourcing →</a>', "><?= t('home.go_sourcing', 'Go to Sourcing') ?> →</a>"),
    # Featured / International
    ('>Featured Businesses</span>', "><?= t('home.featured_biz', 'Featured Businesses') ?></span>"),
    ('>International Suppliers</span>', "><?= t('home.intl_suppliers', 'International Suppliers') ?></span>"),
    # Category showcase
    ("EXPLORE CATEGORY", "<?= t('home.explore_category', 'EXPLORE CATEGORY') ?>"),
    # Pre-orders
    ('>See all pre-orders →</a>', "><?= t('home.see_all_preorders', 'See all pre-orders') ?> →</a>"),
    # Vehicle sourcing
    ('INTERNATIONAL VEHICLE SOURCING — FOB / CIF', "<?= t('home.vehicle_sourcing', 'INTERNATIONAL VEHICLE SOURCING — FOB / CIF') ?>"),
    # Newsletter
    ('placeholder="Email Address..."', "placeholder=\"<?= t('home.newsletter_email_ph', 'Email Address...') ?>\""),
]

for old, new in home_replacements:
    if old in c:
        c = c.replace(old, new, 1)
        print(f'  ✓ {old[:40]}')
    else:
        print(f'  ✗ NOT FOUND: {old[:40]}')

p.write_text(c, encoding='utf-8')
print('\nHomepage refactored!')
