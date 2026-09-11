<?php
/**
 * COLDCONNECT - Update Booking Status Controller
 * Securely handles Accept / Reject actions by storage owners and updates available capacity.
 */
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';

requireOwner();
$user = currentUser();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . base_url('owner/bookings.php'));
    exit;
}

$bookingId = isset($_POST['booking_id']) ? (int)$_POST['booking_id'] : 0;
$action    = strtolower(trim($_POST['action'] ?? ''));

if ($bookingId <= 0 || !in_array($action, ['accept', 'reject'])) {
    setFlash('danger', 'Invalid booking action request.');
    header('Location: ' . base_url('owner/bookings.php'));
    exit;
}

try {
    // 1. Verify booking exists and belongs to a storage owned by this owner
    $stmt = $pdo->prepare("
        SELECT b.*, s.name as storage_name, s.available_capacity, s.owner_id
        FROM bookings b
        JOIN cold_storages s ON b.storage_id = s.id
        WHERE b.id = ?
        LIMIT 1
    ");
    $stmt->execute([$bookingId]);
    $booking = $stmt->fetch();

    if (!$booking) {
        setFlash('danger', 'Booking record not found.');
        header('Location: ' . base_url('owner/bookings.php'));
        exit;
    }

    if ((int)$booking['owner_id'] !== (int)$user['id']) {
        setFlash('danger', 'Unauthorized: You do not own the facility associated with this booking.');
        header('Location: ' . base_url('owner/bookings.php'));
        exit;
    }

    if ($booking['status'] !== 'Pending') {
        setFlash('warning', "Booking #{$bookingId} is already marked as {$booking['status']}.");
        header('Location: ' . base_url('owner/bookings.php'));
        exit;
    }

    // 2. Perform Action with database transaction
    if ($action === 'accept') {
        $neededQty = (float)$booking['quantity'];
        $availCap = (float)$booking['available_capacity'];

        if ($availCap < $neededQty) {
            setFlash('danger', "Cannot accept booking: Facility available capacity ({$availCap} kg) is less than required ({$neededQty} kg).");
            header('Location: ' . base_url('owner/bookings.php'));
            exit;
        }

        $newCapacity = max(0.0, $availCap - $neededQty);
        $newStorageStatus = ($newCapacity == 0.0) ? 'Full' : 'Available';

        // Begin transaction
        $pdo->beginTransaction();

        // Update booking status to Accepted
        $updateBooking = $pdo->prepare("UPDATE bookings SET status = 'Accepted' WHERE id = ?");
        $updateBooking->execute([$bookingId]);

        // Decrement available capacity on storage
        $updateStorage = $pdo->prepare("
            UPDATE cold_storages 
            SET available_capacity = ?, status = ? 
            WHERE id = ?
        ");
        $updateStorage->execute([$newCapacity, $newStorageStatus, $booking['storage_id']]);

        $pdo->commit();

        setFlash('success', "Booking #{$bookingId} accepted! Available capacity for '{$booking['storage_name']}' is now {$newCapacity} kg (reduced by {$neededQty} kg).");

    } elseif ($action === 'reject') {
        $updateBooking = $pdo->prepare("UPDATE bookings SET status = 'Rejected' WHERE id = ?");
        $updateBooking->execute([$bookingId]);

        setFlash('info', "Booking #{$bookingId} has been rejected.");
    }

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    setFlash('danger', 'Database transaction failed: ' . $e->getMessage());
}

$referer = $_SERVER['HTTP_REFERER'] ?? base_url('owner/bookings.php');
header('Location: ' . $referer);
exit;
