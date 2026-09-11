<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

requireOwner();
$user = currentUser();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . base_url('owner/bookings.php'));
    exit;
}

$bookingId        = (int)($_POST['booking_id'] ?? 0);
$damageCause      = trim($_POST['damage_cause'] ?? '');
$affectedCrop     = trim($_POST['affected_crop'] ?? '');
$affectedQuantity = (float)($_POST['affected_quantity'] ?? 0);

if ($bookingId <= 0 || empty($damageCause) || empty($affectedCrop) || $affectedQuantity <= 0) {
    setFlash('danger', 'Please provide valid incident details including cause of damage, crop, and quantity.');
    header('Location: ' . base_url('owner/bookings.php'));
    exit;
}

try {
    // Verify booking belongs to one of this owner's facilities
    $stmt = $pdo->prepare("
        SELECT b.*, s.name as storage_name, u.name as farmer_name 
        FROM bookings b
        JOIN cold_storages s ON b.storage_id = s.id
        JOIN users u ON b.farmer_id = u.id
        WHERE b.id = ? AND s.owner_id = ?
        LIMIT 1
    ");
    $stmt->execute([$bookingId, $user['id']]);
    $booking = $stmt->fetch();

    if (!$booking) {
        setFlash('danger', 'Reservation not found or unauthorized.');
        header('Location: ' . base_url('owner/bookings.php'));
        exit;
    }

    // Compute compensation if insured (85% guarantee)
    $compensation = 0.00;
    if ((int)$booking['has_insurance'] === 1) {
        $totalQty = (float)$booking['quantity'];
        $totalCost = (float)$booking['total_cost'];
        $ratio = ($totalQty > 0) ? min(1.0, $affectedQuantity / $totalQty) : 1.0;
        $compensation = round($totalCost * $ratio * 0.85, 2);
        if ($compensation <= 0) {
            $compensation = round($totalCost * 0.85, 2);
        }
    }

    $updateStmt = $pdo->prepare("
        UPDATE bookings 
        SET damage_status = 'Reported',
            damage_cause = ?,
            affected_crop = ?,
            affected_quantity = ?,
            compensation_amount = ?
        WHERE id = ?
    ");
    $updateStmt->execute([
        $damageCause,
        $affectedCrop,
        $affectedQuantity,
        $compensation,
        $bookingId
    ]);

    if ((int)$booking['has_insurance'] === 1) {
        setFlash('success', "Incident logged for Booking #{$bookingId}. 85% Compensation Guarantee of ₹" . number_format($compensation, 2) . " calculated and displayed to farmer {$booking['farmer_name']}.");
    } else {
        setFlash('warning', "Incident logged for Booking #{$bookingId}. Farmer was not insured, so no compensation is covered.");
    }

    header('Location: ' . base_url('owner/bookings.php?view_incident=' . $bookingId));
    exit;
} catch (PDOException $e) {
    setFlash('danger', 'Database error recording incident: ' . $e->getMessage());
    header('Location: ' . base_url('owner/bookings.php'));
    exit;
}
