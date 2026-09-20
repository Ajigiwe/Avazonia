<?php
// admin/regions.php — Manage delivery/selling regions (Ghana)
require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../core/Session.php';
require_once __DIR__ . '/../models/Region.php';

Session::start();
if (Session::get('user_role') !== 'admin') {
    header('Location: ' . APP_URL . '/login');
    exit;
}

// CSRF Check for POST requests
require_once __DIR__ . '/_csrf_check.php';

$regionModel = new Region();
$error = '';
$success = '';

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create' || $action === 'update') {
        $data = [
            'name' => trim($_POST['name'] ?? ''),
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
            'sort_order' => (int)($_POST['sort_order'] ?? 0)
        ];

        if ($data['name'] === '') {
            $error = "Region name is required.";
        } elseif ($action === 'create') {
            if ($regionModel->create($data)) {
                $success = "Region created successfully.";
            } else {
                $error = "Failed to create region (name may already exist).";
            }
        } else {
            $id = (int)$_POST['id'];
            if ($regionModel->update($id, $data)) {
                $success = "Region updated successfully. Sellers assigned to the old name should update their region.";
            } else {
                $error = "Failed to update region.";
            }
        }
    } elseif ($action === 'delete') {
        $id = (int)$_POST['id'];
        $region = $regionModel->findById($id);
        if ($region && $regionModel->delete($id)) {
            $success = "Region deleted.";
        } else {
            $error = "Failed to delete region.";
        }
    }
}

$regions = [];
foreach ($regionModel->getAll() as $r) {
    $r['seller_count'] = $regionModel->countSellers($r['name']);
    $r['product_count'] = $regionModel->productCount($r['name']);
    $regions[] = $r;
}

$title = "Manage Regions";
include 'layout/header.php';
?>

<div class="admin-header">
    <h1>Regions</h1>
    <button onclick="toggleModal('add-modal')" class="btn-red" style="height: 44px; padding: 0 24px; font-size: 10px; display: flex; align-items: center; justify-content: center; border: none; cursor: pointer;">+ New Region</button>
</div>

<?php if ($success): ?>
    <div style="background: #f6ffed; border: 1px solid #b7eb8f; color: #52c41a; padding: 12px 20px; border-radius: 8px; margin-bottom: 24px; font-family: var(--f-semi); font-size: 13px;">
        <?= htmlspecialchars($success) ?>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div style="background: #fff1f0; border: 1px solid #ffa39e; color: #f5222d; padding: 12px 20px; border-radius: 8px; margin-bottom: 24px; font-family: var(--f-semi); font-size: 13px;">
        <?= htmlspecialchars($error) ?>
    </div>
<?php endif; ?>

<div class="panel">
    <div class="panel-header">
        <div class="panel-title">Selling / Delivery Regions</div>
        <div style="font-family: var(--f-mono); font-size: 10px; opacity: 0.6;">Vendors select their region in Seller Settings; buyers filter products by region on the Shop page.</div>
    </div>
    <table class="admin-table">
        <thead>
            <tr>
                <th>Region</th>
                <th>Slug</th>
                <th>Vendors</th>
                <th>Live Products</th>
                <th>Status</th>
                <th>Order</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($regions as $r): ?>
            <tr>
                <td style="font-weight: 700;"><?= htmlspecialchars($r['name']) ?></td>
                <td style="font-family: var(--f-mono); font-size: 11px; opacity: 0.6;"><?= htmlspecialchars($r['slug']) ?></td>
                <td><span style="font-family: var(--f-mono); font-weight: 600;"><?= (int)$r['seller_count'] ?></span></td>
                <td><span style="font-family: var(--f-mono); font-weight: 600;"><?= (int)$r['product_count'] ?></span></td>
                <td><span class="status-badge <?= $r['is_active'] ? 'status-paid' : 'status-cancelled' ?>"><?= $r['is_active'] ? 'Active' : 'Hidden' ?></span></td>
                <td><?= (int)$r['sort_order'] ?></td>
                <td>
                    <div style="display: flex; gap: 16px;">
                        <button onclick='editRegion(<?= json_encode($r, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)' style="background: none; border: none; font-size: 10px; color: var(--ink); font-weight: 700; text-transform: uppercase; cursor: pointer; padding: 0;">Edit</button>
                        <form method="POST" onsubmit="return confirm('Really delete this region? Sellers assigned to it will keep the name but it will no longer be selectable.')" style="display: inline;">
                            <?= Csrf::field() ?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                            <button type="submit" style="background: none; border: none; font-size: 10px; color: var(--red); font-weight: 700; text-transform: uppercase; cursor: pointer; padding: 0;">Delete</button>
                        </form>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($regions)): ?>
            <tr><td colspan="7" style="text-align:center; padding: 32px; opacity: 0.6;">No regions yet — add Ghana's regions to get started.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Add/Edit Modal -->
<div id="add-modal" style="display:none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
    <div style="background: #fff; width: 100%; max-width: 500px; padding: 40px; border-radius: 8px; border: 1px solid var(--ink); box-shadow: 0 20px 40px rgba(0,0,0,0.2);">
        <h2 id="modal-title" style="font-family: var(--f-display); font-weight: 900; font-size: 28px; text-transform: uppercase; margin-bottom: 32px; letter-spacing: -0.02em;">New Region</h2>
        <form method="POST" style="display: flex; flex-direction: column; gap: 24px;">
            <?= Csrf::field() ?>
            <input type="hidden" name="action" id="form-action" value="create">
            <input type="hidden" name="id" id="form-id" value="">

            <div>
                <label style="display: block; font-family: var(--f-semi); font-size: 10px; font-weight: 700; text-transform: uppercase; color: var(--mid-gray); margin-bottom: 8px;">Region Name</label>
                <input type="text" name="name" id="form-name" required placeholder="e.g. Greater Accra" style="width: 100%; height: 48px; border: 1px solid var(--light-gray); padding: 0 16px; border-radius: 4px; font-family: var(--f-body);">
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
                <div>
                    <label style="display: block; font-family: var(--f-semi); font-size: 10px; font-weight: 700; text-transform: uppercase; color: var(--mid-gray); margin-bottom: 8px;">Sort Order</label>
                    <input type="number" name="sort_order" id="form-order" value="0" style="width: 100%; height: 48px; border: 1px solid var(--light-gray); padding: 0 16px; border-radius: 4px;">
                </div>
                <div style="display: flex; align-items: center; gap: 12px; padding-top: 28px;">
                    <input type="checkbox" name="is_active" id="form-active" checked style="width: 18px; height: 18px; accent-color: var(--red);">
                    <label for="form-active" style="font-family: var(--f-semi); font-size: 12px; font-weight: 600; cursor: pointer;">Active (selectable by vendors)</label>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-top: 16px;">
                <button type="button" onclick="toggleModal('add-modal')" style="height: 52px; background: #f5f5f5; border: 1px solid #ddd; border-radius: 8px; font-family: var(--f-semi); font-weight: 700; cursor: pointer; text-transform: uppercase; font-size: 11px;">Cancel</button>
                <button type="submit" class="btn-red" style="height: 52px; border: none; cursor: pointer; text-transform: uppercase; font-size: 11px;">Save Region</button>
            </div>
        </form>
    </div>
</div>

<script>
function toggleModal(id) {
    const m = document.getElementById(id);
    m.style.display = m.style.display === 'none' ? 'flex' : 'none';
    if (m.style.display === 'flex') {
        document.getElementById('modal-title').innerText = 'New Region';
        document.getElementById('form-action').value = 'create';
        document.getElementById('form-id').value = '';
        document.getElementById('form-name').value = '';
        document.getElementById('form-order').value = '0';
        document.getElementById('form-active').checked = true;
    }
}

function editRegion(r) {
    toggleModal('add-modal');
    document.getElementById('modal-title').innerText = 'Edit Region';
    document.getElementById('form-action').value = 'update';
    document.getElementById('form-id').value = r.id;
    document.getElementById('form-name').value = r.name;
    document.getElementById('form-order').value = r.sort_order;
    document.getElementById('form-active').checked = r.is_active == 1;
}
</script>

<?php include 'layout/footer.php'; ?>
