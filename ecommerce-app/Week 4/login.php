<?php
/**
 * Login Page - User Authentication
 * Handles user login with role-based redirection
 */

require_once ('../config/database.php');

// ========== REDIRECT IF ALREADY LOGGED IN ==========
if (isLoggedIn()) {
    // Redirect based on user role
    if (isAdmin()) {
        header("Location: admin/");
    } else {
        header("Location: dashboard.php");
    }
    exit();
}

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $database = new Database();
    $db = $database->getConnection();
    
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    // Validation
    if (empty($email) || empty($password)) {
        $error = "Please enter both email and password";
    } else {
        // Fetch user with role
        $query = "SELECT id, name, email, password, role FROM users WHERE email = :email";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        if ($stmt->rowCount() == 1) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Verify password
            if (password_verify($password, $user['password'])) {
                // Store user data in session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['role'];
                
                // Regenerate session ID for security (prevents session fixation)
                if (function_exists('regenerateSession')) {
                    regenerateSession();
                } else {
                    session_regenerate_id(true);
                }
                
                // Redirect based on role
                if ($user['role'] == 'admin') {
                    header("Location: admin/index.php");
                } else {
                    header("Location: dashboard.php");
                }
                exit();
            } else {
                $error = "Invalid password. Please try again.";
            }
        } else {
            $error = "Email address not found. Please register first.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - SmartStore</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .login-demo {
            margin-top: 1rem;
            padding: 0.75rem;
            background: #f8f9fa;
            border-radius: 8px;
            font-size: 0.8rem;
        }
        .demo-credentials {
            font-family: monospace;
            background: #e9ecef;
            padding: 0.5rem;
            border-radius: 4px;
            margin-top: 0.5rem;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="navbar-container">
            <a href="index.php" class="logo">SmartStore</a>
            <ul class="nav-links">
                <li><a href="index.php">Home</a></li>
                <li><a href="login.php">Login</a></li>
                <li><a href="register.php">Register</a></li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <div class="card" style="max-width: 500px; margin: 0 auto;">
            <div class="card-header">
                <h2>Login to Your Account</h2>
                <p>Welcome back! Please enter your credentials.</p>
            </div>
            <div class="card-body">
                <?php if($error): ?>
                    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                
                <form method="POST" action="" novalidate>
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" 
                               id="email" 
                               name="email" 
                               value="<?php echo htmlspecialchars($email); ?>" 
                               required 
                               placeholder="your@email.com">
                    </div>
                    
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" 
                               id="password" 
                               name="password" 
                               required 
                               placeholder="Enter your password">
                    </div>
                    
                    <button type="submit" class="btn btn-primary" style="width: 100%;">Login</button>
                </form>
                
                <p style="margin-top: 1rem; text-align: center;">
                    Don't have an account? <a href="register.php">Register here</a>
                </p>
                
                <!-- Demo Credentials (Remove in production) -->
                <div class="login-demo">
                    <strong>📝 Demo Credentials:</strong>
                    <div class="demo-credentials">
                        <strong>Customer:</strong> customer@example.com / password123<br>
                        <strong>Admin:</strong> admin@example.com / admin123
                    </div>
                    <small style="color: #666;">Note: Create these users in your database first</small>
                </div>
            </div>
        </div>
    </div>
    
    <footer class="footer">
        <p>&copy; <?php echo date('Y'); ?> SmartStore. All rights reserved.</p>
    </footer>
</body>
</html>