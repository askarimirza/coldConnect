# ColdConnect: Technical Architecture, Code Walkthrough & Working Guide

**ColdConnect: Smart Cold Storage Finder & Reservation Platform**  
*Tackling Post-Harvest Loss, Perishability & Distress Selling for Smallholder Farmers*

---

## Executive Summary

ColdConnect is an AgriTech web platform built to solve one of India's biggest agricultural challenges: **post-harvest distress selling and crop spoilage**. 

Due to a lack of transparent access to nearby refrigerated storage, smallholder farmers are often forced to sell perishable produce (such as tomatoes, onions, potatoes, and apples) at steep losses during harvest gluts. Simultaneously, cold storage warehouse owners face underutilized capacity due to fragmented, offline communication.

ColdConnect creates a direct, transparent marketplace with:
1. **Intelligent Smart Match**: Automatically scoring and ranking cold storages based on proximity, cold chain price, and crop-specific temperature suitability.
2. **Transparent Live Cost Calculator**: Instantly estimating preservation costs based on crop volume, duration, and warehouse tariff rates.
3. **Structured Booking & Reservation Pipeline**: Farmers submit reservations with real-time status tracking (`Pending`, `Accepted`, `Rejected`, `Completed`).
4. **Owner Management & Capacity Synchronisation**: Storage managers review incoming requests, accept bookings with atomic capacity decrements, or reject requests with an accessible, in-app UI popup modal.

---

## Technology Stack

| Layer | Technology | Purpose |
|---|---|---|
| **Frontend UI** | HTML5, Pure Vanilla CSS3 | Custom AgriTech + Ice Cyan design system, glassmorphism, responsive grid layouts, 0% framework bloat. |
| **Client Logic** | Vanilla JavaScript (ES6+) | Live price estimator, date sync, mobile navigation, and in-app UI modal controllers. |
| **Backend** | PHP 8.x | Lightweight, robust MVC-inspired procedural backend, session authentication, flash messaging, role-based access control (RBAC). |
| **Database** | MySQL / MariaDB (PDO) | ACID transactions, prepared statements, foreign key cascades, strict ENUM states. |
| **Icons & Fonts** | FontAwesome 6.4 + Inter (Google Fonts) | High-contrast visual cues, accessible status badges, modern typography. |

---

## Relational Database Schema

The database consists of 3 core entities managed through PDO with parameterized queries:

### 1. `users` Table
Stores authentication and profile records for both Farmers and Storage Owners:
```sql
CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(120) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('farmer', 'owner') NOT NULL DEFAULT 'farmer',
  `phone` VARCHAR(20) NOT NULL,
  `location` VARCHAR(100) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

### 2. `cold_storages` Table
Stores cold storage facility metadata, environmental controls, pricing, and live capacities:
```sql
CREATE TABLE `cold_storages` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `owner_id` INT NOT NULL,
  `name` VARCHAR(150) NOT NULL,
  `location` VARCHAR(100) NOT NULL,
  `total_capacity` DECIMAL(10,2) NOT NULL,
  `available_capacity` DECIMAL(10,2) NOT NULL,
  `temperature` VARCHAR(50) NOT NULL,
  `price_per_kg` DECIMAL(6,2) NOT NULL,
  `status` ENUM('Available', 'Full', 'Maintenance') NOT NULL DEFAULT 'Available',
  `description` TEXT,
  `contact_phone` VARCHAR(20) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`owner_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
);
```

### 3. `bookings` Table
Tracks farmer reservations through the end-to-end lifecycle:
```sql
CREATE TABLE `bookings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `farmer_id` INT NOT NULL,
  `storage_id` INT NOT NULL,
  `crop` VARCHAR(50) NOT NULL,
  `quantity` DECIMAL(10,2) NOT NULL,
  `start_date` DATE NOT NULL,
  `end_date` DATE NOT NULL,
  `total_cost` DECIMAL(10,2) NOT NULL,
  `status` ENUM('Pending', 'Accepted', 'Rejected', 'Completed') NOT NULL DEFAULT 'Pending',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`farmer_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`storage_id`) REFERENCES `cold_storages`(`id`) ON DELETE CASCADE
);
```

---

## Core System Workflows

### 1. Farmer Discovery & Smart Match Workflow
1. **Search Query**: Farmer enters Crop (e.g. Tomato), Quantity (kg), Expected Duration (days), and Location (e.g. Ahmedabad).
2. **Recommendation Algorithm (`includes/smart-match.php`)**:
   - Scores each storage out of 100 based on:
     - **Distance Score (40 pts)**: Normalized proximity to farmer.
     - **Price Competitiveness (30 pts)**: Inverse proportion to local market average.
     - **Crop Suitability (30 pts)**: Match between crop optimal storage temperature (e.g., Tomato 10°C-15°C, Apple -1°C-4°C, Potato 4°C-8°C) and the facility's temperature capability.
   - Highlights the highest-ranking facility with a **"BEST MATCH"** badge.
3. **Live Cost Estimation**: Real-time formula computation:
   $$\text{Total Cost (₹)} = \text{Quantity (kg)} \times \text{Price (₹/kg/day)} \times \text{Duration (days)}$$
4. **Reservation Submission**: Generates a `Pending` record in `bookings`.

### 2. Storage Owner Workflow & Capacity Synchronization
1. **Incoming Request Alert**: Storage manager views pending reservations on `owner/dashboard.php` and `owner/bookings.php`.
2. **Acceptance (Atomic Transaction)**:
   - Checks that $\text{available\_capacity} \ge \text{requested\_quantity}$.
   - Executes inside a PDO database transaction:
     - Sets booking status to `Accepted`.
     - Decrements facility `available_capacity` by requested quantity.
     - If available capacity hits 0, updates facility status to `Full`.
     - Commits transaction atomically, preventing race conditions or double allocations.
3. **Rejection (Modern UI Popup Modal)**:
   - When owner clicks "Reject", the platform prompts with a styled in-app UI Popup Modal instead of a jarring browser alert.
   - The owner confirms rejection, transitioning the booking status to `Rejected` and keeping facility capacity unallocated.

---

## UI Rejection Modal & Code Minimisation Deep Dive

### The Problem with Browser `confirm(...)`
Previously, rejecting a booking executed:
```html
<!-- OLD Bloated Implementation -->
<form method="POST" action="update-booking.php" style="display: inline;">
    <input type="hidden" name="booking_id" value="12">
    <input type="hidden" name="action" value="reject">
    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Reject booking #12?');">
        Reject
    </button>
</form>
```

#### Drawbacks:
1. **Poor User Experience**: Native browser dialogs look outdated, cannot be styled, block the browser thread, and look disconnected from modern web apps.
2. **Code Redundancy**: Every table row in every management page duplicated `<form>` tags, hidden input fields, and inline JavaScript.
3. **Lack of Context**: The browser dialog could only show a plain text string with no structured summary of farmer name, crop, quantity, or warehouse name.

---

### The New Architecture: Modular In-App UI Popup Modal

#### 1. Minimalist Button Trigger (HTML)
Every table row now renders a lightweight, declarative button without inline forms or scripts:
```html
<button type="button" class="btn btn-danger btn-sm btn-reject-modal"
        data-id="<?php echo $b['id']; ?>"
        data-farmer="<?php echo e($b['farmer_name']); ?>"
        data-crop="<?php echo e($b['crop']); ?>"
        data-qty="<?php echo number_format($b['quantity']); ?> kg"
        data-facility="<?php echo e($b['storage_name']); ?>">
    Reject
</button>
```

#### 2. Reusable Modal Dialog Component (`includes/modal-reject.php`)
A single, modular dialog placed at the page root containing:
- Warning alert badge with pulse aesthetic.
- Title and dynamic subtitle `#<span id="modal-reject-id-title"></span>`.
- Summary preview card displaying Farmer Name, Crop, Quantity, and Target Facility.
- Form targeting `owner/update-booking.php` with hidden `booking_id` and `action=reject`.
- "Keep Booking" (Cancel) and "Confirm Rejection" actions.

#### 3. Pure Vanilla JS Event Controller (`js/script.js`)
Handles opening, dynamic population, and accessible closing:
- **Event Delegation**: Listens on document root for `.btn-reject-modal` triggers.
- **Dynamic Context**: Injects booking data into modal preview cards and hidden input.
- **Accessible Dismissal**: Closes via Cancel button, `&times;` icon, clicking backdrop overlay, or pressing `Escape`.
- **Scroll Locking**: Temporarily locks `document.body.style.overflow = 'hidden'` while modal is open.

#### 4. CSS Design System Integration (`css/style.css`)
- Backdrop blur: `backdrop-filter: blur(4px);` with smooth opacity transition.
- Slide-up scale animation: `cubic-bezier(0.16, 1, 0.3, 1)` for high-end micro-interaction.
- Adheres 100% to project color palette: `--status-rejected: #dc2626` and `--status-rejected-bg: #fee2e2`.

---

## File-by-File Code Walkthrough

### 1. `includes/db.php`
- Initializes PDO connection using UTF-8 charset.
- Sets error mode to `PDO::ERRMODE_EXCEPTION` for robust exception handling.
- Configures default fetch mode to `PDO::FETCH_ASSOC`.

### 2. `includes/auth.php`
- Manages PHP session states (`session_start()`).
- Provides utility functions:
  - `isLoggedIn()`: Checks if `$_SESSION['user_id']` exists.
  - `currentUser()`: Retrieves authenticated user array.
  - `requireLogin()`, `requireFarmer()`, `requireOwner()`: Role-based route guards.
  - `setFlash($type, $message)` and `getFlash()`: One-time notification messaging.
  - `e($str)`: XSS sanitization helper (`htmlspecialchars`).
  - `base_url($path)`: Dynamically generates absolute project URLs.

### 3. `includes/modal-reject.php` [NEW]
- Reusable UI popup modal structure used by owner dashboards.
- Isolates rejection DOM elements and form controls in a single location.

### 4. `includes/smart-match.php`
- Defines ideal storage temperatures for common Indian crops:
  - Potato (4°C - 8°C), Onion (0°C - 2°C), Tomato (10°C - 15°C), Apple (-1°C - 4°C), Carrot (0°C - 2°C).
- Implements `calculateSmartMatchScore($storage, $params)` weighting distance, pricing, and temperature compatibility.

### 5. `owner/dashboard.php`
- Aggregates owner statistics: Total Storages, Total Available Capacity, Pending Requests, Confirmed Bookings.
- Displays pending reservations table with new `btn-reject-modal` trigger.
- Displays registered cold storage warehouses with visual capacity fill bars.

### 6. `owner/bookings.php`
- Full reservation management ledger with status filtering (`All`, `Pending`, `Accepted`, `Rejected`).
- Farmer contact details, crop specifications, and duration metrics.
- Leverages the unified UI rejection modal.

### 7. `owner/update-booking.php`
- Transactional controller processing `accept` and `reject` actions.
- Enforces facility ownership verification to prevent IDOR (Insecure Direct Object Reference).
- Executes atomic transaction for accepted bookings, reducing warehouse capacity and updating status flags.

### 8. `search.php` & `storage-details.php`
- Facilitates farmer search filters by Crop, Location, and Maximum Price.
- Displays facility profile, available capacity gauge, amenities, and direct booking trigger.

### 9. `booking.php`
- Reservation submission form with live cost computation and start/end date synchronization.
- Inserts new reservation in `bookings` with status `Pending`.

---

## Verification & Testing Guide

| Test Scenario | Action Taken | Expected Result | Pass/Fail |
|---|---|---|---|
| **Reject Button Trigger** | Click "Reject" on `owner/dashboard.php` or `owner/bookings.php` | In-app modal pops up smoothly. Browser `confirm()` is NOT called. | **PASS** |
| **Context Verification** | Inspect modal text and badge | Displays exact Booking ID, Farmer Name, Crop & Quantity, and Facility. | **PASS** |
| **Dismissal via Cancel** | Click "Keep Booking" or `&times;` | Modal closes immediately, no status change or page reload. | **PASS** |
| **Dismissal via Escape** | Press `Escape` key on keyboard | Modal closes smoothly. | **PASS** |
| **Dismissal via Backdrop** | Click outside the modal box | Modal closes. | **PASS** |
| **Rejection Submission** | Click "Confirm Rejection" | Submits POST to `update-booking.php`. Booking status updates to `Rejected`. Flash message displayed. | **PASS** |
| **Capacity Preservation** | Check warehouse available capacity after rejection | Capacity remains intact (NOT decremented). | **PASS** |

---

*ColdConnect Documentation & Technical Architecture Report — Generated September 2026*
