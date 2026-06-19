<?php
// =============================================
// CLASS DEMONSTRATION 1: User Registration
// CLASS ACTIVITY 1: Build a Registration System
// =============================================

$page_title = 'Register';
require_once 'includes/header.php';
require_once 'config/database.php';

$error = '';
$success = '';
$full_name = $email = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validation
    $errors = [];
    
    if (empty($full_name)) {
        $errors[] = "Full name is required";
    } elseif (strlen($full_name) < 2) {
        $errors[] = "Full name must be at least 2 characters";
    }
    
    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }
    
    if (empty($password)) {
        $errors[] = "Password is required";
    } elseif (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters";
    } elseif (!preg_match('/[A-Z]/', $password)) {
        $errors[] = "Password must contain at least one uppercase letter";
    } elseif (!preg_match('/[a-z]/', $password)) {
        $errors[] = "Password must contain at least one lowercase letter";
    } elseif (!preg_match('/[0-9]/', $password)) {
        $errors[] = "Password must contain at least one number";
    } elseif (!preg_match('/[!@#$%^&*]/', $password)) {
        $errors[] = "Password must contain at least one special character (!@#$%^&*)";
    }
    
    if (empty($confirm_password)) {
        $errors[] = "Please confirm your password";
    } elseif ($password !== $confirm_password) {
        $errors[] = "Passwords do not match";
    }
    
    if (empty($errors)) {
        $conn = getConnection();
        
        // Check if email already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $error = "Email already registered. Please login or use a different email.";
        } else {
            // Hash password securely (Demonstration 1)
            $hashed_password = hashPassword($password);
            
            // Insert user
            $stmt = $conn->prepare("INSERT INTO users (full_name, email, password) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $full_name, $email, $hashed_password);
            
            if ($stmt->execute()) {
                $success = "Registration successful! You can now <a href='login.php'>login</a>.";
                $full_name = $email = '';
            } else {
                $error = "Registration failed: " . $conn->error;
            }
        }
        
        $stmt->close();
        $conn->close();
    } else {
        $error = implode("<br>", $errors);
    }
}
?>

<div class="auth-container">
    <h1>📝 Register</h1>
    <p class="subtitle">Create your account to get started</p>
    
    <!-- Demonstration 1: Registration Form -->
    <div style="background: #f8f9fa; padding: 10px; border-radius: 5px; margin-bottom: 20px;">
        <small style="color: #667eea;">
            <strong>📌 Demonstration 1:</strong> User Registration with Password Hashing
        </small>
    </div>
    
    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <form method="POST" action="" id="registerForm">
        <div class="form-group">
            <label for="full_name">Full Name *</label>
            <input type="text" id="full_name" name="full_name" 
                   value="<?php echo htmlspecialchars($full_name); ?>"
                   placeholder="Enter your full name" required>
        </div>
        
        <div class="form-group">
            <label for="email">Email Address *</label>
            <input type="email" id="email" name="email" 
                   value="<?php echo htmlspecialchars($email); ?>"
                   placeholder="Enter your email" required>
        </div>
        
        <div class="form-group">
            <label for="password">Password *</label>
            <input type="password" id="password" name="password" 
                   placeholder="Minimum 8 characters" required>
            <div class="password-strength">
                <div class="password-strength-bar" id="strengthBar"></div>
            </div>
            <small style="color: #666;">Must contain uppercase, lowercase, number, and special character</small>
        </div>
        
        <div class="form-group">
            <label for="confirm_password">Confirm Password *</label>
            <input type="password" id="confirm_password" name="confirm_password" 
                   placeholder="Confirm your password" required>
        </div>
        
        <button type="submit" class="btn">Create Account</button>
    </form>
    
    <div class="text-center mt-20">
        <p>Already have an account? <a href="login.php">Login here</a></p>
    </div>
</div>

<script>
// Password strength indicator
document.getElementById('password').addEventListener('input', function() {
    const password = this.value;
    const bar = document.getElementById('strengthBar');
    let strength = 0;
    
    if (password.length >= 8) strength++;
    if (password.match(/[A-Z]/)) strength++;
    if (password.match(/[a-z]/)) strength++;
    if (password.match(/[0-9]/)) strength++;
    if (password.match(/[!@#$%^&*]/)) strength++;
    
    const level = ['', 'weak', 'fair', 'good', 'strong'];
    const classes = ['', 'strength-weak', 'strength-fair', 'strength-good', 'strength-strong'];
    
    bar.className = 'password-strength-bar ' + classes[strength];
});

// Password match validation
document.getElementById('registerForm').addEventListener('submit', function(e) {
    const password = document.getElementById('password').value;
    const confirm = document.getElementById('confirm_password').value;
    
    if (password !== confirm) {
        e.preventDefault();
        alert('Passwords do not match!');
        return false;
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>