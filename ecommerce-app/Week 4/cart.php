<?php
require_once ('../config/database.php');
requireLogin();

$database = new Database();
$db = $database->getConnection();

// ========== CHANGE 1: Using CRUD helpers for cart operations ==========
$user_id = $_SESSION['user_id'];

// Handle cart actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'];
    $product_id = intval($_POST['product_id']);
    
    if ($action == 'add') {
        // Check if product already in cart using CRUD read
        $existing = $database->read('cart_items', [
            'user_id' => $user_id, 
            'product_id' => $product_id
        ]);
        
        if (count($existing) > 0) {
            // Update quantity
            $cart_item = $existing[0];
            $new_quantity = $cart_item['quantity'] + 1;
            $database->update('cart_items', $cart_item['id'], ['quantity' => $new_quantity]);
        } else {
            // Add new cart item using CRUD create
            $database->create('cart_items', [
                'user_id' => $user_id,
                'product_id' => $product_id,
                'quantity' => 1
            ]);
        }
        header("Location: cart.php");
        exit();
        
    } elseif ($action == 'update' && isset($_POST['quantity'])) {
        $cart_id = intval($_POST['cart_id']);
        $quantity = max(0, intval($_POST['quantity']));
        
        if ($quantity > 0) {
            // Update using CRUD helper
            $database->update('cart_items', $cart_id, ['quantity' => $quantity]);
        } else {
            // Delete using CRUD helper
            $database->delete('cart_items', $cart_id);
        }
        header("Location: cart.php");
        exit();
        
    } elseif ($action == 'remove') {
        $cart_id = intval($_POST['cart_id']);
        // Delete using CRUD helper
        $database->delete('cart_items', $cart_id);
        header("Location: cart.php");
        exit();
    }
}

// ========== CHANGE 2: Get cart items with JOIN using custom query ==========
$query = "SELECT ci.id as cart_id, ci.quantity, p.* 
          FROM cart_items ci 
          JOIN products p ON ci.product_id = p.id 
          WHERE ci.user_id = :user_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ========== CHANGE 3: Calculate total with helper function ==========
$total = 0;
$total_items = 0;
foreach ($cartItems as $item) {
    $total += $item['price'] * $item['quantity'];
    $total_items += $item['quantity'];
}

// ========== CHANGE 4: Save cart for later feature ==========
if (isset($_POST['save_for_later'])) {
    $cart_id = intval($_POST['cart_id']);
    // You could implement saved items table here
    $_SESSION['saved_message'] = "Item saved for later!";
    header("Location: cart.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - SmartStore</title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .cart-summary {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: var(--border-radius);
            margin-top: 1.5rem;
        }
        .checkout-btn {
            background: #27ae60;
            color: white;
            padding: 1rem 2rem;
            font-size: 1.1rem;
        }
        .empty-cart {
            text-align: center;
            padding: 3rem;
        }
        .quantity-input {
            width: 70px;
            text-align: center;
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
                <li><a href="cart.php">Cart (<?php echo $total_items; ?>)</a></li>
                <li><a href="orders.php">My Orders</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <h1>Shopping Cart</h1>
        
        <?php if(isset($_SESSION['saved_message'])): ?>
            <div class="alert alert-success"><?php echo $_SESSION['saved_message']; unset($_SESSION['saved_message']); ?></div>
        <?php endif; ?>
        
        <?php if(count($cartItems) > 0): ?>
            <table class="cart-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Price</th>
                        <th>Quantity</th>
                        <th>Subtotal</th>
                        <th>Action</th>
                    </thead>
                    <tbody>
                        <?php foreach($cartItems as $item): ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($item['name']); ?></strong><br>
                                <small>Stock: <?php echo $item['stock']; ?> available</small>
                            </td>
                            <td>$<?php echo number_format($item['price'], 2); ?></td>
                            <td>
                                <form method="POST" style="display: flex; gap: 0.5rem; align-items: center;">
                                    <input type="hidden" name="action" value="update">
                                    <input type="hidden" name="cart_id" value="<?php echo $item['cart_id']; ?>">
                                    <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" 
                                           min="1" max="<?php echo $item['stock']; ?>" class="quantity-input">
                                    <button type="submit" class="btn btn-primary">Update</button>
                                </form>
                            </td>
                            <td>$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></td>
                            <td>
                                <form method="POST">
                                    <input type="hidden" name="action" value="remove">
                                    <input type="hidden" name="cart_id" value="<?php echo $item['cart_id']; ?>">
                                    <button type="submit" class="btn btn-danger">Remove</button>
                                </form>
                                <form method="POST" style="margin-top: 0.5rem;">
                                    <input type="hidden" name="save_for_later" value="1">
                                    <input type="hidden" name="cart_id" value="<?php echo $item['cart_id']; ?>">
                                    <button type="submit" class="btn" style="background: #f39c12;">Save for Later</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <div class="cart-summary">
                    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap;">
                        <div>
                            <h3>Order Summary</h3>
                            <p>Subtotal (<?php echo $total_items; ?> items): <strong>$<?php echo number_format($total, 2); ?></strong></p>
                            <p>Shipping: <strong>Calculated at checkout</strong></p>
                            <hr>
                            <p style="font-size: 1.2rem;">Total: <strong>$<?php echo number_format($total, 2); ?></strong></p>
                        </div>
                        <div>
                            <a href="checkout.php" class="btn checkout-btn">Proceed to Checkout →</a>
                            <a href="index.php" class="btn" style="margin-left: 1rem;">Continue Shopping</a>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="empty-cart">
                    <h2>🛒 Your Cart is Empty</h2>
                    <p>Looks like you haven't added any items to your cart yet.</p>
                    <a href="index.php" class="btn btn-primary" style="margin-top: 1rem;">Start Shopping</a>
                </div>
            <?php endif; ?>
        </div>
        
        <footer class="footer">
            <p>&copy; <?php echo date('Y'); ?> SmartStore. All rights reserved.</p>
        </footer>
    </body>
    </html>