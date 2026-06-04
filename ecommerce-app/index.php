<?php
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

// Handle search and filter parameters
$search = isset($_GET['search']) ? $_GET['search'] : '';
$category = isset($_GET['category']) ? $_GET['category'] : '';

// Build query with filters
$query = "SELECT p.*, c.name as category_name 
          FROM products p 
          LEFT JOIN categories c ON p.category_id = c.id 
          WHERE 1=1";

if ($search) {
    $query .= " AND (p.name LIKE :search OR p.description LIKE :search)";
}
if ($category) {
    $query .= " AND p.category_id = :category";
}

$query .= " ORDER BY p.created_at DESC";

$stmt = $db->prepare($query);

if ($search) {
    $searchParam = "%$search%";
    $stmt->bindParam(':search', $searchParam);
}
if ($category) {
    $stmt->bindParam(':category', $category);
}

$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get categories for filter
$catQuery = "SELECT * FROM categories";
$catStmt = $db->prepare($catQuery);
$catStmt->execute();
$categories = $catStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Commerce Store</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="navbar-container">
            <a href="index.php" class="logo">SmartStore</a>
            <ul class="nav-links">
                <li><a href="index.php">Home</a></li>
                <?php if(isLoggedIn()): ?>
                    <li><a href="dashboard.php">Dashboard</a></li>
                    <li><a href="cart.php">Cart</a></li>
                    <li><a href="orders.php">My Orders</a></li>
                    <?php if(isAdmin()): ?>
                        <li><a href="admin/">Admin Panel</a></li>
                    <?php endif; ?>
                    <li><a href="logout.php">Logout (<?php echo htmlspecialchars($_SESSION['user_name']); ?>)</a></li>
                <?php else: ?>
                    <li><a href="login.php">Login</a></li>
                    <li><a href="register.php">Register</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>

    <div class="container">
        <!-- Search and Filter Section -->
        <div class="search-section">
            <form method="GET" class="search-bar">
                <input type="text" name="search" placeholder="Search products..." 
                       value="<?php echo htmlspecialchars($search); ?>">
                <select name="category">
                    <option value="">All Categories</option>
                    <?php foreach($categories as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>" 
                            <?php echo $category == $cat['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-primary">Search</button>
            </form>
        </div>

        <!-- Product Grid -->
        <div class="products-grid">
            <?php if(count($products) > 0): ?>
                <?php foreach($products as $product): ?>
                    <div class="product-card">
                        <div class="product-image" style="background: #f0f0f0;">
                            <!-- Placeholder for product image -->
                            <div style="height: 200px; display: flex; align-items: center; justify-content: center;">
                                <img src="images/product-placeholder.png" alt="Product Image" style="max-width: 100%; max-height: 100%;">
                            </div>
                        </div>
                        <div class="product-info">
                            <h3 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h3>
                            <p class="product-price">$<?php echo number_format($product['price'], 2); ?></p>
                            <p style="color: #666; font-size: 0.9rem;">
                                Category: <?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?>
                            </p>
                            <p style="margin: 0.5rem 0; color: <?php echo $product['stock'] > 0 ? '#27ae60' : '#e74c3c'; ?>">
                                Stock: <?php echo $product['stock']; ?> units
                            </p>
                            
                            <?php if(isLoggedIn() && $product['stock'] > 0): ?>
                                <form action="cart.php" method="POST" style="margin-top: 1rem;">
                                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                    <input type="hidden" name="action" value="add">
                                    <button type="submit" class="btn btn-primary" style="width: 100%;">
                                        Add to Cart
                                    </button>
                                </form>
                            <?php elseif(!isLoggedIn()): ?>
                                <a href="login.php" class="btn btn-primary" style="display: block; text-align: center;">
                                    Login to Buy
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No products found.</p>
            <?php endif; ?>
        </div>
    </div>

    <footer class="footer">
        <p>&copy; 2024 SmartStore. All rights reserved.</p>
    </footer>
</body>
</html>