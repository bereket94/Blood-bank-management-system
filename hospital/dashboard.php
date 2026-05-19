<?php
session_start();
include '../config/db_connect.php';

if($_SESSION['role'] != 'hospital') {
    header("Location: ../login.php");
    exit();
}

$hospital_id = $_SESSION['user_id'];

$hospital_sql = "SELECT * FROM hospitals WHERE id='$hospital_id'";
$hospital_result = mysqli_query($conn, $hospital_sql);
$hospital = mysqli_fetch_assoc($hospital_result);

$total_donations = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM appointments WHERE hospital_id='$hospital_id'"))['count'];
$success_donations = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM appointments WHERE hospital_id='$hospital_id' AND status='success'"))['count'];
$pending_donations = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM appointments WHERE hospital_id='$hospital_id' AND status='pending'"))['count'];
$failed_donations = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM appointments WHERE hospital_id='$hospital_id' AND status='failed'"))['count'];
$total_nurses = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM nurses WHERE hospital_id='$hospital_id'"))['count'];

$recent_appointments = mysqli_query($conn, "SELECT a.*, d.name as donor_name, d.blood_group 
    FROM appointments a 
    JOIN donors d ON a.donor_id = d.id 
    WHERE a.hospital_id='$hospital_id' 
    ORDER BY a.appointment_date DESC LIMIT 5");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $hospital['name']; ?> - Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }
        .stat-card {
            background: white;
            padding: 25px 15px;
            text-align: center;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .stat-card h3 {
            font-size: 32px;
            margin-bottom: 8px;
        }
        .stat-card p {
            color: #666;
            font-size: 14px;
        }
        .stat-card.green h3 { color: #28a745; }
        .stat-card.orange h3 { color: #fd7e14; }
        .stat-card.red h3 { color: #dc3545; }
        .stat-card.blue h3 { color: #17a2b8; }
        
        .recent-appointments {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-top: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .recent-appointments h3 {
            margin-bottom: 15px;
            color: #8B0000;
            font-size: 18px;
        }
        .appointment-table {
            width: 100%;
            border-collapse: collapse;
        }
        .appointment-table th {
            background: #8B0000;
            color: white;
            padding: 10px;
            text-align: left;
            font-size: 13px;
        }
        .appointment-table td {
            padding: 10px;
            border-bottom: 1px solid #eee;
            font-size: 13px;
        }
        .appointment-table tr:hover td {
            background: #f9f9f9;
        }
        .status-badge {
            padding: 3px 10px;
            border-radius: 15px;
            font-size: 11px;
            display: inline-block;
        }
        .status-success {
            background: #d4edda;
            color: #155724;
        }
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        .status-failed {
            background: #f8d7da;
            color: #721c24;
        }
        .hospital-info-card {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-top: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .hospital-info-card h3 {
            margin-bottom: 15px;
            color: #8B0000;
            font-size: 18px;
        }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }
        .info-item {
            display: flex;
            border-bottom: 1px solid #eee;
            padding: 8px 0;
        }
        .info-label {
            width: 120px;
            font-weight: 600;
            color: #555;
        }
        .info-value {
            flex: 1;
            color: #333;
        }
    </style>
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
            <a href="manage_nurses.php" class="menu-item">
                <i class="fas fa-user-nurse"></i>
                <span class="menu-text">Manage Nurses</span>
            </a>
            <a href="view_reports.php" class="menu-item">
                <i class="fas fa-paper-plane"></i>
                <span class="menu-text">View Reports</span>
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
        <div class="welcome-card" style="background: linear-gradient(135deg, #8B0000 0%, #a00000 100%);">
            <h2>Welcome, <?php echo $hospital['name']; ?>! 🏥</h2>
            <p>Hospital Administrator Dashboard - Blood Donation Management</p>
        </div>
        <div class="stats-grid">
            <div class="stat-card">
                <h3><?php echo $total_nurses; ?></h3>
                <p>👩‍⚕️ Total Nurses</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $total_donations; ?></h3>
                <p>Total Appointments</p>
            </div>
            <div class="stat-card green">
                <h3><?php echo $success_donations; ?></h3>
                <p> Successful Donations</p>
            </div>
            <div class="stat-card orange">
                <h3><?php echo $pending_donations; ?></h3>
                <p>Pending</p>
            </div>
            <div class="stat-card red">
                <h3><?php echo $failed_donations; ?></h3>
                <p>Failed</p>
            </div>
        </div>
        <div class="dashboard-menu">
            <a href="manage_nurses.php">
                <i class="fas fa-user-nurse"></i> Manage Nurses
            </a>
            <a href="view_reports.php">
                <i class="fas fa-file-alt"></i> View Reports
            </a>
        </div>
<div class="reports-section" style="background: white; padding: 20px; border-radius: 10px; margin: 20px 0; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
    <h3><i class="fas fa-file-alt"></i> Donation Reports</h3>
    
    <?php
    $today = date('Y-m-d');
    $week_ago = date('Y-m-d', strtotime('-7 days'));
    $month_ago = date('Y-m-d', strtotime('-30 days'));
    $daily_total = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM appointments WHERE hospital_id='$hospital_id' AND appointment_date='$today'"))['count'];
    $daily_success = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM appointments WHERE hospital_id='$hospital_id' AND appointment_date='$today' AND status='success'"))['count'];
    $weekly_total = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM appointments WHERE hospital_id='$hospital_id' AND appointment_date BETWEEN '$week_ago' AND '$today'"))['count'];
    $weekly_success = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM appointments WHERE hospital_id='$hospital_id' AND appointment_date BETWEEN '$week_ago' AND '$today' AND status='success'"))['count'];
    $monthly_total = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM appointments WHERE hospital_id='$hospital_id' AND appointment_date BETWEEN '$month_ago' AND '$today'"))['count'];
    $monthly_success = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM appointments WHERE hospital_id='$hospital_id' AND appointment_date BETWEEN '$month_ago' AND '$today' AND status='success'"))['count'];
    ?>
    <div class="stats-grid" style="margin-top: 15px;">
        <div class="stat-card">
            <h3><?php echo $daily_total; ?></h3>
            <p>Today's Donations</p>
            <small style="color: green;">Success: <?php echo $daily_success; ?></small>
        </div>
        <div class="stat-card">
            <h3><?php echo $weekly_total; ?></h3>
            <p>This Week (7 days)</p>
            <small style="color: green;">Success: <?php echo $weekly_success; ?></small>
        </div>
        <div class="stat-card">
            <h3><?php echo $monthly_total; ?></h3>
            <p>📈 This Month (30 days)</p>
            <small style="color: green;">Success: <?php echo $monthly_success; ?></small>
        </div>
    </div>
    <div style="text-align: center; margin-top: 15px;">
        <a href="view_reports.php" class="btn" style="background: #17a2b8;">
            <i class="fas fa-eye"></i> View Detailed Reports
        </a>
    </div>
</div>
        <div class="hospital-info-card">
            <h3><i class="fas fa-hospital"></i> Hospital Information</h3>
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">Hospital Name:</div>
                    <div class="info-value"><?php echo $hospital['name']; ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Location:</div>
                    <div class="info-value"><?php echo $hospital['location']; ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Phone:</div>
                    <div class="info-value"><?php echo $hospital['phone']; ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Email:</div>
                    <div class="info-value"><?php echo $hospital['email']; ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">Total Nurses:</div>
                    <div class="info-value"><?php echo $total_nurses; ?> nurses</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Success Rate:</div>
                    <div class="info-value">
                        <?php 
                        $rate = $total_donations > 0 ? round(($success_donations / $total_donations) * 100, 1) : 0;
                        echo $rate . '%';
                        ?>
                        <div style="background: #eee; height: 6px; border-radius: 3px; margin-top: 5px; width: 100%;">
                            <div style="background: #28a745; height: 6px; border-radius: 3px; width: <?php echo $rate; ?>%;"></div>
                        </div>
                    </div>
                </div>
            </div>
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