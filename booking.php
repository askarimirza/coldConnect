<?php
$pageTitle = "Reserve Cold Storage - Agri Storage";
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

// Must be logged in as a farmer
requireFarmer();
$user = currentUser();

$storageId = isset($_REQUEST['storage_id']) ? (int)$_REQUEST['storage_id'] : 0;

if ($storageId <= 0) {
    setFlash('danger', 'Please choose a valid cold storage facility.');
    header('Location: ' . base_url('search.php'));
    exit;
}

// Fetch storage details
try {
    $stmt = $pdo->prepare("SELECT * FROM cold_storages WHERE id = ? AND status = 'Available' LIMIT 1");
    $stmt->execute([$storageId]);
    $storage = $stmt->fetch();

    if (!$storage) {
        setFlash('danger', 'Selected storage facility is not currently available for booking.');
        header('Location: ' . base_url('search.php'));
        exit;
    }

    // Get maximum capacity available from all facilities
    $maxAvailableAll = (float)$pdo->query("SELECT MAX(available_capacity) FROM cold_storages WHERE status = 'Available'")->fetchColumn() ?: 6000;

} catch (PDOException $e) {
    setFlash('danger', 'Database error: ' . $e->getMessage());
    header('Location: ' . base_url('search.php'));
    exit;
}

// Defaults / pre-filled values
$crop           = trim($_REQUEST['crop'] ?? 'Tomato');
$quantity       = isset($_REQUEST['quantity']) ? (float)$_REQUEST['quantity'] : 500;
$duration       = isset($_REQUEST['duration']) ? (int)$_REQUEST['duration'] : 15;
$startDate      = trim($_REQUEST['start_date'] ?? date('Y-m-d'));
$endDate        = trim($_REQUEST['end_date'] ?? date('Y-m-d', strtotime("+{$duration} days")));
$pickupLocation = trim($_REQUEST['pickup_location'] ?? ($user['location'] ?? 'Ahmedabad'));
$hasInsurance   = isset($_REQUEST['has_insurance']) ? (int)$_REQUEST['has_insurance'] : 1;

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_booking'])) {
    $crop           = trim($_POST['crop'] ?? '');
    $quantity       = (float)($_POST['quantity'] ?? 0);
    $startDate      = trim($_POST['start_date'] ?? '');
    $endDate        = trim($_POST['end_date'] ?? '');
    $pickupLocation = trim($_POST['pickup_location'] ?? 'Ahmedabad');
    $hasInsurance   = isset($_POST['has_insurance']) ? 1 : 0;
    $insuranceRate  = 85.00; // 85% Compensation

    // Calculate duration from dates
    $sTime = strtotime($startDate);
    $eTime = strtotime($endDate);

    if ($sTime === false || $eTime === false) {
        $error = 'Invalid start or end date selected.';
    } elseif ($sTime < strtotime('today')) {
        $error = 'Reservation start date cannot be in the past.';
    } elseif ($eTime <= $sTime) {
        $error = 'End date must be after the start date.';
    } elseif ($quantity < 100) {
        $error = 'Minimum reservation quantity is 100 kg.';
    } elseif ($quantity > $storage['available_capacity']) {
        $error = "Requested quantity ({$quantity} kg) exceeds this facility's currently available capacity (" . number_format($storage['available_capacity']) . " kg).";
    } else {
        $days = (int)ceil(($eTime - $sTime) / 86400);
        $unitPrice = (float)$storage['price_per_kg'];
        $baseCost = $quantity * $unitPrice * $days;
        
        // 5% insurance premium fee for 85% compensation coverage
        $insuranceFee = $hasInsurance ? round($baseCost * 0.05, 2) : 0.00;
        $totalCost = $baseCost + $insuranceFee;

        try {
            $insertStmt = $pdo->prepare("
                INSERT INTO bookings (farmer_id, storage_id, pickup_location, crop, quantity, start_date, end_date, total_cost, has_insurance, insurance_rate, insurance_fee, status, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending', NOW())
            ");
            $insertStmt->execute([
                $user['id'],
                $storage['id'],
                $pickupLocation,
                $crop,
                $quantity,
                $startDate,
                $endDate,
                $totalCost,
                $hasInsurance,
                $insuranceRate,
                $insuranceFee
            ]);

            setFlash('success', 'Booking request submitted successfully! ' . ($hasInsurance ? 'Your 85% Crop Spoilage Insurance Guarantee is active.' : ''));
            header('Location: ' . base_url('my-bookings.php'));
            exit;
        } catch (PDOException $e) {
            $error = 'Failed to submit booking: ' . $e->getMessage();
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<!-- Leaflet.js for Interactive Route Map -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.js"></script>

<div class="container" style="padding: 2.5rem 1.25rem; max-width: 860px;">
    <div class="card" style="padding: 2.5rem 2rem; border-top: 4px solid var(--primary);">
        <div style="margin-bottom: 2rem;">
            <h2>Confirm Cold Storage Reservation</h2>
            <p class="text-muted">
                Review your produce requirements, pick-up location, route map, and select owner-backed crop insurance for <strong><?php echo e($storage['name']); ?></strong>.
            </p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger">
                <span><i class="fa-solid fa-triangle-exclamation"></i></span>
                <span><?php echo e($error); ?></span>
            </div>
        <?php endif; ?>

        <!-- Storage Summary Box -->
        <div style="background: #f8fafc; border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 1.25rem; margin-bottom: 1.5rem;">
            <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 0.5rem; flex-wrap: wrap; gap: 0.5rem;">
                <h3 style="margin: 0; font-size: 1.25rem;"><?php echo e($storage['name']); ?></h3>
                <span style="font-weight: 700; color: var(--primary-dark); font-size: 1.1rem;">₹<?php echo number_format($storage['price_per_kg'], 2); ?> /kg/day</span>
            </div>
            <div class="text-muted" style="font-size: 0.9rem;">
                <i class="fa-solid fa-location-dot"></i> <?php echo e($storage['location']); ?> &bull; <i class="fa-solid fa-temperature-half"></i> <?php echo e($storage['temperature']); ?> &bull; <i class="fa-solid fa-boxes-stacked"></i> Free Space: <?php echo number_format($storage['available_capacity']); ?> kg (Capacity across network up to <?php echo number_format($maxAvailableAll); ?> kg)
            </div>
        </div>

        <form method="POST" action="<?php echo base_url('booking.php'); ?>" id="booking-form">
            <input type="hidden" name="storage_id" value="<?php echo $storage['id']; ?>">
            <input type="hidden" id="calc_price" value="<?php echo e($storage['price_per_kg']); ?>">

            <!-- Location & Route Section -->
            <div style="background: #f0f9ff; border: 1px solid #bae6fd; border-radius: var(--radius-md); padding: 1.25rem; margin-bottom: 1.5rem;">
                <h4 style="color: #0369a1; margin-top: 0; margin-bottom: 0.5rem; display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fa-solid fa-route"></i> Farmer Pickup Location &amp; Storage Route
                </h4>
                <p class="text-muted" style="font-size: 0.85rem; margin-bottom: 1rem;">
                    Select your farm's pickup hub to preview transit distance, travel time, and turn-by-turn route to the storage facility.
                </p>

                <div class="form-group" style="margin-bottom: 1rem;">
                    <label for="pickup_location" style="font-weight: 600;">Farmer Pickup Hub / Drop City *</label>
                    <select name="pickup_location" id="pickup_location" class="form-control" required onchange="updateRouteMap()">
                        <option value="Ahmedabad" <?php echo ($pickupLocation === 'Ahmedabad') ? 'selected' : ''; ?>>Ahmedabad</option>
                        <option value="Surat" <?php echo ($pickupLocation === 'Surat') ? 'selected' : ''; ?>>Surat</option>
                        <option value="Vadodara" <?php echo ($pickupLocation === 'Vadodara') ? 'selected' : ''; ?>>Vadodara</option>
                        <option value="Rajkot" <?php echo ($pickupLocation === 'Rajkot') ? 'selected' : ''; ?>>Rajkot</option>
                        <option value="Anand" <?php echo ($pickupLocation === 'Anand') ? 'selected' : ''; ?>>Anand</option>
                        <option value="Bhavnagar" <?php echo ($pickupLocation === 'Bhavnagar') ? 'selected' : ''; ?>>Bhavnagar</option>
                        <option value="Mehsana" <?php echo ($pickupLocation === 'Mehsana') ? 'selected' : ''; ?>>Mehsana</option>
                        <option value="Gandhinagar" <?php echo ($pickupLocation === 'Gandhinagar') ? 'selected' : ''; ?>>Gandhinagar</option>
                    </select>
                </div>

                <!-- Interactive Route Map -->
                <div id="route-map" style="height: 280px; width: 100%; border-radius: 8px; border: 1px solid #cbd5e1; z-index: 1;"></div>

                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.75rem; margin-top: 0.75rem;">
                    <div style="font-size: 0.88rem; color: #0f172a;">
                        <span id="route-dist-text"><strong>Distance:</strong> Calculating...</span> &bull; 
                        <span id="route-time-text"><strong>Transit Time:</strong> ~-- mins (Agro Truck)</span>
                    </div>
                    <a id="gmaps-link" href="#" target="_blank" class="btn btn-outline-primary btn-sm">
                        <i class="fa-solid fa-diamond-turn-right"></i> Open in Google Maps
                    </a>
                </div>
            </div>

            <!-- Produce & Schedule -->
            <div class="grid-2" style="margin-bottom: 1.25rem;">
                <div class="form-group">
                    <label for="crop">Crop Variety *</label>
                    <select name="crop" id="crop" class="form-control" required>
                        <option value="Tomato" <?php echo ($crop === 'Tomato') ? 'selected' : ''; ?>>Tomato</option>
                        <option value="Potato" <?php echo ($crop === 'Potato') ? 'selected' : ''; ?>>Potato</option>
                        <option value="Onion"  <?php echo ($crop === 'Onion')  ? 'selected' : ''; ?>>Onion</option>
                        <option value="Apple"  <?php echo ($crop === 'Apple')  ? 'selected' : ''; ?>>Apple</option>
                        <option value="Mango"  <?php echo ($crop === 'Mango')  ? 'selected' : ''; ?>>Mango</option>
                        <option value="Carrot" <?php echo ($crop === 'Carrot') ? 'selected' : ''; ?>>Carrot</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="quantity">Quantity (kg) *</label>
                    <input type="number" name="quantity" id="quantity" class="form-control" 
                           value="<?php echo e($quantity); ?>" 
                           min="100" 
                           max="<?php echo e($maxAvailableAll); ?>" 
                           step="1" 
                           required>
                    <span class="text-muted" style="font-size: 0.78rem;">
                        Min: 100 kg &bull; Facility Free Space: <?php echo number_format($storage['available_capacity']); ?> kg (Network Max: <?php echo number_format($maxAvailableAll); ?> kg)
                    </span>
                </div>
            </div>

            <div class="grid-2" style="margin-bottom: 1.5rem;">
                <div class="form-group">
                    <label for="start_date">Storage Start Date *</label>
                    <input type="date" name="start_date" id="start_date" class="form-control" value="<?php echo e($startDate); ?>" required>
                </div>

                <div class="form-group">
                    <label for="end_date">Storage End Date *</label>
                    <input type="date" name="end_date" id="end_date" class="form-control" value="<?php echo e($endDate); ?>" required>
                </div>
            </div>

            <!-- Crop Protection Insurance Addon Option -->
            <div class="insurance-addon-card" style="background: #f0fdf4; border: 2px solid var(--primary); border-radius: var(--radius-md); padding: 1.25rem; margin-bottom: 1.75rem;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 1rem; flex-wrap: wrap;">
                    <div style="display: flex; gap: 0.85rem; max-width: 600px;">
                        <div style="font-size: 2rem; color: var(--primary);">
                            <i class="fa-solid fa-shield-halved"></i>
                        </div>
                        <div>
                            <div style="display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap;">
                                <h4 style="margin: 0; color: var(--text-dark); font-size: 1.1rem;">Owner-Backed Crop Protection Insurance</h4>
                                <span class="badge badge-accepted" style="font-size: 0.75rem; font-weight: 700;">85% Compensation Guarantee</span>
                            </div>
                            <p style="margin: 0.35rem 0 0; font-size: 0.88rem; color: #334155; line-height: 1.5;">
                                Protect your harvest against unexpected cooling compressor breakdown, prolonged power outages, mold decay, or chamber temperature failures. If damage occurs, the storage owner compensates <strong>85% of your produce value</strong>.
                            </p>
                            <div style="margin-top: 0.5rem; font-size: 0.85rem; color: var(--primary-dark); font-weight: 600;">
                                <i class="fa-solid fa-circle-check"></i> Protection Premium: +5% of storage charge (<span id="insurance_charge_label">₹0</span>)
                            </div>
                        </div>
                    </div>
                    <div style="text-align: right;">
                        <label style="display: inline-flex; align-items: center; cursor: pointer; gap: 0.5rem; user-select: none; background: white; padding: 0.5rem 0.85rem; border-radius: 8px; border: 1px solid #cbd5e1;">
                            <input type="checkbox" name="has_insurance" id="insurance_toggle" value="1" <?php echo $hasInsurance ? 'checked' : ''; ?> style="width: 20px; height: 20px; accent-color: var(--primary); cursor: pointer;" onchange="updateEstimatedCost()">
                            <span style="font-weight: 700; font-size: 0.95rem; color: var(--primary-dark);">Add 85% Insurance</span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- Dynamic Estimated Cost Display -->
            <div class="cost-calculator-box" style="margin-bottom: 1.75rem;">
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.5rem; margin-bottom: 0.75rem;">
                    <div style="font-size: 0.85rem; text-transform: uppercase; font-weight: 700; color: #047857;">Estimated Total Payable</div>
                    <div id="cost_formula_display" class="cost-formula"></div>
                </div>

                <div style="display: flex; flex-direction: column; gap: 0.4rem; font-size: 0.9rem; margin-bottom: 0.75rem; padding-bottom: 0.75rem; border-bottom: 1px dashed #cbd5e1;">
                    <div style="display: flex; justify-content: space-between;">
                        <span>Base Refrigerated Storage Fee:</span>
                        <strong id="base_cost_display">₹0</strong>
                    </div>
                    <div style="display: flex; justify-content: space-between; color: var(--primary-dark);">
                        <span><i class="fa-solid fa-shield-halved"></i> Crop Protection Guarantee (85% Coverage):</span>
                        <strong id="insurance_cost_display">₹0</strong>
                    </div>
                </div>

                <div class="cost-display">
                    <span id="estimated_cost_display" class="cost-amount" data-price="<?php echo e($storage['price_per_kg']); ?>">₹0</span>
                </div>
                <div style="font-size: 0.82rem; color: var(--text-muted); margin-top: 0.5rem;">
                    <i class="fa-solid fa-circle-info"></i> Zero advance deposit needed online. Fee is settled directly with the facility manager upon produce delivery and inspection.
                </div>
            </div>

            <div style="display: flex; justify-content: space-between; align-items: center; gap: 1rem; flex-wrap: wrap;">
                <a href="<?php echo base_url('storage-details.php?id=' . $storage['id']); ?>" class="btn btn-outline">
                    &larr; Back to Facility Details
                </a>
                <button type="submit" name="confirm_booking" value="1" class="btn btn-primary btn-lg">
                    <i class="fa-solid fa-calendar-check"></i> Submit Reservation Request
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Coordinates dictionary for Gujarat Agricultural Hubs
const HUB_COORDINATES = {
    'Ahmedabad':   [23.0225, 72.5714],
    'Surat':       [21.1702, 72.8311],
    'Vadodara':    [22.3072, 73.1812],
    'Rajkot':      [22.3039, 70.8022],
    'Anand':       [22.5645, 72.9289],
    'Bhavnagar':   [21.7645, 72.1519],
    'Mehsana':     [23.5880, 72.3693],
    'Gandhinagar': [23.2156, 72.6369],
    'Sanand':      [22.9927, 72.3804],
    'Bopal':       [23.0345, 72.4647],
    'Naroda':      [23.0722, 72.6583],
    'Chandkheda':  [23.1118, 72.5937]
};

const storageLocationName = '<?php echo addslashes($storage['location']); ?>';
const storageCoords = HUB_COORDINATES[storageLocationName] || [23.0225, 72.5714];

let map, pickupMarker, storageMarker, routePolyline;

function initRouteMap() {
    const mapDiv = document.getElementById('route-map');
    if (!mapDiv) return;

    map = L.map('route-map').setView(storageCoords, 9);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    storageMarker = L.marker(storageCoords).addTo(map)
        .bindPopup('<strong><?php echo addslashes($storage['name']); ?></strong><br>Cold Storage Facility')
        .openPopup();

    updateRouteMap();
}

function calculateDistanceKm(lat1, lon1, lat2, lon2) {
    const R = 6371; // Earth radius in km
    const dLat = (lat2 - lat1) * Math.PI / 180;
    const dLon = (lon2 - lon1) * Math.PI / 180;
    const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
              Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
              Math.sin(dLon/2) * Math.sin(dLon/2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
    return Math.max(8, Math.round(R * c)); // minimum 8 km for local intra-city
}

function updateRouteMap() {
    if (!map) return;

    const pickupSelect = document.getElementById('pickup_location');
    const pickupName = pickupSelect ? pickupSelect.value : 'Ahmedabad';
    const pickupCoords = HUB_COORDINATES[pickupName] || [23.0225, 72.5714];

    if (pickupMarker) map.removeLayer(pickupMarker);
    if (routePolyline) map.removeLayer(routePolyline);

    pickupMarker = L.marker(pickupCoords).addTo(map)
        .bindPopup(`<strong>Farmer Pickup Point</strong><br>${pickupName}`);

    // Draw route line
    routePolyline = L.polyline([pickupCoords, storageCoords], {
        color: '#059669',
        weight: 4,
        dashArray: '6, 8'
    }).addTo(map);

    const bounds = L.latLngBounds([pickupCoords, storageCoords]);
    map.fitBounds(bounds, { padding: [40, 40] });

    // Distance and transit time calculations
    const distKm = calculateDistanceKm(pickupCoords[0], pickupCoords[1], storageCoords[0], storageCoords[1]);
    const truckSpeed = 45; // average 45 km/h for agricultural freight
    const timeMins = Math.round((distKm / truckSpeed) * 60);

    const distText = document.getElementById('route-dist-text');
    const timeText = document.getElementById('route-time-text');
    const gmapsLink = document.getElementById('gmaps-link');

    if (distText) distText.innerHTML = `<strong>Transit Distance:</strong> ~${distKm} km`;
    if (timeText) timeText.innerHTML = `<strong>Estimated Travel Time:</strong> ~${timeMins} mins (Agro Freight)`;
    if (gmapsLink) {
        gmapsLink.href = `https://www.google.com/maps/dir/?api=1&origin=${encodeURIComponent(pickupName + ', Gujarat')}&destination=${encodeURIComponent(storageLocationName + ', Gujarat')}`;
    }
}

// Live Cost Estimator with 85% Insurance calculation
function updateEstimatedCost() {
    const qtyInput = document.getElementById('quantity');
    const startDateInput = document.getElementById('start_date');
    const endDateInput = document.getElementById('end_date');
    const insuranceToggle = document.getElementById('insurance_toggle');

    const qty = parseFloat(qtyInput ? qtyInput.value : 0) || 0;
    const unitPrice = parseFloat('<?php echo $storage['price_per_kg']; ?>') || 0;

    let days = 15;
    if (startDateInput && endDateInput && startDateInput.value && endDateInput.value) {
        const s = new Date(startDateInput.value);
        const e = new Date(endDateInput.value);
        const diff = Math.ceil((e - s) / (1000 * 60 * 60 * 24));
        if (diff > 0) days = diff;
    }

    const baseCost = qty * unitPrice * days;
    const isInsured = insuranceToggle ? insuranceToggle.checked : false;
    const insuranceFee = isInsured ? Math.round(baseCost * 0.05) : 0;
    const totalCost = baseCost + insuranceFee;

    const baseDisp = document.getElementById('base_cost_display');
    const insDisp = document.getElementById('insurance_cost_display');
    const insLabel = document.getElementById('insurance_charge_label');
    const totalDisp = document.getElementById('estimated_cost_display');
    const formulaDisp = document.getElementById('cost_formula_display');

    if (baseDisp) baseDisp.textContent = `₹${baseCost.toLocaleString('en-IN')}`;
    if (insDisp) insDisp.textContent = isInsured ? `+ ₹${insuranceFee.toLocaleString('en-IN')} (85% Coverage)` : '₹0 (Uninsured)';
    if (insLabel) insLabel.textContent = `₹${Math.round(baseCost * 0.05).toLocaleString('en-IN')}`;
    if (totalDisp) totalDisp.textContent = `₹${totalCost.toLocaleString('en-IN')}`;
    if (formulaDisp) {
        formulaDisp.textContent = `${qty} kg × ₹${unitPrice.toFixed(2)}/kg/day × ${days} days${isInsured ? ' + 5% Crop Insurance' : ''}`;
    }
}

document.addEventListener('DOMContentLoaded', () => {
    initRouteMap();
    updateEstimatedCost();

    const qty = document.getElementById('quantity');
    const sDate = document.getElementById('start_date');
    const eDate = document.getElementById('end_date');

    if (qty) qty.addEventListener('input', updateEstimatedCost);
    if (sDate) sDate.addEventListener('change', updateEstimatedCost);
    if (eDate) eDate.addEventListener('change', updateEstimatedCost);
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
