<?php
$pageTitle = "Storage Owner Dashboard";
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

requireOwner();
$user = currentUser();

// Metrics calculation
$totalStorages = 0;
$totalAvailableCapacity = 0;
$totalCapacity = 0;
$pendingRequests = 0;
$acceptedBookings = 0;

$storages = [];
$pendingBookings = [];

try {
    // Fetch owner's facilities
    $stmtStorages = $pdo->prepare("
        SELECT * FROM cold_storages 
        WHERE owner_id = ? 
        ORDER BY id ASC
    ");
    $stmtStorages->execute([$user['id']]);
    $storages = $stmtStorages->fetchAll();

    $totalStorages = count($storages);
    foreach ($storages as $st) {
        $totalAvailableCapacity += (float)$st['available_capacity'];
        $totalCapacity += (float)$st['total_capacity'];
    }

    // Fetch booking metrics for owner's storages
    $stmtBookingsCount = $pdo->prepare("
        SELECT 
            SUM(CASE WHEN b.status = 'Pending' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN b.status = 'Accepted' THEN 1 ELSE 0 END) as accepted
        FROM bookings b
        JOIN cold_storages s ON b.storage_id = s.id
        WHERE s.owner_id = ?
    ");
    $stmtBookingsCount->execute([$user['id']]);
    $bCount = $stmtBookingsCount->fetch();
    if ($bCount) {
        $pendingRequests = (int)($bCount['pending'] ?? 0);
        $acceptedBookings = (int)($bCount['accepted'] ?? 0);
    }

    // Pending bookings list
    $stmtPending = $pdo->prepare("
        SELECT b.*, u.name as farmer_name, u.phone as farmer_phone, s.name as storage_name, s.available_capacity
        FROM bookings b
        JOIN users u ON b.farmer_id = u.id
        JOIN cold_storages s ON b.storage_id = s.id
        WHERE s.owner_id = ? AND b.status = 'Pending'
        ORDER BY b.id DESC
        LIMIT 5
    ");
    $stmtPending->execute([$user['id']]);
    $pendingBookings = $stmtPending->fetchAll();

} catch (PDOException $e) {
    setFlash('danger', 'Database error: ' . $e->getMessage());
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container" style="padding: 2.5rem 1.25rem;">
    <!-- Welcome Header -->
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 2rem;">
        <div>
            <h1>Owner Management Portal</h1>
            <p class="text-muted">Logged in as: <strong><?php echo e($user['name']); ?></strong> (Facility Manager)</p>
        </div>
        <div style="display: flex; gap: 0.75rem;">
            <a href="<?php echo base_url('owner/add-storage.php'); ?>" class="btn btn-primary">
                + Add New Facility
            </a>
            <a href="<?php echo base_url('owner/bookings.php'); ?>" class="btn btn-outline-primary">
                View All Requests (<?php echo $pendingRequests; ?>)
            </a>
        </div>
    </div>

    <!-- Metric Stat Cards -->
    <div class="grid-4" style="margin-bottom: 2.5rem;">
        <div class="stat-card">
            <div class="stat-icon blue"><i class="fa-solid fa-warehouse"></i></div>
            <div>
                <div class="stat-value"><?php echo $totalStorages; ?></div>
                <div class="stat-title">Managed Storages</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon green"><i class="fa-solid fa-boxes-stacked"></i></div>
            <div>
                <div class="stat-value"><?php echo number_format($totalAvailableCapacity); ?> <span style="font-size: 0.9rem; font-weight: 500;">kg</span></div>
                <div class="stat-title">Available Capacity</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon amber"><i class="fa-solid fa-clock"></i></div>
            <div>
                <div class="stat-value"><?php echo $pendingRequests; ?></div>
                <div class="stat-title">Pending Requests</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon green"><i class="fa-solid fa-circle-check"></i></div>
            <div>
                <div class="stat-value"><?php echo $acceptedBookings; ?></div>
                <div class="stat-title">Confirmed Bookings</div>
            </div>
        </div>
    </div>

    <!-- Incoming Pending Booking Requests Card -->
    <div class="card" style="margin-bottom: 2.5rem; border-top: 4px solid var(--accent-gold);">
        <div class="card-header">
            <div>
                <h2 class="card-title"><i class="fa-solid fa-clock"></i> Incoming Farmer Booking Requests</h2>
                <p class="text-muted" style="margin: 0; font-size: 0.9rem;">
                    Accepting a booking will automatically decrement available warehouse capacity.
                </p>
            </div>
            <a href="<?php echo base_url('owner/bookings.php'); ?>" class="btn btn-outline btn-sm">
                Full Bookings History &rarr;
            </a>
        </div>

        <?php if (empty($pendingBookings)): ?>
            <div class="text-center" style="padding: 2.5rem 1rem;">
                <div style="font-size: 2.5rem; margin-bottom: 0.5rem; color: var(--primary);"><i class="fa-solid fa-circle-check"></i></div>
                <h4>No pending booking requests</h4>
                <p class="text-muted">All incoming reservations have been processed.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Farmer & Contact</th>
                            <th>Facility</th>
                            <th>Crop</th>
                            <th>Quantity</th>
                            <th>Schedule</th>
                            <th>Estimated Value</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pendingBookings as $pb): ?>
                            <?php 
                                $diffDays = max(1, (int)ceil((strtotime($pb['end_date']) - strtotime($pb['start_date'])) / 86400));
                                $canAccept = ($pb['available_capacity'] >= $pb['quantity']);
                            ?>
                            <tr>
                                <td><strong>#<?php echo e($pb['id']); ?></strong></td>
                                <td>
                                    <strong><?php echo e($pb['farmer_name']); ?></strong>
                                    <div class="text-muted" style="font-size: 0.8rem;">
                                        <i class="fa-solid fa-phone"></i> <?php echo e($pb['farmer_phone']); ?>
                                    </div>
                                </td>
                                <td><strong><?php echo e($pb['storage_name']); ?></strong></td>
                                <td><span class="crop-tag highlight"><?php echo e($pb['crop']); ?></span></td>
                                <td>
                                    <strong><?php echo number_format($pb['quantity']); ?> kg</strong>
                                    <?php if (!$canAccept): ?>
                                        <div style="color: var(--status-rejected); font-size: 0.75rem; font-weight: 600;">
                                            <i class="fa-solid fa-triangle-exclamation"></i> Insufficient space (<?php echo number_format($pb['available_capacity']); ?>kg free)
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php echo date('d M Y', strtotime($pb['start_date'])); ?> &rarr; 
                                    <?php echo date('d M Y', strtotime($pb['end_date'])); ?>
                                    <div class="text-muted" style="font-size: 0.8rem;"><?php echo $diffDays; ?> days</div>
                                </td>
                                <td><strong style="color: var(--primary-dark);">₹<?php echo number_format($pb['total_cost'], 2); ?></strong></td>
                                <td>
                                    <div style="display: flex; gap: 0.4rem;">
                                        <form method="POST" action="<?php echo base_url('owner/update-booking.php'); ?>" style="display: inline;">
                                            <input type="hidden" name="booking_id" value="<?php echo $pb['id']; ?>">
                                            <input type="hidden" name="action" value="accept">
                                            <button type="submit" class="btn btn-success btn-sm" <?php echo !$canAccept ? 'disabled title="Insufficient capacity"' : ''; ?>>
                                                Accept
                                            </button>
                                        </form>

                                        <button type="button" class="btn btn-danger btn-sm btn-reject-modal"
                                                data-id="<?php echo $pb['id']; ?>"
                                                data-farmer="<?php echo e($pb['farmer_name']); ?>"
                                                data-crop="<?php echo e($pb['crop']); ?>"
                                                data-qty="<?php echo number_format($pb['quantity']); ?> kg"
                                                data-facility="<?php echo e($pb['storage_name']); ?>">
                                            Reject
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <!-- Managed Facilities Section -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title"><i class="fa-solid fa-warehouse"></i> My Cold Storage Facilities</h2>
            <a href="<?php echo base_url('owner/add-storage.php'); ?>" class="btn btn-outline-primary btn-sm">
                + Register New Facility
            </a>
        </div>

        <?php if (empty($storages)): ?>
            <div class="text-center" style="padding: 3rem 1.5rem;">
                <div style="font-size: 3rem; margin-bottom: 0.75rem; color: #94a3b8;"><i class="fa-solid fa-warehouse"></i></div>
                <h3>No Cold Storage Facilities Registered Yet</h3>
                <p class="text-muted" style="max-width: 450px; margin: 0 auto 1.5rem;">
                    Register your cold storage warehouse so local farmers can discover and book your available refrigerated chambers.
                </p>
                <a href="<?php echo base_url('owner/add-storage.php'); ?>" class="btn btn-primary">
                    + Register Your First Facility
                </a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Facility Name</th>
                            <th>Location</th>
                            <th>Available / Total Capacity</th>
                            <th>Temperature</th>
                            <th>Rate (₹/kg/day)</th>
                            <th>Status</th>
                            <th>Manage</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($storages as $st): ?>
                            <?php 
                                $pct = round(($st['available_capacity'] / $st['total_capacity']) * 100);
                            ?>
                            <tr>
                                <td><strong style="font-size: 1.05rem;"><?php echo e($st['name']); ?></strong></td>
                                <td><i class="fa-solid fa-location-dot"></i> <?php echo e($st['location']); ?></td>
                                <td>
                                    <strong><?php echo number_format($st['available_capacity']); ?> kg</strong> / <?php echo number_format($st['total_capacity']); ?> kg
                                    <div style="height: 6px; background: #e2e8f0; border-radius: 9999px; margin-top: 4px; width: 140px;">
                                        <div style="width: <?php echo $pct; ?>%; height: 100%; background: var(--primary); border-radius: 9999px;"></div>
                                    </div>
                                </td>
                                <td><i class="fa-solid fa-temperature-half"></i> <?php echo e($st['temperature']); ?></td>
                                <td><strong style="color: var(--primary-dark);">₹<?php echo number_format($st['price_per_kg'], 2); ?></strong></td>
                                <td>
                                    <span class="badge badge-<?php echo ($st['status'] === 'Available') ? 'accepted' : 'rejected'; ?>">
                                        <?php echo e($st['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="<?php echo base_url('owner/update-storage.php?id=' . $st['id']); ?>" class="btn btn-outline-primary btn-sm">
                                        <i class="fa-solid fa-gear"></i> Update Facility
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/modal-reject.php'; ?>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
