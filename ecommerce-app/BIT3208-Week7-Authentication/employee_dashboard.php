<?php
// =============================================
// PRACTICAL TASK 2: Employee Portal
// =============================================

$page_title = 'Employee Portal';
require_once 'includes/header.php';
require_once 'config/database.php';

// Check login
if (!isLoggedIn()) {
    header('Location: login.php?error=Please login to access the employee portal');
    exit;
}

$user = getCurrentUser();
$conn = getConnection();

// Get employee data or create if not exists
$stmt = $conn->prepare("SELECT * FROM employees WHERE email = ?");
$stmt->bind_param("s", $user['email']);
$stmt->execute();
$result = $stmt->get_result();
$employee = $result->fetch_assoc();

// If employee record doesn't exist, create one
if (!$employee) {
    $employee_id = 'EMP' . str_pad($user['id'], 3, '0', STR_PAD_LEFT);
    $full_name = $user['full_name'];
    $email = $user['email'];
    $password = $user['password'];
    $department = 'General';
    $position = 'Staff';
    
    $insert = $conn->prepare("INSERT INTO employees (employee_id, full_name, email, password, department, position) VALUES (?, ?, ?, ?, ?, ?)");
    $insert->bind_param("ssssss", $employee_id, $full_name, $email, $password, $department, $position);
    $insert->execute();
    $insert->close();
    
    // Fetch the new employee record
    $stmt = $conn->prepare("SELECT * FROM employees WHERE email = ?");
    $stmt->bind_param("s", $user['email']);
    $stmt->execute();
    $result = $stmt->get_result();
    $employee = $result->fetch_assoc();
}

$conn->close();
?>

<div class="dashboard-container">
    <h1>🏢 Employee Portal</h1>
    
    <div style="background: #f8f9fa; padding: 10px; border-radius: 5px; margin-bottom: 20px;">
        <small style="color: #667eea;">
            <strong>📌 Practical Task 2:</strong> Employee Portal with Registration, Authentication, and Protected Pages
        </small>
    </div>
    
    <div class="nav">
        <div class="nav-left">
            <a href="dashboard.php">🏠 Dashboard</a>
            <a href="employee_dashboard.php" class="active">👔 Employee Portal</a>
        </div>
        <div>
            <span class="nav-user">👤 <?php echo htmlspecialchars($employee['full_name']); ?></span>
            <a href="logout.php" class="btn btn-danger btn-sm">Logout</a>
        </div>
    </div>
    
    <!-- Employee