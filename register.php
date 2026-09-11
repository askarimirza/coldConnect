<?php
$pageTitle = "Register - Agri Storage";
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    header('Location: ' . base_url('farmer-dashboard.php'));
    exit;
}

$error = '';
$preselectedRole = ($_GET['role'] ?? '') === 'owner' ? 'owner' : 'farmer';

$name     = '';
$email    = '';
$phone    = '';
$role     = $preselectedRole;
$location = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $phone    = trim($_POST['phone'] ?? '');
    $role     = in_array($_POST['role'] ?? '', ['farmer', 'owner']) ? $_POST['role'] : 'farmer';
    $location = trim($_POST['location'] ?? '');

    // Validation
    if (empty($name) || empty($email) || empty($password) || empty($phone) || empty($location)) {
        $error = 'Please fill out all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters in length.';
    } else {
        try {
            // Check email uniqueness
            $checkStmt = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
            $checkStmt->execute([$email]);
            if ($checkStmt->fetch()) {
                $error = 'An account with this email address already exists. Please log in.';
            } else {
                // Hash password securely
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

                $insertStmt = $pdo->prepare("
                    INSERT INTO users (name, email, password, phone, role, location, created_at)
                    VALUES (?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
                ");
                $insertStmt->execute([$name, $email, $hashedPassword, $phone, $role, $location]);
                $newUserId = $pdo->lastInsertId();

                // Auto-login newly registered user
                $_SESSION['user_id']       = $newUserId;
                $_SESSION['user_name']     = $name;
                $_SESSION['user_email']    = $email;
                $_SESSION['user_role']     = $role;
                $_SESSION['user_location'] = $location;
                $_SESSION['user_phone']    = $phone;

                setFlash('success', "Welcome to Agri Storage, {$name}! Your account has been created.");

                if ($role === 'owner') {
                    header('Location: ' . base_url('owner/dashboard.php'));
                } else {
                    header('Location: ' . base_url('farmer-dashboard.php'));
                }
                exit;
            }
        } catch (PDOException $e) {
            $error = 'Registration error: ' . $e->getMessage();
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="max-width: 580px; padding: 3rem 1.25rem;">
    <div class="card" style="padding: 2.5rem 2rem;">
        <div class="text-center" style="margin-bottom: 2rem;">
            <h2>Create an Account</h2>
            <p class="text-muted">Join Agri Storage as a Farmer or Cold Storage Owner</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger">
                <span><i class="fa-solid fa-triangle-exclamation"></i></span>
                <span><?php echo e($error); ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?php echo base_url('register.php'); ?>">
            <div class="form-group" style="margin-bottom: 1.25rem;">
                <label for="role_select">I am registering as: *</label>
                <select name="role" id="role_select" class="form-control" style="font-weight: 600;" required>
                    <option value="farmer" <?php echo ($role === 'farmer') ? 'selected' : ''; ?>>Farmer (Looking for cold storage)</option>
                    <option value="owner" <?php echo ($role === 'owner') ? 'selected' : ''; ?>>Cold Storage Facility Owner</option>
                </select>
            </div>

            <div class="form-group" style="margin-bottom: 1.25rem;">
                <label for="reg_name">Full Name or Business Name *</label>
                <input type="text" name="name" id="reg_name" class="form-control" value="<?php echo e($name); ?>" placeholder="e.g. Ramesh Patel" required>
            </div>

            <div class="grid-2" style="margin-bottom: 1.25rem;">
                <div class="form-group">
                    <label for="reg_email">Email Address *</label>
                    <input type="email" name="email" id="reg_email" class="form-control" value="<?php echo e($email); ?>" placeholder="name@domain.com" required>
                </div>
                <div class="form-group">
                    <label for="reg_phone">Phone Number *</label>
                    <input type="text" name="phone" id="reg_phone" class="form-control" value="<?php echo e($phone); ?>" placeholder="+91 98765 43210" required>
                </div>
            </div>

            <div class="grid-2" style="margin-bottom: 1.5rem;">
                <div class="form-group">
                    <label for="reg_password">Password (min 6 chars) *</label>
                    <input type="password" name="password" id="reg_password" class="form-control" placeholder="••••••••" minlength="6" required>
                </div>
                <div class="form-group">
                    <label for="reg_location">City / Hub Location *</label>
                    <select name="location" id="reg_location" class="form-control" required>
                        <option value="">-- Choose Hub --</option>
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

            <button type="submit" class="btn btn-primary btn-block btn-lg">
                <i class="fa-solid fa-user-plus"></i> Create Agri Storage Account
            </button>
        </form>

        <div class="text-center" style="margin-top: 1.5rem; font-size: 0.9rem; color: var(--text-muted);">
            Already registered? 
            <a href="<?php echo base_url('login.php'); ?>" style="font-weight: 600; color: var(--secondary);">Log in here</a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
