<?php
require_once 'config/database.php';
requireLogin();

$database = new Database();
$db = $database->getConnection();

// Handle cart actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'];
    $product_id = $_POST['product_id'];
    $user_id = $_SESSION['user_id'];
    
    if ($action == 'add') {
        // Check if product already in cart
        $checkQuery = "SELECT id, quantity FROM cart_items WHERE user_id = :user_id AND product_id = :product_id";
        $checkStmt = $db->prepare($checkQuery);
        $checkStmt->bindParam(':user_id', $user_id);
        $checkStmt->bindParam(':product_id', $product_id);
        $checkStmt->execute();
        
        if ($checkStmt->rowCount() > 0) {
            // Update quantity
            $cartItem = $checkStmt->fetch(PDO::FETCH_ASSOC);
            $newQuantity = $cartItem['quantity'] + 1;
            $updateQuery = "UPDATE cart_items SET quantity = :quantity WHERE id = :id";
            $updateStmt = $db->prepare($updateQuery);
            $updateStmt->bindParam(':quantity', $newQuantity);
            $updateStmt->bindParam(':id', $cartItem['id']);
            $updateStmt->execute();
        } else {
            // Add new item
            $insertQuery = "INSERT INTO cart_items (user_id, product_id, quantity) VALUES (:user_id, :product_id, 1)";
            $insertStmt = $db->prepare($insertQuery);
            $insertStmt->bindParam(':user_id', $user_id);
            $insertStmt->bindParam(':product_id', $product_id);
            $insertStmt->execute();
        }
    } elseif ($action == 'update' && isset($_POST['quantity'])) {
        $quantity = (int)$_POST['quantity'];
        $cart_id = $_POST['cart_id'];
        
        if ($quantity > 0) {
            $updateQuery = "UPDATE cart_items SET quantity = :quantity WHERE id = :id AND user_id = :user_id";
            $updateStmt = $db->prepare($updateQuery);
            $updateStmt->bindParam(':quantity', $quantity);
            $updateStmt->bindParam(':id', $cart_id);
            $updateStmt->bindParam(':user_id', $user_id);
            $updateStmt->execute();
        } else {
            // Remove if quantity is 0
            $deleteQuery = "DELETE FROM cart_items WHERE id = :id AND user_id = :user_id";
            $deleteStmt = $db->prepare($deleteQuery);
            $deleteStmt->bindParam(':id', $cart_id);
            $deleteStmt->bindParam(':user_id', $user_id);
            $deleteStmt->execute();
        }
    } elseif ($action == 'remove') {
        $cart_id = $_POST['cart_id'];
        $deleteQuery = "DELETE FROM cart_items WHERE id = :id AND user_id = :user_id";
        $deleteStmt = $db->prepare($deleteQuery);
        $deleteStmt->bindParam(':id', $cart_id);
        $deleteStmt->bindParam(':user_id', $user_id);
        $deleteStmt->execute();
    }
    
    header("Location: cart.php");
    exit();
}

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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - SmartStore</title>
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
        <h1>Shopping Cart</h1>
        
        <?php if(count($cartItems) > 0): ?>
            <table class="cart-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Price</th>
                        <th>Quantity</th>
                        <th>Subtotal</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($cartItems as $item): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['name']); ?></td>
                            <td>$<?php echo number_format($item['price'], 2); ?></td>
                            <td>
                                <form method="POST" style="display: flex; gap: 0.5rem;">
                                    <input type="hidden" name="action" value="update">
                                    <input type="hidden" name="cart_id" value="<?php echo $item['cart_id']; ?>">
                                    <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" 
                                           min="0" style="width: 70px;">
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
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3" style="text-align: right;"><strong>Total:</strong></td>
                        <td colspan="2"><strong>$<?php echo number_format($total, 2); ?></strong></td>
                    </tr>
                </tfoot>
            </table>
            
            <div style="margin-top: 2rem; text-align: right;">
                <a href="checkout.php" class="btn btn-success">Proceed to Checkout</a>
            </div>
        <?php else: ?>
            <div class="alert alert-info">
                Your cart is empty. <a href="index.php">Continue shopping</a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>