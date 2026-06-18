<?php
// =============================================
// Demonstration 1 & 2: Database Creation and Connection
// =============================================

require_once 'config/database.php';

$conn = getConnection();
$result = $conn->query("SELECT COUNT(*) as total FROM students");
$total = $result->fetch_assoc()['total'];
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Management System</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <h1>🎓 Student Management System</h1>
        
        <div class="nav">
            <a href="index.php" class="active">🏠 Home</a>
            <a href="add_student.php">➕ Add Student</a>
            <a href="view_students.php">📋 View Students</a>
        </div>

        <h2>Demonstration 1: Creating a Database</h2>
        <div class="demo-box">
            <h3>✅ Database Created Successfully</h3>
            <ul style="margin-left: 20px; line-height: 1.8;">
                <li><strong>Database Name:</strong> student_management</li>
                <li><strong>Table Name:</strong> students</li>
                <li><strong>Fields:</strong> id, full_name, email, course, created_at</li>
                <li><strong>Sample Records:</strong> <?php echo $total; ?> students inserted</li>
            </ul>
            <p><em>📝 Check database.sql file for complete SQL commands</em></p>
        </div>

        <h2>Demonstration 2: PHP Database Connection</h2>
        <?php testConnection(); ?>

        <div class="stats">
            <div class="stat-card">
                <h3><?php echo $total; ?></h3>
                <p>Total Students</p>
            </div>
            <div class="stat-card">
                <h3>✅</h3>
                <p>Database Connected</p>
            </div>
        </div>

        <hr style="margin: 30px 0;">

        <h2>Class Activities</h2>
        <div class="features">
            <div class="feature-card">
                <h3>📝 Activity 1</h3>
                <p>Student Registration Form</p>
                <p style="font-size: 12px; color: #666;">Full Name, Email, Course</p>
                <a href="add_student.php" class="btn">Go to Form</a>
            </div>
            <div class="feature-card">
                <h3>📋 Activity 2</h3>
                <p>Display Student Records</p>
                <p style="font-size: 12px; color: #666;">Retrieve & Display in Table</p>
                <a href="view_students.php" class="btn">View Records</a>
            </div>
            <div class="feature-card">
                <h3>✏️ Activity 3</h3>
                <p>Edit and Delete Records</p>
                <p style="font-size: 12px; color: #666;">Update & Delete Students</p>
                <a href="view_students.php" class="btn">Manage Records</a>
            </div>
        </div>

        <div style="text-align: center; margin-top: 30px; padding: 20px; background: #f8f9fa; border-radius: 5px;">
            <p style="color: #666;">&copy; <?php echo date('Y'); ?> Student Management System - All Demonstrations & Activities</p>
        </div>
    </div>
</body>
</html>