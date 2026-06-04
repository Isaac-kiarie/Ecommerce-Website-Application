<?php
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

// ========== CHANGE 1: Enhanced product fetching with CRUD helper ==========
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
$category = isset($_GET['category']) ? intval($_GET['category']) : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';
$min_price = isset($_GET['min_price']) ? floatval($_GET['min_price']) : 0;
$max_price = isset($_GET['max_price']) ? floatval($_GET['max_price']) : 10000;

// Build query with all CRUD filters
$query = "SELECT p.*, c.name as category_name 
          FROM products p 
          LEFT JOIN categories c ON p.category_id = c.id 
          WHERE 1=1";
$params = [];

if ($search) {
    $query .= " AND (p.name LIKE :search OR p.description LIKE :search)";
    $params[':search'] = "%$search%";
}
if ($category) {
    $query .= " AND p.category_id = :category";
    $params[':category'] = $category;
}
if ($min_price > 0) {
    $query .= " AND p.price >= :min_price";
    $params[':min_price'] = $min_price;
}
if ($max_price < 10000) {
    $query .= " AND p.price <= :max_price";
    $params[':max_price'] = $max_price;
}

// Sorting options
switch($sort) {
    case 'price_low':
        $query .= " ORDER BY p.price ASC";
        break;
    case 'price_high':
        $query .= " ORDER BY p.price DESC";
        break;
    case 'name_asc':
        $query .= " ORDER BY p.name ASC";
        break;
    default:
        $query .= " ORDER BY p.created_at DESC";
}

$stmt = $db->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ========== CHANGE 2: Using CRUD helper for categories ==========
$categories = $database->read('categories', [], 'name', '');

// ========== CHANGE 3: Featured products using CRUD helper ==========
$featured_products = $database->read('products', [], 'created_at DESC', '4');

// ========== CHANGE 4: Cart count using CRUD helper ==========
$cart_count = 0;
if (isLoggedIn()) {
    $cart_items = $database->read('cart_items', ['user_id' => $_SESSION['user_id']]);
    foreach ($cart_items as $item) {
        $cart_count += $item['quantity'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SmartStore - E-Commerce Platform</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        /* Additional styles for price filter (layout unchanged) */
        .price-filter {
            display: flex;
            gap: 1rem;
            align-items: center;
        }
        .price-filter input {
            width: 100px;
        }
        .featured-section {
            margin-bottom: 2rem;
        }
        .cart-badge {
            background: #e74c3c;
            color: white;
            border-radius: 50%;
            padding: 2px 6px;
            font-size: 0.7rem;
            margin-left: 5px;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="navbar-container">
            <a href="index.php" class="logo">SmartStore</a>
            <ul class="nav-links">
                <li><a href="index.php">Home</a></li>
                <?php if(isLoggedIn()): ?>
                    <li><a href="dashboard.php">Dashboard</a></li>
                    <li><a href="cart.php">Cart <?php if($cart_count > 0): ?><span class="cart-badge"><?php echo $cart_count; ?></span><?php endif; ?></a></li>
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
        <!-- Featured Products Section - NEW with CRUD -->
        <?php if(!$search && !$category): ?>
        <div class="featured-section">
            <h2>Featured Products</h2>
            <div class="products-grid">
                <?php foreach($featured_products as $product): ?>
                <div class="product-card">
                    <div class="product-image" style="background: linear-gradient(135deg, #667eea, #764ba2); height: 200px; display: flex; align-items: center; justify-content: center; color: white;">
                        🔥 Featured
                    </div>
                    <div class="product-info">
                        <h3 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h3>
                        <p class="product-price">$<?php echo number_format($product['price'], 2); ?></p>
                        <?php if(isLoggedIn() && $product['stock'] > 0): ?>
                            <form action="cart.php" method="POST">
                                <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                <input type="hidden" name="action" value="add">
                                <button type="submit" class="btn btn-primary" style="width: 100%;">Add to Cart</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
        
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
                <select name="sort">
                    <option value="newest" <?php echo $sort == 'newest' ? 'selected' : ''; ?>>Newest First</option>
                    <option value="price_low" <?php echo $sort == 'price_low' ? 'selected' : ''; ?>>Price: Low to High</option>
                    <option value="price_high" <?php echo $sort == 'price_high' ? 'selected' : ''; ?>>Price: High to Low</option>
                    <option value="name_asc" <?php echo $sort == 'name_asc' ? 'selected' : ''; ?>>Name A-Z</option>
                </select>
                <div class="price-filter">
                    <input type="number" name="min_price" placeholder="Min $" value="<?php echo $min_price ?: ''; ?>">
                    <span>-</span>
                    <input type="number" name="max_price" placeholder="Max $" value="<?php echo $max_price != 10000 ? $max_price : ''; ?>">
                </div>
                <button type="submit" class="btn btn-primary">Search</button>
                <?php if($search || $category || $min_price || $sort != 'newest'): ?>
                    <a href="index.php" class="btn">Clear</a>
                <?php endif; ?>
            </form>
        </div>

        <!-- Product Grid -->
        <div class="products-grid">
            <?php if(count($products) > 0): ?>
                <?php foreach($products as $product): ?>
                    <div class="product-card">
                        <div class="product-image" style="background: #f0f0f0; display: flex; align-items: center; justify-content: center;">
                            <?php if($product['stock'] < 5 && $product['stock'] > 0): ?>
                                <div style="position: absolute; background: #f39c12; color: white; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.8rem; margin-top: -90px; margin-left: -100px;">
                                    Low Stock!
                                </div>
                            <?php elseif($product['stock'] == 0): ?>
                                <div style="position: absolute; background: #e74c3c; color: white; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.8rem; margin-top: -90px; margin-left: -100px;">
                                    Out of Stock
                                </div>
                            <?php endif; ?>
                            📦 Product
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
                            <?php elseif($product['stock'] == 0): ?>
                                <button class="btn" style="width: 100%; background: #ccc; cursor: not-allowed;" disabled>
                                    Out of Stock
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No products found. Try adjusting your filters.</p>
            <?php endif; ?>
        </div>
    </div>

    <footer class="footer">
        <p>&copy; <?php echo date('Y'); ?> SmartStore. All rights reserved.</p>
    </footer>
</body>
</html>