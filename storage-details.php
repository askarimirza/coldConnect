<?php
$pageTitle = "Cold Storage Facility Details - Agri Storage";
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/smart-match.php';

$storageId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($storageId <= 0) {
    setFlash('danger', 'Invalid storage facility selected.');
    header('Location: ' . base_url('search.php'));
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT s.*, u.name as owner_name, u.phone as owner_phone, u.email as owner_email
        FROM cold_storages s
        JOIN users u ON s.owner_id = u.id
        WHERE s.id = ?
        LIMIT 1
    ");
    $stmt->execute([$storageId]);
    $storage = $stmt->fetch();

    if (!$storage) {
        setFlash('danger', 'Storage facility not found.');
        header('Location: ' . base_url('search.php'));
        exit;
    }

    // Maximum capacity across all storage owners
    $maxAvailableAll = (float)$pdo->query("SELECT MAX(available_capacity) FROM cold_storages WHERE status = 'Available'")->fetchColumn() ?: 6000;

} catch (PDOException $e) {
    setFlash('danger', 'Database error: ' . $e->getMessage());
    header('Location: ' . base_url('search.php'));
    exit;
}

// Read search params
$crop     = trim($_GET['crop'] ?? 'Tomato');
$quantity = isset($_GET['quantity']) ? (float)$_GET['quantity'] : 500;
$duration = isset($_GET['duration']) ? (int)$_GET['duration'] : 15;
$location = trim($_GET['location'] ?? 'Ahmedabad');

// Calculate Smart Match if search params available
$matchData = calculateSmartMatch($storage, $crop, $quantity, $duration, $location);

require_once __DIR__ . '/includes/header.php';
?>

<!-- Leaflet.js for Interactive Map -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.js"></script>

<div class="container" style="padding: 2.5rem 1.25rem; max-width: 1050px;">
    <!-- Breadcrumb -->
    <div style="margin-bottom: 1.5rem; font-size: 0.9rem; color: var(--text-muted);">
        <a href="<?php echo base_url('index.php'); ?>">Home</a> &bull; 
        <a href="<?php echo base_url('search.php?crop=' . urlencode($crop) . '&quantity=' . $quantity . '&duration=' . $duration . '&location=' . urlencode($location)); ?>">Search Results</a> &bull; 
        <span style="color: var(--text-dark); font-weight: 600;"><?php echo e($storage['name']); ?></span>
    </div>

    <!-- Main Detail Card -->
    <div class="card" style="padding: 2rem; margin-bottom: 2rem;">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 1rem; border-bottom: 1px solid var(--border-color); padding-bottom: 1.5rem; margin-bottom: 1.5rem;">
            <div>
                <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.5rem; flex-wrap: wrap;">
                    <h1 style="margin: 0; font-size: 2rem;"><?php echo e($storage['name']); ?></h1>
                    <span class="badge badge-accepted"><?php echo e($storage['status']); ?></span>
                </div>
                <div class="storage-location" style="font-size: 1.05rem;">
                    <span><i class="fa-solid fa-location-dot"></i></span>
                    <span><?php echo e($storage['location']); ?> &bull; <strong>~<?php echo e($matchData['distance_km']); ?> km from <?php echo e($location); ?></strong></span>
                </div>
            </div>

            <!-- Smart Match Score Display -->
            <div class="match-pill match-high" style="padding: 0.75rem 1.5rem; min-width: 120px;">
                <span class="match-score-value" style="font-size: 2rem;"><?php echo $matchData['score']; ?>%</span>
                <span class="match-score-label">Smart Match</span>
            </div>
        </div>

        <!-- Explanation Alert -->
        <div class="match-explanation" style="font-size: 0.95rem; padding: 0.85rem 1.25rem;">
            <strong>Smart Match Reasoning:</strong> <?php echo e($matchData['explanation']); ?>
        </div>

        <!-- Owner-Backed Insurance Protection Callout -->
        <div class="insurance-banner" style="background: #ecfdf5; border: 1.5px solid #10b981; border-radius: var(--radius-md); padding: 1rem 1.25rem; margin-top: 1.25rem; display: flex; align-items: center; justify-content: space-between; gap: 1rem; flex-wrap: wrap;">
            <div style="display: flex; align-items: center; gap: 0.85rem;">
                <div style="font-size: 2rem; color: #059669;"><i class="fa-solid fa-shield-halved"></i></div>
                <div>
                    <strong style="color: #065f46; font-size: 1.02rem;">Owner-Backed 85% Crop Safety Insurance Available</strong>
                    <p style="margin: 0.2rem 0 0; font-size: 0.85rem; color: #334155;">
                        This facility offers optional 85% financial compensation against compressor failure, chamber temperature breakdown, or mold decay.
                    </p>
                </div>
            </div>
            <span class="badge badge-accepted" style="font-weight: 700; font-size: 0.82rem;">85% Coverage Guarantee</span>
        </div>

        <!-- Capacity Visual Progress Meter -->
        <?php 
            $capacityPercent = round(($storage['available_capacity'] / $storage['total_capacity']) * 100);
            $meterColor = ($capacityPercent > 40) ? 'var(--primary)' : (($capacityPercent > 15) ? 'var(--accent-gold)' : 'var(--status-rejected)');
        ?>
        <div style="margin: 1.5rem 0;">
            <div style="display: flex; justify-content: space-between; font-size: 0.9rem; margin-bottom: 0.4rem;">
                <span style="font-weight: 600;">Available Warehouse Capacity</span>
                <span><strong><?php echo number_format($storage['available_capacity']); ?> kg</strong> of <?php echo number_format($storage['total_capacity']); ?> kg (<?php echo $capacityPercent; ?>% free)</span>
            </div>
            <div style="height: 12px; background: #e2e8f0; border-radius: 9999px; overflow: hidden;">
                <div style="width: <?php echo $capacityPercent; ?>%; height: 100%; background: <?php echo $meterColor; ?>; border-radius: 9999px; transition: width 0.4s ease;"></div>
            </div>
        </div>

        <!-- Details Grid -->
        <div class="grid-2" style="gap: 1.5rem; margin-top: 1.5rem;">
            <div>
                <h3>Facility Specifications</h3>
                <div class="storage-specs" style="grid-template-columns: 1fr;">
                    <div class="spec-item" style="margin-bottom: 0.75rem;">
                        <span class="spec-label">Chamber Temperature Range</span>
                        <span class="spec-value" style="font-size: 1.1rem;"><i class="fa-solid fa-temperature-half"></i> <?php echo e($storage['temperature']); ?></span>
                    </div>
                    <div class="spec-item" style="margin-bottom: 0.75rem;">
                        <span class="spec-label">Daily Rental Rate</span>
                        <span class="spec-value" style="font-size: 1.1rem; color: var(--primary-dark);"><i class="fa-solid fa-indian-rupee-sign"></i> <?php echo number_format($storage['price_per_kg'], 2); ?> per kg / day</span>
                    </div>
                    <div class="spec-item">
                        <span class="spec-label">Minimum Reservation Threshold</span>
                        <span class="spec-value"><i class="fa-solid fa-scale-balanced"></i> 100 kg (Facility threshold: <?php echo number_format($storage['minimum_quantity']); ?> kg)</span>
                    </div>
                </div>

                <div style="margin-top: 1.5rem;">
                    <h3>Compatible &amp; Supported Produce</h3>
                    <div class="crops-tag-container" style="margin-top: 0.5rem;">
                        <?php 
                            $cropsArr = array_map('trim', explode(',', $storage['supported_crops']));
                            foreach ($cropsArr as $c):
                                $isCur = (strtolower($c) === strtolower($crop));
                        ?>
                            <span class="crop-tag <?php echo $isCur ? 'highlight' : ''; ?>" style="font-size: 0.85rem; padding: 0.35rem 0.85rem;">
                                <?php if ($isCur): ?><i class="fa-solid fa-check"></i> <?php endif; ?><?php echo e($c); ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Provider & Contact Information -->
            <div>
                <h3>Facility Management &amp; Contact</h3>
                <div class="card" style="background: #f8fafc; border-color: var(--border-color); margin-top: 0.5rem;">
                    <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1rem;">
                        <div class="brand-icon" style="width: 44px; height: 44px; font-size: 1.25rem; background: var(--secondary-soft); color: var(--secondary);">
                            <i class="fa-solid fa-warehouse"></i>
                        </div>
                        <div>
                            <strong><?php echo e($storage['owner_name']); ?></strong>
                            <div class="text-muted" style="font-size: 0.85rem;">Verified Storage Partner</div>
                        </div>
                    </div>

                    <div style="display: flex; flex-direction: column; gap: 0.5rem; font-size: 0.9rem;">
                        <div><i class="fa-solid fa-phone"></i> <strong>Phone:</strong> <a href="tel:<?php echo e($storage['contact']); ?>"><?php echo e($storage['contact']); ?></a></div>
                        <div><i class="fa-solid fa-envelope"></i> <strong>Email:</strong> <?php echo e($storage['owner_email']); ?></div>
                        <div><i class="fa-solid fa-location-dot"></i> <strong>Facility Address:</strong> <?php echo e($storage['location']); ?></div>
                    </div>
                </div>

                <!-- Match Factor Breakdown -->
                <div style="margin-top: 1.5rem;">
                    <h4>Smart Match Breakdown (100 pts)</h4>
                    <div style="font-size: 0.85rem; color: #475569; display: flex; flex-direction: column; gap: 0.4rem; margin-top: 0.5rem;">
                        <div style="display: flex; justify-content: space-between;">
                            <span>Distance (~<?php echo e($matchData['distance_km']); ?> km)</span>
                            <strong><?php echo $matchData['breakdown']['distance']['score']; ?> / 30 pts</strong>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <span>Competitive Price (₹<?php echo number_format($storage['price_per_kg'], 2); ?>/kg/day)</span>
                            <strong><?php echo $matchData['breakdown']['price']['score']; ?> / 25 pts</strong>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <span>Available Capacity (<?php echo number_format($storage['available_capacity']); ?> kg)</span>
                            <strong><?php echo $matchData['breakdown']['capacity']['score']; ?> / 20 pts</strong>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <span>Crop Compatibility (<?php echo e($crop); ?>)</span>
                            <strong><?php echo $matchData['breakdown']['crop']['score']; ?> / 15 pts</strong>
                        </div>
                        <div style="display: flex; justify-content: space-between;">
                            <span>Temperature Suitability (<?php echo e($storage['temperature']); ?>)</span>
                            <strong><?php echo $matchData['breakdown']['temperature']['score']; ?> / 10 pts</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Interactive Route Map Card -->
        <div class="card" style="margin-top: 1.75rem; background: #f0f9ff; border: 1px solid #bae6fd;">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.75rem; margin-bottom: 0.75rem;">
                <h3 style="margin: 0; color: #0369a1; font-size: 1.15rem;">
                    <i class="fa-solid fa-route"></i> Farm Pickup to Warehouse Route Map
                </h3>
                <div style="display: flex; align-items: center; gap: 0.5rem;">
                    <label for="pickup_select" style="font-size: 0.85rem; font-weight: 600;">Farmer Pickup Hub:</label>
                    <select id="pickup_select" class="form-control" style="width: 170px; height: 36px; padding: 2px 8px;" onchange="updateDetailsRouteMap()">
                        <option value="Ahmedabad" <?php echo ($location === 'Ahmedabad') ? 'selected' : ''; ?>>Ahmedabad</option>
                        <option value="Surat" <?php echo ($location === 'Surat') ? 'selected' : ''; ?>>Surat</option>
                        <option value="Vadodara" <?php echo ($location === 'Vadodara') ? 'selected' : ''; ?>>Vadodara</option>
                        <option value="Rajkot" <?php echo ($location === 'Rajkot') ? 'selected' : ''; ?>>Rajkot</option>
                        <option value="Anand" <?php echo ($location === 'Anand') ? 'selected' : ''; ?>>Anand</option>
                        <option value="Bhavnagar" <?php echo ($location === 'Bhavnagar') ? 'selected' : ''; ?>>Bhavnagar</option>
                        <option value="Mehsana" <?php echo ($location === 'Mehsana') ? 'selected' : ''; ?>>Mehsana</option>
                        <option value="Gandhinagar" <?php echo ($location === 'Gandhinagar') ? 'selected' : ''; ?>>Gandhinagar</option>
                    </select>
                </div>
            </div>
            <div id="details-route-map" style="height: 280px; width: 100%; border-radius: 8px; border: 1px solid #cbd5e1; z-index: 1;"></div>
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.5rem; margin-top: 0.75rem; font-size: 0.88rem;">
                <div id="details-route-meta"><strong>Transit Distance:</strong> ~<?php echo e($matchData['distance_km']); ?> km &bull; ~-- mins via Agro Truck</div>
                <a id="details-gmaps-link" href="#" target="_blank" class="btn btn-outline-primary btn-sm">
                    <i class="fa-solid fa-diamond-turn-right"></i> Turn-by-Turn Navigation
                </a>
            </div>
        </div>
    </div>

    <!-- Live Dynamic Cost Estimator & Booking Box -->
    <div class="cost-calculator-box">
        <h2 style="font-size: 1.5rem; margin-bottom: 0.5rem;"><i class="fa-solid fa-calculator"></i> Live Estimated Cost Calculator</h2>
        <p class="text-muted" style="margin-bottom: 1.25rem;">
            Transparent calculation with zero hidden commissions. Adjust produce weight (min 100 kg up to network maximum <?php echo number_format($maxAvailableAll); ?> kg) and duration.
        </p>

        <div class="form-grid" style="margin-bottom: 1.25rem;">
            <div class="form-group">
                <label for="calc_quantity">Produce Quantity (kg)</label>
                <input type="number" id="calc_quantity" class="form-control" value="<?php echo e($quantity); ?>" min="100" max="<?php echo e($maxAvailableAll); ?>" step="1">
                <span class="text-muted" style="font-size: 0.78rem;">Min: 100 kg &bull; Facility Free Space: <?php echo number_format($storage['available_capacity']); ?> kg (Network Max: <?php echo number_format($maxAvailableAll); ?> kg)</span>
            </div>

            <div class="form-group">
                <label for="calc_days">Duration (Days)</label>
                <input type="number" id="calc_days" class="form-control" value="<?php echo e($duration); ?>" min="1" max="180">
                <span class="text-muted" style="font-size: 0.78rem;">Recommended for <?php echo e($crop); ?>: 10 - 30 days</span>
            </div>

            <div class="form-group">
                <label>Unit Storage Rate</label>
                <input type="text" class="form-control" value="₹<?php echo number_format($storage['price_per_kg'], 2); ?> / kg / day" readonly style="background: #e2e8f0;">
                <input type="hidden" id="calc_price" value="<?php echo e($storage['price_per_kg']); ?>">
            </div>
        </div>

        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 1rem; border-top: 1px solid #a7f3d0; padding-top: 1.25rem;">
            <div>
                <span style="font-size: 0.85rem; text-transform: uppercase; font-weight: 700; color: #047857;">Estimated Total Rental Cost:</span>
                <div class="cost-display">
                    <span id="estimated_cost_display" class="cost-amount" data-price="<?php echo e($storage['price_per_kg']); ?>">₹0</span>
                </div>
                <div id="cost_formula_display" class="cost-formula"></div>
            </div>

            <div>
                <a id="book-btn-link" href="<?php echo base_url('booking.php?storage_id=' . $storage['id'] . '&crop=' . urlencode($crop) . '&quantity=' . $quantity . '&duration=' . $duration . '&pickup_location=' . urlencode($location)); ?>" class="btn btn-primary btn-lg">
                    <i class="fa-solid fa-calendar-check"></i> Book This Storage Facility
                </a>
            </div>
        </div>
    </div>
</div>

<script>
const HUB_COORDS = {
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

const stLoc = '<?php echo addslashes($storage['location']); ?>';
const stCoords = HUB_COORDS[stLoc] || [23.0225, 72.5714];

let detailsMap, dPickupMarker, dStorageMarker, dPolyline;

function initDetailsMap() {
    const mapDiv = document.getElementById('details-route-map');
    if (!mapDiv) return;

    detailsMap = L.map('details-route-map').setView(stCoords, 9);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(detailsMap);

    dStorageMarker = L.marker(stCoords).addTo(detailsMap)
        .bindPopup('<strong><?php echo addslashes($storage['name']); ?></strong><br>Storage Destination')
        .openPopup();

    updateDetailsRouteMap();
}

function updateDetailsRouteMap() {
    if (!detailsMap) return;

    const sel = document.getElementById('pickup_select');
    const pName = sel ? sel.value : 'Ahmedabad';
    const pCoords = HUB_COORDS[pName] || [23.0225, 72.5714];

    if (dPickupMarker) detailsMap.removeLayer(dPickupMarker);
    if (dPolyline) detailsMap.removeLayer(dPolyline);

    dPickupMarker = L.marker(pCoords).addTo(detailsMap)
        .bindPopup(`<strong>Farmer Pickup Hub</strong><br>${pName}`);

    dPolyline = L.polyline([pCoords, stCoords], {
        color: '#0284c7',
        weight: 4,
        dashArray: '6, 8'
    }).addTo(detailsMap);

    detailsMap.fitBounds(L.latLngBounds([pCoords, stCoords]), { padding: [35, 35] });

    // Distance computation
    const R = 6371;
    const dLat = (stCoords[0] - pCoords[0]) * Math.PI / 180;
    const dLon = (stCoords[1] - pCoords[1]) * Math.PI / 180;
    const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
              Math.cos(pCoords[0] * Math.PI / 180) * Math.cos(stCoords[0] * Math.PI / 180) *
              Math.sin(dLon/2) * Math.sin(dLon/2);
    const dist = Math.max(8, Math.round(R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a))));
    const time = Math.round((dist / 45) * 60);

    const meta = document.getElementById('details-route-meta');
    const gLink = document.getElementById('details-gmaps-link');
    if (meta) meta.innerHTML = `<strong>Transit Distance:</strong> ~${dist} km &bull; <strong>Travel Time:</strong> ~${time} mins (Agro Truck)`;
    if (gLink) gLink.href = `https://www.google.com/maps/dir/?api=1&origin=${encodeURIComponent(pName + ', Gujarat')}&destination=${encodeURIComponent(stLoc + ', Gujarat')}`;

    syncBookingLink();
}

function syncBookingLink() {
    const qIn = document.getElementById('calc_quantity');
    const dIn = document.getElementById('calc_days');
    const sel = document.getElementById('pickup_select');
    const bookLink = document.getElementById('book-btn-link');

    if (!bookLink) return;
    const q = qIn ? qIn.value : '<?php echo $quantity; ?>';
    const d = dIn ? dIn.value : '<?php echo $duration; ?>';
    const pLoc = sel ? sel.value : '<?php echo $location; ?>';

    bookLink.href = '<?php echo base_url('booking.php'); ?>?storage_id=<?php echo $storage['id']; ?>&crop=<?php echo urlencode($crop); ?>&quantity=' + encodeURIComponent(q) + '&duration=' + encodeURIComponent(d) + '&pickup_location=' + encodeURIComponent(pLoc);
}

document.addEventListener('DOMContentLoaded', () => {
    initDetailsMap();

    const qIn = document.getElementById('calc_quantity');
    const dIn = document.getElementById('calc_days');

    if (qIn) qIn.addEventListener('input', syncBookingLink);
    if (dIn) dIn.addEventListener('input', syncBookingLink);
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
