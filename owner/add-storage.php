<?php
$pageTitle = "Add New Cold Storage Facility";
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

requireOwner();
$user = currentUser();

$error = '';
$name              = '';
$location          = $user['location'] ?? 'Ahmedabad';
$totalCapacity     = 2000;
$availableCapacity = 2000;
$temperature       = '2°C - 8°C';
$pricePerKg        = 2.00;
$supportedCrops    = 'Tomato, Potato, Onion, Apple';
$minimumQuantity   = 100;
$contact           = $user['phone'] ?? '+91 98250 11223';
$status            = 'Available';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name              = trim($_POST['name'] ?? '');
    $location          = trim($_POST['location'] ?? '');
    $totalCapacity     = (float)($_POST['total_capacity'] ?? 0);
    $availableCapacity = (float)($_POST['available_capacity'] ?? 0);
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
        try {
            $insertStmt = $pdo->prepare("
                INSERT INTO cold_storages 
                (owner_id, name, location, available_capacity, total_capacity, temperature, price_per_kg, supported_crops, minimum_quantity, contact, status, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $insertStmt->execute([
                $user['id'],
                $name,
                $location,
                $availableCapacity,
                $totalCapacity,
                $temperature,
                $pricePerKg,
                $supportedCrops,
                $minimumQuantity,
                $contact,
                $status
            ]);

            setFlash('success', "Facility '{$name}' registered successfully and is now discoverable by farmers!");
            header('Location: ' . base_url('owner/dashboard.php'));
            exit;
        } catch (PDOException $e) {
            $error = 'Failed to register facility: ' . $e->getMessage();
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container" style="padding: 2.5rem 1.25rem; max-width: 800px;">
    <div style="margin-bottom: 1.5rem; font-size: 0.9rem; color: var(--text-muted);">
        <a href="<?php echo base_url('owner/dashboard.php'); ?>">&larr; Back to Owner Portal</a>
    </div>

    <div class="card" style="padding: 2.5rem 2rem; border-top: 4px solid var(--primary);">
        <div style="margin-bottom: 2rem;">
            <h2>Register New Cold Storage Facility</h2>
            <p class="text-muted">Add your refrigerated warehouse chambers to the Agri Storage network for farmers to find and book.</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger">
                <span><i class="fa-solid fa-triangle-exclamation"></i></span>
                <span><?php echo e($error); ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?php echo base_url('owner/add-storage.php'); ?>">
            <div class="grid-2" style="margin-bottom: 1.25rem;">
                <div class="form-group">
                    <label for="st_name">Facility Name *</label>
                    <input type="text" name="name" id="st_name" class="form-control" value="<?php echo e($name); ?>" placeholder="e.g. Navrang Cold Hub" required>
                </div>

                <div class="form-group">
                    <label for="st_location">Facility Location / Area *</label>
                    <input type="text" name="location" id="st_location" class="form-control" value="<?php echo e($location); ?>" placeholder="e.g. Ahmedabad, Bopal, Sanand" required>
                </div>
            </div>

            <div class="grid-2" style="margin-bottom: 1.25rem;">
                <div class="form-group">
                    <label for="st_total">Total Facility Capacity (kg) *</label>
                    <input type="number" step="0.1" name="total_capacity" id="st_total" class="form-control" value="<?php echo e($totalCapacity); ?>" placeholder="e.g. 2500" required>
                </div>

                <div class="form-group">
                    <label for="st_avail">Currently Available Free Capacity (kg) *</label>
                    <input type="number" step="0.1" name="available_capacity" id="st_avail" class="form-control" value="<?php echo e($availableCapacity); ?>" placeholder="e.g. 2500" required>
                </div>
            </div>

            <div class="grid-3" style="margin-bottom: 1.25rem;">
                <div class="form-group">
                    <label for="st_temp">Operating Temperature Range *</label>
                    <input type="text" name="temperature" id="st_temp" class="form-control" value="<?php echo e($temperature); ?>" placeholder="e.g. 2°C - 8°C" required>
                </div>

                <div class="form-group">
                    <label for="st_price">Rental Price (₹ per kg / day) *</label>
                    <input type="number" step="0.05" name="price_per_kg" id="st_price" class="form-control" value="<?php echo e($pricePerKg); ?>" placeholder="e.g. 2.00" required>
                </div>

                <div class="form-group">
                    <label for="st_min">Min. Reservation (kg) *</label>
                    <input type="number" step="1" name="minimum_quantity" id="st_min" class="form-control" value="<?php echo e($minimumQuantity); ?>" placeholder="e.g. 100" required>
                </div>
            </div>

            <div class="form-group" style="margin-bottom: 1.25rem;">
                <label for="st_crops">Supported Produce Varieties (comma-separated) *</label>
                <input type="text" name="supported_crops" id="st_crops" class="form-control" value="<?php echo e($supportedCrops); ?>" placeholder="e.g. Tomato, Potato, Onion, Mango, Apple" required>
                <span class="text-muted" style="font-size: 0.8rem;">Enter produce names separated by commas. Smart Match uses this to recommend your facility to relevant farmers.</span>
            </div>

            <div class="grid-2" style="margin-bottom: 1.75rem;">
                <div class="form-group">
                    <label for="st_contact">Contact Phone Number *</label>
                    <input type="text" name="contact" id="st_contact" class="form-control" value="<?php echo e($contact); ?>" placeholder="+91 98250 11223" required>
                </div>

                <div class="form-group">
                    <label for="st_status">Initial Facility Status *</label>
                    <select name="status" id="st_status" class="form-control" required>
                        <option value="Available" <?php echo ($status === 'Available') ? 'selected' : ''; ?>>Available (Accepting Bookings)</option>
                        <option value="Full" <?php echo ($status === 'Full') ? 'selected' : ''; ?>>Full (At Capacity)</option>
                        <option value="Inactive" <?php echo ($status === 'Inactive') ? 'selected' : ''; ?>>Inactive (Setup / Maintenance)</option>
                    </select>
                </div>
            </div>

            <div style="display: flex; justify-content: space-between; align-items: center; gap: 1rem; flex-wrap: wrap;">
                <a href="<?php echo base_url('owner/dashboard.php'); ?>" class="btn btn-outline">
                    Cancel
                </a>
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="fa-solid fa-plus"></i> Register Facility
                </button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
