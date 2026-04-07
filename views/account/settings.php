<?php
// views/account/settings.php
require_once __DIR__ . '/../layout/head.php';
require_once __DIR__ . '/../layout/nav.php';

$user_name = Session::get('user_name') ?: 'Member';
?>

<section class="account-page" style="padding: 100px 0 80px; background: #fafafa; min-height: 80vh;">
    <div class="container" style="max-width: 1100px;">
        
        <!-- Breadcrumb & Header -->
        <nav style="margin-bottom: 32px;">
            <div style="font-family: var(--f-mono); font-size: 10px; text-transform: uppercase; color: var(--mid-gray); letter-spacing: 0.1em; display: flex; align-items: center; gap: 8px;">
                <a href="<?= APP_URL ?>" style="color: inherit; text-decoration: none;">Avazonia</a>
                <span>/</span>
                <a href="<?= APP_URL ?>/account" style="color: inherit; text-decoration: none;">Account</a>
                <span>/</span>
                <span style="color: var(--ink);">Settings</span>
            </div>
            <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-top: 16px;">
                <div>
                    <a href="<?= APP_URL ?>/account" style="display: inline-block; font-family: var(--f-mono); font-size: 10px; text-transform: uppercase; color: var(--mid-gray); text-decoration: none; margin-bottom: 12px; letter-spacing: 0.05em;">← Back to Account</a>
                    <h1 style="font-family: var(--f-display); font-weight: 800; font-size: 32px; margin: 0; color: var(--ink); letter-spacing: -0.02em;">Profile Settings</h1>
                </div>
            </div>
        </nav>

        <div class="account-grid" style="display: grid; grid-template-columns: 240px 1fr; gap: 48px;">
            
            <!-- Sidebar -->
            <aside>
                <div style="background: #fff; border: 1px solid #eee; border-radius: 12px; padding: 24px; position: sticky; top: 120px;">
                    <nav style="display: flex; flex-direction: column; gap: 4px;">
                        <a href="<?= APP_URL ?>/account" style="display: flex; align-items: center; gap: 12px; padding: 12px 16px; border-radius: 8px; text-decoration: none; color: var(--mid-gray); font-size: 13px;">
                            <span style="font-size: 16px;">📊</span> Dashboard
                        </a>
                        <a href="<?= APP_URL ?>/account/settings" style="display: flex; align-items: center; gap: 12px; padding: 12px 16px; background: var(--off); border-radius: 8px; text-decoration: none; color: var(--red); font-weight: 700; font-size: 13px;">
                            <span style="font-size: 16px;">⚙️</span> Profile Settings
                        </a>
                        <div style="margin: 12px 0; border-top: 1px solid #eee;"></div>
                        <a href="<?= APP_URL ?>/logout" style="display: flex; align-items: center; gap: 12px; padding: 12px 16px; border-radius: 8px; text-decoration: none; color: #f5222d; font-size: 13px;">
                            <span style="font-size: 16px;">👋</span> Logout
                        </a>
                    </nav>
                </div>
            </aside>

            <!-- Main Content -->
            <main>
                <div style="background: #fff; border: 1px solid #eee; border-radius: 12px; padding: 40px; max-width: 680px;">
                    <?php if (isset($success)): ?>
                        <div style="background: #e6f7ec; color: #00a854; padding: 16px; border-radius: 8px; margin-bottom: 32px; font-size: 13px; font-weight: 500; border-left: 4px solid #00a854;">
                            ✅ <?= $success ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" style="display: flex; flex-direction: column; gap: 24px;">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
                            <div>
                                <label style="display: block; font-family: var(--f-semi); font-size: 11px; text-transform: uppercase; color: var(--mid-gray); margin-bottom: 8px; letter-spacing: 0.05em;">Full Name</label>
                                <input type="text" name="full_name" value="<?= $user['full_name'] ?>" required style="width: 100%; padding: 14px 16px; border: 1px solid #ddd; border-radius: 8px; font-family: inherit; font-size: 14px;">
                            </div>
                            <div>
                                <label style="display: block; font-family: var(--f-semi); font-size: 11px; text-transform: uppercase; color: var(--mid-gray); margin-bottom: 8px; letter-spacing: 0.05em;">Phone Number</label>
                                <input type="tel" name="phone" value="<?= $user['phone'] ?>" style="width: 100%; padding: 14px 16px; border: 1px solid #ddd; border-radius: 8px; font-family: inherit; font-size: 14px;">
                            </div>
                        </div>

                        <div>
                            <label style="display: block; font-family: var(--f-semi); font-size: 11px; text-transform: uppercase; color: var(--mid-gray); margin-bottom: 8px; letter-spacing: 0.05em;">Email Address (Locked)</label>
                            <input type="email" value="<?= $user['email'] ?>" disabled style="width: 100%; padding: 14px 16px; background: #f9f9f9; border: 1px solid #eee; border-radius: 8px; color: #999; font-family: inherit; font-size: 14px; cursor: not-allowed;">
                        </div>

                        <div style="margin-top: 12px;">
                            <button type="submit" style="height: 52px; padding: 0 40px; background: var(--ink); color: #fff; border: none; border-radius: 8px; font-family: var(--f-display); font-weight: 800; font-size: 14px; text-transform: uppercase; letter-spacing: 0.05em; cursor: pointer; transition: 0.2s;">
                                Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </main>
        </div>
    </div>
</section>

<style>
    input:focus { outline: none; border-color: var(--red) !important; box-shadow: 0 0 0 4px rgba(229,0,26,0.05); }
    button:hover { background: var(--red) !important; transform: translateY(-1px); box-shadow: 0 10px 20px rgba(229,0,26,0.1); }

    @media (max-width: 768px) {
        .account-page { padding: 40px 0 60px !important; }
        .account-grid { grid-template-columns: 1fr !important; gap: 32px !important; }
        aside { display: none; } /* Hide sidebar on mobile to focus on the form */
        
        form > div[style*="grid-template-columns"] {
            grid-template-columns: 1fr !important;
            gap: 16px !important;
        }

        main > div {
            padding: 24px !important;
        }

        h1 { font-size: 24px !important; }
    }
</style>

<?php require_once __DIR__ . '/../layout/footer.php'; ?>
