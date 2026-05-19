<?php
session_start();
include '../config/db_connect.php';

if($_SESSION['role'] != 'admin') {
    header("Location: ../index.php");
}

$total_donors = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM donors"))['count'];
$total_hospitals = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM hospitals"))['count'];
$total_nurses = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM nurses"))['count'];
$total_appointments = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM appointments"))['count'];
$successful_donations = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM appointments WHERE status='success'"))['count'];
$pending_donations = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM appointments WHERE status='pending'"))['count'];
$failed_donations = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM appointments WHERE status='failed'"))['count'];

$recent_logs = mysqli_query($conn, "SELECT * FROM system_logs ORDER BY created_at DESC LIMIT 10");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Blood Bank</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <button class="toggle-btn" id="toggleBtn">
        <i class="fas fa-bars"></i>
    </button>

    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <i class="fas fa-tint"></i>
            <h3>Blood Bank System</h3>
        </div>
        
        <div class="sidebar-menu">
            <a href="dashboard.php" class="menu-item active">
                <i class="fas fa-tachometer-alt"></i>
                <span class="menu-text">Dashboard</span>
            </a>
            <a href="manage_donors.php" class="menu-item">
                <i class="fas fa-users"></i>
                <span class="menu-text">Manage Donors</span>
            </a>
            <a href="manage_hospitals.php" class="menu-item">
                <i class="fas fa-hospital"></i>
                <span class="menu-text">Manage Hospitals</span>
            </a>
            <a href="manage_nurses.php" class="menu-item">
                <i class="fas fa-user-nurse"></i>
                <span class="menu-text">Manage Nurses</span>
            </a>
            <a href="blood_inventory.php" class="menu-item">
                <i class="fas fa-database"></i>
                <span class="menu-text">Blood Inventory</span>
            </a>
            <a href="system_reports.php" class="menu-item">
                <i class="fas fa-chart-line"></i>
                <span class="menu-text">Reports</span>
            </a>
            <a href="system_logs.php" class="menu-item">
                <i class="fas fa-history"></i>
                <span class="menu-text">System Logs</span>
            </a>
        </div>
        <div class="sidebar-footer">
            <a href="../logout.php" class="menu-item">
                <i class="fas fa-sign-out-alt"></i>
                <span class="menu-text">Logout</span>
            </a>
        </div>
    </div>
    <div class="main-content" id="mainContent">
        <div class="welcome-card">
            <h2>Welcome, <?php echo $_SESSION['user_name']; ?></h2>
            <p>System Administrator - Blood Bank Management System</p>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <h3><?php echo $total_donors; ?></h3>
                <p> Total Donors</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $total_hospitals; ?></h3>
                <p> Hospitals</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $total_nurses; ?></h3>
                <p> Nurses</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $total_appointments; ?></h3>
                <p> Appointments</p>
            </div>
            <div class="stat-card green">
                <h3><?php echo $successful_donations; ?></h3>
                <p> Successful Donations</p>
            </div>
            <div class="stat-card orange">
                <h3><?php echo $pending_donations; ?></h3>
                <p> Pending</p>
            </div>
            <div class="stat-card red">
                <h3><?php echo $failed_donations; ?></h3>
                <p> Failed</p>
            </div>
        </div>
        <div class="recent-activity">
            <h3><i class="fas fa-clock"></i> Recent System Activity</h3>
            <table class="activity-table">
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
                    <?php if(mysqli_num_rows($recent_logs) == 0): ?>
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 30px;">No activity logs found.</td>
                        </tr>
                    <?php else: ?>
                        <?php while($log = mysqli_fetch_assoc($recent_logs)): ?>
                        <tr>
                            <td><?php echo date('Y-m-d H:i:s', strtotime($log['created_at'])); ?></td>
                            <td>
                                <span class="badge badge-<?php echo $log['user_type']; ?>">
                                    <?php echo ucfirst($log['user_type']); ?>
                                </span>
                            </td>
                            <td><?php echo ucfirst($log['action']); ?></td>
                            <td><?php echo htmlspecialchars($log['details'] ?? ''); ?></td>
                            <td><?php echo $log['ip_address'] ?? '127.0.0.1'; ?></td>
                        </tr>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <script>
        const toggleBtn = document.getElementById('toggleBtn');
        const sidebar = document.getElementById('sidebar');
        const mainContent = document.getElementById('mainContent');

        toggleBtn.addEventListener('click', () => {
            sidebar.classList.toggle('collapsed');
            mainContent.classList.toggle('expanded');
            toggleBtn.classList.toggle('collapsed');
            
            const icon = toggleBtn.querySelector('i');
            if (sidebar.classList.contains('collapsed')) {
                icon.classList.remove('fa-bars');
                icon.classList.add('fa-chevron-right');
            } else {
                icon.classList.remove('fa-chevron-right');
                icon.classList.add('fa-bars');
            }
        });
        if (window.innerWidth <= 768) {
            sidebar.classList.add('mobile-closed');
            toggleBtn.addEventListener('click', () => {
                sidebar.classList.toggle('mobile-open');
            });
        }
    </script>
</body>
</html>