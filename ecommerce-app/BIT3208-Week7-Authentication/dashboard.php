<?php
// =============================================
// CLASS DEMONSTRATION 3: Protected Dashboard
// CLASS ACTIVITY 3: Secure a Dashboard
// =============================================

$page_title = 'Dashboard';
require_once 'includes/header.php';
require_once 'config/database.php';

// Check if user is logged in (Demonstration 3)
if (!isLoggedIn()) {
    header('Location: login.php?error=Please login to access the dashboard');
    exit;
}

// Get user data
$user = getCurrentUser();
$conn = getConnection();

// Get statistics
$totalUsers = $conn->query("SELECT COUNT(*) as total FROM users")->fetch_assoc()['total'];
$totalStudents = $conn->query("SELECT COUNT(*) as total FROM users WHERE role = 'student'")->fetch_assoc()['total'];
$totalLecturers = $conn->query("SELECT COUNT(*) as total FROM users WHERE role = 'lecturer'")->fetch_assoc()['total'];

$conn->close();
?>

<div class="dashboard-container">
    <h1>🎯 Dashboard</h1>
    
    <!-- Demonstration 3: Display user info -->
    <div style="background: #f8f9fa; padding: 10px; border-radius: 5px; margin-bottom: 20px;">
        <small style="color: #667eea;">
            <strong>📌 Demonstration 3:</strong> Protected Dashboard - Only accessible to logged-in users
        </small>
    </div>
    
    <!-- Navigation -->
    <div class="nav">
        <div class="nav-left">
            <a href="dashboard.php" class="active">🏠 Dashboard</a>
            <a href="multi_user_dashboard.php">👥 Multi-User</a>
        </div>
        <div>
            <span class="nav-user">👤 <?php echo htmlspecialchars($user['full_name']); ?></span>
            <a href="logout.php" class="btn btn-danger btn-sm">Logout</a>
        </div>
    </div>
    
    <!-- User Information -->
    <div class="user-info">
        <h3>Welcome, <?php echo htmlspecialchars($user['full_name']); ?>! 👋</h3>
        <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
        <p><strong>Role:</strong> 
            <span class="role-badge role-<?php echo $user['role']; ?>">
                <?php echo ucfirst($user['role']); ?>
            </span>
        </p>
        <p><strong>Member Since:</strong> <?php echo date('F d, Y', strtotime($user['created_at'])); ?></p>
        <?php if ($user['last_login']): ?>
            <p><strong>Last Login:</strong> <?php echo date('F d, Y h:i A', strtotime($user['last_login'])); ?></p>
        <?php endif; ?>
    </div>
    
    <!-- Dashboard Statistics -->
    <h2>📊 System Overview</h2>
    <div class="dashboard-grid">
        <div class="card">
            <h3><?php echo $totalUsers; ?></h3>
            <p>Total Users</p>
        </div>
        <div class="card">
            <h3><?php echo $totalStudents; ?></h3>
            <p>Students</p>
        </div>
        <div class="card">
            <h3><?php echo $totalLecturers; ?></h3>
            <p>Lecturers</p>
        </div>
        <div class="card">
            <h3><?php echo date('F d, Y'); ?></h3>
            <p>Today's Date</p>
        </div>
    </div>
    
    <!-- Demonstration 3: Protected content -->
    <h2>📌 Your Protected Content</h2>
    <div style="background: #f8f9fa; padding: 20px; border-radius: 5px; margin: 20px 0;">
        <p>✅ This content is only visible to authenticated users.</p>
        <p>🔒 Your session is secure and protected.</p>
        <p>👤 Logged in as: <strong><?php echo htmlspecialchars($user['full_name']); ?></strong></p>
    </div>
    
    <!-- Activity 3: Logout option -->
    <div style="margin-top: 20px; text-align: center;">
        <a href="logout.php" class="btn btn-danger">🚪 Logout</a>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>