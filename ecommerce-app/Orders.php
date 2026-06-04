<?php
require_once 'config/database.php';
requireLogin();

$database = new Database();
$db = $database->getConnection();

// Get all user orders
$query = "SELECT * FROM orders WHERE user_id = :user_id ORDER BY order_date DESC";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $_SESSION['user_id']);
$stmt->execute();
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - SmartStore</title>
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
                <h2>My Orders</h2>
            </div>
            <div class="card-body">
                <?php if(isset($_GET['success'])): ?>
                    <div class="alert alert-success">Order placed successfully!</div>
                <?php endif; ?>
                
                <?php if(count($orders) > 0): ?>
                    <?php foreach($orders as $order): ?>
                        <div class="card" style="margin-bottom: 1rem;">
                            <div class="card-body">
                                <div style="display: flex; justify-content: space-between; flex-wrap: wrap;">
                                    <div>
                                        <strong>Order #<?php echo $order['id']; ?></strong><br>
                                        Date: <?php echo date('F d, Y', strtotime($order['order_date'])); ?><br>
                                        Total: $<?php echo number_format($order['total_amount'], 2); ?>
                                    </div>
                                    <div>
                                        <strong>Status:</strong>
                                        <span style="background: <?php 
                                            echo $order['status'] == 'completed' ? '#27ae60' : 
                                                ($order['status'] == 'cancelled' ? '#e74c3c' : '#f39c12'); 
                                        ?>; color: white; padding: 0.25rem 0.5rem; border-radius: 4px;">
                                            <?php echo ucfirst($order['status']); ?>
                                        </span>
                                    </div>
                                </div>
                                <div style="margin-top: 1rem;">
                                    <strong>Shipping Address:</strong><br>
                                    <?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?>
                                </div>
                                <div style="margin-top: 1rem;">
                                    <strong>Payment Method:</strong><br>
                                    <?php echo ucfirst(str_replace('_', ' ', $order['payment_method'])); ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="alert alert-info">
                        You haven't placed any orders yet. <a href="index.php">Start shopping!</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>