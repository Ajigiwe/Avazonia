-- 018: Admin-managed delivery/selling regions (Ghana) for seller storefronts and shop filtering.
-- sellers.region (VARCHAR, from 010) stores the chosen region NAME; regions table is the source of truth.

CREATE TABLE IF NOT EXISTS regions (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  slug VARCHAR(100) NOT NULL,
  is_active TINYINT(1) DEFAULT 1,
  sort_order INT DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_region_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed Ghana's 16 regions (idempotent)
INSERT INTO regions (name, slug, sort_order)
SELECT 'Ahafo','ahafo',1 WHERE NOT EXISTS (SELECT 1 FROM regions WHERE slug='ahafo');
INSERT INTO regions (name, slug, sort_order)
SELECT 'Ashanti','ashanti',2 WHERE NOT EXISTS (SELECT 1 FROM regions WHERE slug='ashanti');
INSERT INTO regions (name, slug, sort_order)
SELECT 'Bono','bono',3 WHERE NOT EXISTS (SELECT 1 FROM regions WHERE slug='bono');
INSERT INTO regions (name, slug, sort_order)
SELECT 'Bono East','bono-east',4 WHERE NOT EXISTS (SELECT 1 FROM regions WHERE slug='bono-east');
INSERT INTO regions (name, slug, sort_order)
SELECT 'Central','central',5 WHERE NOT EXISTS (SELECT 1 FROM regions WHERE slug='central');
INSERT INTO regions (name, slug, sort_order)
SELECT 'Eastern','eastern',6 WHERE NOT EXISTS (SELECT 1 FROM regions WHERE slug='eastern');
INSERT INTO regions (name, slug, sort_order)
SELECT 'Greater Accra','greater-accra',7 WHERE NOT EXISTS (SELECT 1 FROM regions WHERE slug='greater-accra');
INSERT INTO regions (name, slug, sort_order)
SELECT 'North East','north-east',8 WHERE NOT EXISTS (SELECT 1 FROM regions WHERE slug='north-east');
INSERT INTO regions (name, slug, sort_order)
SELECT 'Northern','northern',9 WHERE NOT EXISTS (SELECT 1 FROM regions WHERE slug='northern');
INSERT INTO regions (name, slug, sort_order)
SELECT 'Oti','oti',10 WHERE NOT EXISTS (SELECT 1 FROM regions WHERE slug='oti');
INSERT INTO regions (name, slug, sort_order)
SELECT 'Savannah','savannah',11 WHERE NOT EXISTS (SELECT 1 FROM regions WHERE slug='savannah');
INSERT INTO regions (name, slug, sort_order)
SELECT 'Upper East','upper-east',12 WHERE NOT EXISTS (SELECT 1 FROM regions WHERE slug='upper-east');
INSERT INTO regions (name, slug, sort_order)
SELECT 'Upper West','upper-west',13 WHERE NOT EXISTS (SELECT 1 FROM regions WHERE slug='upper-west');
INSERT INTO regions (name, slug, sort_order)
SELECT 'Volta','volta',14 WHERE NOT EXISTS (SELECT 1 FROM regions WHERE slug='volta');
INSERT INTO regions (name, slug, sort_order)
SELECT 'Western','western',15 WHERE NOT EXISTS (SELECT 1 FROM regions WHERE slug='western');
INSERT INTO regions (name, slug, sort_order)
SELECT 'Western North','western-north',16 WHERE NOT EXISTS (SELECT 1 FROM regions WHERE slug='western-north');
