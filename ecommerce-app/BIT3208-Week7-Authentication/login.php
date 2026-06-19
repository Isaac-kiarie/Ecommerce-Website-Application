<?php
// =============================================
// CLASS DEMONSTRATION 2: User Login
// CLASS ACTIVITY 2: Build a Login System
// =============================================

$page_title = 'Login';
require_once 'includes/header.php';
require_once 'config/database.php';

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = "Please fill in all fields";
    } else {
        $conn = getConnection();
        
        // Get user by email
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $error = "Invalid email or password";
        } else {
            $user = $result->fetch_assoc();
            
            // Verify password (Demonstration 2)
            if (verifyPassword($password, $user['password'])) {
                // Create session (Demonstration 2)
                startSession();
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_name'] = $user['full_name'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['logged_in'] = true;
                
                // Update last login
                $updateStmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                $updateStmt->bind_param("i", $user['id']);
                $updateStmt->execute();
                $updateStmt->close();
                
                // Redirect to dashboard
                header('Location: dashboard.php');
                exit;
            } else {
                $error = "Invalid email or password";
            }
        }
        
        $stmt->close();
        $conn->close();
    }
}
?>

<div class="auth-container">
    <h1>🔐 Login</h1>
    <p class="subtitle">Welcome back! Please login to continue</p>
    
    <!-- Demonstration 2: Login Form -->
    <div style="background: #f8f9fa; padding: 10px; border-radius: 5px; margin-bottom: 20px;">
        <small style="color: #667eea;">
            <strong>📌 Demonstration 2:</strong> User Login with Session Creation
        </small>
    </div>
    
    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($_GET['error']); ?></div>
    <?php endif; ?>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if (isset($_GET['registered'])): ?>
        <div class="alert alert-success">Registration successful! Please login.</div>
    <?php endif; ?>
    
    <form method="POST" action="" id="loginForm">
        <div class="form-group">
            <label for="email">Email Address</label>
            <input type="email" id="email" name="email" 
                   value="<?php echo htmlspecialchars($email); ?>"
                   placeholder="Enter your email" required>
        </div>
        
        <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" 
                   placeholder="Enter your password" required>
        </div>
        
        <button type="submit" class="btn">Login</button>
    </form>
    
    <div class="text-center mt-20">
        <p>Don't have an account? <a href="register.php">Register here</a></p>
        <p style="margin-top: 10px;">
            <small style="color: #999;">
                Demo accounts: john@example.com / Password123!
            </small>
        </p>
    </div>
</div>

<script>
document.getElementById('loginForm').addEventListener('submit', function(e) {
    const email = document.getElementById('email').value.trim();
    const password = document.getElementById('password').value;
    
    if (!email || !password) {
        e.preventDefault();
        alert('Please fill in all fields');
        return false;
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>