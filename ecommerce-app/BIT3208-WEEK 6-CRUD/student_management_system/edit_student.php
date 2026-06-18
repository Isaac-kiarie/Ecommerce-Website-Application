<?php
// =============================================
// Demonstration 5: Update and Delete Records
// Class Activity 3: Edit and Delete Records
// =============================================

require_once 'config/database.php';

$message = '';
$messageType = '';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header('Location: view_students.php');
    exit;
}

$conn = getConnection();

// Fetch student data
$query = "SELECT * FROM students WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    header('Location: view_students.php');
    exit;
}

$student = $result->fetch_assoc();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $course = trim($_POST['course'] ?? '');
    
    $errors = [];
    
    if (empty($full_name)) $errors[] = "Full name is required";
    if (empty($email)) $errors[] = "Email is required";
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Invalid email format";
    if (empty($course)) $errors[] = "Course is required";
    
    if (empty($errors)) {
        // Check if email exists for another student
        $checkQuery = "SELECT id FROM students WHERE email = ? AND id != ?";
        $stmt = $conn->prepare($checkQuery);
        $stmt->bind_param("si", $email, $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $message = "❌ Email already exists for another student!";
            $messageType = "danger";
        } else {
            $updateQuery = "UPDATE students SET full_name = ?, email = ?, course = ? WHERE id = ?";
            $stmt = $conn->prepare($updateQuery);
            $stmt->bind_param("sssi", $full_name, $email, $course, $id);
            
            if ($stmt->execute()) {
                $message = "✅ Student updated successfully!";
                $messageType = "success";
                $student['full_name'] = $full_name;
                $student['email'] = $email;
                $student['course'] = $course;
            } else {
                $message = "❌ Error: " . $conn->error;
                $messageType = "danger";
            }
        }
        $stmt->close();
    } else {
        $message = "❌ " . implode("<br>", $errors);
        $messageType = "danger";
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Student</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <h1>🎓 Student Management System</h1>
        
        <div class="nav">
            <a href="index.php">🏠 Home</a>
            <a href="add_student.php">➕ Add Student</a>
            <a href="view_students.php" class="active">📋 View Students</a>
        </div>

        <h2>Demonstration 5 & Activity 3: Edit Student Record</h2>
        
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>

        <div class="demo-box">
            <h3>✏️ Editing Student #<?php echo $id; ?></h3>
            <p>Update the student information below.</p>
        </div>

        <form method="POST" action="">
            <div class="form-group">
                <label for="full_name">Full Name *</label>
                <input type="text" id="full_name" name="full_name" 
                       value="<?php echo htmlspecialchars($student['full_name']); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="email">Email *</label>
                <input type="email" id="email" name="email" 
                       value="<?php echo htmlspecialchars($student['email']); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="course">Course *</label>
                <select id="course" name="course" required>
                    <option value="">-- Select Course --</option>
                    <option value="Computer Science" <?php echo ($student['course'] == 'Computer Science') ? 'selected' : ''; ?>>Computer Science</option>
                    <option value="Mathematics" <?php echo ($student['course'] == 'Mathematics') ? 'selected' : ''; ?>>Mathematics</option>
                    <option value="Physics" <?php echo ($student['course'] == 'Physics') ? 'selected' : ''; ?>>Physics</option>
                    <option value="Chemistry" <?php echo ($student['course'] == 'Chemistry') ? 'selected' : ''; ?>>Chemistry</option>
                    <option value="Biology" <?php echo ($student['course'] == 'Biology') ? 'selected' : ''; ?>>Biology</option>
                    <option value="Engineering" <?php echo ($student['course'] == 'Engineering') ? 'selected' : ''; ?>>Engineering</option>
                    <option value="Business" <?php echo ($student['course'] == 'Business') ? 'selected' : ''; ?>>Business</option>
                    <option value="Information Technology" <?php echo ($student['course'] == 'Information Technology') ? 'selected' : ''; ?>>Information Technology</option>
                </select>
            </div>
            
            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                <button type="submit" class="btn btn-success">💾 Update Student</button>
                <a href="view_students.php" class="btn">❌ Cancel</a>
            </div>
        </form>
    </div>
</body>
</html>