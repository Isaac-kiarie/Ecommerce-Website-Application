<?php
require_once ('../config/database.php');
requireLogin();

$database = new Database();
$db = $database->getConnection();
$user_id = $_SESSION['user_id'];

// ========== CHANGE 1: Get cart items with stock validation ==========
$query = "SELECT ci.id as cart_id, ci.quantity, p.* 
          FROM cart_items ci 
          JOIN products p ON ci.product_id = p.id 
          WHERE ci.user_id = :user_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate total
$total = 0;
foreach ($cartItems as $item) {
    $total += $item['price'] * $item['quantity'];
}

// ========== CHANGE 2: Stock validation before checkout ==========
$stock_errors = [];
foreach ($cartItems as $item) {
    if ($item['quantity'] > $item['stock']) {
        $stock_errors[] = "{$item['name']} only has {$item['stock']} units available. You requested {$item['quantity']}.";
    }
}

if (empty($cartItems)) {
    header("Location: cart.php");
    exit();
}

// ========== CHANGE 3: Process order with CRUD helpers and transaction ==========
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $shipping_address = sanitizeInput($_POST['shipping_address']);
    $payment_method = $_POST['payment_method'];
    
    if (empty($shipping_address)) {
        $error = "Please enter shipping address";
    } elseif (!empty($stock_errors)) {
        $error = implode("<br>", $stock_errors);
    } else {
        try {
            $db->beginTransaction();
            
            // Create order using CRUD helper
            $order_id = $database->create('orders', [
                'user_id' => $user_id,
                'total_amount' => $total,
                'shipping_address' => $shipping_address,
                'payment_method' => $payment_method,
                'status' => 'pending'
            ]);
            
            if ($order_id) {
                // Add order items and update stock
                foreach ($cartItems as $item) {
                    // Add order item
                    $database->create('order_items', [
                        'order_id' => $order_id,
                        'product_id' => $item['id'],
                        'quantity' => $item['quantity'],
                        'price' => $item['price']
                    ]);
                    
                    // Update stock using direct query
                    $new_stock = $item['stock'] - $item['quantity'];
                    $updateStock = "UPDATE products SET stock = :stock WHERE id = :id";
                    $stockStmt = $db->prepare($updateStock);
                    $stockStmt->bindParam(':stock', $new_stock);
                    $stockStmt->bindParam(':id', $item['id']);
                    $stockStmt->execute();
                }
                
                // Clear cart using CRUD delete with condition
                $clearQuery = "DELETE FROM cart_items WHERE user_id = :user_id";
                $clearStmt = $db->prepare($clearQuery);
                $clearStmt->bindParam(':user_id', $user_id);
                $clearStmt->execute();
                
                $db->commit();
                
                // Set session variable for order confirmation
                $_SESSION['last_order_id'] = $order_id;
                header("Location: orders.php?success=1");
                exit();
            } else {
                throw new Exception("Failed to create order");
            }
        } catch(Exception $e) {
            $db->rollBack();
            $error = "Order processing failed: " . $e->getMessage();
        }
    }
}

// ========== CHANGE 4: Get user saved addresses using CRUD ==========
$user = $database->readOne('users', $user_id);
$saved_addresses = []; // You could implement saved addresses table

// ========== CHANGE 5: Calculate shipping estimate ==========
$shipping_cost = $total > 100 ? 0 : 9.99;
$grand_total = $total + $shipping_cost;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - SmartStore</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .checkout-layout {
            display: grid;
            grid-template-columns: 1fr 1.5fr;
            gap: 2rem;
        }
        .order-summary {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: var(--border-radius);
            height: fit-content;
            position: sticky;
            top: 100px;
        }
        .order-item {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem 0;
            border-bottom: 1px solid #ddd;
        }
        .checkout-form {
            background: white;
            padding: 1.5rem;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
        }
        @media (max-width: 768px) {
            .checkout-layout {
                grid-template-columns: 1fr;
            }
            .order-summary {
                position: static;
            }
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
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <h1>Checkout</h1>
        
        <?php if(isset($error)): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if(!empty($stock_errors)): ?>
            <div class="alert alert-error">
                <strong>Stock Issues:</strong><br>
                <?php echo implode("<br>", $stock_errors); ?>
                <br><br>
                <a href="cart.php" class="btn btn-primary">Update Cart</a>
            </div>
        <?php endif; ?>
        
        <div class="checkout-layout">
            <!-- Checkout Form -->
            <div class="checkout-form">
                <h3>Shipping Information</h3>
                <form method="POST" id="checkoutForm">
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" value="<?php echo htmlspecialchars($user['name'] ?? ''); ?>" readonly disabled style="background: #f5f5f5;">
                    </div>
                    
                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" readonly disabled style="background: #f5f5f5;">
                    </div>
                    
                    <div class="form-group">
                        <label>Shipping Address *</label>
                        <textarea name="shipping_address" rows="3" required placeholder="Street address, City, State, ZIP Code"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>Payment Method *</label>
                        <select name="payment_method" required>
                            <option value="">Select payment method</option>
                            <option value="credit_card">💳 Credit Card</option>
                            <option value="paypal">📧 PayPal</option>
                            <option value="bank_transfer">🏦 Bank Transfer</option>
                            <option value="cash_on_delivery">💵 Cash on Delivery</option>
                        </select>
                    </div>
                    
                    <!-- Credit Card Fields (hidden by default) -->
                    <div id="creditCardFields" style="display: none;">
                        <div class="form-group">
                            <label>Card Number</label>
                            <input type="text" placeholder="1234 5678 9012 3456">
                        </div>
                        <div class="form-row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                            <div class="form-group">
                                <label>Expiry Date</label>
                                <input type="text" placeholder="MM/YY">
                            </div>
                            <div class="form-group">
                                <label>CVV</label>
                                <input type="text" placeholder="123">
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-success" style="width: 100%; padding: 1rem; font-size: 1.1rem;">
                        Place Order - $<?php echo number_format($grand_total, 2); ?>
                    </button>
                </form>
            </div>
            
            <!-- Order Summary -->
            <div class="order-summary">
                <h3>Order Summary</h3>
                <?php foreach($cartItems as $item): ?>
                    <div class="order-item">
                        <span><?php echo htmlspecialchars($item['name']); ?> × <?php echo $item['quantity']; ?></span>
                        <span>$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></span>
                    </div>
                <?php endforeach; ?>
                
                <div class="order-item">
                    <span>Subtotal</span>
                    <span>$<?php echo number_format($total, 2); ?></span>
                </div>
                <div class="order-item">
                    <span>Shipping</span>
                    <span>$<?php echo number_format($shipping_cost, 2); ?></span>
                </div>
                <div class="order-item" style="border-bottom: none; font-weight: bold; font-size: 1.2rem;">
                    <span>Total</span>
                    <span>$<?php echo number_format($grand_total, 2); ?></span>
                </div>
                
                <small style="display: block; margin-top: 1rem; text-align: center;">
                    Free shipping on orders over $100
                </small>
            </div>
        </div>
    </div>
    
    <script>
        // Show/hide credit card fields based on payment method
        const paymentSelect = document.querySelector('select[name="payment_method"]');
        const creditCardFields = document.getElementById('creditCardFields');
        
        if (paymentSelect) {
            paymentSelect.addEventListener('change', function() {
                if (this.value === 'credit_card') {
                    creditCardFields.style.display = 'block';
                } else {
                    creditCardFields.style.display = 'none';
                }
            });
        }
    </script>
    
    <footer class="footer">
        <p>&copy; <?php echo date('Y'); ?> SmartStore. All rights reserved.</p>
    </footer>
</body>
</html>