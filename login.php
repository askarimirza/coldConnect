<?php
$pageTitle = "Login to Agri Storage";
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

// If already logged in, redirect to proper dashboard
if (isLoggedIn()) {
    $u = currentUser();
    if ($u['role'] === 'owner') {
        header('Location: ' . base_url('owner/dashboard.php'));
    } else {
        header('Location: ' . base_url('farmer-dashboard.php'));
    }
    exit;
}

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Please enter both email and password.';
    } else {
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                // Set session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['user_location'] = $user['location'];
                $_SESSION['user_phone'] = $user['phone'];

                setFlash('success', 'Welcome back, ' . $user['name'] . '!');

                // Redirect based on role
                if ($user['role'] === 'owner') {
                    header('Location: ' . base_url('owner/dashboard.php'));
                } else {
                    header('Location: ' . base_url('farmer-dashboard.php'));
                }
                exit;
            } else {
                $error = 'Invalid email or password. Please check your credentials.';
            }
        } catch (PDOException $e) {
            $error = 'A database error occurred. Please try again later.';
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container" style="max-width: 500px; padding: 3rem 1.25rem;">
    <div class="card" style="padding: 2.5rem 2rem;">
        <div class="text-center" style="margin-bottom: 2rem;">
            <div class="brand-icon" style="margin: 0 auto 1rem; width: 48px; height: 48px; font-size: 1.5rem; background: linear-gradient(135deg, var(--primary), var(--secondary)); color: white; border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                <i class="fa-solid fa-snowflake"></i>
            </div>
            <h2>Account Login</h2>
            <p class="text-muted">Access your Agri Storage dashboard</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger">
                <span><i class="fa-solid fa-triangle-exclamation"></i></span>
                <span><?php echo e($error); ?></span>
            </div>
        <?php endif; ?>

        <!-- Quick Demo Login Switcher for Judges -->
        <div style="background: #f1f5f9; padding: 1rem; border-radius: var(--radius-md); margin-bottom: 1.5rem; border: 1px solid var(--border-color);">
            <div style="font-size: 0.8rem; font-weight: 700; color: var(--text-muted); text-transform: uppercase; margin-bottom: 0.5rem; letter-spacing: 0.5px;">
                Judge Logins
            </div>
            <div class="judge-logins-grid">
                <button type="button" class="btn btn-outline-primary btn-sm" onclick="quickFill('demo@coldconnect.test', 'demo123')">
                    <i class="fa-solid fa-wheat-awn"></i> Demo Farmer
                </button>
                <button type="button" class="btn btn-outline btn-sm" onclick="quickFill('owner@coldconnect.test', 'owner123')">
                    <i class="fa-solid fa-warehouse"></i> Owner 1 (Haresh)
                </button>
            </div>
            <div>
                <button type="button" class="btn btn-outline btn-sm btn-block" onclick="quickFill('mahesh.owner@coldconnect.test', 'owner123')">
                    <i class="fa-solid fa-warehouse"></i> Owner 2 (Mahesh - Green Fresh Storage)
                </button>
            </div>
        </div>

        <form method="POST" action="<?php echo base_url('login.php'); ?>">
            <div class="form-group" style="margin-bottom: 1.25rem;">
                <label for="login_email">Email Address</label>
                <input type="email" name="email" id="login_email" class="form-control" value="<?php echo e($email); ?>" placeholder="name@example.com" required autofocus>
            </div>

            <div class="form-group" style="margin-bottom: 1.5rem;">
                <label for="login_password">Password</label>
                <input type="password" name="password" id="login_password" class="form-control" placeholder="••••••••" required>
            </div>

            <button type="submit" class="btn btn-primary btn-block btn-lg">
                <i class="fa-solid fa-arrow-right-to-bracket"></i> Sign In
            </button>
        </form>

        <div class="text-center" style="margin-top: 1.5rem; font-size: 0.9rem; color: var(--text-muted);">
            Don't have an account yet? 
            <a href="<?php echo base_url('register.php'); ?>" style="font-weight: 600; color: var(--primary);">Create one here</a>
        </div>
    </div>
</div>

<script>
function quickFill(email, password) {
    document.getElementById('login_email').value = email;
    document.getElementById('login_password').value = password;
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
