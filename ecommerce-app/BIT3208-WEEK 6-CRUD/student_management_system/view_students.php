<?php
// =============================================
// Demonstration 4: Display Records
// Class Activity 2: Display Student Records
// =============================================

require_once 'config/database.php';

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$conn = getConnection();

if ($search) {
    $searchTerm = "%{$search}%";
    $query = "SELECT * FROM students 
              WHERE full_name LIKE ? 
              OR email LIKE ? 
              OR course LIKE ? 
              ORDER BY created_at DESC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sss", $searchTerm, $searchTerm, $searchTerm);
    $stmt->execute();
    $result = $stmt->get_result();
} else {
    $query = "SELECT * FROM students ORDER BY created_at DESC";
    $result = $conn->query($query);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Students</title>
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

        <h2>Demonstration 4 & Activity 2: Display Student Records</h2>
        
        <div class="demo-box">
            <h3>📋 All Student Records</h3>
            <p>Below is the list of all students currently in the database.</p>
        </div>

        <div class="search-box">
            <input type="text" id="searchInput" placeholder="Search by name, email, or course..." 
                   value="<?php echo htmlspecialchars($search); ?>">
            <button class="btn" onclick="searchStudents()">🔍 Search</button>
            <?php if ($search): ?>
                <a href="view_students.php" class="btn btn-info">Clear</a>
            <?php endif; ?>
        </div>

        <?php 
        $count = $result->num_rows;
        ?>
        <p><strong>Total Students:</strong> <?php echo $count; ?>
        <?php if ($search): ?>
            (Search results for "<?php echo htmlspecialchars($search); ?>")
        <?php endif; ?>
        </p>

        <div class="table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Full Name</th>
                        <th>Email</th>
                        <th>Course</th>
                        <th>Registered</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($count > 0): ?>
                        <?php while($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $row['id']; ?></td>
                                <td><strong><?php echo htmlspecialchars($row['full_name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($row['email']); ?></td>
                                <td><span class="course-badge"><?php echo htmlspecialchars($row['course']); ?></span></td>
                                <td><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
                                <td>
                                    <a href="edit_student.php?id=<?php echo $row['id']; ?>" 
                                       class="btn btn-warning btn-sm">✏️ Edit</a>
                                    <a href="delete_student.php?id=<?php echo $row['id']; ?>" 
                                       class="btn btn-danger btn-sm"
                                       onclick="return confirm('Are you sure you want to delete this student?')">🗑️ Delete</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 40px; color: #666;">
                                <?php if ($search): ?>
                                    No students found matching "<?php echo htmlspecialchars($search); ?>"
                                <?php else: ?>
                                    No students in the database. <a href="add_student.php">Add your first student!</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <script>
        function searchStudents() {
            const searchValue = document.getElementById('searchInput').value.trim();
            if (searchValue) {
                window.location.href = 'view_students.php?search=' + encodeURIComponent(searchValue);
            } else {
                window.location.href = 'view_students.php';
            }
        }

        // Allow Enter key for search
        document.getElementById('searchInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                searchStudents();
            }
        });
        </script>

        <?php $conn->close(); ?>
    </div>
</body>
</html>