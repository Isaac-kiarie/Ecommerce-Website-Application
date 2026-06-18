<?php
// =============================================
// Demonstration 3: Student Registration Form
// Class Activity 1: Build a Student Registration Form
// =============================================

require_once 'config/database.php';

$message = '';
$messageType = '';
$formData = ['full_name' => '', 'email' => '', 'course' => ''];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $course = trim($_POST['course'] ?? '');
    $formData = ['full_name' => $full_name, 'email' => $email, 'course' => $course];
    
    // Validation
    $errors = [];
    
    if (empty($full_name)) {
        $errors['full_name'] = "Full name is required";
    }
    
    if (empty($email)) {
        $errors['email'] = "Email is required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Invalid email format";
    }
    
    if (empty($course)) {
        $errors['course'] = "Please select a course";
    }
    
    if (empty($errors)) {
        $conn = getConnection();
        
        // Check if email exists
        $checkQuery = "SELECT id FROM students WHERE email = ?";
        $stmt = $conn->prepare($checkQuery);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $message = "❌ Email already exists! Please use a different email.";
            $messageType = "danger";
            $errors['email'] = "Email already registered";
        } else {
            // Insert student
            $insertQuery = "INSERT INTO students (full_name, email, course) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($insertQuery);
            $stmt->bind_param("sss", $full_name, $email, $course);
            
            if ($stmt->execute()) {
                $message = "✅ Student registered successfully!";
                $messageType = "success";
                $formData = ['full_name' => '', 'email' => '', 'course' => ''];
            } else {
                $message = "❌ Error: " . $conn->error;
                $messageType = "danger";
            }
        }
        $stmt->close();
        $conn->close();
    } else {
        $message = "❌ Please fix the errors below.";
        $messageType = "danger";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Student</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <h1>🎓 Student Management System</h1>
        
        <div class="nav">
            <a href="index.php">🏠 Home</a>
            <a href="add_student.php" class="active">➕ Add Student</a>
            <a href="view_students.php">📋 View Students</a>
        </div>

        <h2>Demonstration 3 & Activity 1: Student Registration Form</h2>
        
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div class="demo-box">
            <h3>📝 Register New Student</h3>
            <p>Fill in the form below to add a new student to the database.</p>
        </div>

        <form method="POST" action="">
            <div class="form-group">
                <label for="full_name">Full Name *</label>
                <input type="text" id="full_name" name="full_name" 
                       value="<?php echo htmlspecialchars($formData['full_name']); ?>"
                       class="<?php echo isset($errors['full_name']) ? 'error' : ''; ?>"
                       placeholder="Enter full name">
                <?php if (isset($errors['full_name'])): ?>
                    <div class="error-message"><?php echo $errors['full_name']; ?></div>
                <?php endif; ?>
            </div>
            
            <div class="form-group">
                <label for="email">Email Address *</label>
                <input type="email" id="email" name="email" 
                       value="<?php echo htmlspecialchars($formData['email']); ?>"
                       class="<?php echo isset($errors['email']) ? 'error' : ''; ?>"
                       placeholder="Enter email address">
                <?php if (isset($errors['email'])): ?>
                    <div class="error-message"><?php echo $errors['email']; ?></div>
                <?php endif; ?>
            </div>
            
            <div class="form-group">
                <label for="course">Course *</label>
                <select id="course" name="course" class="<?php echo isset($errors['course']) ? 'error' : ''; ?>">
                    <option value="">-- Select Course --</option>
                    <option value="Computer Science" <?php echo ($formData['course'] == 'Computer Science') ? 'selected' : ''; ?>>Computer Science</option>
                    <option value="Mathematics" <?php echo ($formData['course'] == 'Mathematics') ? 'selected' : ''; ?>>Mathematics</option>
                    <option value="Physics" <?php echo ($formData['course'] == 'Physics') ? 'selected' : ''; ?>>Physics</option>
                    <option value="Chemistry" <?php echo ($formData['course'] == 'Chemistry') ? 'selected' : ''; ?>>Chemistry</option>
                    <option value="Biology" <?php echo ($formData['course'] == 'Biology') ? 'selected' : ''; ?>>Biology</option>
                    <option value="Engineering" <?php echo ($formData['course'] == 'Engineering') ? 'selected' : ''; ?>>Engineering</option>
                    <option value="Business" <?php echo ($formData['course'] == 'Business') ? 'selected' : ''; ?>>Business</option>
                    <option value="Information Technology" <?php echo ($formData['course'] == 'Information Technology') ? 'selected' : ''; ?>>Information Technology</option>
                </select>
                <?php if (isset($errors['course'])): ?>
                    <div class="error-message"><?php echo $errors['course']; ?></div>
                <?php endif; ?>
            </div>
            
            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                <button type="submit" class="btn btn-success">➕ Add Student</button>
                <button type="reset" class="btn">🔄 Clear</button>
                <a href="view_students.php" class="btn btn-info">📋 View All</a>
            </div>
        </form>

        <script>
        // Client-side validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const name = document.getElementById('full_name').value.trim();
            const email = document.getElementById('email').value.trim();
            const course = document.getElementById('course').value;
            let errors = [];
            
            if (!name) errors.push('Full name is required');
            if (!email) errors.push('Email is required');
            else if (!email.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) errors.push('Invalid email format');
            if (!course) errors.push('Please select a course');
            
            if (errors.length > 0) {
                e.preventDefault();
                alert('Please fix these errors:\n- ' + errors.join('\n- '));
                return false;
            }
        });
        </script>
    </div>
</body>
</html>