#!/usr/bin/env python3
"""Probe production Avazonia for category existence.

For each requested category (top-level + sub), GET /shop?cat=SLUG and classify:
  - "landing"  -> page contains cat-list-page (category exists, has children)
  - "exists"   -> title is "<Name> — Avazonia" (category exists, product grid)
  - "missing"  -> title is "Shop — Avazonia"

Also walks the landing pages to reconstruct the live production tree.
"""
import concurrent.futures as cf
import re
import sys
import urllib.request

BASE = "https://www.avazonia.com/shop?cat="

# ---- Requested final structure (from client) ----
REQUESTED = {
    "Electronics": ["TVs & Home Entertainment", "Cameras & Photography", "Audio & Headphones", "Speakers", "Gaming", "Smart Home", "Networking", "Storage Devices", "Printers & Scanners", "Electronic Components", "Other Electronics"],
    "Mobile Phones & Accessories": ["Smartphones", "Feature Phones", "Phone Cases & Covers", "Screen Protectors", "Chargers", "Charging Cables", "Power Banks", "Wireless Chargers", "Car Chargers", "Phone Holders", "Selfie Sticks & Tripods", "Phone Stands", "Mobile Accessories"],
    "Computers & Accessories": ["Laptops", "Desktop Computers", "Monitors", "Keyboards", "Mice", "Webcams", "Laptop Bags", "Laptop Stands", "USB Hubs", "Hard Drives", "SSDs", "Flash Drives", "RAM & Components", "Routers & Networking", "Computer Accessories"],
    "Fashion": ["Men's Clothing", "Women's Clothing", "Children's Clothing", "Shoes", "Bags", "Watches", "Jewelry", "Sunglasses", "Belts", "Hats & Caps", "Fashion Accessories"],
    "Beauty & Personal Care": ["Skincare", "Hair Care", "Hair Extensions & Wigs", "Makeup", "Fragrances", "Men's Grooming", "Personal Care Appliances", "Nail Care", "Bath & Body", "Beauty Accessories"],
    "Health & Wellness": ["Vitamins & Supplements", "Herbal Products", "Medical Supplies", "First Aid", "Fitness & Wellness", "Personal Health Devices", "Mobility & Support Products"],
    "Home & Living": ["Furniture", "Home Décor", "Lighting", "Bedding", "Bathroom", "Storage & Organization", "Cleaning Supplies", "Home Improvement", "Household Essentials"],
    "Kitchen & Appliances": ["Refrigerators", "Freezers", "Cookers & Ovens", "Microwaves", "Blenders", "Air Fryers", "Rice Cookers", "Electric Kettles", "Coffee Makers", "Kitchen Appliances", "Kitchen Tools & Utensils"],
    "Automotive": ["Cars", "SUVs", "Pickup Trucks", "Vans", "Buses", "Trucks", "Motorcycles", "Electric Vehicles", "Car Parts", "Car Accessories", "Car Electronics", "Car Care Products", "Tyres & Wheels", "Automotive Tools & Equipment"],
    "Gadgets & Smart Devices": ["Smartwatches", "Fitness Trackers", "Smart Glasses", "Smart Bands", "Bluetooth Trackers", "Smart Home Devices", "Wearable Technology", "Other Gadgets"],
    "Electrical & Power": ["Solar Panels", "Solar Inverters", "Batteries", "Power Stations", "Generators", "UPS", "Voltage Regulators", "Electrical Cables", "Switches & Sockets", "Electrical Accessories"],
    "Tools & Hardware": ["Power Tools", "Hand Tools", "Construction Tools", "Workshop Equipment", "Measuring Tools", "Welding Equipment", "Hardware", "Safety Equipment", "Tool Storage"],
    "Sports & Fitness": ["Gym Equipment", "Fitness Accessories", "Football", "Basketball", "Volleyball", "Tennis", "Running", "Cycling", "Swimming", "Outdoor Sports", "Sportswear", "Sports Accessories"],
    "Musical Instruments": ["Pianos & Keyboards", "Guitars", "Drums & Percussion", "Violins & String Instruments", "Brass Instruments", "Wind Instruments", "DJ Equipment", "Microphones", "Amplifiers", "Speakers", "Studio Equipment", "Musical Accessories"],
    "Baby & Kids": ["Baby Clothing", "Baby Shoes", "Baby Feeding", "Baby Care", "Strollers", "Car Seats", "Toys", "Educational Toys", "Kids' Furniture", "School Supplies"],
    "Toys & Hobbies": ["Remote Control Toys", "Drones", "Collectibles", "Board Games", "Outdoor Toys", "Educational Toys", "Model Kits", "Hobby Equipment"],
    "Office & School Supplies": ["Stationery", "Pens & Pencils", "Notebooks", "School Bags", "Office Furniture", "Office Electronics", "Printers", "Ink & Toner", "Filing & Organization", "Educational Materials"],
    "Agriculture & Farming": ["Farm Machinery", "Agricultural Tools", "Irrigation Equipment", "Seeds", "Gardening Equipment", "Poultry Equipment", "Livestock Equipment", "Greenhouse Equipment", "Agricultural Supplies"],
    "Industrial & Commercial Equipment": ["Manufacturing Equipment", "Packaging Machinery", "Food Processing Equipment", "Restaurant Equipment", "Commercial Refrigeration", "Construction Equipment", "Warehouse Equipment", "Industrial Tools", "Safety Equipment"],
    "Food & Beverages": ["Groceries", "Snacks", "Beverages", "Cooking Ingredients", "Canned & Packaged Foods", "Baby Food", "Specialty Foods"],
    "Pet Supplies": ["Dog Supplies", "Cat Supplies", "Bird Supplies", "Fish & Aquarium", "Pet Food", "Pet Accessories", "Pet Grooming", "Pet Health"],
    "Travel & Luggage": ["Suitcases", "Travel Bags", "Backpacks", "Travel Accessories", "Travel Organizers", "Camping & Outdoor Gear", "Travel Electronics"],
    "Jewelry & Accessories": ["Rings", "Necklaces", "Bracelets", "Earrings", "Watches", "Fashion Jewelry", "Jewelry Boxes", "Jewelry Accessories"],
    "Books, Media & Entertainment": ["Books", "Educational Books", "Religious Books", "Magazines", "Music", "Movies", "Educational Media", "Collectibles"],
    "Services": ["Business Services", "Professional Services", "Repair Services", "Installation Services", "Delivery & Logistics", "Events & Entertainment", "Real Estate Services", "Other Services"],
    "Other Products": ["Miscellaneous Products", "Other Unclassified Products"],
}

# Production slugs seen in nav (may exist but not be in the client list)
EXTRA_PROBES = ["audi", "automobile", "vehicles-cars", "vehicles", "energy", "industrial-machinery",
                "wholesale-general", "fashion-textiles", "home-living", "health-medical",
                "smart-home-devices", "mobile-accessories", "smartphones", "laptops", "audio",
                "wearables", "computers-accessories", "electronics", "gadgets-smart-devices",
                "kitchen-appliances", "sports-fitness", "musical-instruments", "baby-kids",
                "toys-hobbies", "office-school-supplies", "agriculture-farming", "food-beverages",
                "pet-supplies", "travel-luggage", "jewelry-accessories", "books-media-entertainment",
                "services", "other-products", "tools-hardware", "electrical-power", "automotive",
                "home-living-2", "electronics-2"]


def slugify(name):
    s = name.lower()
    s = s.replace("&", "and")
    s = re.sub(r"[^a-z0-9]+", "-", s)
    return s.strip("-")


def fetch(slug):
    url = BASE + slug
    try:
        req = urllib.request.Request(url, headers={"User-Agent": "Mozilla/5.0"})
        with urllib.request.urlopen(req, timeout=20) as r:
            html = r.read().decode("utf-8", errors="replace")
        return slug, html
    except Exception as e:
        return slug, "ERROR:" + str(e)


def classify(slug, html):
    if html.startswith("ERROR:"):
        return "error"
    if "cat-list-page" in html:
        return "landing"
    m = re.search(r"<title>(.*?)</title>", html, re.S)
    title = m.group(1).strip() if m else ""
    if title.endswith("Shop — Avazonia") or title == "Shop — Avazonia":
        return "missing"
    return "exists"


def extract_children(slug, html):
    """From a landing page, pull subcategory slugs + names + counts."""
    out = []
    for m in re.finditer(r'href="[^"]*cat=([a-z0-9-]+)"[^>]*class="cat-list-row"[^>]*>', html):
        out.append(m.group(1))
    names = re.findall(r'class="cat-list-name">([^<]+)</div>', html)
    counts = re.findall(r'class="cat-list-count">([^<]+)</div>', html)
    return out, names, counts


def main():
    all_slugs = []          # (slug, requested_name, parent)
    for parent, subs in REQUESTED.items():
        all_slugs.append((slugify(parent), parent, None))
        for sub in subs:
            all_slugs.append((slugify(sub), sub, parent))
    extra = [(s, s, None) for s in EXTRA_PROBES]

    probes = all_slugs + [e for e in extra if e[0] not in {a[0] for a in all_slugs}]
    slugs = [p[0] for p in probes]
    results = {}

    with cf.ThreadPoolExecutor(max_workers=12) as ex:
        for slug, html in ex.map(fetch, slugs):
            results[slug] = html

    print("=" * 70)
    print("PRODUCTION CATEGORY LANDING PAGES (exists + has children)")
    print("=" * 70)
    production_tree = {}
    for slug, html in results.items():
        cls = classify(slug, html)
        if cls == "landing":
            children, names, counts = extract_children(slug, html)
            production_tree[slug] = children
            print(f"* {slug}: {len(children)} children -> {children}")
    print()
    print("=" * 70)
    print("REQUESTED vs PRODUCTION")
    print("=" * 70)
    stats = {"exists": 0, "landing": 0, "missing": 0, "error": 0}
    for slug, req_name, parent in all_slugs:
        html = results.get(slug, "")
        cls = classify(slug, html)
        stats[cls] += 1
        flag = {"exists": "AVAILABLE", "landing": "AVAILABLE (has subs)", "missing": "MISSING", "error": "ERROR"}[cls]
        parent_label = f"  [{parent}]" if parent else "  [TOP]"
        print(f"{flag:<20} {slug:<45} {req_name}{parent_label}")
    print()
    print("STATS:", stats)
    print()
    print("=" * 70)
    print("EXTRA slugs found on production (not in requested list)")
    print("=" * 70)
    for slug, req_name, parent in extra:
        html = results.get(slug, "")
        cls = classify(slug, html)
        if cls in ("exists", "landing"):
            print(f"  {slug}: {cls}")


if __name__ == "__main__":
    main()