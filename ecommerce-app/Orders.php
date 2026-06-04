<?php
require_once 'config/database.php';
requireLogin();

$database = new Database();
$db = $database->getConnection();
$user_id = $_SESSION['user_id'];

// ========== CHANGE 1: Get all user orders using CRUD helper ==========
$orders = $database->read('orders', ['user_id' => $user_id], 'order_date DESC');

// ========== CHANGE 2: Function to get order items ==========
function getOrderItems($db, $order_id) {
    $query = "SELECT oi.*, p.name as product_name 
              FROM order_items oi 
              JOIN products p ON oi.product_id = p.id 
              WHERE oi.order_id = :order_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':order_id', $order_id);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// ========== CHANGE 3: Cancel order functionality ==========
if (isset($_GET['cancel']) && is_numeric($_GET['cancel'])) {
    $order_id = intval($_GET['cancel']);
    $order = $database->readOne('orders', $order_id);
    
    if ($order && $order['user_id'] == $user_id && $order['status'] == 'pending') {
        // Restore stock
        $items = getOrderItems($db, $order_id);
        foreach ($items as $item) {
            $updateStock = "UPDATE products SET stock = stock + :quantity WHERE id = :id";
            $stockStmt = $db->prepare($updateStock);
            $stockStmt->bindParam(':quantity', $item['quantity']);
            $stockStmt->bindParam(':id', $item['product_id']);
            $stockStmt->execute();
        }
        
        // Update order status
        $database->update('orders', $order_id, ['status' => 'cancelled']);
        $_SESSION['cancel_message'] = "Order #$order_id has been cancelled.";
        header("Location: orders.php");
        exit();
    }
}

// ========== CHANGE 4: Reorder functionality ==========
if (isset($_GET['reorder']) && is_numeric($_GET['reorder'])) {
    $order_id = intval($_GET['reorder']);
    $order = $database->readOne('orders', $order_id);
    
    if ($order && $order['user_id'] == $user_id) {
        $items = getOrderItems($db, $order_id);
        
        // Clear existing cart
        $clearQuery = "DELETE FROM cart_items WHERE user_id = :user_id";
        $clearStmt = $db->prepare($clearQuery);
        $clearStmt->bindParam(':user_id', $user_id);
        $clearStmt->execute();
        
        // Add items to cart
        foreach ($items as $item) {
            $database->create('cart_items', [
                'user_id' => $user_id,
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity']
            ]);
        }
        
        $_SESSION['reorder_message'] = "Items added to your cart!";
        header("Location: cart.php");
        exit();
    }
}

$cancel_message = $_SESSION['cancel_message'] ?? '';
unset($_SESSION['cancel_message']);
$success_message = isset($_GET['success']) ? "Order placed successfully! Order #{$_SESSION['last_order_id']}" : '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - SmartStore</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .order-card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            margin-bottom: 1.5rem;
            overflow: hidden;
        }
        .order-header {
            background: linear-gradient(135deg, var(--secondary-color), #34495e);
            color: white;
            padding: 1rem 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        }
        .order-body {
            padding: 1.5rem;
        }
        .order-items {
            margin-top: 1rem;
        }
        .order-item-row {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
            border-bottom: 1px solid #eee;
        }
        .tracking-status {
            display: flex;
            justify-content: space-between;
            margin: 1rem 0;
            padding: 1rem 0;
        }
        .status-step {
            text-align: center;
            flex: 1;
            position: relative;
        }
        .status-step.active .status-dot {
            background: #27ae60;
            box-shadow: 0 0 0 3px rgba(39, 174, 96, 0.2);
        }
        .status-step.completed .status-dot {
            background: #27ae60;
        }
        .status-dot {
            width: 12px;
            height: 12px;
            background: #ccc;
            border-radius: 50%;
            margin: 0 auto 0.5rem;
        }
        .status-label {
            font-size: 0.8rem;
            color: #666;
        }
        .status-step.active .status-label {
            color: #27ae60;
            font-weight: bold;
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
                <li><a href="cart.php">Cart</a></li>
                <li><a href="orders.php">My Orders</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <h1>My Orders</h1>
        
        <?php if($success_message): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <?php if($cancel_message): ?>
            <div class="alert alert-info"><?php echo $cancel_message; ?></div>
        <?php endif; ?>
        
        <?php if(count($orders) > 0): ?>
            <?php foreach($orders as $order): ?>
                <?php $order_items = getOrderItems($db, $order['id']); ?>
                <div class="order-card">
                    <div class="order-header">
                        <div>
                            <strong>Order #<?php echo $order['id']; ?></strong><br>
                            <small><?php echo date('F d, Y h:i A', strtotime($order['order_date'])); ?></small>
                        </div>
                        <div>
                            <span class="status-badge status-<?php echo $order['status']; ?>">
                                <?php echo ucfirst($order['status']); ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="order-body">
                        <!-- ========== CHANGE 5: Order tracking timeline ========== -->
                        <div class="tracking-status">
                            <?php
                            $statuses = ['pending', 'processing', 'completed'];
                            $current_index = array_search($order['status'], $statuses);
                            if ($order['status'] == 'cancelled') $current_index = -1;
                            ?>
                            <?php foreach($statuses as $index => $status): ?>
                                <div class="status-step <?php echo ($index <= $current_index) ? 'completed' : ''; ?>">
                                    <div class="status-dot"></div>
                                    <div class="status-label"><?php echo ucfirst($status); ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div class="order-items">
                            <?php foreach($order_items as $item): ?>
                                <div class="order-item-row">
                                    <span><?php echo htmlspecialchars($item['product_name']); ?> × <?php echo $item['quantity']; ?></span>
                                    <span>$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #ddd;">
                            <div>
                                <strong>Total Paid:</strong> $<?php echo number_format($order['total_amount'], 2); ?><br>
                                <small>Payment: <?php echo ucfirst(str_replace('_', ' ', $order['payment_method'])); ?></small>
                            </div>
                            <div>
                                <?php if($order['status'] == 'pending'): ?>
                                    <a href="?cancel=<?php echo $order['id']; ?>" class="btn btn-danger" onclick="return confirm('Cancel this order?')">Cancel Order</a>
                                <?php endif; ?>
                                <?php if($order['status'] == 'completed'): ?>
                                    <a href="?reorder=<?php echo $order['id']; ?>" class="btn btn-primary">Buy Again</a>
                                <?php endif; ?>
                                <button onclick="viewInvoice(<?php echo $order['id']; ?>)" class="btn">View Invoice</button>
                            </div>
                        </div>
                        
                        <div style="margin-top: 1rem; padding: 1rem; background: #f8f9fa; border-radius: var(--border-radius);">
                            <strong>Shipping Address:</strong><br>
                            <?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="card">
                <div class="card-body" style="text-align: center; padding: 3rem;">
                    <h2>📦 No Orders Yet</h2>
                    <p>You haven't placed any orders. Start shopping to see your orders here!</p>
                    <a href="index.php" class="btn btn-primary" style="margin-top: 1rem;">Start Shopping</a>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <script>
        function viewInvoice(orderId) {
            window.open(`admin/orders.php?action=invoice&id=${orderId}`, '_blank');
        }
    </script>
    
    <footer class="footer">
        <p>&copy; <?php echo date('Y'); ?> SmartStore. All rights reserved.</p>
    </footer>
</body>
</html>