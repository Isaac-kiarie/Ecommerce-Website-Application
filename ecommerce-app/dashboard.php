<?php
require_once 'config/database.php';
requireLogin();

$database = new Database();
$db = $database->getConnection();

// Get user's recent orders
$orderQuery = "SELECT * FROM orders WHERE user_id = :user_id ORDER BY order_date DESC LIMIT 5";
$orderStmt = $db->prepare($orderQuery);
$orderStmt->bindParam(':user_id', $_SESSION['user_id']);
$orderStmt->execute();
$recentOrders = $orderStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - SmartStore</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="navbar-container">
            <a href="index.php" class="logo">SmartStore</a>
            <ul class="nav-links">
                <li><a href="index.php">Home</a></li>
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="cart.php">Cart</a></li>
                <li><a href="orders.php">My Orders</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <div class="card">
            <div class="card-header">
                <h2>Welcome, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</h2>
            </div>
            <div class="card-body">
                <div class="stats-grid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem; margin-bottom: 2rem;">
                    <div class="card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                        <div class="card-body">
                            <h3>Account Information</h3>
                            <p>Email: <?php echo htmlspecialchars($_SESSION['user_email']); ?></p>
                            <p>Role: <?php echo htmlspecialchars($_SESSION['user_role']); ?></p>
                        </div>
                    </div>
                    
                    <div class="card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white;">
                        <div class="card-body">
                            <h3>Quick Actions</h3>
                            <a href="cart.php" style="color: white; display: block;">View Cart</a>
                            <a href="orders.php" style="color: white; display: block;">My Orders</a>
                        </div>
                    </div>
                </div>
                
                <h3>Recent Orders</h3>
                <?php if(count($recentOrders) > 0): ?>
                    <table class="cart-table">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Date</th>
                                <th>Total</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($recentOrders as $order): ?>
                            <tr>
                                <td>#<?php echo $order['id']; ?></td>
                                <td><?php echo date('M d, Y', strtotime($order['order_date'])); ?></td>
                                <td>$<?php echo number_format($order['total_amount'], 2); ?></td>
                                <td>
                                    <span style="background: <?php echo $order['status'] == 'completed' ? '#27ae60' : '#f39c12'; ?>; 
                                                 color: white; padding: 0.25rem 0.5rem; border-radius: 4px;">
                                        <?php echo ucfirst($order['status']); ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>No orders yet. <a href="index.php">Start shopping!</a></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>