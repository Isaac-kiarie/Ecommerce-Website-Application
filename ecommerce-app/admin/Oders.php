<?php
/**
 * Admin Orders Management
 * Complete order tracking, status management, and order details viewing
 */

require_once '../config/database.php';
requireAdmin();

$database = new Database();
$db = $database->getConnection();

// Handle different actions
$action = $_GET['action'] ?? $_POST['action'] ?? 'list';
$message = '';
$error = '';

// --- UPDATE ORDER STATUS ---
if ($action == 'update_status' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $order_id = intval($_POST['order_id']);
    $status = $_POST['status'];
    
    // Validate status
    $valid_statuses = ['pending', 'processing', 'completed', 'cancelled'];
    if (!in_array($status, $valid_statuses)) {
        $error = "Invalid status value.";
    } else {
        $query = "UPDATE orders SET status = :status WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':id', $order_id);
        
        if ($stmt->execute()) {
            $message = "Order #$order_id status updated to " . ucfirst($status) . "!";
            
            // If order is completed, ensure all items are properly recorded
            if ($status == 'completed') {
                // You could add additional logic here for generating invoices, sending emails, etc.
            }
        } else {
            $error = "Failed to update order status.";
        }
    }
    $action = 'list';
}

// --- BULK STATUS UPDATE ---
if ($action == 'bulk_update' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['order_ids']) && isset($_POST['bulk_status'])) {
        $order_ids = $_POST['order_ids'];
        $bulk_status = $_POST['bulk_status'];
        
        $valid_statuses = ['pending', 'processing', 'completed', 'cancelled'];
        if (!in_array($bulk_status, $valid_statuses)) {
            $error = "Invalid status value.";
        } else {
            $placeholders = rtrim(str_repeat('?,', count($order_ids)), ',');
            $query = "UPDATE orders SET status = ? WHERE id IN ($placeholders)";
            $stmt = $db->prepare($query);
            
            $params = array_merge([$bulk_status], $order_ids);
            if ($stmt->execute($params)) {
                $message = count($order_ids) . " orders updated to " . ucfirst($bulk_status) . "!";
            } else {
                $error = "Failed to update orders.";
            }
        }
    }
    $action = 'list';
}

// --- DELETE ORDER (Admin only - rarely used, but available) ---
if ($action == 'delete' && isset($_GET['id'])) {
    $order_id = intval($_GET['id']);
    
    // First delete order items (due to foreign key)
    $deleteItems = "DELETE FROM order_items WHERE order_id = :order_id";
    $stmtItems = $db->prepare($deleteItems);
    $stmtItems->bindParam(':order_id', $order_id);
    $stmtItems->execute();
    
    // Then delete the order
    $query = "DELETE FROM orders WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $order_id);
    
    if ($stmt->execute()) {
        $message = "Order #$order_id deleted successfully!";
    } else {
        $error = "Failed to delete order.";
    }
    $action = 'list';
}

// --- EXPORT ORDERS (CSV) ---
if ($action == 'export') {
    $status_filter = $_GET['status_filter'] ?? '';
    $date_from = $_GET['date_from'] ?? '';
    $date_to = $_GET['date_to'] ?? '';
    
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="orders_export_' . date('Y-m-d') . '.csv"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Order ID', 'Customer Name', 'Customer Email', 'Order Date', 'Total Amount', 'Status', 'Payment Method', 'Shipping Address']);
    
    $query = "SELECT o.*, u.name as customer_name, u.email as customer_email 
              FROM orders o 
              JOIN users u ON o.user_id = u.id 
              WHERE 1=1";
    $params = [];
    
    if ($status_filter) {
        $query .= " AND o.status = :status";
        $params[':status'] = $status_filter;
    }
    if ($date_from) {
        $query .= " AND o.order_date >= :date_from";
        $params[':date_from'] = $date_from;
    }
    if ($date_to) {
        $query .= " AND o.order_date <= :date_to";
        $params[':date_to'] = $date_to . ' 23:59:59';
    }
    
    $query .= " ORDER BY o.order_date DESC";
    $stmt = $db->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $orders = $stmt->fetchAll();
    
    foreach ($orders as $order) {
        fputcsv($output, [
            $order['id'],
            $order['customer_name'],
            $order['customer_email'],
            $order['order_date'],
            $order['total_amount'],
            $order['status'],
            $order['payment_method'],
            $order['shipping_address']
        ]);
    }
    fclose($output);
    exit();
}

// --- INVOICE GENERATION (HTML) ---
if ($action == 'invoice' && isset($_GET['id'])) {
    $order_id = intval($_GET['id']);
    
    // Get order details
    $orderQuery = "SELECT o.*, u.name as customer_name, u.email as customer_email 
                   FROM orders o 
                   JOIN users u ON o.user_id = u.id 
                   WHERE o.id = :id";
    $orderStmt = $db->prepare($orderQuery);
    $orderStmt->bindParam(':id', $order_id);
    $orderStmt->execute();
    $order = $orderStmt->fetch();
    
    if (!$order) {
        die("Order not found.");
    }
    
    // Get order items
    $itemsQuery = "SELECT oi.*, p.name as product_name 
                   FROM order_items oi 
                   JOIN products p ON oi.product_id = p.id 
                   WHERE oi.order_id = :order_id";
    $itemsStmt = $db->prepare($itemsQuery);
    $itemsStmt->bindParam(':order_id', $order_id);
    $itemsStmt->execute();
    $order_items = $itemsStmt->fetchAll();
    ?>
    
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Invoice #<?php echo $order_id; ?></title>
        <style>
            body {
                font-family: Arial, sans-serif;
                margin: 0;
                padding: 20px;
            }
            .invoice-container {
                max-width: 800px;
                margin: 0 auto;
                background: white;
                padding: 30px;
                border: 1px solid #ddd;
            }
            .invoice-header {
                text-align: center;
                border-bottom: 2px solid #333;
                padding-bottom: 20px;
                margin-bottom: 20px;
            }
            .invoice-header h1 {
                margin: 0;
                color: #4a90e2;
            }
            .invoice-details {
                display: flex;
                justify-content: space-between;
                margin-bottom: 30px;
            }
            .invoice-table {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 30px;
            }
            .invoice-table th, .invoice-table td {
                border: 1px solid #ddd;
                padding: 10px;
                text-align: left;
            }
            .invoice-table th {
                background: #f5f5f5;
            }
            .total-row {
                font-weight: bold;
                font-size: 1.2em;
            }
            @media print {
                body {
                    padding: 0;
                }
                .no-print {
                    display: none;
                }
            }
            .no-print {
                text-align: center;
                margin-top: 20px;
            }
            .btn-print {
                padding: 10px 20px;
                background: #4a90e2;
                color: white;
                border: none;
                cursor: pointer;
                border-radius: 5px;
            }
        </style>
    </head>
    <body>
        <div class="invoice-container">
            <div class="invoice-header">
                <h1>SmartStore</h1>
                <p>123 E-Commerce Street, Digital City</p>
                <p>Email: support@smartstore.com | Phone: +1 234 567 890</p>
            </div>
            
            <h2>INVOICE</h2>
            
            <div class="invoice-details">
                <div>
                    <strong>Invoice #:</strong> <?php echo $order_id; ?><br>
                    <strong>Order Date:</strong> <?php echo date('F d, Y', strtotime($order['order_date'])); ?><br>
                    <strong>Status:</strong> <?php echo ucfirst($order['status']); ?>
                </div>
                <div>
                    <strong>Bill To:</strong><br>
                    <?php echo htmlspecialchars($order['customer_name']); ?><br>
                    <?php echo htmlspecialchars($order['customer_email']); ?><br>
                    <?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?>
                </div>
            </div>
            
            <table class="invoice-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Quantity</th>
                        <th>Unit Price</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($order_items as $item): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                        <td><?php echo $item['quantity']; ?></td>
                        <td>$<?php echo number_format($item['price'], 2); ?></td>
                        <td>$<?php echo number_format($item['quantity'] * $item['price'], 2); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr class="total-row">
                        <td colspan="3" style="text-align: right;">Total Amount:</td>
                        <td>$<?php echo number_format($order['total_amount'], 2); ?></td>
                    </tr>
                    <tr>
                        <td colspan="3" style="text-align: right;">Payment Method:</td>
                        <td><?php echo ucfirst(str_replace('_', ' ', $order['payment_method'])); ?></td>
                    </tr>
                </tfoot>
            </table>
            
            <div class="no-print">
                <button class="btn-print" onclick="window.print()">Print Invoice</button>
                <button class="btn-print" onclick="window.close()">Close</button>
            </div>
        </div>
        <script>
            // Auto-print when invoice is opened
            // window.print();
        </script>
    </body>
    </html>
    <?php
    exit();
}

// --- VIEW ORDER DETAILS (AJAX Modal) ---
if ($action == 'view_details' && isset($_GET['id'])) {
    $order_id = intval($_GET['id']);
    
    $orderQuery = "SELECT o.*, u.name as customer_name, u.email as customer_email 
                   FROM orders o 
                   JOIN users u ON o.user_id = u.id 
                   WHERE o.id = :id";
    $orderStmt = $db->prepare($orderQuery);
    $orderStmt->bindParam(':id', $order_id);
    $orderStmt->execute();
    $order = $orderStmt->fetch();
    
    $itemsQuery = "SELECT oi.*, p.name as product_name 
                   FROM order_items oi 
                   JOIN products p ON oi.product_id = p.id 
                   WHERE oi.order_id = :order_id";
    $itemsStmt = $db->prepare($itemsQuery);
    $itemsStmt->bindParam(':order_id', $order_id);
    $itemsStmt->execute();
    $order_items = $itemsStmt->fetchAll();
    ?>
    
    <div style="padding: 1rem;">
        <h3>Order #<?php echo $order_id; ?> Details</h3>
        
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 1rem;">
            <div>
                <strong>Customer:</strong><br>
                <?php echo htmlspecialchars($order['customer_name']); ?><br>
                <?php echo htmlspecialchars($order['customer_email']); ?>
            </div>
            <div>
                <strong>Order Date:</strong><br>
                <?php echo date('F d, Y H:i', strtotime($order['order_date'])); ?><br>
                <strong>Status:</strong> 
                <span style="background: <?php 
                    echo $order['status'] == 'completed' ? '#27ae60' : 
                        ($order['status'] == 'cancelled' ? '#e74c3c' : '#f39c12'); 
                ?>; color: white; padding: 0.25rem 0.5rem; border-radius: 4px;">
                    <?php echo ucfirst($order['status']); ?>
                </span>
            </div>
        </div>
        
        <div style="margin-bottom: 1rem;">
            <strong>Shipping Address:</strong><br>
            <?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?>
        </div>
        
        <div style="margin-bottom: 1rem;">
            <strong>Payment Method:</strong><br>
            <?php echo ucfirst(str_replace('_', ' ', $order['payment_method'])); ?>
        </div>
        
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr>
                    <th style="border-bottom: 1px solid #ddd; padding: 0.5rem; text-align: left;">Product</th>
                    <th style="border-bottom: 1px solid #ddd; padding: 0.5rem; text-align: left;">Quantity</th>
                    <th style="border-bottom: 1px solid #ddd; padding: 0.5rem; text-align: left;">Price</th>
                    <th style="border-bottom: 1px solid #ddd; padding: 0.5rem; text-align: left;">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($order_items as $item): ?>
                <tr>
                    <td style="padding: 0.5rem;"><?php echo htmlspecialchars($item['product_name']); ?></td>
                    <td style="padding: 0.5rem;"><?php echo $item['quantity']; ?></td>
                    <td style="padding: 0.5rem;">$<?php echo number_format($item['price'], 2); ?></td>
                    <td style="padding: 0.5rem;">$<?php echo number_format($item['quantity'] * $item['price'], 2); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3" style="padding: 0.5rem; text-align: right;"><strong>Total:</strong></td>
                    <td style="padding: 0.5rem;"><strong>$<?php echo number_format($order['total_amount'], 2); ?></strong></td>
                </tr>
            </tfoot>
        </table>
    </div>
    <?php
    exit();
}

// --- GET FILTERED ORDERS FOR MAIN LIST ---
$status_filter = $_GET['status_filter'] ?? '';
$search_term = $_GET['search'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$sort_by = $_GET['sort_by'] ?? 'order_date';
$sort_order = $_GET['sort_order'] ?? 'DESC';

// Validate sort parameters
$allowed_sort_columns = ['id', 'order_date', 'total_amount', 'status'];
if (!in_array($sort_by, $allowed_sort_columns)) {
    $sort_by = 'order_date';
}
$sort_order = strtoupper($sort_order) == 'ASC' ? 'ASC' : 'DESC';

// Build query
$query = "SELECT o.*, u.name as customer_name, u.email as customer_email 
          FROM orders o 
          JOIN users u ON o.user_id = u.id 
          WHERE 1=1";
$params = [];

if ($status_filter) {
    $query .= " AND o.status = :status";
    $params[':status'] = $status_filter;
}
if ($search_term) {
    $query .= " AND (u.name LIKE :search OR u.email LIKE :search OR o.id LIKE :search)";
    $params[':search'] = "%$search_term%";
}
if ($date_from) {
    $query .= " AND o.order_date >= :date_from";
    $params[':date_from'] = $date_from;
}
if ($date_to) {
    $query .= " AND o.order_date <= :date_to";
    $params[':date_to'] = $date_to . ' 23:59:59';
}

$query .= " ORDER BY $sort_by $sort_order";
$stmt = $db->prepare($query);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$orders = $stmt->fetchAll();

// Get statistics for dashboard
$statsQuery = "SELECT 
                COUNT(*) as total_orders,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END) as processing,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled,
                SUM(CASE WHEN status = 'completed' THEN total_amount ELSE 0 END) as total_revenue
               FROM orders";
$statsStmt = $db->prepare($statsQuery);
$statsStmt->execute();
$stats = $statsStmt->fetch();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Orders - Admin Panel</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .stat-box {
            background: white;
            border-radius: var(--border-radius);
            padding: 1rem;
            text-align: center;
            box-shadow: var(--box-shadow);
            border-left: 4px solid var(--primary-color);
        }
        
        .stat-box h4 {
            font-size: 0.85rem;
            color: #666;
            margin-bottom: 0.5rem;
        }
        
        .stat-box .number {
            font-size: 1.5rem;
            font-weight: bold;
        }
        
        .filter-bar {
            background: white;
            padding: 1rem;
            border-radius: var(--border-radius);
            margin-bottom: 1.5rem;
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            align-items: flex-end;
        }
        
        .filter-group {
            flex: 1;
            min-width: 150px;
        }
        
        .filter-group label {
            font-size: 0.8rem;
            margin-bottom: 0.25rem;
        }
        
        .status-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-processing {
            background: #cce5ff;
            color: #004085;
        }
        
        .status-completed {
            background: #d4edda;
            color: #155724;
        }
        
        .status-cancelled {
            background: #f8d7da;
            color: #721c24;
        }
        
        .action-icons {
            display: flex;
            gap: 0.5rem;
            justify-content: center;
        }
        
        .action-icon {
            cursor: pointer;
            padding: 0.25rem;
            border-radius: 4px;
            transition: var(--transition);
        }
        
        .action-icon:hover {
            background: #f0f0f0;
        }
        
        .bulk-actions-bar {
            display: flex;
            gap: 1rem;
            align-items: center;
            margin-bottom: 1rem;
            padding: 1rem;
            background: #f9f9f9;
            border-radius: var(--border-radius);
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
        
        .modal-content {
            background: white;
            border-radius: var(--border-radius);
            max-width: 800px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
        }
        
        .modal-header {
            padding: 1rem;
            border-bottom: 1px solid #ddd;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-body {
            padding: 1rem;
        }
        
        .close-modal {
            cursor: pointer;
            font-size: 1.5rem;
        }
        
        .sort-link {
            color: #333;
            text-decoration: none;
        }
        
        .sort-link:hover {
            color: var(--primary-color);
        }
        
        @media (max-width: 768px) {
            .filter-bar {
                flex-direction: column;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .action-icons {
                flex-direction: column;
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
                <li><a href="orders.php" class="active">Orders</a></li>
                <li><a href="categories.php">Categories</a></li>
                <li><a href="../logout.php">Logout</a></li>
            </ul>
        </div>
    </nav>

    <div class="container">
        <h1>Order Management</h1>
        
        <!-- Display Messages -->
        <?php if($message): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-box">
                <h4>Total Orders</h4>
                <div class="number"><?php echo number_format($stats['total_orders']); ?></div>
            </div>
            <div class="stat-box">
                <h4>Pending</h4>
                <div class="number" style="color: #f39c12;"><?php echo number_format($stats['pending']); ?></div>
            </div>
            <div class="stat-box">
                <h4>Processing</h4>
                <div class="number" style="color: #3498db;"><?php echo number_format($stats['processing']); ?></div>
            </div>
            <div class="stat-box">
                <h4>Completed</h4>
                <div class="number" style="color: #27ae60;"><?php echo number_format($stats['completed']); ?></div>
            </div>
            <div class="stat-box">
                <h4>Cancelled</h4>
                <div class="number" style="color: #e74c3c;"><?php echo number_format($stats['cancelled']); ?></div>
            </div>
            <div class="stat-box">
                <h4>Total Revenue</h4>
                <div class="number">$<?php echo number_format($stats['total_revenue'], 2); ?></div>
            </div>
        </div>
        
        <!-- Filter Bar -->
        <div class="filter-bar">
            <div class="filter-group">
                <label>Search</label>
                <input type="text" id="searchInput" placeholder="Order ID, Customer, Email..." 
                       value="<?php echo htmlspecialchars($search_term); ?>">
            </div>
            
            <div class="filter-group">
                <label>Status</label>
                <select id="statusFilter">
                    <option value="">All Status</option>
                    <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="processing" <?php echo $status_filter == 'processing' ? 'selected' : ''; ?>>Processing</option>
                    <option value="completed" <?php echo $status_filter == 'completed' ? 'selected' : ''; ?>>Completed</option>
                    <option value="cancelled" <?php echo $status_filter == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                </select>
            </div>
            
            <div class="filter-group">
                <label>Date From</label>
                <input type="date" id="dateFrom" value="<?php echo htmlspecialchars($date_from); ?>">
            </div>
            
            <div class="filter-group">
                <label>Date To</label>
                <input type="date" id="dateTo" value="<?php echo htmlspecialchars($date_to); ?>">
            </div>
            
            <div class="filter-group">
                <button class="btn btn-primary" onclick="applyFilters()">Apply Filters</button>
                <button class="btn" onclick="resetFilters()">Reset</button>
            </div>
            
            <div class="filter-group">
                <a href="?action=export&status_filter=<?php echo urlencode($status_filter); ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>" 
                   class="btn btn-success">Export CSV</a>
            </div>
        </div>
        
        <!-- Bulk Actions -->
        <div class="bulk-actions-bar">
            <span><strong>Bulk Actions:</strong></span>
            <select id="bulkStatusSelect">
                <option value="">Change Status To...</option>
                <option value="pending">Pending</option>
                <option value="processing">Processing</option>
                <option value="completed">Completed</option>
                <option value="cancelled">Cancelled</option>
            </select>
            <button class="btn btn-primary" onclick="bulkUpdateStatus()">Apply to Selected</button>
            <button class="btn" onclick="selectAllOrders()">Select All</button>
            <button class="btn" onclick="deselectAllOrders()">Deselect All</button>
        </div>
        
        <!-- Orders Table -->
        <div class="card">
            <div class="card-header">
                <h3>All Orders</h3>
                <span><?php echo count($orders); ?> orders found</span>
            </div>
            <div class="card-body" style="overflow-x: auto;">
                <form id="bulkUpdateForm" method="POST" action="?action=bulk_update">
                    <table class="cart-table">
                        <thead>
                            <tr>
                                <th><input type="checkbox" id="selectAllCheckbox"></th>
                                <th>
                                    <a href="?sort_by=id&sort_order=<?php echo $sort_by == 'id' && $sort_order == 'DESC' ? 'ASC' : 'DESC'; ?>&status_filter=<?php echo urlencode($status_filter); ?>&search=<?php echo urlencode($search_term); ?>" class="sort-link">
                                        Order ID <?php echo $sort_by == 'id' ? ($sort_order == 'DESC' ? '↓' : '↑') : ''; ?>
                                    </a>
                                </th>
                                <th>Customer</th>
                                <th>
                                    <a href="?sort_by=order_date&sort_order=<?php echo $sort_by == 'order_date' && $sort_order == 'DESC' ? 'ASC' : 'DESC'; ?>&status_filter=<?php echo urlencode($status_filter); ?>&search=<?php echo urlencode($search_term); ?>" class="sort-link">
                                        Date <?php echo $sort_by == 'order_date' ? ($sort_order == 'DESC' ? '↓' : '↑') : ''; ?>
                                    </a>
                                </th>
                                <th>
                                    <a href="?sort_by=total_amount&sort_order=<?php echo $sort_by == 'total_amount' && $sort_order == 'DESC' ? 'ASC' : 'DESC'; ?>&status_filter=<?php echo urlencode($status_filter); ?>&search=<?php echo urlencode($search_term); ?>" class="sort-link">
                                        Total <?php echo $sort_by == 'total_amount' ? ($sort_order == 'DESC' ? '↓' : '↑') : ''; ?>
                                    </a>
                                </th>
                                <th>
                                    <a href="?sort_by=status&sort_order=<?php echo $sort_by == 'status' && $sort_order == 'DESC' ? 'ASC' : 'DESC'; ?>&status_filter=<?php echo urlencode($status_filter); ?>&search=<?php echo urlencode($search_term); ?>" class="sort-link">
                                        Status <?php echo $sort_by == 'status' ? ($sort_order == 'DESC' ? '↓' : '↑') : ''; ?>
                                    </a>
                                </th>
                                <th>Payment</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(count($orders) > 0): ?>
                                <?php foreach($orders as $order): ?>
                                <tr>
                                    <td>
                                        <input type="checkbox" name="order_ids[]" value="<?php echo $order['id']; ?>" class="order-checkbox">
                                    </td>
                                    <td>#<?php echo $order['id']; ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($order['customer_name']); ?></strong><br>
                                        <small><?php echo htmlspecialchars($order['customer_email']); ?></small>
                                    </td>
                                    <td><?php echo date('M d, Y H:i', strtotime($order['order_date'])); ?></td>
                                    <td>$<?php echo number_format($order['total_amount'], 2); ?></td>
                                    <td>
                                        <span class="status-badge status-<?php echo $order['status']; ?>">
                                            <?php echo ucfirst($order['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo ucfirst(str_replace('_', ' ', $order['payment_method'])); ?></td>
                                    <td class="action-icons">
                                        <span class="action-icon" onclick="viewOrderDetails(<?php echo $order['id']; ?>)" title="View Details">👁️</span>
                                        <span class="action-icon" onclick="showStatusModal(<?php echo $order['id']; ?>, '<?php echo $order['status']; ?>')" title="Update Status">✏️</span>
                                        <a href="?action=invoice&id=<?php echo $order['id']; ?>" target="_blank" class="action-icon" title="Print Invoice">🖨️</a>
                                        <span class="action-icon" onclick="confirmDelete(<?php echo $order['id']; ?>)" title="Delete Order" style="color: #e74c3c;">🗑️</span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" style="text-align: center;">No orders found.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Status Update Modal -->
    <div id="statusModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Update Order Status</h3>
                <span class="close-modal" onclick="closeStatusModal()">&times;</span>
            </div>
            <div class="modal-body">
                <form id="statusUpdateForm" method="POST" action="?action=update_status">
                    <input type="hidden" name="order_id" id="statusOrderId">
                    <div class="form-group">
                        <label>Select New Status</label>
                        <select name="status" id="newStatus" class="form-control" required>
                            <option value="pending">Pending</option>
                            <option value="processing">Processing</option>
                            <option value="completed">Completed</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">Update Status</button>
                        <button type="button" class="btn" onclick="closeStatusModal()">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Order Details Modal -->
    <div id="detailsModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Order Details</h3>
                <span class="close-modal" onclick="closeDetailsModal()">&times;</span>
            </div>
            <div class="modal-body" id="detailsModalBody">
                <div style="text-align: center;">Loading...</div>
            </div>
        </div>
    </div>
    
    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Confirm Delete</h3>
                <span class="close-modal" onclick="closeDeleteModal()">&times;</span>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this order? This action cannot be undone and will also delete all order items.</p>
                <div class="form-group">
                    <a id="confirmDeleteBtn" href="#" class="btn btn-danger">Delete Order</a>
                    <button type="button" class="btn" onclick="closeDeleteModal()">Cancel</button>
                </div>
            </div>
        </div>
    </div>
    
    <footer class="footer">
        <p>&copy; <?php echo date('Y'); ?> SmartStore Admin Panel. All rights reserved.</p>
    </footer>
    
    <script>
        // Apply filters
        function applyFilters() {
            const search = document.getElementById('searchInput').value;
            const status = document.getElementById('statusFilter').value;
            const dateFrom = document.getElementById('dateFrom').value;
            const dateTo = document.getElementById('dateTo').value;
            
            let url = '?';
            if (search) url += `search=${encodeURIComponent(search)}&`;
            if (status) url += `status_filter=${encodeURIComponent(status)}&`;
            if (dateFrom) url += `date_from=${dateFrom}&`;
            if (dateTo) url += `date_to=${dateTo}&`;
            url += `sort_by=<?php echo $sort_by; ?>&sort_order=<?php echo $sort_order; ?>`;
            
            window.location.href = url;
        }
        
        function resetFilters() {
            window.location.href = '?';
        }
        
        // Bulk operations
        function selectAllOrders() {
            const checkboxes = document.querySelectorAll('.order-checkbox');
            checkboxes.forEach(cb => cb.checked = true);
        }
        
        function deselectAllOrders() {
            const checkboxes = document.querySelectorAll('.order-checkbox');
            checkboxes.forEach(cb => cb.checked = false);
        }
        
        function bulkUpdateStatus() {
            const checked = document.querySelectorAll('.order-checkbox:checked');
            const bulkStatus = document.getElementById('bulkStatusSelect').value;
            
            if (checked.length === 0) {
                alert('Please select at least one order.');
                return;
            }
            
            if (!bulkStatus) {
                alert('Please select a status to apply.');
                return;
            }
            
            if (confirm(`Update ${checked.length} order(s) to ${bulkStatus.toUpperCase()}?`)) {
                const form = document.getElementById('bulkUpdateForm');
                const hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = 'bulk_status';
                hiddenInput.value = bulkStatus;
                form.appendChild(hiddenInput);
                form.submit();
            }
        }
        
        // Select All checkbox
        document.getElementById('selectAllCheckbox')?.addEventListener('change', function() {
            const checkboxes = document.querySelectorAll('.order-checkbox');
            checkboxes.forEach(cb => cb.checked = this.checked);
        });
        
        // Status modal functions
        function showStatusModal(orderId, currentStatus) {
            document.getElementById('statusOrderId').value = orderId;
            document.getElementById('newStatus').value = currentStatus;
            document.getElementById('statusModal').style.display = 'flex';
        }
        
        function closeStatusModal() {
            document.getElementById('statusModal').style.display = 'none';
        }
        
        // Details modal functions
        function viewOrderDetails(orderId) {
            const modal = document.getElementById('detailsModal');
            const body = document.getElementById('detailsModalBody');
            modal.style.display = 'flex';
            body.innerHTML = '<div style="text-align: center;">Loading order details...</div>';
            
            fetch(`?action=view_details&id=${orderId}`)
                .then(response => response.text())
                .then(data => {
                    body.innerHTML = data;
                })
                .catch(error => {
                    body.innerHTML = '<div style="text-align: center; color: red;">Error loading details</div>';
                });
        }
        
        function closeDetailsModal() {
            document.getElementById('detailsModal').style.display = 'none';
        }
        
        // Delete modal functions
        let deleteOrderId = null;
        
        function confirmDelete(orderId) {
            deleteOrderId = orderId;
            document.getElementById('deleteModal').style.display = 'flex';
            document.getElementById('confirmDeleteBtn').href = `?action=delete&id=${orderId}`;
        }
        
        function closeDeleteModal() {
            document.getElementById('deleteModal').style.display = 'none';
        }
        
        // Close modals when clicking outside
        window.onclick = function(event) {
            if (event.target.classList.contains('modal')) {
                event.target.style.display = 'none';
            }
        }
        
        // Enter key for search
        document.getElementById('searchInput')?.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') applyFilters();
        });
    </script>
</body>
</html>