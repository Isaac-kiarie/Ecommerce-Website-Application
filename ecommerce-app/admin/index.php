<?php
/**
 * Admin Dashboard - Main Control Panel
 * Displays system statistics and recent activity
 */

require_once '../config/database.php';
requireAdmin(); // Ensures only admin users can access

$database = new Database();
$db = $database->getConnection();

// Fetch system statistics
$stats = [];

// Total products
$query = "SELECT COUNT(*) as total FROM products";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['products'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Total orders
$query = "SELECT COUNT(*) as total FROM orders";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['orders'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Total customers (users with role 'customer')
$query = "SELECT COUNT(*) as total FROM users WHERE role = 'customer'";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['customers'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Total revenue (from completed orders)
$query = "SELECT SUM(total_amount) as total FROM orders WHERE status = 'completed'";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['revenue'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

// Low stock products (less than 10 units)
$query = "SELECT COUNT(*) as total FROM products WHERE stock < 10";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['low_stock'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Recent orders (last 5)
$query = "SELECT o.*, u.name as customer_name 
          FROM orders o 
          JOIN users u ON o.user_id = u.id 
          ORDER BY o.order_date DESC 
          LIMIT 5";
$stmt = $db->prepare($query);
$stmt->execute();
$recent_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Recent products (last 5 added)
$query = "SELECT * FROM products ORDER BY created_at DESC LIMIT 5";
$stmt = $db->prepare($query);
$stmt->execute();
$recent_products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Monthly orders for chart (last 6 months)
$query = "SELECT 
            DATE_FORMAT(order_date, '%Y-%m') as month,
            COUNT(*) as order_count,
            SUM(total_amount) as monthly_revenue
          FROM orders 
          WHERE order_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
          GROUP BY DATE_FORMAT(order_date, '%Y-%m')
          ORDER BY month ASC";
$stmt = $db->prepare($query);
$stmt->execute();
$monthly_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Prepare data for JavaScript chart
$months = [];
$order_counts = [];
$revenues = [];
foreach ($monthly_stats as $stat) {
    $months[] = $stat['month'];
    $order_counts[] = $stat['order_count'];
    $revenues[] = $stat['monthly_revenue'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - SmartStore</title>
    <link rel="stylesheet" href="../css/style.css">
    <!-- Chart.js for analytics charts -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .dashboard-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: linear-gradient(135deg, var(--primary-color), #357abd);
            color: white;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            transition: transform 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-card h3 {
            font-size: 0.9rem;
            opacity: 0.9;
            margin-bottom: 0.5rem;
        }
        
        .stat-card .stat-value {
            font-size: 2rem;
            font-weight: bold;
        }
        
        .stat-card.stat-warning {
            background: linear-gradient(135deg, #f39c12, #e67e22);
        }
        
        .stat-card.stat-success {
            background: linear-gradient(135deg, #27ae60, #229954);
        }
        
        .stat-card.stat-danger {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
        }
        
        .dashboard-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
            gap: 1.5rem;
            margin-top: 1.5rem;
        }
        
        .chart-container {
            background: white;
            border-radius: var(--border-radius);
            padding: 1rem;
            box-shadow: var(--box-shadow);
        }
        
        canvas {
            max-height: 300px;
        }
        
        .quick-actions {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            margin-top: 1rem;
        }
        
        .quick-action-btn {
            padding: 0.5rem 1rem;
            background: var(--primary-color);
            color: white;
            text-decoration: none;
            border-radius: var(--border-radius);
            transition: var(--transition);
        }
        
        .quick-action-btn:hover {
            background: #357abd;
            transform: translateY(-2px);
        }
        
        @media (max-width: 768px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="navbar-container">
            <a href="index.php" class="logo">SmartStore Admin</a>
            <ul class="nav-links">
                <li><a href="index.php">Dashboard</a></li>
                <li><a href="products.php">Products</a></li>
                <li><a href="orders.php">Orders</a></li>
                <li><a href="categories.php">Categories</a></li>
                <li><a href="../logout.php">Logout</a></li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <h1>Admin Dashboard</h1>
        <p>Welcome back, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</p>
        
        <!-- Statistics Cards -->
        <div class="dashboard-stats">
            <div class="stat-card">
                <h3>Total Products</h3>
                <div class="stat-value"><?php echo number_format($stats['products']); ?></div>
                <small>In catalog</small>
            </div>
            
            <div class="stat-card">
                <h3>Total Orders</h3>
                <div class="stat-value"><?php echo number_format($stats['orders']); ?></div>
                <small>All time</small>
            </div>
            
            <div class="stat-card stat-success">
                <h3>Total Revenue</h3>
                <div class="stat-value">$<?php echo number_format($stats['revenue'], 2); ?></div>
                <small>From completed orders</small>
            </div>
            
            <div class="stat-card">
                <h3>Total Customers</h3>
                <div class="stat-value"><?php echo number_format($stats['customers']); ?></div>
                <small>Registered users</small>
            </div>
            
            <div class="stat-card stat-warning">
                <h3>Low Stock Alert</h3>
                <div class="stat-value"><?php echo number_format($stats['low_stock']); ?></div>
                <small>Products with stock &lt; 10</small>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="card" style="margin-bottom: 1.5rem;">
            <div class="card-header">
                <h3>Quick Actions</h3>
            </div>
            <div class="card-body">
                <div class="quick-actions">
                    <a href="products.php#add-product" class="quick-action-btn">+ Add New Product</a>
                    <a href="orders.php" class="quick-action-btn">📦 View All Orders</a>
                    <a href="categories.php" class="quick-action-btn">📁 Manage Categories</a>
                    <a href="../index.php" class="quick-action-btn">🏠 View Store</a>
                </div>
            </div>
        </div>
        
        <!-- Charts and Recent Activity -->
        <div class="dashboard-grid">
            <!-- Monthly Orders Chart -->
            <div class="chart-container">
                <h3>Monthly Orders (Last 6 Months)</h3>
                <canvas id="ordersChart"></canvas>
            </div>
            
            <!-- Monthly Revenue Chart -->
            <div class="chart-container">
                <h3>Monthly Revenue (Last 6 Months)</h3>
                <canvas id="revenueChart"></canvas>
            </div>
        </div>
        
        <!-- Recent Orders -->
        <div class="card" style="margin-top: 1.5rem;">
            <div class="card-header">
                <h3>Recent Orders</h3>
            </div>
            <div class="card-body">
                <?php if(count($recent_orders) > 0): ?>
                    <table class="cart-table">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Customer</th>
                                <th>Date</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Action</th>
                            </thead>
                            <tbody>
                                <?php foreach($recent_orders as $order): ?>
                                <tr>
                                    <td><a href="orders.php?view=<?php echo $order['id']; ?>">#<?php echo $order['id']; ?></a></td>
                                    <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                    <td><?php echo date('M d, Y H:i', strtotime($order['order_date'])); ?></td>
                                    <td>$<?php echo number_format($order['total_amount'], 2); ?></td>
                                    <td>
                                        <span style="background: <?php 
                                            echo $order['status'] == 'completed' ? '#27ae60' : 
                                                ($order['status'] == 'cancelled' ? '#e74c3c' : '#f39c12'); 
                                        ?>; color: white; padding: 0.25rem 0.5rem; border-radius: 4px;">
                                            <?php echo ucfirst($order['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="orders.php?view=<?php echo $order['id']; ?>" class="btn btn-primary" style="padding: 0.25rem 0.5rem;">
                                            View
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <p>No orders found.</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Recent Products -->
            <div class="card" style="margin-top: 1.5rem;">
                <div class="card-header">
                    <h3>Recently Added Products</h3>
                </div>
                <div class="card-body">
                    <?php if(count($recent_products) > 0): ?>
                        <table class="cart-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Price</th>
                                    <th>Stock</th>
                                    <th>Created</th>
                                    <th>Action</th>
                                </thead>
                                <tbody>
                                    <?php foreach($recent_products as $product): ?>
                                    <tr>
                                        <td><?php echo $product['id']; ?></td>
                                        <td><?php echo htmlspecialchars($product['name']); ?></td>
                                        <td>$<?php echo number_format($product['price'], 2); ?></td>
                                        <td style="color: <?php echo $product['stock'] < 10 ? '#e74c3c' : '#27ae60'; ?>">
                                            <?php echo $product['stock']; ?>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($product['created_at'])); ?></td>
                                        <td>
                                            <a href="products.php?edit=<?php echo $product['id']; ?>" class="btn btn-primary" style="padding: 0.25rem 0.5rem;">
                                                Edit
                                            </a>
                                         </td>
                                     '</tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <p>No products found.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Footer -->
            <footer class="footer">
                <p>&copy; <?php echo date('Y'); ?> SmartStore Admin Panel. All rights reserved.</p>
            </footer>
            
            <script>
                // Orders Chart
                const ordersCtx = document.getElementById('ordersChart').getContext('2d');
                new Chart(ordersCtx, {
                    type: 'line',
                    data: {
                        labels: <?php echo json_encode($months); ?>,
                        datasets: [{
                            label: 'Number of Orders',
                            data: <?php echo json_encode($order_counts); ?>,
                            borderColor: '#4a90e2',
                            backgroundColor: 'rgba(74, 144, 226, 0.1)',
                            tension: 0.4,
                            fill: true
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        plugins: {
                            legend: {
                                position: 'top',
                            }
                        }
                    }
                });
                
                // Revenue Chart
                const revenueCtx = document.getElementById('revenueChart').getContext('2d');
                new Chart(revenueCtx, {
                    type: 'bar',
                    data: {
                        labels: <?php echo json_encode($months); ?>,
                        datasets: [{
                            label: 'Revenue ($)',
                            data: <?php echo json_encode($revenues); ?>,
                            backgroundColor: '#27ae60',
                            borderRadius: 8
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        plugins: {
                            legend: {
                                position: 'top',
                            }
                        }
                    }
                });
            </script>
        </body>
        </html>