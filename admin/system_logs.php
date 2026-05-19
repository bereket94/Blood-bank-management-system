<?php
session_start();
include '../config/db_connect.php';

// Access Control: Only allow logged-in admins to access this log review layout
if(!isset($_SESSION['role']) || $_SESSION['role'] != 'admin') {
    header("Location: ../index.php");
    exit();
}

// Get filter parameters safely
$user_type = isset($_GET['user_type']) ? trim($_GET['user_type']) : 'all';
$action = isset($_GET['action']) ? trim($_GET['action']) : 'all';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// 1. SECURE: Base Query Template
$sql = "SELECT * FROM system_logs WHERE 1=1";
$params = [];
$types = "";

// Dynamic Filter Conditions using Prepared SQL parameters
if($user_type != 'all') {
    $sql .= " AND user_type = ?";
    $params[] = $user_type;
    $types .= "s";
}
if($action != 'all') {
    $sql .= " AND action = ?";
    $params[] = $action;
    $types .= "s";
}
if($search !== '') {
    // Wrap search terms inside SQL wildcards safely
    $search_term = "%" . $search . "%";
    $sql .= " AND (details LIKE ? OR user_type LIKE ? OR action LIKE ? OR ip_address LIKE ?)";
    
    // Add parameters 4 times for each search field
    for ($i = 0; $i < 4; $i++) {
        $params[] = $search_term;
        $types .= "s";
    }
}

$sql .= " ORDER BY created_at DESC LIMIT 200";

// 2. SECURE: Execute Prepared Statement for main activity log list
$stmt = $conn->prepare($sql);
if ($stmt) {
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $logs = $stmt->get_result();
} else {
    die("Database statement error. Please try again later.");
}

// Fetch distinct categories dynamically to build dropdown menus
$user_types = mysqli_query($conn, "SELECT DISTINCT user_type FROM system_logs");
$actions = mysqli_query($conn, "SELECT DISTINCT action FROM system_logs");
?>
<!DOCTYPE html>
<html>
<head>
    <title>System Logs - Admin</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .filter-bar {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .filter-bar form {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            align-items: center;
        }
        .filter-bar select, .filter-bar input {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .filter-bar button {
            padding: 8px 20px;
            background: #740202;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .log-table {
            background: white;
            border-radius: 10px;
            overflow-x: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        th {
            background: #571209;
            color: white;
        }
        .badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }
        /* Color matching codes for your app badges */
        .badge-admin { background: #dc3545; color: white; }
        .badge-donor { background: #28a745; color: white; }
        .badge-nurse { background: #17a2b8; color: white; }
        .badge-hospital { background: #ffc107; color: black; }
        .clear-btn {
            background: #6c757d;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header" style="background: #750703;">
            <h1>📋 System Activity Logs</h1>
            <p>Track all user activities in the system</p>
            <a href="dashboard.php" class="logout">Back to Dashboard</a>
        </div>
        
        <div class="filter-bar">
            <form method="GET" action="system_logs.php">
                <select name="user_type">
                    <option value="all">All User Types</option>
                    <?php while($ut = mysqli_fetch_assoc($user_types)): ?>
                        <option value="<?php echo htmlspecialchars($ut['user_type']); ?>" <?php echo $user_type == $ut['user_type'] ? 'selected' : ''; ?>>
                            <?php echo ucfirst(htmlspecialchars($ut['user_type'])); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
                
                <select name="action">
                    <option value="all">All Actions</option>
                    <?php while($act = mysqli_fetch_assoc($actions)): ?>
                        <option value="<?php echo htmlspecialchars($act['action']); ?>" <?php echo $action == $act['action'] ? 'selected' : ''; ?>>
                            <?php echo ucfirst(htmlspecialchars($act['action'])); ?>
                        </option>
                    <?php endwhile; ?>
                </select>
                
                <input type="text" name="search" placeholder="Search logs..." value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit">Filter</button>
                <?php if($user_type != 'all' || $action != 'all' || $search !== ''): ?>
                    <a href="system_logs.php" class="clear-btn" style="padding: 8px 20px; background: #6c757d; color: white; text-decoration: none; border-radius: 5px;">Clear Filters</a>
                <?php endif; ?>
            </form>
        </div>
        
        <div class="log-table">
            <table>
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>User Type</th>
                        <th>Action</th>
                        <th>Details</th>
                        <th>IP Address</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(!$logs || $logs->num_rows == 0): ?>
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 40px;">
                                No activity logs recorded yet.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php while($log = $logs->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo date('Y-m-d H:i:s', strtotime($log['created_at'])); ?></td>
                                <td>
                                    <span class="badge badge-<?php echo htmlspecialchars($log['user_type']); ?>">
                                        <?php echo ucfirst(htmlspecialchars($log['user_type'])); ?>
                                    </span>
                                </td>
                                <td><?php echo ucfirst(htmlspecialchars($log['action'])); ?></td>
                                <td><?php echo htmlspecialchars($log['details'] ?? 'No extra details provided'); ?></td>
                                <td><?php echo htmlspecialchars($log['ip_address']); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <div style="margin-top: 20px; text-align: center;">
            <a href="dashboard.php" style="text-decoration: none; padding: 10px 20px; background: #1a1a2e; color: white; border-radius: 5px;">← Back to Dashboard</a>
        </div>
    </div>
    <?php if (isset($stmt)) { $stmt->close(); } ?>
</body>
</html>