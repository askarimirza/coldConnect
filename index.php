<?php
$pageTitle = "Smart Cold Storage Finder & Booking System";
require_once __DIR__ . '/includes/db.php';
$maxAvailableAll = 10000;
try {
    $maxAvailableAll = (float)$pdo->query("SELECT MAX(available_capacity) FROM cold_storages WHERE status = 'Available'")->fetchColumn() ?: 10000;
} catch (Exception $e) {}
require_once __DIR__ . '/includes/header.php';
?>

<!-- Hero Section -->
<section class="hero">
    <div class="container">
        <div class="hero-content">
            <div class="hero-pill">
                <i class="fa-solid fa-seedling"></i> KALPVRUKSH 2.0 &bull; Problem P21 AgriTech
            </div>
            <h1>Smart Cold Storage for Every Farmer</h1>
            <p>
                Stop distress selling and post-harvest spoilage. Find verified nearby cold storage facilities with available capacity, transparent pricing, and rule-based Smart Match scoring.
            </p>
            <div class="hero-actions">
                <a href="<?php echo base_url('search.php'); ?>" class="btn btn-primary btn-lg">
                    <i class="fa-solid fa-magnifying-glass"></i> Find Cold Storage
                </a>
                <a href="<?php echo base_url('register.php?role=owner'); ?>" class="btn btn-outline btn-lg" style="color: white; border-color: rgba(255,255,255,0.3);">
                    <i class="fa-solid fa-warehouse"></i> Register Storage Facility
                </a>
            </div>

            <div class="hero-stats">
                <div class="hero-stat-card">
                    <div class="hero-stat-num">30-40%</div>
                    <div class="hero-stat-label">Produce Spoilage Prevented</div>
                </div>
                <div class="hero-stat-card">
                    <div class="hero-stat-num">100%</div>
                    <div class="hero-stat-label">Transparent Pricing</div>
                </div>
                <div class="hero-stat-card">
                    <div class="hero-stat-num">5 Factors</div>
                    <div class="hero-stat-label">Smart Match Algorithm</div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Quick Search Card Overlay -->
<div class="container">
    <div class="search-box-card">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem;">
            <h3 style="margin: 0; font-size: 1.3rem;"><i class="fa-solid fa-bolt" style="color: var(--accent-gold);"></i> Quick Cold Storage Search</h3>
            <button type="button" id="demo-fill-btn" class="btn btn-outline-primary btn-sm" title="Loads standard hackathon demo inputs">
                <i class="fa-solid fa-wand-magic-sparkles"></i> Load Judge Demo (Tomato, 500kg)
            </button>
        </div>

        <form action="<?php echo base_url('search.php'); ?>" method="GET">
            <div class="form-grid">
                <div class="form-group">
                    <label for="crop">Select Crop *</label>
                    <select name="crop" id="crop" class="form-control" required>
                        <option value="">-- Choose Crop --</option>
                        <option value="Tomato">Tomato (Ideal: 4°C - 10°C)</option>
                        <option value="Potato">Potato (Ideal: 4°C - 8°C)</option>
                        <option value="Onion">Onion (Ideal: 0°C - 4°C)</option>
                        <option value="Apple">Apple (Ideal: -1°C - 4°C)</option>
                        <option value="Mango">Mango (Ideal: 8°C - 13°C)</option>
                        <option value="Carrot">Carrot (Ideal: 0°C - 4°C)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="quantity">Quantity (kg) *</label>
                    <input type="number" name="quantity" id="quantity" class="form-control" placeholder="Min 100 kg" value="500" min="100" max="<?php echo e($maxAvailableAll); ?>" step="1" required>
                    <small class="text-muted" style="font-size: 0.75rem;">Min: 100 kg &bull; Network Max: <?php echo number_format($maxAvailableAll); ?> kg</small>
                </div>

                <div class="form-group">
                    <label for="duration">Storage Duration (Days) *</label>
                    <input type="number" name="duration" id="duration" class="form-control" placeholder="e.g. 15" min="1" required>
                </div>

                <div class="form-group">
                    <label for="location">Farmer Pickup Location *</label>
                    <select name="location" id="location" class="form-control" required>
                        <option value="">-- Choose City / Hub --</option>
                        <option value="Ahmedabad" selected>Ahmedabad</option>
                        <option value="Surat">Surat</option>
                        <option value="Vadodara">Vadodara</option>
                        <option value="Rajkot">Rajkot</option>
                        <option value="Anand">Anand</option>
                        <option value="Bhavnagar">Bhavnagar</option>
                        <option value="Mehsana">Mehsana</option>
                        <option value="Gandhinagar">Gandhinagar</option>
                    </select>
                </div>

                <div class="form-group">
                    <button type="submit" class="btn btn-primary btn-block" style="height: 42px;">
                        <i class="fa-solid fa-magnifying-glass"></i> Find Best Storage
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- How It Works Section -->
<section class="section">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">How AgriStorage Works</h2>
            <p class="section-subtitle">
                A simple 4-step workflow designed to save farmers from distress sales and spoiled harvests.
            </p>
        </div>

        <div class="grid-4">
            <div class="card step-card">
                <div class="step-num">1</div>
                <h3>Enter Produce</h3>
                <p class="text-muted">Specify your harvested crop, total weight in kg, duration, and local farm location.</p>
            </div>

            <div class="card step-card">
                <div class="step-num">2</div>
                <h3>Smart Matching</h3>
                <p class="text-muted">Our rule-based engine matches distance, crop compatibility, temperature, and rates.</p>
            </div>

            <div class="card step-card">
                <div class="step-num">3</div>
                <h3>Review & Estimate</h3>
                <p class="text-muted">Check facility temperatures, available capacity, and dynamic estimated costs.</p>
            </div>

            <div class="card step-card">
                <div class="step-num">4</div>
                <h3>Reserve & Confirm</h3>
                <p class="text-muted">Submit instant booking requests. Owners confirm and lock in your refrigerated space.</p>
            </div>
        </div>
    </div>
</section>

<!-- The Problem & Solution (P21 AgriTech Highlight) -->
<section class="section section-bg">
    <div class="container">
        <div class="section-header">
            <div class="hero-pill" style="background: var(--primary-soft); color: var(--primary-dark); border-color: #a7f3d0;">
                The Core Challenge: Problem P21
            </div>
            <h2 class="section-title">Solving Agricultural Perishability</h2>
            <p class="section-subtitle">
                Why thousands of Indian farmers suffer devastating post-harvest losses every season.
            </p>
        </div>

        <div class="grid-2">
            <div class="card" style="border-left: 4px solid var(--status-rejected);">
                <h3 style="color: var(--status-rejected); display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fa-solid fa-triangle-exclamation"></i> Without AgriStorage (The Traditional Reality)
                </h3>
                <ul style="margin-top: 1rem; padding-left: 1.25rem; line-height: 1.8; color: #475569;">
                    <li><strong>Zero Capacity Visibility:</strong> Farmers travel to cold storages only to find them completely full.</li>
                    <li><strong>Crop Incompatibility:</strong> Storages that don't support humidity or temperatures for delicate produce like tomatoes.</li>
                    <li><strong>Distress Selling:</strong> Due to lack of cold chain options, farmers dump produce or sell at ₹1-2/kg to middlemen.</li>
                    <li><strong>Arbitrary Pricing:</strong> Unregulated rates and hidden seasonal surcharges.</li>
                </ul>
            </div>

            <div class="card" style="border-left: 4px solid var(--primary);">
                <h3 style="color: var(--primary); display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fa-solid fa-circle-check"></i> With AgriStorage (Our Smart Solution)
                </h3>
                <ul style="margin-top: 1rem; padding-left: 1.25rem; line-height: 1.8; color: #475569;">
                    <li><strong>Live Available Capacity:</strong> Only facilities with actual capacity for the requested weight are listed.</li>
                    <li><strong>Crop Compatibility Filter:</strong> Only storages certified and configured for the specific crop are shown.</li>
                    <li><strong>Transparent Smart Match Score:</strong> Ranks storages objectively by distance, price, capacity, and temperature.</li>
                    <li><strong>Instant Cost Calculator:</strong> Exact estimated cost (`Quantity × Price × Days`) shown upfront.</li>
                </ul>
            </div>
        </div>
    </div>
</section>

<!-- Key Platform Features -->
<section class="section">
    <div class="container">
        <div class="section-header">
            <h2 class="section-title">Platform Features</h2>
            <p class="section-subtitle">Engineered for speed, transparency, and simplicity on all devices.</p>
        </div>

        <div class="grid-3">
            <div class="card feature-card">
                <div class="feature-icon"><i class="fa-solid fa-bullseye"></i></div>
                <h3>Rule-Based Smart Match</h3>
                <p class="text-muted">Calculates a transparent 100-point compatibility score without opaque blackbox models.</p>
            </div>

            <div class="card feature-card">
                <div class="feature-icon"><i class="fa-solid fa-temperature-half"></i></div>
                <h3>Temperature Profiling</h3>
                <p class="text-muted">Validates that storage chambers operate in the safe temperature window for your crop.</p>
            </div>

            <div class="card feature-card">
                <div class="feature-icon"><i class="fa-solid fa-indian-rupee-sign"></i></div>
                <h3>Dynamic Cost Estimation</h3>
                <p class="text-muted">Calculates exact estimated rental costs in real-time before committing to any booking.</p>
            </div>

            <div class="card feature-card">
                <div class="feature-icon"><i class="fa-solid fa-clipboard-check"></i></div>
                <h3>One-Click Booking Requests</h3>
                <p class="text-muted">Farmers send structured reservations; owners can review, accept, or reject instantly.</p>
            </div>

            <div class="card feature-card">
                <div class="feature-icon"><i class="fa-solid fa-warehouse"></i></div>
                <h3>Storage Owner Portal</h3>
                <p class="text-muted">Allows warehouse owners to manage facilities, update available kg capacity, and track bookings.</p>
            </div>

            <div class="card feature-card">
                <div class="feature-icon"><i class="fa-solid fa-mobile-screen"></i></div>
                <h3>Mobile-First Design</h3>
                <p class="text-muted">Accessible on low-cost smartphones in rural farming communities without high bandwidth.</p>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
