<?php
require_once 'config/database.php';
requireLogin();

$database = new Database();
$db = $database->getConnection();

// Get cart items
$query = "SELECT ci.id as cart_id, ci.quantity, p.* 
          FROM cart_items ci 
          JOIN products p ON ci.product_id = p.id 
          WHERE ci.user_id = :user_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $_SESSION['user_id']);
$stmt->execute();
$cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate total
$total = 0;
foreach ($cartItems as $item) {
    $total += $item['price'] * $item['quantity'];
}

if (empty($cartItems)) {
    header("Location: cart.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $shipping_address = trim($_POST['shipping_address']);
    $payment_method = $_POST['payment_method'];
    
    if (empty($shipping_address)) {
        $error = "Please enter shipping address";
    } else {
        try {
            $db->beginTransaction();
            
            // Create order
            $orderQuery = "INSERT INTO orders (user_id, total_amount, shipping_address, payment_method, status) 
                          VALUES (:user_id, :total, :address, :payment, 'pending')";
            $orderStmt = $db->prepare($orderQuery);
            $orderStmt->bindParam(':user_id', $_SESSION['user_id']);
            $orderStmt->bindParam(':total', $total);
            $orderStmt->bindParam(':address', $shipping_address);
            $orderStmt->bindParam(':payment', $payment_method);
            $orderStmt->execute();
            
            $order_id = $db->lastInsertId();
            
            // Add order items and update stock
            foreach ($cartItems as $item) {
                $itemQuery = "INSERT INTO order_items (order_id, product_id, quantity, price) 
                             VALUES (:order_id, :product_id, :quantity, :price)";
                $itemStmt = $db->prepare($itemQuery);
                $itemStmt->bindParam(':order_id', $order_id);
                $itemStmt->bindParam(':product_id', $item['id']);
                $itemStmt->bindParam(':quantity', $item['quantity']);
                $itemStmt->bindParam(':price', $item['price']);
                $itemStmt->execute();
                
                // Update stock
                $stockQuery = "UPDATE products SET stock = stock - :quantity WHERE id = :product_id";
                $stockStmt = $db->prepare($stockQuery);
                $stockStmt->bindParam(':quantity', $item['quantity']);
                $stockStmt->bindParam(':product_id', $item['id']);
                $stockStmt->execute();
            }
            
            // Clear cart
            $clearQuery = "DELETE FROM cart_items WHERE user_id = :user_id";
            $clearStmt = $db->prepare($clearQuery);
            $clearStmt->bindParam(':user_id', $_SESSION['user_id']);
            $clearStmt->execute();
            
            $db->commit();
            
            header("Location: orders.php?success=1");
            exit();
            
        } catch(Exception $e) {
            $db->rollBack();
            $error = "Order processing failed. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - SmartStore</title>
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
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <div class="card">
            <div class="card-header">
                <h2>Checkout</h2>
            </div>
            <div class="card-body">
                <?php if(isset($error)): ?>
                    <div class="alert alert-error"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <h3>Order Summary</h3>
                <table class="cart-table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Quantity</th>
                            <th>Price</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($cartItems as $item): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['name']); ?></td>
                            <td><?php echo $item['quantity']; ?></td>
                            <td>$<?php echo number_format($item['price'], 2); ?></td>
                            <td>$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" style="text-align: right;"><strong>Total:</strong></td>
                            <td><strong>$<?php echo number_format($total, 2); ?></strong></td>
                        </tr>
                    </tfoot>
                </table>
                
                <form method="POST" style="margin-top: 2rem;">
                    <div class="form-group">
                        <label>Shipping Address</label>
                        <textarea name="shipping_address" rows="3" required 
                                  placeholder="Enter your complete shipping address"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>Payment Method</label>
                        <select name="payment_method" required>
                            <option value="">Select payment method</option>
                            <option value="credit_card">Credit Card</option>
                            <option value="paypal">PayPal</option>
                            <option value="bank_transfer">Bank Transfer</option>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn btn-success">Place Order</button>
                    <a href="cart.php" class="btn">Back to Cart</a>
                </form>
            </div>
        </div>
    </div>
</body>
</html>