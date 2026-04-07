<?php require_once __DIR__ . '/../layout/head.php'; ?>
<?php require_once __DIR__ . '/../layout/nav.php'; ?>

<style>
    .about-main { padding-top: 120px; padding-bottom: 120px; }
    .about-header { margin-bottom: 100px; max-width: 700px; }
    .about-grid { display: grid; grid-template-columns: 1fr 1.2fr; gap: 80px; align-items: start; }
    
    @media (max-width: 1024px) {
        .about-grid { grid-template-columns: 1fr; gap: 60px; }
        .about-main { padding-top: 80px; padding-bottom: 80px; }
        .about-header { margin-bottom: 60px; }
    }
    
    @media (max-width: 768px) {
        .about-header h1 { font-size: 48px !important; }
        .about-main { padding-top: 60px; }
    }
</style>

<main class="about-main">
    <div class="container" style="max-width: 1100px;">
        <!-- Editorial Header -->
        <header class="about-header">
            <div class="sec-over" style="margin-bottom: 16px;">ESTABLISHED 2024</div>
            <h1 style="font-family: var(--f-display); font-size: clamp(64px, 10vw, 100px); font-weight: 900; line-height: 0.85; letter-spacing: -0.04em; color: var(--ink); text-transform: uppercase; margin-bottom: 40px;">
                CRAFTING the<br>
                <span class="outline" style="-webkit-text-stroke: 1.5px var(--ink); color: transparent;">DIGITAL</span><br>
                FUTURE
            </h1>
            <p style="font-family: var(--f-body); font-size: 20px; line-height: 1.4; color: var(--mid-gray); font-weight: 500;">
                Avazonia is a modern online tech store dedicated to making quality gadgets and accessories accessible and affordable for everyone in Ghana.
            </p>
        </header>

        <!-- Dynamic Grid Content -->
        <div class="about-grid">
            <!-- Left Side: Vision & Mission -->
            <div style="display: flex; flex-direction: column; gap: 60px;">
                <div class="reveal">
                    <h3 style="font-family: var(--f-display); font-size: 11px; font-weight: 900; letter-spacing: 0.2em; text-transform: uppercase; color: var(--red); margin-bottom: 24px;">The Mission</h3>
                    <p style="font-size: 18px; line-height: 1.6; color: var(--ink);">
                        We specialize in sourcing and delivering a wide range of reliable tech products — including smartwatches, wireless earbuds, phone accessories, chargers, and other everyday digital essentials. Our goal is simple: to connect customers with the latest and most useful gadgets without the high prices often associated with technology.
                    </p>
                </div>
                
                <div class="reveal rd1">
                    <h3 style="font-family: var(--f-display); font-size: 11px; font-weight: 900; letter-spacing: 0.2em; text-transform: uppercase; color: var(--red); margin-bottom: 24px;">Our Vision</h3>
                    <p style="font-size: 18px; line-height: 1.6; color: var(--ink);">
                        Our vision is to become one of Ghana’s most trusted and recognized online tech stores — a go-to destination for affordable gadgets and digital accessories.
                    </p>
                </div>

                <div style="padding: 40px; background: var(--off); border-left: 4px solid var(--red);">
                    <p style="font-family: var(--f-display); font-size: 20px; font-weight: 800; line-height: 1.3; color: var(--ink);">
                        "Technology is no longer a luxury; it is an essential component of modern productivity. We bridge the gap between innovation and affordability."
                    </p>
                </div>
            </div>

            <!-- Right Side: Philosophy & Convenience -->
            <div style="display: flex; flex-direction: column; gap: 40px;">
                <div class="reveal rd2">
                    <div style="margin-bottom: 32px; overflow: hidden; border-radius: 4px;">
                        <img src="https://images.unsplash.com/photo-1519389950473-47ba0277781c?q=80&w=2070&auto=format&fit=crop" alt="Technology Lifestyle" style="width: 100%; max-height: 350px; object-fit: cover;">
                    </div>
                    <h2 style="font-family: var(--f-display); font-size: 32px; font-weight: 800; line-height: 1.1; color: var(--ink); margin-bottom: 24px; text-transform: uppercase;">A Commitment<br>to Convenience</h2>
                    <p style="font-size: 16px; line-height: 1.8; color: var(--mid-gray); margin-bottom: 24px;">
                        What sets us apart is our commitment to convenience and trust. We offer a seamless online shopping experience, flexible payment options, and reliable delivery services across Ghana. Whether you’re shopping from Accra or anywhere else in the country, Avazonia ensures your order gets to you safely and on time.
                    </p>
                    <p style="font-size: 16px; line-height: 1.8; color: var(--mid-gray);">
                        We also work with a growing network of partners and resellers, creating opportunities for individuals and businesses to earn by selling tech products without the burden of holding stock.
                    </p>
                </div>

                <div style="margin-top: 40px;">
                    <a href="<?= APP_URL ?>/shop" class="btn-ink" style="width: fit-content; padding: 0 40px; height: 56px;">EXPLORE THE COLLECTION →</a>
                </div>
            </div>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>

