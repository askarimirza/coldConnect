<?php
$pageTitle = "Update Storage Facility";
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

requireOwner();
$user = currentUser();

$storageId = isset($_REQUEST['id']) ? (int)$_REQUEST['id'] : 0;

if ($storageId <= 0) {
    setFlash('danger', 'Invalid storage facility selected.');
    header('Location: ' . base_url('owner/dashboard.php'));
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT * FROM cold_storages WHERE id = ? AND owner_id = ? LIMIT 1");
    $stmt->execute([$storageId, $user['id']]);
    $storage = $stmt->fetch();

    if (!$storage) {
        setFlash('danger', 'Facility not found or you do not have permission to edit it.');
        header('Location: ' . base_url('owner/dashboard.php'));
        exit;
    }
} catch (PDOException $e) {
    setFlash('danger', 'Database error: ' . $e->getMessage());
    header('Location: ' . base_url('owner/dashboard.php'));
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name              = trim($_POST['name'] ?? '');
    $location          = trim($_POST['location'] ?? '');
    $availableCapacity = (float)($_POST['available_capacity'] ?? 0);
    $totalCapacity     = (float)($_POST['total_capacity'] ?? 0);
    $temperature       = trim($_POST['temperature'] ?? '');
    $pricePerKg        = (float)($_POST['price_per_kg'] ?? 0);
    $supportedCrops    = trim($_POST['supported_crops'] ?? '');
    $minimumQuantity   = (float)($_POST['minimum_quantity'] ?? 0);
    $contact           = trim($_POST['contact'] ?? '');
    $status            = in_array($_POST['status'] ?? '', ['Available', 'Full', 'Inactive']) ? $_POST['status'] : 'Available';

    if (empty($name) || empty($location) || empty($temperature) || empty($supportedCrops) || empty($contact)) {
        $error = 'Please fill out all required fields.';
    } elseif ($totalCapacity <= 0) {
        $error = 'Total capacity must be greater than 0 kg.';
    } elseif ($availableCapacity > $totalCapacity) {
        $error = 'Available capacity cannot exceed total warehouse capacity.';
    } elseif ($pricePerKg <= 0) {
        $error = 'Price per kg/day must be greater than 0.';
    } else {
        // Automatically set status to Full if available capacity is 0 and status is Available
        if ($availableCapacity <= 0 && $status === 'Available') {
            $status = 'Full';
        }

        try {
            $updateStmt = $pdo->prepare("
                UPDATE cold_storages 
                SET name = ?, location = ?, available_capacity = ?, total_capacity = ?, 
                    temperature = ?, price_per_kg = ?, supported_crops = ?, 
                    minimum_quantity = ?, contact = ?, status = ?
                WHERE id = ? AND owner_id = ?
            ");
            $updateStmt->execute([
                $name,
                $location,
                $availableCapacity,
                $totalCapacity,
                $temperature,
                $pricePerKg,
                $supportedCrops,
                $minimumQuantity,
                $contact,
                $status,
                $storageId,
                $user['id']
            ]);

            setFlash('success', "Facility '{$name}' updated successfully.");
            header('Location: ' . base_url('owner/dashboard.php'));
            exit;
        } catch (PDOException $e) {
            $error = 'Failed to update facility: ' . $e->getMessage();
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container" style="padding: 2.5rem 1.25rem; max-width: 800px;">
    <!-- Breadcrumb -->
    <div style="margin-bottom: 1.5rem; font-size: 0.9rem; color: var(--text-muted);">
        <a href="<?php echo base_url('owner/dashboard.php'); ?>">&larr; Back to Owner Portal</a>
    </div>

    <div class="card" style="padding: 2.5rem 2rem; border-top: 4px solid var(--secondary);">
        <div style="margin-bottom: 2rem;">
            <h2>Manage Storage Facility: <?php echo e($storage['name']); ?></h2>
            <p class="text-muted">Update chamber temperature settings, capacity levels, and crop compatibility.</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger">
                <span><i class="fa-solid fa-triangle-exclamation"></i></span>
                <span><?php echo e($error); ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?php echo base_url('owner/update-storage.php?id=' . $storage['id']); ?>">
            <div class="grid-2" style="margin-bottom: 1.25rem;">
                <div class="form-group">
                    <label for="st_name">Facility Name *</label>
                    <input type="text" name="name" id="st_name" class="form-control" value="<?php echo e($storage['name']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="st_location">Facility Location / Area *</label>
                    <input type="text" name="location" id="st_location" class="form-control" value="<?php echo e($storage['location']); ?>" required>
                </div>
            </div>

            <div class="grid-2" style="margin-bottom: 1.25rem;">
                <div class="form-group">
                    <label for="st_avail">Current Available Capacity (kg) *</label>
                    <input type="number" step="0.1" name="available_capacity" id="st_avail" class="form-control" value="<?php echo e($storage['available_capacity']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="st_total">Total Facility Capacity (kg) *</label>
                    <input type="number" step="0.1" name="total_capacity" id="st_total" class="form-control" value="<?php echo e($storage['total_capacity']); ?>" required>
                </div>
            </div>

            <div class="grid-3" style="margin-bottom: 1.25rem;">
                <div class="form-group">
                    <label for="st_temp">Operating Temperature Range *</label>
                    <input type="text" name="temperature" id="st_temp" class="form-control" value="<?php echo e($storage['temperature']); ?>" placeholder="e.g. 2°C - 8°C" required>
                </div>

                <div class="form-group">
                    <label for="st_price">Price (₹ per kg / day) *</label>
                    <input type="number" step="0.05" name="price_per_kg" id="st_price" class="form-control" value="<?php echo e($storage['price_per_kg']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="st_min">Min. Quantity (kg) *</label>
                    <input type="number" step="1" name="minimum_quantity" id="st_min" class="form-control" value="<?php echo e($storage['minimum_quantity']); ?>" required>
                </div>
            </div>

            <div class="form-group" style="margin-bottom: 1.25rem;">
                <label for="st_crops">Supported Produce Varieties (comma-separated) *</label>
                <input type="text" name="supported_crops" id="st_crops" class="form-control" value="<?php echo e($storage['supported_crops']); ?>" placeholder="e.g. Tomato, Potato, Onion, Apple" required>
                <span class="text-muted" style="font-size: 0.8rem;">Enter crops separated by commas. Smart Match uses this to verify compatibility.</span>
            </div>

            <div class="grid-2" style="margin-bottom: 1.75rem;">
                <div class="form-group">
                    <label for="st_contact">Contact Phone Number *</label>
                    <input type="text" name="contact" id="st_contact" class="form-control" value="<?php echo e($storage['contact']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="st_status">Facility Status *</label>
                    <select name="status" id="st_status" class="form-control" required>
                        <option value="Available" <?php echo ($storage['status'] === 'Available') ? 'selected' : ''; ?>>Available (Accepting Bookings)</option>
                        <option value="Full" <?php echo ($storage['status'] === 'Full') ? 'selected' : ''; ?>>Full (At Maximum Capacity)</option>
                        <option value="Inactive" <?php echo ($storage['status'] === 'Inactive') ? 'selected' : ''; ?>>Inactive / Maintenance</option>
                    </select>
                </div>
            </div>

            <div style="display: flex; justify-content: space-between; align-items: center; gap: 1rem; flex-wrap: wrap;">
                <a href="<?php echo base_url('owner/dashboard.php'); ?>" class="btn btn-outline">
                    Cancel
                </a>
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="fa-solid fa-floppy-disk"></i> Save Facility Changes
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
