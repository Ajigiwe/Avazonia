<?php
// admin/brands.php
require_once '../config/app.php';
require_once '../config/database.php';
require_once '../core/Session.php';
require_once '../models/Brand.php';

Session::start();
if (Session::get('user_role') !== 'admin') {
    header('Location: ' . APP_URL . '/login');
    exit;
}

$brandModel = new Brand(db());
$error = '';
$success = '';

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create' || $action === 'update') {
        $data = [
            'name' => $_POST['name'] ?? '',
            'slug' => $_POST['slug'] ?: strtolower(preg_replace('/[^A-Za-z0-9-]+/', '-', $_POST['name'])),
            'logo_url' => $_POST['logo_url'] ?? '',
            'sort_order' => (int)($_POST['sort_order'] ?? 0),
            'is_active' => isset($_POST['is_active']) ? 1 : 0
        ];

        if ($action === 'create') {
            if ($brandModel->create($data)) {
                $success = "Brand created successfully.";
            } else {
                $error = "Failed to create brand.";
            }
        } else {
            $id = (int)$_POST['id'];
            if ($brandModel->update($id, $data)) {
                $success = "Brand updated successfully.";
            } else {
                $error = "Failed to update brand.";
            }
        }
    } elseif ($action === 'delete') {
        $id = (int)$_POST['id'];
        if ($brandModel->delete($id)) {
            $success = "Brand deleted successfully.";
        } else {
            $error = "Failed to delete brand.";
        }
    }
}

$brands = db()->query("SELECT b.*, (SELECT COUNT(*) FROM products WHERE brand_id = b.id) as product_count FROM brands b ORDER BY b.name ASC")->fetchAll();

$title = "Manage Brands";
include 'layout/header.php';
?>

<div class="admin-header">
    <h1>Brands</h1>
    <button onclick="toggleModal('add-modal')" class="btn-red" style="height: 44px; padding: 0 24px; font-size: 10px; display: flex; align-items: center; justify-content: center; border: none; cursor: pointer;">+ New Brand</button>
</div>

<?php if ($success): ?>
    <div style="background: #f6ffed; border: 1px solid #b7eb8f; color: #52c41a; padding: 12px 20px; border-radius: 8px; margin-bottom: 24px; font-family: var(--f-semi); font-size: 13px;">
        <?= $success ?>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div style="background: #fff1f0; border: 1px solid #ffa39e; color: #f5222d; padding: 12px 20px; border-radius: 8px; margin-bottom: 24px; font-family: var(--f-semi); font-size: 13px;">
        <?= $error ?>
    </div>
<?php endif; ?>

<div class="panel">
    <div class="panel-header">
        <div class="panel-title">Brand Partners</div>
    </div>
    <table class="admin-table">
        <thead>
            <tr>
                <th>Brand Name</th>
                <th>Slug</th>
                <th>Total Inventory</th>
                <th>Status</th>
                <th>Order</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($brands as $b): ?>
            <tr>
                <td style="font-weight: 700;"><?= $b['name'] ?></td>
                <td style="font-family: var(--f-mono); font-size: 11px; opacity: 0.6;"><?= $b['slug'] ?></td>
                <td><span style="font-family: var(--f-mono); font-weight: 600;"><?= $b['product_count'] ?> units</span></td>
                <td><span class="status-badge <?= $b['is_active'] ? 'status-paid' : 'status-cancelled' ?>"><?= $b['is_active'] ? 'Active' : 'Hidden' ?></span></td>
                <td><?= $b['sort_order'] ?></td>
                <td>
                    <div style="display: flex; gap: 16px;">
                        <button onclick='editBrand(<?= json_encode($b) ?>)' style="background: none; border: none; font-size: 10px; color: var(--ink); font-weight: 700; text-transform: uppercase; cursor: pointer; padding: 0;">Edit</button>
                        <form method="POST" onsubmit="return confirm('Really delete this brand?')" style="display: inline;">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= $b['id'] ?>">
                            <button type="submit" style="background: none; border: none; font-size: 10px; color: var(--red); font-weight: 700; text-transform: uppercase; cursor: pointer; padding: 0;">Delete</button>
                        </form>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Add/Edit Modal -->
<div id="add-modal" style="display:none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
    <div style="background: #fff; width: 100%; max-width: 500px; padding: 40px; border-radius: 8px; border: 1px solid var(--ink); box-shadow: 0 20px 40px rgba(0,0,0,0.2);">
        <h2 id="modal-title" style="font-family: var(--f-display); font-weight: 900; font-size: 28px; text-transform: uppercase; margin-bottom: 32px; letter-spacing: -0.02em;">New Brand</h2>
        <form method="POST" style="display: flex; flex-direction: column; gap: 24px;">
            <input type="hidden" name="action" id="form-action" value="create">
            <input type="hidden" name="id" id="form-id" value="">
            
            <div>
                <label style="display: block; font-family: var(--f-semi); font-size: 10px; font-weight: 700; text-transform: uppercase; color: var(--mid-gray); margin-bottom: 8px;">Name</label>
                <input type="text" name="name" id="form-name" required style="width: 100%; height: 48px; border: 1px solid var(--light-gray); padding: 0 16px; border-radius: 4px; font-family: var(--f-body);">
            </div>

            <div>
                <label style="display: block; font-family: var(--f-semi); font-size: 10px; font-weight: 700; text-transform: uppercase; color: var(--mid-gray); margin-bottom: 8px;">Slug (Optional)</label>
                <input type="text" name="slug" id="form-slug" placeholder="auto-generated" style="width: 100%; height: 48px; border: 1px solid var(--light-gray); padding: 0 16px; border-radius: 4px; font-family: var(--f-mono); font-size: 12px;">
            </div>

            <div>
                <label style="display: block; font-family: var(--f-semi); font-size: 10px; font-weight: 700; text-transform: uppercase; color: var(--mid-gray); margin-bottom: 8px;">Logo URL (Optional)</label>
                <input type="text" name="logo_url" id="form-logo" placeholder="https://..." style="width: 100%; height: 48px; border: 1px solid var(--light-gray); padding: 0 16px; border-radius: 4px; font-family: var(--f-mono); font-size: 12px;">
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
                <div>
                    <label style="display: block; font-family: var(--f-semi); font-size: 10px; font-weight: 700; text-transform: uppercase; color: var(--mid-gray); margin-bottom: 8px;">Sort Order</label>
                    <input type="number" name="sort_order" id="form-order" value="0" style="width: 100%; height: 48px; border: 1px solid var(--light-gray); padding: 0 16px; border-radius: 4px;">
                </div>
                <div style="display: flex; align-items: center; gap: 12px; padding-top: 28px;">
                    <input type="checkbox" name="is_active" id="form-active" checked style="width: 18px; height: 18px; accent-color: var(--red);">
                    <label for="form-active" style="font-family: var(--f-semi); font-size: 12px; font-weight: 600; cursor: pointer;">Active</label>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-top: 16px;">
                <button type="button" onclick="toggleModal('add-modal')" style="height: 52px; background: #f5f5f5; border: 1px solid #ddd; border-radius: 8px; font-family: var(--f-semi); font-weight: 700; cursor: pointer; text-transform: uppercase; font-size: 11px;">Cancel</button>
                <button type="submit" class="btn-red" style="height: 52px; border: none; cursor: pointer; text-transform: uppercase; font-size: 11px;">Save Brand</button>
            </div>
        </form>
    </div>
</div>

<script>
function toggleModal(id) {
    const m = document.getElementById(id);
    m.style.display = m.style.display === 'none' ? 'flex' : 'none';
    if (m.style.display === 'flex') {
        document.getElementById('modal-title').innerText = 'New Brand';
        document.getElementById('form-action').value = 'create';
        document.getElementById('form-id').value = '';
        document.getElementById('form-name').value = '';
        document.getElementById('form-slug').value = '';
        document.getElementById('form-logo').value = '';
        document.getElementById('form-order').value = '0';
        document.getElementById('form-active').checked = true;
    }
}

function editBrand(b) {
    toggleModal('add-modal');
    document.getElementById('modal-title').innerText = 'Edit Brand';
    document.getElementById('form-action').value = 'update';
    document.getElementById('form-id').value = b.id;
    document.getElementById('form-name').value = b.name;
    document.getElementById('form-slug').value = b.slug;
    document.getElementById('form-logo').value = b.logo_url || '';
    document.getElementById('form-order').value = b.sort_order;
    document.getElementById('form-active').checked = b.is_active == 1;
}
</script>

<?php include 'layout/footer.php'; ?>
