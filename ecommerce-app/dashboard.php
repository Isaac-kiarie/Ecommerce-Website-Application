<?php
require_once 'config/database.php';
requireLogin();

$database = new Database();
$db = $database->getConnection();
$user_id = $_SESSION['user_id'];

// ========== CHANGE 1: Using CRUD helpers for statistics ==========
$total_orders = $database->count('orders', ['user_id' => $user_id]);
$completed_orders = $database->count('orders', ['user_id' => $user_id, 'status' => 'completed']);
$cart_items = $database->read('cart_items', ['user_id' => $user_id]);

$cart_total = 0;
foreach ($cart_items as $item) {
    $product = $database->readOne('products', $item['product_id']);
    if ($product) {
        $cart_total += $product['price'] * $item['quantity'];
    }
}

// ========== CHANGE 2: Get recent orders using CRUD ==========
$recent_orders = $database->read('orders', ['user_id' => $user_id], 'order_date DESC', '5');

// ========== CHANGE 3: Get total spent ==========
$total_spent = 0;
$all_orders = $database->read('orders', ['user_id' => $user_id, 'status' => 'completed']);
foreach ($all_orders as $order) {
    $total_spent += $order['total_amount'];
}

// ========== CHANGE 4: Wishlist count (if implemented) ==========
$wishlist_count = 0; // You can implement wishlist table
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - SmartStore</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .welcome-banner {
            background: linear-gradient(135deg, var(--primary-color), #357abd);
            color: white;
            padding: 2rem;
            border-radius: var(--border-radius);
            margin-bottom: 2rem;
        }
        .stats-dashboard {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        .stat-card-dashboard {
            background: white;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            text-align: center;
            box-shadow: var(--box-shadow);
            transition: var(--transition);
        }
        .stat-card-dashboard:hover {
            transform: translateY(-5px);
        }
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: var(--primary-color);
        }
        .stat-label {
            color: #666;
            margin-top: 0.5rem;
        }
        .quick-actions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }
        .quick-action-card {
            background: white;
            border-radius: var(--border-radius);
            padding: 1rem;
            text-align: center;
            text-decoration: none;
            color: var(--dark-text);
            transition: var(--transition);
            box-shadow: var(--box-shadow);
        }
        .quick-action-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 20px rgba(0,0,0,0.15);
        }
        .quick-action-icon {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="navbar-container">
            <a href="index.php" class="logo">SmartStore</a>
            <ul class="nav-links">
                <li><a href="index.php">Home</a></li>
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="cart.php">Cart (<?php echo count($cart_items); ?>)</a></li>
                <li><a href="orders.php">My Orders</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <div class="welcome-banner">
            <h1>Welcome back, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</h1>
            <p>Your one-stop shop for amazing products. Track your orders and discover new deals.</p>
        </div>
        
        <!-- Statistics Dashboard -->
        <div class="stats-dashboard">
            <div class="stat-card-dashboard">
                <div class="stat-number"><?php echo $total_orders; ?></div>
                <div class="stat-label">Total Orders</div>
            </div>
            <div class="stat-card-dashboard">
                <div class="stat-number"><?php echo $completed_orders; ?></div>
                <div class="stat-label">Completed Orders</div>
            </div>
            <div class="stat-card-dashboard">
                <div class="stat-number">$<?php echo number_format($cart_total, 2); ?></div>
                <div class="stat-label">Cart Value</div>
            </div>
            <div class="stat-card-dashboard">
                <div class="stat-number">$<?php echo number_format($total_spent, 2); ?></div>
                <div class="stat-label">Total Spent</div>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="card">
            <div class="card-header">
                <h3>Quick Actions</h3>
            </div>
            <div class="card-body">
                <div class="quick-actions-grid">
                    <a href="index.php" class="quick-action-card">
                        <div class="quick-action-icon">🛍️</div>
                        <div>Continue Shopping</div>
                    </a>
                    <a href="cart.php" class="quick-action-card">
                        <div class="quick-action-icon">🛒</div>
                        <div>View Cart</div>
                    </a>
                    <a href="orders.php" class="quick-action-card">
                        <div class="quick-action-icon">📦</div>
                        <div>Track Orders</div>
                    </a>
                    <a href="wishlist.php" class="quick-action-card">
                        <div class="quick-action-icon">❤️</div>
                        <div>Wishlist (<?php echo $wishlist_count; ?>)</div>
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Recent Orders -->
        <div class="card" style="margin-top: 1.5rem;">
            <div class="card-header">
                <h3>Recent Orders</h3>
                <a href="orders.php" style="color: var(--primary-color);">View All →</a>
            </div>
            <div class="card-body">
                <?php if(count($recent_orders) > 0): ?>
                    <table class="cart-table">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Date</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Action</th>
                            </thead>
                            <tbody>
                                <?php foreach($recent_orders as $order): ?>
                                <tr>
                                    <td>#<?php echo $order['id']; ?></a></td>
                                    <td><?php echo date('M d, Y', strtotime($order['order_date'])); ?></a></td>
                                    <td>$<?php echo number_format($order['total_amount'], 2); ?></a></td>
                                    <td>
                                        <span class="status-badge status-<?php echo $order['status']; ?>">
                                            <?php echo ucfirst($order['status']); ?>
                                        </span>
                                    </a>
                                    <td>
                                        <a href="orders.php" class="btn btn-primary" style="padding: 0.25rem 0.5rem;">Details</a>
                                    </a>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p>No orders yet. <a href="index.php">Start shopping!</a></p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Account Information -->
            <div class="card" style="margin-top: 1.5rem;">
                <div class="card-header">
                    <h3>Account Information</h3>
                </div>
                <div class="card-body">
                    <p><strong>Name:</strong> <?php echo htmlspecialchars($_SESSION['user_name']); ?></p>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($_SESSION['user_email']); ?></p>
                    <p><strong>Account Type:</strong> <?php echo ucfirst($_SESSION['user_role']); ?></p>
                    <p><strong>Member Since:</strong> <?php 
                        $user = $database->readOne('users', $user_id);
                        echo date('F d, Y', strtotime($user['created_at'] ?? 'now')); 
                    ?></p>
                </div>
            </div>
        </div>
        
        <footer class="footer">
            <p>&copy; <?php echo date('Y'); ?> SmartStore. All rights reserved.</p>
        </footer>
    </body>
    </html>