"""One-off converter: HOCO supplier quotation -> Avazonia v3 bulk-import CSV.

Supplier layout (sheet 'popular products'):
  name | Picture(embedded) | Package(empty) | sku | Color | price(USD) | Msrp | stock
  | Carton Size | VOLUME | Description | Link for clear photos (gallery page, not hotlinkable)

Output: v3 template CSV with image_url_1 pointing at extracted embedded images
saved under public/uploads/products/hoco-<row>.<ext>.

Usage:
  python scripts/convert-hoco-import.py [input.xlsx] [output.csv]

Defaults assume the file at C:\\Users\\Vader\\Downloads\\HOCO Popular products quotation USD 20260827.xlsx.
Category is left blank (no error in preview; fix via grid autocomplete).
Brand is HOCO (create it in admin if missing - otherwise those rows error in preview).
Rows without a numeric price are kept with an empty price so preview flags them.
"""
import csv
import os
import re
import sys

V3_HEADERS = [
    'name', 'sku', 'price', 'currency', 'stock', 'category', 'sub_category', 'brand',
    'description', 'tags', 'listing_type', 'condition', 'visibility', 'moq',
    'wholesale_price', 'location_country', 'features', 'specs',
    'image_url_1', 'image_url_2', 'image_url_3', 'image_url_4', 'image_url_5',
    'image_url_6', 'image_url_7', 'image_url_8', 'image_url_9', 'image_url_10',
]

DEFAULT_INPUT = r'C:\Users\Vader\Downloads\HOCO Popular products quotation USD 20260827.xlsx'


def main():
    import openpyxl

    base = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
    src = sys.argv[1] if len(sys.argv) > 1 else DEFAULT_INPUT
    out = sys.argv[2] if len(sys.argv) > 2 else os.path.join(base, 'scratch', 'hoco-v3-import.csv')
    img_dir = os.path.join(base, 'public', 'uploads', 'products')
    os.makedirs(img_dir, exist_ok=True)
    os.makedirs(os.path.dirname(out), exist_ok=True)

    wb = openpyxl.load_workbook(src, read_only=True, data_only=True)
    ws = wb['popular products'] if 'popular products' in wb.sheetnames else wb.active
    rows = list(ws.iter_rows(values_only=True))
    wb.close()

    # Embedded images need a second (non-read-only) handle for drawings.
    wb2 = openpyxl.load_workbook(src, data_only=False)
    ws2 = wb2['popular products'] if 'popular products' in wb2.sheetnames else wb2.active
    by_row = {}
    for im in ws2._images:
        try:
            excel_row = int(im.anchor._from.row) + 1  # openpyxl anchor is 0-based
        except Exception:
            continue
        by_row.setdefault(excel_row, []).append(im)
    n_embedded = sum(len(v) for v in by_row.values())

    def clean(v):
        if v is None:
            return ''
        if isinstance(v, float) and v.is_integer():
            return str(int(v))
        return str(v).strip()

    written = 0
    with_images = 0
    missing_price = 0
    skipped = 0
    with open(out, 'w', newline='', encoding='utf-8-sig') as f:
        w = csv.writer(f)
        w.writerow(V3_HEADERS)
        for i, r in enumerate(rows[1:], start=2):  # i = excel row number
            name = clean(r[0])
            sku = clean(r[3])
            if not name and not sku:
                skipped += 1
                continue
            price_raw = r[5]
            desc_raw = clean(r[10])
            # Section-header rows (e.g. 'Other', 'EA series ...'): name only, no sku/price/description.
            if not sku and not isinstance(price_raw, (int, float)) and not desc_raw:
                skipped += 1
                continue
            price = ''
            if isinstance(price_raw, (int, float)) and float(price_raw) > 0:
                price = str(float(price_raw)).rstrip('0').rstrip('.') if '.' in str(float(price_raw)) else str(price_raw)
            else:
                missing_price += 1
            try:
                stock = int(float(r[7])) if r[7] not in (None, '') else 0
                stock = max(0, stock)
            except Exception:
                stock = 0
            color = clean(r[4])
            desc = desc_raw
            meta = []
            if color:
                meta.append('Color: ' + color)
            if r[6] not in (None, ''):
                meta.append('MSRP (USD): ' + clean(r[6]))
            if r[8] not in (None, ''):
                meta.append('Carton: ' + clean(r[8]))
            if r[9] not in (None, ''):
                meta.append('Volume (cbm): ' + clean(r[9]))
            link = clean(r[11])
            if link:
                meta.append('Supplier gallery: ' + link)
            if meta:
                desc = (desc + '\n' + '\n'.join(meta)).strip() if desc else '\n'.join(meta)

            # Attach embedded images anchored to this excel row (max 10).
            img_urls = []
            for k, im in enumerate(by_row.get(i, [])[:10]):
                ext = (getattr(im, 'format', '') or 'jpeg').lower().strip() or 'jpeg'
                ext = {'jpg': 'jpg', 'jpeg': 'jpg', 'png': 'png', 'webp': 'webp', 'gif': 'gif'}.get(ext, 'jpg')
                fname = 'hoco-r%d-%d.%s' % (i, k + 1, ext)
                try:
                    data = im._data()
                except Exception:
                    continue
                with open(os.path.join(img_dir, fname), 'wb') as img_f:
                    img_f.write(data)
                img_urls.append('public/uploads/products/' + fname)
            if img_urls:
                with_images += 1

            row = [
                name or sku, sku, price, 'USD', str(stock), '', '', 'HOCO',
                desc, color, 'retail', 'new', 'public', '', '', 'CN', '', '',
            ]
            row += (img_urls + [''] * 10)[:10]
            # Scrub newlines inside CSV cells already handled by csv module quoting.
            w.writerow(row)
            written += 1
    wb2.close()

    print('rows written      : %d' % written)
    print('rows skipped blank: %d' % skipped)
    print('rows missing price: %d (kept; preview will flag them)' % missing_price)
    print('embedded images   : %d total, attached to %d rows' % (n_embedded, with_images))
    print('csv               : %s' % out)
    print('images dir        : %s' % img_dir)
    print('NOTE: create brand HOCO in admin before import, or brand column will error.')


if __name__ == '__main__':
    main()
