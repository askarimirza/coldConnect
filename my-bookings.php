<?php
$pageTitle = "My Storage Bookings";
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

requireFarmer();
$user = currentUser();

$bookings = [];
try {
    $stmt = $pdo->prepare("
        SELECT b.*, s.name as storage_name, s.location as storage_location, s.contact as storage_contact, s.price_per_kg
        FROM bookings b
        JOIN cold_storages s ON b.storage_id = s.id
        WHERE b.farmer_id = ?
        ORDER BY b.id DESC
    ");
    $stmt->execute([$user['id']]);
    $bookings = $stmt->fetchAll();
} catch (PDOException $e) {
    setFlash('danger', 'Database error: ' . $e->getMessage());
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="padding: 2.5rem 1.25rem;">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; margin-bottom: 2rem;">
        <div>
            <h1>My Storage Bookings</h1>
            <p class="text-muted">Track your cold room reservation requests and confirmation statuses.</p>
        </div>
        <div>
            <a href="<?php echo base_url('search.php'); ?>" class="btn btn-primary">
                + New Storage Search
            </a>
        </div>
    </div>

    <?php if (empty($bookings)): ?>
        <div class="card text-center" style="padding: 3.5rem 1.5rem;">
            <div style="font-size: 3.5rem; margin-bottom: 1rem; color: #94a3b8;"><i class="fa-solid fa-clipboard-list"></i></div>
            <h3>No Bookings Found</h3>
            <p class="text-muted" style="max-width: 450px; margin: 0 auto 1.5rem;">
                You have not submitted any cold storage reservation requests yet.
            </p>
            <a href="<?php echo base_url('search.php'); ?>" class="btn btn-primary">
                <i class="fa-solid fa-magnifying-glass"></i> Find Available Storages Now
            </a>
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Booking ID</th>
                        <th>Facility Name</th>
                        <th>Crop & Weight</th>
                        <th>Storage Dates</th>
                        <th>Estimated Cost</th>
                        <th>Status</th>
                        <th>Contact Facility</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($bookings as $b): ?>
                        <?php 
                            $diffDays = max(1, (int)ceil((strtotime($b['end_date']) - strtotime($b['start_date'])) / 86400));
                            $today = strtotime('today');
                            $endDate = strtotime($b['end_date']);
                            $remainingDays = (int)ceil(($endDate - $today) / 86400);
                        ?>
                        <tr>
                            <td>
                                <strong>#<?php echo e($b['id']); ?></strong>
                                <div class="text-muted" style="font-size: 0.75rem;">
                                    <?php echo date('d M Y, h:i A', strtotime($b['created_at'])); ?>
                                </div>
                                <?php if (!empty($b['pickup_location'])): ?>
                                    <div class="text-muted" style="font-size: 0.72rem; margin-top: 4px;">
                                        <i class="fa-solid fa-truck-pickup"></i> Pickup: <strong><?php echo e($b['pickup_location']); ?></strong>
                                    </div>
                                <?php endif; ?>
                            </td>

                            <td>
                                <strong style="font-size: 1rem;"><?php echo e($b['storage_name']); ?></strong>
                                <div class="text-muted" style="font-size: 0.85rem;">
                                    <i class="fa-solid fa-location-dot"></i> <?php echo e($b['storage_location']); ?>
                                </div>

                                <?php if (!empty($b['damage_status']) && in_array($b['damage_status'], ['Reported', 'Compensated'])): ?>
                                    <div class="spoilage-alert-box">
                                        <div style="font-weight: 700; color: #b91c1c; display: flex; align-items: center; gap: 0.35rem;">
                                            <i class="fa-solid fa-triangle-exclamation"></i> Crop Damage Incident Logged
                                        </div>
                                        <div style="margin-top: 0.3rem; font-size: 0.8rem; color: #374151; line-height: 1.35;">
                                            <div><strong>Cause of Damage:</strong> <?php echo e($b['damage_cause'] ?: 'Chamber equipment / power interruption'); ?></div>
                                            <div><strong>Affected Crop:</strong> <?php echo e($b['affected_crop'] ?: $b['crop']); ?> (<?php echo number_format($b['affected_quantity'] ?: $b['quantity']); ?> kg damaged)</div>
                                        </div>
                                        <div style="margin-top: 0.4rem; display: flex; flex-wrap: wrap; gap: 0.4rem; align-items: center;">
                                            <?php if (!empty($b['has_insurance'])): ?>
                                                <span class="spoilage-compensation-badge covered">
                                                    <i class="fa-solid fa-shield-halved"></i> 85% Compensation: ₹<?php echo number_format($b['compensation_amount'] ?: ($b['total_cost'] * 0.85), 2); ?> (Owner Guaranteed)
                                                </span>
                                            <?php else: ?>
                                                <span class="spoilage-compensation-badge uncovered">
                                                    <i class="fa-solid fa-circle-xmark"></i> Not Insured (No Claim Coverage)
                                                </span>
                                            <?php endif; ?>

                                            <button type="button" class="btn btn-outline-danger btn-sm btn-view-incident"
                                                    data-id="<?php echo $b['id']; ?>"
                                                    data-farmer="<?php echo e($user['name']); ?>"
                                                    data-farmer-phone="<?php echo e($user['phone'] ?? 'Not Listed'); ?>"
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
                                                    data-compensation="<?php echo e($b['compensation_amount'] ?: ($b['total_cost'] * 0.85)); ?>"
                                                    data-status="<?php echo e($b['damage_status']); ?>"
                                                    style="font-size: 0.72rem; padding: 0.2rem 0.5rem; display: inline-flex; align-items: center; gap: 0.3rem;">
                                                <i class="fa-solid fa-file-invoice"></i> View Incident Report
                                            </button>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </td>

                            <td>
                                <div><span class="crop-tag highlight"><?php echo e($b['crop']); ?></span></div>
                                <div style="font-weight: 700; margin-top: 4px;"><?php echo number_format($b['quantity']); ?> kg</div>
                                <div style="margin-top: 5px;">
                                    <?php if (!empty($b['has_insurance'])): ?>
                                        <span class="badge" style="background: #ecfdf5; color: #047857; border: 1px solid #6ee7b7; font-size: 0.72rem;">
                                            <i class="fa-solid fa-shield-halved"></i> 85% Insured (+₹<?php echo number_format($b['insurance_fee'], 2); ?>)
                                        </span>
                                    <?php else: ?>
                                        <span class="badge" style="background: #f1f5f9; color: #64748b; font-size: 0.72rem;">
                                            <i class="fa-solid fa-shield"></i> Standard
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </td>

                            <td>
                                <div style="font-weight: 600;">
                                    <?php echo date('d M Y', strtotime($b['start_date'])); ?> &rarr; 
                                    <?php echo date('d M Y', strtotime($b['end_date'])); ?>
                                </div>
                                <div class="text-muted" style="font-size: 0.8rem; margin-bottom: 4px;">
                                    Total Duration: <?php echo $diffDays; ?> days
                                </div>
                                <div>
                                    <?php if ($b['status'] === 'Accepted'): ?>
                                        <?php if ($remainingDays > 5): ?>
                                            <div class="countdown-badge active">
                                                <i class="fa-solid fa-hourglass-half"></i> <strong><?php echo $remainingDays; ?></strong> days remaining
                                            </div>
                                        <?php elseif ($remainingDays > 0): ?>
                                            <div class="countdown-badge urgent">
                                                <i class="fa-solid fa-clock-rotate-left"></i> <strong><?php echo $remainingDays; ?></strong> days remaining! (Expiring Soon)
                                            </div>
                                        <?php elseif ($remainingDays === 0): ?>
                                            <div class="countdown-badge urgent">
                                                <i class="fa-solid fa-triangle-exclamation"></i> <strong>Expires Today</strong>
                                            </div>
                                        <?php else: ?>
                                            <div class="countdown-badge expired">
                                                <i class="fa-solid fa-calendar-xmark"></i> Storage Expired (<?php echo abs($remainingDays); ?>d ago)
                                            </div>
                                        <?php endif; ?>
                                    <?php elseif ($b['status'] === 'Pending'): ?>
                                        <div class="countdown-badge active" style="background: #fef3c7; color: #92400e; border: 1px solid #fde68a;">
                                            <i class="fa-solid fa-clock"></i> Awaiting Owner Confirmation
                                        </div>
                                    <?php elseif ($b['status'] === 'Rejected'): ?>
                                        <div class="countdown-badge expired" style="background: #fee2e2; color: #b91c1c; border: 1px solid #fca5a5;">
                                            <i class="fa-solid fa-circle-xmark"></i> Request Declined
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </td>

                            <td>
                                <div style="font-size: 1.1rem; font-weight: 800; color: var(--primary-dark);">
                                    ₹<?php echo number_format($b['total_cost'], 2); ?>
                                </div>
                                <div class="text-muted" style="font-size: 0.75rem;">
                                    @ ₹<?php echo number_format($b['price_per_kg'], 2); ?>/kg/day
                                </div>
                            </td>

                            <td>
                                <span class="badge badge-<?php echo strtolower($b['status']); ?>">
                                    <?php 
                                        if ($b['status'] === 'Accepted') echo '<i class="fa-solid fa-circle-check"></i> ';
                                        elseif ($b['status'] === 'Pending') echo '<i class="fa-solid fa-clock"></i> ';
                                        elseif ($b['status'] === 'Rejected') echo '<i class="fa-solid fa-circle-xmark"></i> ';
                                        echo e($b['status']); 
                                    ?>
                                </span>
                            </td>

                            <td>
                                <a href="tel:<?php echo e($b['storage_contact']); ?>" class="btn btn-outline btn-sm">
                                    <i class="fa-solid fa-phone"></i> <?php echo e($b['storage_contact']); ?>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/modal-view-incident.php'; ?>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
