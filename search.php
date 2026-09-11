<?php
$pageTitle = "Find Cold Storage Facilities";
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/smart-match.php';

$user = currentUser();

// Read query parameters
$crop           = trim($_GET['crop'] ?? '');
$quantity       = isset($_GET['quantity']) ? (float)$_GET['quantity'] : 0;
$duration       = isset($_GET['duration']) ? (int)$_GET['duration'] : 0;
$location       = trim($_GET['location'] ?? ($user['location'] ?? 'Ahmedabad'));
$maxPriceFilter = isset($_GET['max_price']) && $_GET['max_price'] !== '' ? (float)$_GET['max_price'] : null;

$maxAvailableAll = (float)$pdo->query("SELECT MAX(available_capacity) FROM cold_storages WHERE status = 'Available'")->fetchColumn() ?: 10000;

$hasSearched = !empty($crop) || $quantity > 0 || $duration > 0;
$validationErrors = [];
$results = [];

if ($hasSearched) {
    // Validation
    if (empty($crop)) {
        $validationErrors[] = 'Please select a crop type.';
    }
    if ($quantity < 100) {
        $validationErrors[] = 'Quantity must be at least 100 kg.';
    }
    if ($quantity > $maxAvailableAll) {
        $validationErrors[] = 'Requested quantity exceeds the maximum network capacity (' . number_format($maxAvailableAll) . ' kg).';
    }
    if ($duration <= 0) {
        $validationErrors[] = 'Storage duration must be at least 1 day.';
    }
    if (empty($location)) {
        $validationErrors[] = 'Please provide your farm or transit location.';
    }

    if (empty($validationErrors)) {
        try {
            // Query available cold storages
            $sql = "
                SELECT s.*, u.name as owner_name 
                FROM cold_storages s
                JOIN users u ON s.owner_id = u.id
                WHERE s.status = 'Available'
                  AND s.available_capacity >= :qty
            ";
            $params = [':qty' => $quantity];

            if ($maxPriceFilter !== null && $maxPriceFilter > 0) {
                $sql .= " AND s.price_per_kg <= :max_price";
                $params[':max_price'] = $maxPriceFilter;
            }

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $allStorages = $stmt->fetchAll();

            // Filter for crop compatibility and compute Smart Match score
            $cropLower = strtolower($crop);

            foreach ($allStorages as $st) {
                $supportedList = array_map('trim', explode(',', strtolower($st['supported_crops'])));

                // Check crop compatibility: either exact match in comma-separated list, or partial
                $isCompatible = false;
                if ($cropLower === 'other') {
                    $isCompatible = true;
                } else {
                    foreach ($supportedList as $sup) {
                        if (strpos($sup, $cropLower) !== false || strpos($cropLower, $sup) !== false) {
                            $isCompatible = true;
                            break;
                        }
                    }
                }

                // If crop is supported, evaluate Smart Match score
                if ($isCompatible) {
                    $matchData = calculateSmartMatch($st, $crop, $quantity, $duration, $location);
                    $st['smart_match'] = $matchData['score'];
                    $st['distance_km'] = $matchData['distance_km'];
                    $st['match_explanation'] = $matchData['explanation'];
                    $st['match_breakdown'] = $matchData['breakdown'];
                    $st['estimated_cost'] = $quantity * $st['price_per_kg'] * $duration;

                    $results[] = $st;
                }
            }

            // Sort results by Smart Match score descending
            usort($results, function($a, $b) {
                if ($a['smart_match'] === $b['smart_match']) {
                    return $a['price_per_kg'] <=> $b['price_per_kg'];
                }
                return $b['smart_match'] <=> $a['smart_match'];
            });

        } catch (PDOException $e) {
            $validationErrors[] = 'Failed to fetch storages from database: ' . $e->getMessage();
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="padding: 2.5rem 1.25rem;">
    <!-- Page Title & Header -->
    <div style="margin-bottom: 2rem;">
        <h1>Find Available Cold Storage</h1>
        <p class="text-muted">
            Locate compatible, nearby cold storage facilities ranked by our transparent 5-factor Smart Match engine.
        </p>
    </div>

    <!-- Search / Filter Card -->
    <div class="card" style="margin-bottom: 2.5rem; padding: 2rem; border-top: 4px solid var(--primary);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem; flex-wrap: wrap; gap: 0.5rem;">
            <h3 style="margin: 0;">Produce & Storage Requirements</h3>
            <button type="button" id="demo-fill-btn" class="btn btn-outline-primary btn-sm">
                <i class="fa-solid fa-wand-magic-sparkles"></i> Fill Standard Demo (Tomato 500kg, 15 Days)
            </button>
        </div>

        <?php if (!empty($validationErrors)): ?>
            <div class="alert alert-danger">
                <div>
                    <strong>Please review the following errors:</strong>
                    <ul style="margin-top: 0.25rem; padding-left: 1.25rem;">
                        <?php foreach ($validationErrors as $err): ?>
                            <li><?php echo e($err); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        <?php endif; ?>

        <form action="<?php echo base_url('search.php'); ?>" method="GET">
            <div class="form-grid">
                <div class="form-group">
                    <label for="crop">Crop Variety *</label>
                    <select name="crop" id="crop" class="form-control" required>
                        <option value="">-- Select Crop --</option>
                        <option value="Tomato" <?php echo ($crop === 'Tomato') ? 'selected' : ''; ?>>Tomato</option>
                        <option value="Potato" <?php echo ($crop === 'Potato') ? 'selected' : ''; ?>>Potato</option>
                        <option value="Onion"  <?php echo ($crop === 'Onion')  ? 'selected' : ''; ?>>Onion</option>
                        <option value="Apple"  <?php echo ($crop === 'Apple')  ? 'selected' : ''; ?>>Apple</option>
                        <option value="Mango"  <?php echo ($crop === 'Mango')  ? 'selected' : ''; ?>>Mango</option>
                        <option value="Carrot" <?php echo ($crop === 'Carrot') ? 'selected' : ''; ?>>Carrot</option>
                        <option value="Other"  <?php echo ($crop === 'Other')  ? 'selected' : ''; ?>>Other Produce</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="quantity">Quantity (kg) *</label>
                    <input type="number" name="quantity" id="quantity" class="form-control" value="<?php echo $quantity >= 100 ? e($quantity) : '500'; ?>" min="100" max="<?php echo e($maxAvailableAll); ?>" step="1" placeholder="Min 100 kg" required>
                    <small class="text-muted" style="font-size: 0.75rem;">Min: 100 kg &bull; Network Max: <?php echo number_format($maxAvailableAll); ?> kg</small>
                </div>

                <div class="form-group">
                    <label for="duration">Storage Duration (Days) *</label>
                    <input type="number" name="duration" id="duration" class="form-control" value="<?php echo $duration > 0 ? e($duration) : '15'; ?>" min="1" placeholder="e.g. 15" required>
                </div>

                <div class="form-group">
                    <label for="location">Farmer Pickup Location *</label>
                    <select name="location" id="location" class="form-control" required>
                        <option value="Ahmedabad" <?php echo ($location === 'Ahmedabad' || empty($location)) ? 'selected' : ''; ?>>Ahmedabad</option>
                        <option value="Surat" <?php echo ($location === 'Surat') ? 'selected' : ''; ?>>Surat</option>
                        <option value="Vadodara" <?php echo ($location === 'Vadodara') ? 'selected' : ''; ?>>Vadodara</option>
                        <option value="Rajkot" <?php echo ($location === 'Rajkot') ? 'selected' : ''; ?>>Rajkot</option>
                        <option value="Anand" <?php echo ($location === 'Anand') ? 'selected' : ''; ?>>Anand</option>
                        <option value="Bhavnagar" <?php echo ($location === 'Bhavnagar') ? 'selected' : ''; ?>>Bhavnagar</option>
                        <option value="Mehsana" <?php echo ($location === 'Mehsana') ? 'selected' : ''; ?>>Mehsana</option>
                        <option value="Gandhinagar" <?php echo ($location === 'Gandhinagar') ? 'selected' : ''; ?>>Gandhinagar</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="max_price">Max Price (₹/kg/day)</label>
                    <input type="number" step="0.1" name="max_price" id="max_price" class="form-control" value="<?php echo $maxPriceFilter !== null ? e($maxPriceFilter) : ''; ?>" placeholder="Optional max rate">
                </div>

                <div class="form-group">
                    <button type="submit" class="btn btn-primary btn-block" style="height: 42px;">
                        <i class="fa-solid fa-magnifying-glass"></i> Find Best Storage
                    </button>
                </div>
            </div>
        </form>
    </div>

    <!-- Search Results Section -->
    <?php if ($hasSearched && empty($validationErrors)): ?>
        <div style="margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: baseline; flex-wrap: wrap; gap: 0.5rem;">
            <h2>
                Found <?php echo count($results); ?> Matching Storage Facilities
            </h2>
            <span class="text-muted" style="font-size: 0.9rem;">
                Ranked by <strong>Smart Match Score</strong> (Distance 30%, Price 25%, Capacity 20%, Crop 15%, Temp 10%)
            </span>
        </div>

        <?php if (empty($results)): ?>
            <!-- Empty state when no storage matches -->
            <div class="card" style="text-align: center; padding: 3.5rem 1.5rem; border: 2px dashed var(--border-color);">
                <div style="font-size: 3.5rem; margin-bottom: 1rem; color: #94a3b8;"><i class="fa-solid fa-snowflake"></i></div>
                <h3 style="color: #475569; font-size: 1.5rem;">No suitable cold storage found</h3>
                <p class="text-muted" style="max-width: 500px; margin: 0 auto 1.5rem; line-height: 1.6;">
                    None of our active cold storage facilities currently meet the exact combination of available capacity, crop temperature compatibility, and location.
                </p>

                <div style="background: #f8fafc; border: 1px solid var(--border-color); border-radius: var(--radius-md); max-width: 480px; margin: 0 auto 2rem; padding: 1.25rem; text-align: left;">
                    <div style="font-weight: 700; margin-bottom: 0.5rem; color: var(--text-dark);"><i class="fa-solid fa-lightbulb" style="color: var(--accent-gold);"></i> Recommended Adjustments:</div>
                    <ul style="padding-left: 1.25rem; font-size: 0.9rem; color: #475569; line-height: 1.7;">
                        <li><strong>Reduce quantity:</strong> Try splitting your produce into smaller batches (e.g., 300 kg instead of 500 kg).</li>
                        <li><strong>Try another location:</strong> Search with "Ahmedabad" for regional facilities.</li>
                        <li><strong>Adjust max price filter:</strong> Remove rate restrictions if any were set.</li>
                        <li><strong>Adjust duration:</strong> Contact facilities for custom seasonal arrangements.</li>
                    </ul>
                </div>

                <a href="<?php echo base_url('search.php?crop=Tomato&quantity=500&duration=15&location=Ahmedabad'); ?>" class="btn btn-primary">
                    <i class="fa-solid fa-rotate-left"></i> Reset to Standard Demo Search
                </a>
            </div>
        <?php else: ?>
            <!-- Display list of storage cards -->
            <div style="display: flex; flex-direction: column; gap: 1.75rem;">
                <?php foreach ($results as $index => $s): ?>
                    <?php 
                        $isBestMatch = ($index === 0);
                        $matchScore = $s['smart_match'];
                        $matchClass = ($matchScore >= 85) ? 'match-high' : (($matchScore >= 70) ? 'match-medium' : 'match-low');
                    ?>
                    <div class="storage-card <?php echo $isBestMatch ? 'best-match' : ''; ?>">
                        <?php if ($isBestMatch): ?>
                            <div class="best-match-badge">
                                <i class="fa-solid fa-star"></i> BEST SMART MATCH
                            </div>
                        <?php endif; ?>

                        <div class="storage-card-header">
                            <div>
                                <h3 class="storage-name"><?php echo e($s['name']); ?></h3>
                                <div class="storage-location">
                                    <span><i class="fa-solid fa-location-dot"></i></span>
                                    <span><?php echo e($s['location']); ?> &bull; <strong>~<?php echo e($s['distance_km']); ?> km away</strong></span>
                                </div>
                            </div>

                            <div class="match-pill <?php echo $matchClass; ?>">
                                <span class="match-score-value"><?php echo $matchScore; ?>%</span>
                                <span class="match-score-label">Smart Match</span>
                            </div>
                        </div>

                        <!-- Match Explanation -->
                        <div class="match-explanation">
                            <strong>Smart Analysis:</strong> <?php echo e($s['match_explanation']); ?>
                        </div>

                        <!-- Technical Specs Grid -->
                        <div class="storage-specs">
                            <div class="spec-item">
                                <span class="spec-label">Storage Temperature</span>
                                <span class="spec-value"><i class="fa-solid fa-temperature-half"></i> <?php echo e($s['temperature']); ?></span>
                            </div>
                            <div class="spec-item">
                                <span class="spec-label">Storage Rate</span>
                                <span class="spec-value" style="color: var(--primary-dark);"><i class="fa-solid fa-indian-rupee-sign"></i> <?php echo number_format($s['price_per_kg'], 2); ?> /kg/day</span>
                            </div>
                            <div class="spec-item">
                                <span class="spec-label">Available Capacity</span>
                                <span class="spec-value">
                                    <i class="fa-solid fa-boxes-stacked"></i> <?php echo number_format($s['available_capacity']); ?> kg 
                                    <span class="text-muted" style="font-weight: 400; font-size: 0.8rem;">(of <?php echo number_format($s['total_capacity']); ?> kg)</span>
                                </span>
                            </div>
                            <div class="spec-item">
                                <span class="spec-label">Min. Booking Quantity</span>
                                <span class="spec-value"><i class="fa-solid fa-scale-balanced"></i> <?php echo number_format($s['minimum_quantity']); ?> kg</span>
                            </div>
                        </div>

                        <!-- Supported Crops List -->
                        <div style="margin-bottom: 1rem;">
                            <span class="spec-label" style="display: block; margin-bottom: 0.35rem;">Supported Crops:</span>
                            <div class="crops-tag-container">
                                <?php 
                                    $cropsArr = array_map('trim', explode(',', $s['supported_crops']));
                                    foreach ($cropsArr as $c):
                                        $isCur = (strtolower($c) === strtolower($crop));
                                ?>
                                    <span class="crop-tag <?php echo $isCur ? 'highlight' : ''; ?>">
                                        <?php if ($isCur): ?><i class="fa-solid fa-check"></i> <?php endif; ?><?php echo e($c); ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Estimated Cost for this specific search -->
                        <div style="background: #f8fafc; border: 1px solid var(--border-color); border-radius: var(--radius-md); padding: 0.85rem 1rem; margin-bottom: 1.25rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.5rem;">
                            <div>
                                <span style="font-size: 0.8rem; color: var(--text-muted); text-transform: uppercase; font-weight: 600;">Estimated Storage Cost</span>
                                <div style="font-size: 0.85rem; color: #475569;">
                                    <?php echo e($quantity); ?> kg × ₹<?php echo number_format($s['price_per_kg'], 2); ?> × <?php echo e($duration); ?> days
                                </div>
                            </div>
                            <div style="font-size: 1.5rem; font-weight: 800; color: var(--primary-dark);">
                                ₹<?php echo number_format($s['estimated_cost'], 2); ?>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="storage-card-actions">
                            <a href="<?php echo base_url('storage-details.php?id=' . $s['id'] . '&crop=' . urlencode($crop) . '&quantity=' . $quantity . '&duration=' . $duration . '&location=' . urlencode($location)); ?>" class="btn btn-outline-primary">
                                View Details & Specs
                            </a>
                            <a href="<?php echo base_url('booking.php?storage_id=' . $s['id'] . '&crop=' . urlencode($crop) . '&quantity=' . $quantity . '&duration=' . $duration); ?>" class="btn btn-primary">
                                Book Storage Now
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
