<?php
// =============================================
// Demonstration 5: Delete Records
// Class Activity 3: Delete Student
// =============================================

require_once 'config/database.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header('Location: view_students.php');
    exit;
}

$conn = getConnection();

// Delete student
$query = "DELETE FROM students WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    // Set success message in session
    session_start();
    $_SESSION['message'] = "✅ Student deleted successfully!";
    $_SESSION['message_type'] = "success";
} else {
    session_start();
    $_SESSION['message'] = "❌ Error deleting student: " . $conn->error;
    $_SESSION['message_type'] = "danger";
}

$stmt->close();
$conn->close();

header('Location: view_students.php');
exit;
?>