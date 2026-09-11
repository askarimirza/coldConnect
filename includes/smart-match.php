<?php
/**
 * COLDCONNECT - Transparent Rule-Based Smart Match Algorithm
 * 
 * Hackathon Notice:
 * This algorithm is 100% deterministic and rule-based.
 * No machine-learning black box or external paid APIs are used.
 * Total Score = Distance (30%) + Price (25%) + Available Capacity (20%) + Crop Compatibility (15%) + Temperature Suitability (10%)
 */

/**
 * Approximate distance between localities in Ahmedabad region (in km)
 * Does NOT require Google Maps API.
 */
function calculateEstimatedDistance($farmerLocation, $storageLocation) {
    $farmerLoc = strtolower(trim($farmerLocation));
    $storageLoc = strtolower(trim($storageLocation));

    // Coordinates mapping for prominent areas in & around Ahmedabad (latitude, longitude)
    $areaCoordinates = [
        'ahmedabad'    => ['lat' => 23.0225, 'lng' => 72.5714],
        'central'      => ['lat' => 23.0225, 'lng' => 72.5714],
        'bopal'        => ['lat' => 23.0336, 'lng' => 72.4634],
        'chandkheda'   => ['lat' => 23.1114, 'lng' => 72.5724],
        'sanand'       => ['lat' => 22.9927, 'lng' => 72.3815],
        'naroda'       => ['lat' => 23.0694, 'lng' => 72.6560],
        'sg highway'   => ['lat' => 23.0526, 'lng' => 72.5186],
        'sarkhej'      => ['lat' => 22.9856, 'lng' => 72.5023],
        'maninagar'    => ['lat' => 22.9978, 'lng' => 72.6033],
        'navrangpura'  => ['lat' => 23.0365, 'lng' => 72.5611],
        'gandhinagar'  => ['lat' => 23.2156, 'lng' => 72.6369],
    ];

    $findCoords = function($locString) use ($areaCoordinates) {
        foreach ($areaCoordinates as $key => $coords) {
            if (strpos($locString, $key) !== false) {
                return $coords;
            }
        }
        // Fallback default center
        return ['lat' => 23.0225, 'lng' => 72.5714];
    };

    $c1 = $findCoords($farmerLoc);
    $c2 = $findCoords($storageLoc);

    // If both match exact same locality string
    if ($farmerLoc === $storageLoc || (strpos($storageLoc, $farmerLoc) !== false && $farmerLoc !== 'ahmedabad')) {
        return 3.2; // Local neighborhood distance
    }

    // Haversine distance formula (in km)
    $earthRadius = 6371; // km
    $latDiff = deg2rad($c2['lat'] - $c1['lat']);
    $lonDiff = deg2rad($c2['lng'] - $c1['lng']);

    $a = sin($latDiff / 2) * sin($latDiff / 2) +
         cos(deg2rad($c1['lat'])) * cos(deg2rad($c2['lat'])) *
         sin($lonDiff / 2) * sin($lonDiff / 2);
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    $dist = $earthRadius * $c;

    // Add small urban route factor (1.2x) and min bound
    $dist = round(max(3.5, $dist * 1.25), 1);
    return $dist;
}

/**
 * Optimal temperature guide for perishable produce
 */
function getCropOptimalTemp($cropName) {
    $crop = strtolower(trim($cropName));
    $tempTable = [
        'tomato' => ['min' => 4,  'max' => 10, 'label' => '4°C - 10°C'],
        'potato' => ['min' => 4,  'max' => 8,  'label' => '4°C - 8°C'],
        'onion'  => ['min' => 0,  'max' => 4,  'label' => '0°C - 4°C'],
        'apple'  => ['min' => -1, 'max' => 4,  'label' => '-1°C - 4°C'],
        'mango'  => ['min' => 8,  'max' => 13, 'label' => '8°C - 13°C'],
        'carrot' => ['min' => 0,  'max' => 4,  'label' => '0°C - 4°C'],
        'garlic' => ['min' => 0,  'max' => 3,  'label' => '0°C - 3°C'],
    ];

    return $tempTable[$crop] ?? ['min' => 2, 'max' => 10, 'label' => '2°C - 10°C'];
}

/**
 * Parse storage temperature range string like "2°C - 8°C"
 */
function parseStorageTempRange($tempStr) {
    preg_match_all('/(-?\d+)/', $tempStr, $matches);
    if (!empty($matches[1]) && count($matches[1]) >= 2) {
        return ['min' => (int)$matches[1][0], 'max' => (int)$matches[1][1]];
    }
    return ['min' => 0, 'max' => 10];
}

/**
 * Calculate transparent Smart Match Score (0 - 100)
 */
function calculateSmartMatch($storage, $crop, $quantity, $duration, $farmerLocation) {
    $requestedQty = max(1, (float)$quantity);
    $availCapacity = (float)$storage['available_capacity'];
    $price = (float)$storage['price_per_kg'];
    $storageLoc = $storage['location'];

    // 1. DISTANCE COMPONENT (30%)
    $distKm = calculateEstimatedDistance($farmerLocation, $storageLoc);
    if ($distKm <= 5.0) {
        $distanceScore = 30;
    } elseif ($distKm <= 10.0) {
        $distanceScore = 26;
    } elseif ($distKm <= 16.0) {
        $distanceScore = 21;
    } elseif ($distKm <= 25.0) {
        $distanceScore = 16;
    } else {
        $distanceScore = 10;
    }

    // 2. PRICE COMPONENT (25%)
    // Typical storage rates range from ₹1.80 to ₹3.00/kg/day
    if ($price <= 1.80) {
        $priceScore = 25;
    } elseif ($price <= 2.00) {
        $priceScore = 23;
    } elseif ($price <= 2.20) {
        $priceScore = 20;
    } elseif ($price <= 2.50) {
        $priceScore = 17;
    } elseif ($price <= 2.80) {
        $priceScore = 14;
    } else {
        $priceScore = 10;
    }

    // 3. AVAILABLE CAPACITY COMPONENT (20%)
    $capacityRatio = $availCapacity / $requestedQty;
    if ($capacityRatio >= 2.0) {
        $capacityScore = 20;
    } elseif ($capacityRatio >= 1.5) {
        $capacityScore = 18;
    } elseif ($capacityRatio >= 1.2) {
        $capacityScore = 15;
    } elseif ($capacityRatio >= 1.0) {
        $capacityScore = 12;
    } else {
        $capacityScore = 0; // Inadequate capacity
    }

    // 4. CROP COMPATIBILITY COMPONENT (15%)
    $supportedList = array_map('trim', explode(',', strtolower($storage['supported_crops'])));
    $cropLower = strtolower(trim($crop));
    $isCropSupported = in_array($cropLower, $supportedList);

    if ($isCropSupported) {
        $cropScore = 15;
    } else {
        // Partial or other
        $cropScore = 6;
    }

    // 5. TEMPERATURE SUITABILITY COMPONENT (10%)
    $cropTemp = getCropOptimalTemp($crop);
    $storageTemp = parseStorageTempRange($storage['temperature']);

    // Check overlap of optimal range and facility range
    $overlapMin = max($cropTemp['min'], $storageTemp['min']);
    $overlapMax = min($cropTemp['max'], $storageTemp['max']);

    if ($overlapMin <= $overlapMax) {
        // Perfect temperature window overlap
        $tempScore = 10;
        $tempQuality = "optimal";
    } elseif (abs($overlapMin - $overlapMax) <= 2) {
        $tempScore = 7;
        $tempQuality = "acceptable";
    } else {
        $tempScore = 4;
        $tempQuality = "sub-optimal";
    }

    // TOTAL COMPOSITE SCORE (out of 100)
    $totalScore = $distanceScore + $priceScore + $capacityScore + $cropScore + $tempScore;
    $totalScore = min(100, max(0, $totalScore));

    // Dynamic explanation generation
    $reasons = [];
    if ($distKm <= 8.0) {
        $reasons[] = "convenient location (~{$distKm} km)";
    }
    if ($price <= 2.00) {
        $reasons[] = "competitive price (₹" . number_format($price, 2) . "/kg/day)";
    }
    if ($capacityScore >= 18) {
        $reasons[] = "ample available capacity (" . number_format($availCapacity) . " kg)";
    }
    if ($tempScore === 10) {
        $reasons[] = "ideal storage temperature ({$storage['temperature']}) for {$crop}";
    }

    if (empty($reasons)) {
        $explanation = "Meets fundamental storage capacity and duration requirements.";
    } else {
        $explanation = "Recommended match because of " . implode(', ', $reasons) . ".";
    }

    return [
        'score'          => $totalScore,
        'distance_km'    => $distKm,
        'explanation'    => $explanation,
        'is_supported'   => $isCropSupported,
        'temp_quality'   => $tempQuality,
        'breakdown'      => [
            'distance'    => ['score' => $distanceScore, 'max' => 30],
            'price'       => ['score' => $priceScore,    'max' => 25],
            'capacity'    => ['score' => $capacityScore, 'max' => 20],
            'crop'        => ['score' => $cropScore,     'max' => 15],
            'temperature' => ['score' => $tempScore,     'max' => 10],
        ]
    ];
}
