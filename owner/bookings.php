<?php
$pageTitle = "Manage Booking Requests";
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

requireOwner();
$user = currentUser();

$statusFilter = trim($_GET['status'] ?? '');

$bookings = [];
try {
    $sql = "
        SELECT b.*, u.name as farmer_name, u.phone as farmer_phone, u.location as farmer_location,
               s.name as storage_name, s.location as storage_location, s.available_capacity, s.total_capacity
        FROM bookings b
        JOIN users u ON b.farmer_id = u.id
        JOIN cold_storages s ON b.storage_id = s.id
        WHERE s.owner_id = ?
    ";
    $params = [$user['id']];

    if (!empty($statusFilter) && in_array($statusFilter, ['Pending', 'Accepted', 'Rejected', 'Completed'])) {
        $sql .= " AND b.status = ?";
        $params[] = $statusFilter;
    }

    $sql .= " ORDER BY b.id DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $bookings = $stmt->fetchAll();
} catch (PDOException $e) {
    setFlash('danger', 'Database error: ' . $e->getMessage());
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container" style="padding: 2.5rem 1.25rem;">
    <!-- Page Header -->
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 2rem;">
        <div>
            <h1>Incoming Booking Reservations</h1>
            <p class="text-muted">Review and act upon space requests submitted by farmers.</p>
        </div>
        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
            <a href="<?php echo base_url('owner/bookings.php'); ?>" class="btn <?php echo empty($statusFilter) ? 'btn-primary' : 'btn-outline'; ?> btn-sm">
                All
            </a>
            <a href="<?php echo base_url('owner/bookings.php?status=Pending'); ?>" class="btn <?php echo ($statusFilter === 'Pending') ? 'btn-primary' : 'btn-outline'; ?> btn-sm">
                <i class="fa-solid fa-clock"></i> Pending
            </a>
            <a href="<?php echo base_url('owner/bookings.php?status=Accepted'); ?>" class="btn <?php echo ($statusFilter === 'Accepted') ? 'btn-primary' : 'btn-outline'; ?> btn-sm">
                <i class="fa-solid fa-circle-check"></i> Accepted
            </a>
            <a href="<?php echo base_url('owner/bookings.php?status=Rejected'); ?>" class="btn <?php echo ($statusFilter === 'Rejected') ? 'btn-primary' : 'btn-outline'; ?> btn-sm">
                <i class="fa-solid fa-circle-xmark"></i> Rejected
            </a>
        </div>
    </div>

    <?php if (empty($bookings)): ?>
        <div class="card text-center" style="padding: 3.5rem 1.5rem;">
            <div style="font-size: 3rem; margin-bottom: 0.75rem; color: #94a3b8;"><i class="fa-solid fa-clipboard-list"></i></div>
            <h3>No Bookings Found</h3>
            <p class="text-muted">No reservations match the selected filter criteria.</p>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Farmer Details</th>
                        <th>Facility</th>
                        <th>Crop & Quantity</th>
                        <th>Dates & Duration</th>
                        <th>Est. Cost</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($bookings as $b): ?>
                        <?php 
                            $diffDays = max(1, (int)ceil((strtotime($b['end_date']) - strtotime($b['start_date'])) / 86400));
                            $canAccept = ($b['available_capacity'] >= $b['quantity']);
                            $today = strtotime('today');
                            $endDate = strtotime($b['end_date']);
                            $remainingDays = (int)ceil(($endDate - $today) / 86400);
                        ?>
                        <tr>
                            <td>
                                <strong>#<?php echo e($b['id']); ?></strong>
                                <div class="text-muted" style="font-size: 0.75rem;">
                                    <?php echo date('d M Y', strtotime($b['created_at'])); ?>
                                </div>
                            </td>

                            <td>
                                <strong><?php echo e($b['farmer_name']); ?></strong>
                                <div class="text-muted" style="font-size: 0.8rem;">
                                    <i class="fa-solid fa-phone"></i> <a href="tel:<?php echo e($b['farmer_phone']); ?>"><?php echo e($b['farmer_phone']); ?></a>
                                </div>
                                <div class="text-muted" style="font-size: 0.75rem;">
                                    <i class="fa-solid fa-location-dot"></i> <?php echo e($b['farmer_location']); ?>
                                </div>
                                <?php if (!empty($b['pickup_location'])): ?>
                                    <div class="text-muted" style="font-size: 0.72rem;">
                                        <i class="fa-solid fa-truck-pickup"></i> Pickup: <?php echo e($b['pickup_location']); ?>
                                    </div>
                                <?php endif; ?>
                            </td>

                            <td>
                                <strong><?php echo e($b['storage_name']); ?></strong>
                                <div class="text-muted" style="font-size: 0.75rem;">
                                    Free: <?php echo number_format($b['available_capacity']); ?> kg
                                </div>
                            </td>

                            <td>
                                <div><span class="crop-tag highlight"><?php echo e($b['crop']); ?></span></div>
                                <div style="font-weight: 700; margin-top: 3px;"><?php echo number_format($b['quantity']); ?> kg</div>
                                <div style="margin-top: 3px;">
                                    <?php if (!empty($b['has_insurance'])): ?>
                                        <span class="badge" style="background: #ecfdf5; color: #047857; border: 1px solid #6ee7b7; font-size: 0.7rem;">
                                            <i class="fa-solid fa-shield-halved"></i> 85% Insured
                                        </span>
                                    <?php else: ?>
                                        <span class="badge" style="background: #f1f5f9; color: #64748b; font-size: 0.7rem;">
                                            Standard
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </td>

                            <td>
                                <div><?php echo date('d M Y', strtotime($b['start_date'])); ?> &rarr;</div>
                                <div><?php echo date('d M Y', strtotime($b['end_date'])); ?></div>
                                <div class="text-muted" style="font-size: 0.75rem;"><?php echo $diffDays; ?> days</div>
                                <div style="margin-top: 3px;">
                                    <?php if ($b['status'] === 'Accepted'): ?>
                                        <?php if ($remainingDays > 5): ?>
                                            <span class="countdown-badge active" style="font-size: 0.72rem;"><i class="fa-solid fa-hourglass-half"></i> <?php echo $remainingDays; ?>d left</span>
                                        <?php elseif ($remainingDays > 0): ?>
                                            <span class="countdown-badge urgent" style="font-size: 0.72rem;"><i class="fa-solid fa-clock"></i> <?php echo $remainingDays; ?>d left!</span>
                                        <?php elseif ($remainingDays === 0): ?>
                                            <span class="countdown-badge urgent" style="font-size: 0.72rem;"><i class="fa-solid fa-triangle-exclamation"></i> Ends Today</span>
                                        <?php else: ?>
                                            <span class="countdown-badge expired" style="font-size: 0.72rem;">Ended</span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </td>

                            <td>
                                <strong style="color: var(--primary-dark); font-size: 1.05rem;">
                                    ₹<?php echo number_format($b['total_cost'], 2); ?>
                                </strong>
                            </td>

                            <td>
                                <span class="badge badge-<?php echo strtolower($b['status']); ?>">
                                    <?php echo e($b['status']); ?>
                                </span>
                            </td>

                            <td>
                                <?php if ($b['status'] === 'Pending'): ?>
                                    <div style="display: flex; gap: 0.35rem;">
                                        <form method="POST" action="<?php echo base_url('owner/update-booking.php'); ?>" style="display: inline;">
                                            <input type="hidden" name="booking_id" value="<?php echo $b['id']; ?>">
                                            <input type="hidden" name="action" value="accept">
                                            <button type="submit" class="btn btn-success btn-sm" <?php echo !$canAccept ? 'disabled title="Available capacity is lower than requested quantity"' : ''; ?>>
                                                Accept
                                            </button>
                                        </form>

                                        <button type="button" class="btn btn-danger btn-sm btn-reject-modal"
                                                data-id="<?php echo $b['id']; ?>"
                                                data-farmer="<?php echo e($b['farmer_name']); ?>"
                                                data-crop="<?php echo e($b['crop']); ?>"
                                                data-qty="<?php echo number_format($b['quantity']); ?> kg"
                                                data-facility="<?php echo e($b['storage_name']); ?>">
                                            Reject
                                        </button>
                                    </div>
                                    <?php if (!$canAccept): ?>
                                        <div style="font-size: 0.72rem; color: var(--status-rejected); margin-top: 3px;">
                                            Low capacity
                                        </div>
                                    <?php endif; ?>
                                <?php elseif ($b['status'] === 'Accepted'): ?>
                                    <?php if (empty($b['damage_status']) || $b['damage_status'] === 'None'): ?>
                                        <button type="button" class="btn btn-outline-danger btn-sm btn-incident-modal"
                                                data-id="<?php echo $b['id']; ?>"
                                                data-farmer="<?php echo e($b['farmer_name']); ?>"
                                                data-crop="<?php echo e($b['crop']); ?>"
                                                data-qty="<?php echo e($b['quantity']); ?>"
                                                data-cost="<?php echo e($b['total_cost']); ?>"
                                                data-insured="<?php echo (int)$b['has_insurance']; ?>"
                                                data-facility="<?php echo e($b['storage_name']); ?>"
                                                style="font-size: 0.75rem; padding: 0.25rem 0.5rem;">
                                            <i class="fa-solid fa-triangle-exclamation"></i> Report Incident
                                        </button>
                                    <?php else: ?>
                                        <div style="display: flex; flex-direction: column; gap: 0.35rem; align-items: flex-start;">
                                            <span class="badge badge-rejected" style="font-size: 0.72rem;">
                                                <i class="fa-solid fa-triangle-exclamation"></i> <?php echo e($b['damage_status']); ?>
                                            </span>
                                            <div style="font-size: 0.72rem; color: #dc2626; font-weight: 600; max-width: 150px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="<?php echo e($b['damage_cause']); ?>">
                                                <?php echo e($b['damage_cause']); ?>
                                            </div>
                                            <?php if (!empty($b['compensation_amount']) && $b['compensation_amount'] > 0): ?>
                                                <div style="font-size: 0.72rem; color: #16a34a; font-weight: 700;">
                                                    85% Claim: ₹<?php echo number_format($b['compensation_amount'], 2); ?>
                                                </div>
                                            <?php endif; ?>

                                            <button type="button" class="btn btn-outline-danger btn-sm btn-view-incident"
                                                    data-id="<?php echo $b['id']; ?>"
                                                    data-farmer="<?php echo e($b['farmer_name']); ?>"
                                                    data-farmer-phone="<?php echo e($b['farmer_phone'] ?? 'Not Listed'); ?>"
                                                    data-facility="<?php echo e($b['storage_name']); ?>"
                                                    data-facility-location="<?php echo e($b['storage_location'] ?? 'Gujarat Hub'); ?>"
                                                    data-start-date="<?php echo date('M d, Y', strtotime($b['start_date'])); ?>"
                                                    data-end-date="<?php echo date('M d, Y', strtotime($b['end_date'])); ?>"
                                                    data-crop="<?php echo e($b['crop']); ?>"
                                                    data-qty="<?php echo e($b['quantity']); ?>"
                                                    data-affected-crop="<?php echo e($b['affected_crop'] ?: $b['crop']); ?>"
                                                    data-affected-qty="<?php echo e($b['affected_quantity'] ?: $b['quantity']); ?>"
                                                    data-cause="<?php echo e($b['damage_cause']); ?>"
                                                    data-cost="<?php echo e($b['total_cost']); ?>"
                                                    data-insured="<?php echo (int)$b['has_insurance']; ?>"
                                                    data-compensation="<?php echo e($b['compensation_amount'] ?: 0); ?>"
                                                    data-status="<?php echo e($b['damage_status']); ?>"
                                                    style="font-size: 0.73rem; padding: 0.25rem 0.55rem; display: inline-flex; align-items: center; gap: 0.35rem; margin-top: 2px;">
                                                <i class="fa-solid fa-file-invoice"></i> View Incident Report
                                            </button>
                                        </div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-muted" style="font-size: 0.85rem;">Processed</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/modal-incident.php'; ?>
<?php require_once __DIR__ . '/../includes/modal-view-incident.php'; ?>
<?php require_once __DIR__ . '/../includes/modal-reject.php'; ?>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
