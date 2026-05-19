<?php
session_start();
include '../config/db_connect.php';

if($_SESSION['role'] != 'nurse') {
    header("Location: ../index.php");
    exit();
}

$hospital_id = $_SESSION['hospital_id'];
$today = date('Y-m-d');

// Get statistics
$total_today = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM appointments WHERE hospital_id='$hospital_id' AND appointment_date='$today'"))['count'];
$pending_today = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM appointments WHERE hospital_id='$hospital_id' AND appointment_date='$today' AND status='pending'"))['count'];
$completed_today = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM appointments WHERE hospital_id='$hospital_id' AND appointment_date='$today' AND status='success'"))['count'];

// Get today's appointments with first_name and last_name
$sql = "SELECT a.*, d.first_name, d.last_name, d.blood_group, d.age, d.phone 
        FROM appointments a 
        JOIN donors d ON a.donor_id = d.id 
        WHERE a.hospital_id='$hospital_id' AND a.appointment_date='$today'
        ORDER BY a.appointment_time ASC";

$appointments = mysqli_query($conn, $sql);
$all_time_total = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM appointments WHERE hospital_id='$hospital_id'"))['count'];
$all_time_success = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM appointments WHERE hospital_id='$hospital_id' AND status='success'"))['count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nurse Dashboard - Blood Bank</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <style>
    .appointment-table {
            text-align: center;
            padding: 50px;
            background: #f8f9fa;
            border-radius: 10px;
            margin: 20px 0;
        }
        .no-appointments i {
            font-size: 48px;
            color: #17a2b8;
            margin-bottom: 15px;
            display: block;
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
            <h3 style="color: #fff;">Blood Bank System</h3>
        </div>
        <div class="sidebar-menu">
            <a href="dashboard.php" class="menu-item active">
                <i class="fas fa-tachometer-alt"></i>
                <span class="menu-text">Dashboard</span>
            </a>
            <a href="verify_donor.php" class="menu-item">
                <i class="fas fa-qrcode"></i>
                <span class="menu-text">Verify Donor</span>
            </a>
            <a href="update_status.php" class="menu-item">
                <i class="fas fa-edit"></i>
                <span class="menu-text">New Appointments</span>
            </a>
            <a href="today_appointments.php" class="menu-item">
                <i class="fas fa-calendar-day"></i>
                <span class="menu-text">Today's List</span>
            </a>
            <a href="send_report.php" class="menu-item">
                <i class="fas fa-paper-plane"></i>
                <span class="menu-text">Send Report</span>
           </a>
        </div>
        
        <div class="sidebar-footer">
            <a href="../logout.php" class="menu-item">
                <i class="fas fa-sign-out-alt"></i>
                <span class="menu-text">Logout</span>
            </a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content" id="mainContent">
        <!-- Welcome Card -->
        <div class="welcome-card1">
            <h2>Welcome,<?php echo $_SESSION['user_name']; ?>! </h2>
            <p><?php echo $_SESSION['hospital_name']; ?> | Today is <?php echo date('l, F d, Y'); ?></p>
        </div>
        
        <!-- Statistics Cards -->
        <div class="stats-grid">
           <div class="stat-card blue">
                <h3><?php echo $all_time_total; ?></h3>
                <p> All Appointments</p>
            </div>
            <div class="stat-card green">
                <h3><?php echo $all_time_success; ?></h3>
                <p> All Successful</p>
            </div>
            <div class="stat-card blue">
                <h3><?php echo $total_today; ?></h3>
                <p>Today's Total Appointments</p>
            </div>
            <div class="stat-card orange">
                <h3><?php echo $pending_today; ?></h3>
                <p>Pending</p>
            </div>
            <div class="stat-card green">
                <h3><?php echo $completed_today; ?></h3>
                <p>Completed</p>
            </div>
        </div>
        <!-- Today's Appointments Table -->
        <div class="appointments-table">
            <h3><i class="fas fa-calendar-alt"></i> Today's Appointments (<?php echo $today; ?>)</h3>
            
            <?php if(mysqli_num_rows($appointments) == 0): ?>
                <div class="no-appointments">
                    <i class="fas fa-calendar-check" style="font-size: 48px; color: #17a2b8; margin-bottom: 15px; display: block;"></i>
                    No appointments scheduled for today.<br>
                </div>
            <?php else: ?>
                <table class="appointment-table">
                    <thead>
                        <tr>
                            <th>Time</th>
                            <th>Donor Name</th>
                            <th>Blood Group</th>
                            <th>Age</th>
                            <th>Phone</th>
                            <th>Donation Code</th>
                            <th>Status</th>
                         </tr>
                    </thead>
                    <tbody>
                        <?php while($app = mysqli_fetch_assoc($appointments)): ?>
                        <tr>
                            <td><i class="fas fa-clock"></i> <?php echo $app['appointment_time'] ?? '--:--'; ?></td>
                            <td><strong><?php echo $app['first_name'] . ' ' . $app['last_name']; ?></strong></td>
                            <td><span style="background:#8B0000; color:white; padding:3px 10px; border-radius:15px; font-size:12px;"><?php echo $app['blood_group']; ?></span></td>
                            <td><?php echo $app['age']; ?> yrs</span>
                            <td><?php echo $app['phone']; ?></span>
                            <td><code class="code-badge"><?php echo $app['unique_code']; ?></code></span>
                            <td>
                                <?php 
                                if($app['status'] == 'pending') {
                                    echo "<span class='status-badge status-pending'> Pending</span>";
                                } elseif($app['status'] == 'success') {
                                    echo "<span class='status-badge status-success'> Success</span>";
                                } else {
                                    echo "<span class='status-badge status-failed'> Failed</span>";
                                }
                                ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
        
        <div style="text-align: center; margin-top: 20px;">
            <a href="dashboard.php" class="refresh-btn">
                <i class="fas fa-sync-alt"></i> Refresh
            </a>
        </div>
    </div>

    <script>
        // Sidebar Toggle Functionality
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

        // Mobile menu handling
        if (window.innerWidth <= 768) {
            sidebar.classList.add('mobile-closed');
            toggleBtn.addEventListener('click', () => {
                sidebar.classList.toggle('mobile-open');
            });
        }
    </script>
</body>
</html>