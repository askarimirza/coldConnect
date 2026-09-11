<?php
/**
 * COLDCONNECT - Global Header Component
 */
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
$user = currentUser();
$currentPage = basename($_SERVER['PHP_SELF']);

// Compute Live Dynamic Notifications & Reminders
$liveNotifs = [];
if ($user && isset($pdo)) {
    try {
        if ($user['role'] === 'farmer') {
            // 1. Crop Damage Alert
            $stmtDam = $pdo->prepare("
                SELECT b.id, b.crop, b.damage_cause, b.compensation_amount, b.has_insurance, s.name as storage_name
                FROM bookings b
                JOIN cold_storages s ON b.storage_id = s.id
                WHERE b.farmer_id = ? AND b.damage_status IN ('Reported', 'Compensated')
                ORDER BY b.id DESC LIMIT 2
            ");
            $stmtDam->execute([$user['id']]);
            while ($row = $stmtDam->fetch()) {
                $compMsg = $row['has_insurance'] ? ("85% Compensation: ₹" . number_format($row['compensation_amount'] ?: 0, 2)) : "No insurance claim";
                $liveNotifs[] = [
                    'class' => 'alert-danger-item',
                    'icon'  => 'red',
                    'icon_fa' => 'fa-triangle-exclamation',
                    'title' => "Crop Incident Alert (#{$row['id']})",
                    'title_color' => '#dc2626',
                    'desc'  => "Booking #{$row['id']} ({$row['crop']}): {$row['damage_cause']}. <strong>{$compMsg}</strong>",
                    'time'  => 'Urgent Alert',
                    'link'  => base_url('my-bookings.php')
                ];
            }

            // 2. Storage Expiry Countdown Reminder (within 5 days)
            $stmtExp = $pdo->prepare("
                SELECT b.id, b.crop, b.end_date, s.name as storage_name
                FROM bookings b
                JOIN cold_storages s ON b.storage_id = s.id
                WHERE b.farmer_id = ? AND b.status = 'Accepted'
                ORDER BY b.end_date ASC LIMIT 2
            ");
            $stmtExp->execute([$user['id']]);
            while ($row = $stmtExp->fetch()) {
                $daysRemaining = (int)ceil((strtotime($row['end_date']) - strtotime('today')) / 86400);
                if ($daysRemaining <= 5 && $daysRemaining >= 0) {
                    $liveNotifs[] = [
                        'class' => 'unread',
                        'icon'  => 'amber',
                        'icon_fa' => 'fa-clock-rotate-left',
                        'title' => 'Storage Expiry Reminder',
                        'title_color' => '#d97706',
                        'desc'  => "Booking #{$row['id']} ({$row['crop']} at {$row['storage_name']}) has <strong>{$daysRemaining} days remaining</strong>.",
                        'time'  => 'Expiring Soon',
                        'link'  => base_url('my-bookings.php')
                    ];
                }
            }

            // 3. Active Insurance Guarantee
            $stmtIns = $pdo->prepare("
                SELECT b.id, b.crop, s.name as storage_name
                FROM bookings b
                JOIN cold_storages s ON b.storage_id = s.id
                WHERE b.farmer_id = ? AND b.has_insurance = 1 AND b.status = 'Accepted'
                ORDER BY b.id DESC LIMIT 1
            ");
            $stmtIns->execute([$user['id']]);
            if ($row = $stmtIns->fetch()) {
                $liveNotifs[] = [
                    'class' => '',
                    'icon'  => 'green',
                    'icon_fa' => 'fa-shield-halved',
                    'title' => 'Crop Insurance Active',
                    'title_color' => '#059669',
                    'desc'  => "Booking #{$row['id']} ({$row['crop']}) has 85% spoilage protection backed by facility owner.",
                    'time'  => 'Protection Active',
                    'link'  => base_url('my-bookings.php')
                ];
            }
        } elseif ($user['role'] === 'owner') {
            // 1. Pending Booking Requests
            $stmtPend = $pdo->prepare("
                SELECT b.id, b.crop, b.quantity, u.name as farmer_name, s.name as storage_name
                FROM bookings b
                JOIN users u ON b.farmer_id = u.id
                JOIN cold_storages s ON b.storage_id = s.id
                WHERE s.owner_id = ? AND b.status = 'Pending'
                ORDER BY b.id DESC LIMIT 2
            ");
            $stmtPend->execute([$user['id']]);
            while ($row = $stmtPend->fetch()) {
                $liveNotifs[] = [
                    'class' => 'unread',
                    'icon'  => 'amber',
                    'icon_fa' => 'fa-calendar-plus',
                    'title' => 'Pending Booking Request',
                    'title_color' => '#d97706',
                    'desc'  => "Farmer {$row['farmer_name']} requested " . number_format($row['quantity']) . " kg space for {$row['crop']} at {$row['storage_name']}.",
                    'time'  => 'Action Required',
                    'link'  => base_url('owner/bookings.php?status=Pending')
                ];
            }

            // 2. Spoilage claims pending
            $stmtClaim = $pdo->prepare("
                SELECT b.id, b.crop, b.damage_cause, b.compensation_amount, u.name as farmer_name
                FROM bookings b
                JOIN users u ON b.farmer_id = u.id
                JOIN cold_storages s ON b.storage_id = s.id
                WHERE s.owner_id = ? AND b.damage_status = 'Reported'
                ORDER BY b.id DESC LIMIT 2
            ");
            $stmtClaim->execute([$user['id']]);
            while ($row = $stmtClaim->fetch()) {
                $liveNotifs[] = [
                    'class' => 'alert-danger-item',
                    'icon'  => 'red',
                    'icon_fa' => 'fa-triangle-exclamation',
                    'title' => 'Spoilage Claim Active (#' . $row['id'] . ')',
                    'title_color' => '#dc2626',
                    'desc'  => "Farmer {$row['farmer_name']} ({$row['crop']}): 85% claim compensation pending payout of ₹" . number_format($row['compensation_amount'] ?: 0, 2) . ".",
                    'time'  => 'Claim Pending',
                    'link'  => base_url('owner/bookings.php')
                ];
            }
        }
    } catch (PDOException $e) {
        // Fallback gracefully
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Agri Storage - Smart Cold Storage Finder & Booking System for Farmers. Transparent Smart Match scoring, real-time availability, and distress selling prevention.">
    <meta name="theme-color" content="#059669">
    <title><?php echo isset($pageTitle) ? e($pageTitle) . ' | Agri Storage' : 'Agri Storage - Smart Cold Storage Finder'; ?></title>
    
    <!-- Font Awesome 6.5.1 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- Design System CSS -->
    <link rel="stylesheet" href="<?php echo base_url('css/style.css'); ?>">
</head>
<body>

<?php if (defined('IS_SQLITE_DEMO') && IS_SQLITE_DEMO): ?>
<div style="background: linear-gradient(90deg, #1e3a8a, #2563eb); color: #fff; font-size: 0.82rem; padding: 6px 15px; text-align: center; font-weight: 500; display: flex; align-items: center; justify-content: center; gap: 8px; flex-wrap: wrap;">
    <span><i class="fa-solid fa-circle-check" style="color: #6ee7b7; margin-right: 4px;"></i><strong>Cloud Demo Mode:</strong> Running with pre-loaded database (Demo Farmers, Facilities &amp; Bookings).</span>
    <span style="opacity: 0.88; font-size: 0.76rem;">(Set <code>DB_HOST</code> in Vercel to link your persistent MySQL database)</span>
</div>
<?php endif; ?>

<!-- Main Navigation Bar -->
<header class="navbar">
    <div class="container nav-container">
        <a href="<?php echo base_url('index.php'); ?>" class="brand-logo" title="Agri Storage Homepage">
            <span class="brand-icon"><i class="fa-solid fa-wheat-awn"></i></span>
            <span>Agri<span class="brand-accent">Storage</span></span>
        </a>

        <button class="nav-toggle" aria-label="Toggle navigation" aria-expanded="false">
            <i class="fa-solid fa-bars"></i>
        </button>

        <ul class="nav-menu">
            <li><a href="<?php echo base_url('index.php'); ?>" class="nav-link <?php echo ($currentPage === 'index.php') ? 'active' : ''; ?>">Home</a></li>
            <li><a href="<?php echo base_url('search.php'); ?>" class="nav-link <?php echo ($currentPage === 'search.php') ? 'active' : ''; ?>">Find Storage</a></li>

            <?php if ($user && $user['role'] === 'farmer'): ?>
                <li><a href="<?php echo base_url('farmer-dashboard.php'); ?>" class="nav-link <?php echo ($currentPage === 'farmer-dashboard.php') ? 'active' : ''; ?>">Dashboard</a></li>
                <li><a href="<?php echo base_url('my-bookings.php'); ?>" class="nav-link <?php echo ($currentPage === 'my-bookings.php') ? 'active' : ''; ?>">My Bookings</a></li>
            <?php elseif ($user && $user['role'] === 'owner'): ?>
                <li><a href="<?php echo base_url('owner/dashboard.php'); ?>" class="nav-link <?php echo (strpos($_SERVER['REQUEST_URI'], 'owner/dashboard') !== false) ? 'active' : ''; ?>">Owner Portal</a></li>
                <li><a href="<?php echo base_url('owner/bookings.php'); ?>" class="nav-link <?php echo (strpos($_SERVER['REQUEST_URI'], 'owner/bookings') !== false) ? 'active' : ''; ?>">Requests</a></li>
            <?php endif; ?>
        </ul>

        <div class="nav-actions" style="display: flex; align-items: center; gap: 0.65rem;">
            <!-- Notification Bar & Bell Icon -->
            <?php 
                $hasLive = !empty($liveNotifs);
                $badgeCount = $hasLive ? count($liveNotifs) : ($user ? 2 : 1);
            ?>
            <div class="notification-wrapper">
                <button type="button" id="notif-bell-btn" class="notif-bell-btn" aria-label="Notifications" title="View reminders and alerts">
                    <i class="fa-solid fa-bell"></i>
                    <span class="notif-badge-dot" id="notif-badge"><?php echo $badgeCount; ?></span>
                </button>
                <div id="notif-dropdown" class="notif-dropdown" aria-hidden="true">
                    <div class="notif-dropdown-header">
                        <div style="font-weight: 700; display: flex; align-items: center; gap: 0.4rem; font-size: 0.9rem;">
                            <i class="fa-solid fa-bell" style="color: var(--primary);"></i> Reminders &amp; Alerts
                        </div>
                        <span class="badge badge-accepted" style="font-size: 0.7rem;">Live</span>
                    </div>
                    <div class="notif-dropdown-body">
                        <?php if ($hasLive): ?>
                            <?php foreach ($liveNotifs as $n): ?>
                                <a href="<?php echo $n['link']; ?>" class="notif-item <?php echo $n['class']; ?>" style="text-decoration: none; color: inherit; display: flex;">
                                    <div class="notif-icon <?php echo $n['icon']; ?>"><i class="fa-solid <?php echo $n['icon_fa']; ?>"></i></div>
                                    <div>
                                        <div class="notif-title" style="<?php echo !empty($n['title_color']) ? 'color:' . $n['title_color'] . ';' : ''; ?>"><?php echo $n['title']; ?></div>
                                        <div class="notif-desc"><?php echo $n['desc']; ?></div>
                                        <div class="notif-time"><?php echo $n['time']; ?></div>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        <?php elseif ($user && $user['role'] === 'farmer'): ?>
                            <div class="notif-item unread">
                                <div class="notif-icon green"><i class="fa-solid fa-shield-halved"></i></div>
                                <div>
                                    <div class="notif-title">Crop Insurance Active</div>
                                    <div class="notif-desc">85% spoilage compensation guarantee available across registered cold rooms.</div>
                                    <div class="notif-time">Protected</div>
                                </div>
                            </div>
                            <div class="notif-item">
                                <div class="notif-icon blue"><i class="fa-solid fa-hourglass-half"></i></div>
                                <div>
                                    <div class="notif-title">Produce Tracking Active</div>
                                    <div class="notif-desc">Remaining days countdowns actively monitor chamber storage duration.</div>
                                    <div class="notif-time">Real-time</div>
                                </div>
                            </div>
                        <?php elseif ($user && $user['role'] === 'owner'): ?>
                            <div class="notif-item unread">
                                <div class="notif-icon green"><i class="fa-solid fa-warehouse"></i></div>
                                <div>
                                    <div class="notif-title">Facility Ready for Bookings</div>
                                    <div class="notif-desc">Farmers can now reserve chamber space from 100 kg with 85% insurance.</div>
                                    <div class="notif-time">Online</div>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="notif-item">
                                <div class="notif-icon green"><i class="fa-solid fa-wheat-awn"></i></div>
                                <div>
                                    <div class="notif-title">Welcome to Agri Storage!</div>
                                    <div class="notif-desc">Search verified cold storages across Gujarat with transparent rates and 85% insurance.</div>
                                    <div class="notif-time">Just now</div>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="notif-dropdown-footer">
                        <a href="<?php echo ($user && $user['role'] === 'owner') ? base_url('owner/bookings.php') : base_url('my-bookings.php'); ?>" style="font-size: 0.82rem; font-weight: 600; color: var(--primary);">
                            View All Activity &rarr;
                        </a>
                    </div>
                </div>
            </div>

            <?php if ($user): ?>
                <span class="user-badge role-<?php echo e($user['role']); ?>">
                    <?php if ($user['role'] === 'farmer'): ?>
                        <i class="fa-solid fa-wheat-awn"></i>
                    <?php else: ?>
                        <i class="fa-solid fa-warehouse"></i>
                    <?php endif; ?>
                    <span><?php echo e($user['name']); ?></span>
                </span>
                <a href="<?php echo base_url('logout.php'); ?>" class="btn btn-outline btn-sm">
                    <i class="fa-solid fa-right-from-bracket"></i> Logout
                </a>
            <?php else: ?>
                <a href="<?php echo base_url('login.php'); ?>" class="btn btn-outline-primary btn-sm">
                    <i class="fa-solid fa-arrow-right-to-bracket"></i> Login
                </a>
                <a href="<?php echo base_url('register.php'); ?>" class="btn btn-primary btn-sm">
                    <i class="fa-solid fa-user-plus"></i> Register
                </a>
            <?php endif; ?>
        </div>
    </div>
</header>

<!-- Global Flash Alert Messages -->
<?php $flash = getFlash(); if ($flash): ?>
    <div class="container flash-container">
        <div class="alert alert-<?php echo e($flash['type']); ?>">
            <span>
                <?php if ($flash['type'] === 'success'): ?>
                    <i class="fa-solid fa-circle-check"></i>
                <?php elseif ($flash['type'] === 'danger'): ?>
                    <i class="fa-solid fa-triangle-exclamation"></i>
                <?php elseif ($flash['type'] === 'warning'): ?>
                    <i class="fa-solid fa-triangle-exclamation"></i>
                <?php else: ?>
                    <i class="fa-solid fa-circle-info"></i>
                <?php endif; ?>
            </span>
            <span><?php echo e($flash['message']); ?></span>
        </div>
    </div>
<?php endif; ?>

<main>
