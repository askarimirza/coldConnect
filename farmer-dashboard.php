<?php
$pageTitle = "Farmer Dashboard";
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

requireFarmer();
$user = currentUser();

// Fetch summary metrics for this farmer
$totalBookings = 0;
$activeBookings = 0;
$pendingRequests = 0;
$recentBookings = [];

try {
    // Counts
    $stmtCount = $pdo->prepare("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN status = 'Accepted' THEN 1 ELSE 0 END) as active,
            SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending
        FROM bookings 
        WHERE farmer_id = ?
    ");
    $stmtCount->execute([$user['id']]);
    $counts = $stmtCount->fetch();
    if ($counts) {
        $totalBookings = (int)($counts['total'] ?? 0);
        $activeBookings = (int)($counts['active'] ?? 0);
        $pendingRequests = (int)($counts['pending'] ?? 0);
    }

    // Recent 5 Bookings with storage names
    $stmtRecent = $pdo->prepare("
        SELECT b.*, s.name as storage_name, s.location as storage_location, s.price_per_kg
        FROM bookings b
        JOIN cold_storages s ON b.storage_id = s.id
        WHERE b.farmer_id = ?
        ORDER BY b.id DESC
        LIMIT 5
    ");
    $stmtRecent->execute([$user['id']]);
    $recentBookings = $stmtRecent->fetchAll();

    // Fetch max available capacity across all facilities
    $maxAvailableAll = (float)$pdo->query("SELECT MAX(available_capacity) FROM cold_storages WHERE status = 'Available'")->fetchColumn() ?: 10000;
} catch (PDOException $e) {
    // Fail gracefully
    $maxAvailableAll = 10000;
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="padding: 2.5rem 1.25rem;">
    <!-- Welcome Header -->
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 2rem;">
        <div>
            <h1>Welcome, <?php echo e($user['name']); ?></h1>
            <p class="text-muted">Farmer Portal &bull; Base Location: <strong><?php echo e($user['location']); ?></strong></p>
        </div>
        <div>
            <a href="<?php echo base_url('search.php'); ?>" class="btn btn-primary btn-lg">
                <i class="fa-solid fa-magnifying-glass"></i> Find Cold Storage
            </a>
        </div>
    </div>

    <!-- Metric Stat Cards -->
    <div class="grid-4" style="margin-bottom: 2.5rem;">
        <div class="stat-card">
            <div class="stat-icon green"><i class="fa-solid fa-boxes-stacked"></i></div>
            <div>
                <div class="stat-value"><?php echo $totalBookings; ?></div>
                <div class="stat-title">Total Bookings</div>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon green"><i class="fa-solid fa-circle-check"></i></div>
            <div>
                <div class="stat-value"><?php echo $activeBookings; ?></div>
                <div class="stat-title">Active / Accepted</div>
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
            <div class="stat-icon blue"><i class="fa-solid fa-location-dot"></i></div>
            <div>
                <div class="stat-value" style="font-size: 1.2rem;"><?php echo e($user['location']); ?></div>
                <div class="stat-title">Registered Region</div>
            </div>
        </div>
    </div>

    <!-- Quick Search Card -->
    <div class="card" style="margin-bottom: 2.5rem; background: linear-gradient(135deg, #f0fdf4, #ffffff);">
        <div class="card-header">
            <h2 class="card-title"><i class="fa-solid fa-magnifying-glass"></i> Quick Produce Search</h2>
            <span class="badge badge-accepted"><i class="fa-solid fa-circle-check"></i> Smart Matching Active</span>
        </div>
        <p class="text-muted" style="margin-bottom: 1.5rem;">
            Enter your freshly harvested crop details to match against nearest available cold storages.
        </p>
        <form action="<?php echo base_url('search.php'); ?>" method="GET">
            <div class="form-grid">
                <div class="form-group">
                    <label for="crop">Crop</label>
                    <select name="crop" id="crop" class="form-control" required>
                        <option value="Tomato">Tomato</option>
                        <option value="Potato">Potato</option>
                        <option value="Onion">Onion</option>
                        <option value="Apple">Apple</option>
                        <option value="Mango">Mango</option>
                        <option value="Carrot">Carrot</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="quantity">Quantity (kg)</label>
                    <input type="number" name="quantity" id="quantity" class="form-control" value="500" min="100" max="<?php echo e($maxAvailableAll); ?>" step="1" required>
                    <small class="text-muted" style="font-size: 0.72rem;">Min: 100 kg &bull; Max: <?php echo number_format($maxAvailableAll); ?> kg</small>
                </div>

                <div class="form-group">
                    <label for="duration">Duration (Days)</label>
                    <input type="number" name="duration" id="duration" class="form-control" value="15" min="1" required>
                </div>

                <div class="form-group">
                    <label for="location">Location</label>
                    <input type="text" name="location" id="location" class="form-control" value="<?php echo e($user['location']); ?>" required>
                </div>

                <div class="form-group">
                    <button type="submit" class="btn btn-primary btn-block" style="height: 42px;">
                        <i class="fa-solid fa-magnifying-glass"></i> Find Storages
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Recent Bookings Table -->
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Recent Storage Bookings</h2>
            <a href="<?php echo base_url('my-bookings.php'); ?>" class="btn btn-outline btn-sm">
                View All Bookings &rarr;
            </a>
        </div>

        <?php if (empty($recentBookings)): ?>
            <div class="text-center" style="padding: 2.5rem 1rem;">
                <div style="font-size: 3rem; margin-bottom: 0.75rem; color: var(--text-muted);"><i class="fa-solid fa-boxes-stacked"></i></div>
                <h3>No storage bookings yet</h3>
                <p class="text-muted" style="margin-bottom: 1.5rem;">
                    Find and reserve a nearby cold storage facility to protect your harvested produce.
                </p>
                <a href="<?php echo base_url('search.php'); ?>" class="btn btn-primary">
                    <i class="fa-solid fa-magnifying-glass"></i> Start New Search
                </a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Facility</th>
                            <th>Crop & Quantity</th>
                            <th>Schedule & Countdown</th>
                            <th>Estimated Cost</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentBookings as $b): ?>
                            <?php 
                                $today = strtotime('today');
                                $endDate = strtotime($b['end_date']);
                                $remainingDays = (int)ceil(($endDate - $today) / 86400);
                            ?>
                            <tr>
                                <td><strong>#<?php echo e($b['id']); ?></strong></td>
                                <td>
                                    <strong><?php echo e($b['storage_name']); ?></strong>
                                    <div class="text-muted" style="font-size: 0.8rem;"><i class="fa-solid fa-location-dot"></i> <?php echo e($b['storage_location']); ?></div>
                                </td>
                                <td>
                                    <div><span class="crop-tag highlight"><?php echo e($b['crop']); ?></span></div>
                                    <div style="font-weight: 700; margin-top: 3px;"><?php echo number_format($b['quantity']); ?> kg</div>
                                    <?php if (!empty($b['has_insurance'])): ?>
                                        <div style="margin-top: 3px;">
                                            <span class="badge" style="background: #ecfdf5; color: #047857; border: 1px solid #6ee7b7; font-size: 0.7rem;">
                                                <i class="fa-solid fa-shield-halved"></i> 85% Insured
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div style="font-weight: 600;">
                                        <?php echo date('d M Y', strtotime($b['start_date'])); ?> &rarr; 
                                        <?php echo date('d M Y', strtotime($b['end_date'])); ?>
                                    </div>
                                    <div style="margin-top: 4px;">
                                        <?php if ($b['status'] === 'Accepted'): ?>
                                            <?php if ($remainingDays > 5): ?>
                                                <span class="countdown-badge active"><i class="fa-solid fa-hourglass-half"></i> <strong><?php echo $remainingDays; ?></strong> days remaining</span>
                                            <?php elseif ($remainingDays > 0): ?>
                                                <span class="countdown-badge urgent"><i class="fa-solid fa-clock-rotate-left"></i> <strong><?php echo $remainingDays; ?></strong> days remaining!</span>
                                            <?php elseif ($remainingDays === 0): ?>
                                                <span class="countdown-badge urgent"><i class="fa-solid fa-triangle-exclamation"></i> <strong>Expires Today</strong></span>
                                            <?php else: ?>
                                                <span class="countdown-badge expired"><i class="fa-solid fa-calendar-xmark"></i> Expired (<?php echo abs($remainingDays); ?>d ago)</span>
                                            <?php endif; ?>
                                        <?php elseif ($b['status'] === 'Pending'): ?>
                                            <span class="countdown-badge active" style="background: #fef3c7; color: #92400e; border: 1px solid #fde68a;">
                                                <i class="fa-solid fa-clock"></i> Awaiting Approval
                                            </span>
                                        <?php elseif ($b['status'] === 'Rejected'): ?>
                                            <span class="countdown-badge expired" style="background: #fee2e2; color: #b91c1c; border: 1px solid #fca5a5;">
                                                <i class="fa-solid fa-circle-xmark"></i> Declined
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if (!empty($b['damage_status']) && $b['damage_status'] === 'Reported'): ?>
                                        <div style="font-size: 0.72rem; color: #dc2626; font-weight: 700; margin-top: 3px; display: flex; align-items: center; gap: 0.4rem; flex-wrap: wrap;">
                                            <span><i class="fa-solid fa-triangle-exclamation"></i> Spoilage Logged (₹<?php echo number_format($b['compensation_amount'] ?: ($b['total_cost'] * 0.85), 2); ?> Claim)</span>
                                            <a href="<?php echo base_url('my-bookings.php?view_incident=' . $b['id']); ?>" class="btn btn-outline-danger btn-sm" style="font-size: 0.68rem; padding: 0.1rem 0.4rem;">
                                                <i class="fa-solid fa-file-invoice"></i> View Report
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td><strong style="color: var(--primary-dark);">₹<?php echo number_format($b['total_cost'], 2); ?></strong></td>
                                <td>
                                    <span class="badge badge-<?php echo strtolower($b['status']); ?>">
                                        <?php if ($b['status'] === 'Accepted'): ?>
                                            <i class="fa-solid fa-circle-check"></i>
                                        <?php elseif ($b['status'] === 'Pending'): ?>
                                            <i class="fa-solid fa-clock"></i>
                                        <?php elseif ($b['status'] === 'Rejected'): ?>
                                            <i class="fa-solid fa-circle-xmark"></i>
                                        <?php endif; ?>
                                        <?php echo e($b['status']); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
