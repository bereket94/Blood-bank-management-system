<?php
session_start();
include '../config/db_connect.php';

if($_SESSION['role'] != 'hospital') {
    header("Location: ../login.php");
    exit();
}

$hospital_id = $_SESSION['user_id'];
$today = date('Y-m-d');

$sql = "SELECT a.*, d.name as donor_name, d.blood_group, d.age, d.phone 
        FROM appointments a 
        JOIN donors d ON a.donor_id = d.id 
        WHERE a.hospital_id='$hospital_id' AND a.appointment_date='$today'
        ORDER BY a.appointment_time ASC";

$appointments = mysqli_query($conn, $sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Today's Appointments</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <button class="toggle-btn" id="toggleBtn"><i class="fas fa-bars"></i></button>
    
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <i class="fas fa-tint"></i>
            <h3>Blood Bank System</h3>
        </div>
        <div class="sidebar-menu">
            <a href="dashboard.php" class="menu-item"><i class="fas fa-tachometer-alt"></i><span class="menu-text">Dashboard</span></a>
            <a href="manage_nurses.php" class="menu-item"><i class="fas fa-user-nurse"></i><span class="menu-text">Manage Nurses</span></a>
            <a href="view_donations.php" class="menu-item"><i class="fas fa-chart-bar"></i><span class="menu-text">View Donations</span></a>
            <a href="appointments.php" class="menu-item active"><i class="fas fa-calendar-alt"></i><span class="menu-text">Appointments</span></a>
        </div>
        <div class="sidebar-footer">
            <a href="../logout.php" class="menu-item"><i class="fas fa-sign-out-alt"></i><span class="menu-text">Logout</span></a>
        </div>
    </div>

    <div class="main-content" id="mainContent">
        <div class="welcome-card" style="background: linear-gradient(135deg, #8B0000 0%, #a00000 100%);">
            <h2>📅 Today's Appointments</h2>
            <p><?php echo date('F d, Y'); ?></p>
        </div>
        
        <div class="recent-appointments">
            <?php if(mysqli_num_rows($appointments) == 0): ?>
                <p style="text-align: center; padding: 40px;">No appointments scheduled for today.</p>
            <?php else: ?>
                <table class="appointment-table">
                    <thead>
                        <tr><th>Time</th><th>Donor Name</th><th>Blood Group</th><th>Age</th><th>Phone</th><th>Code</th><th>Status</th></tr>
                    </thead>
                    <tbody>
                        <?php while($app = mysqli_fetch_assoc($appointments)): ?>
                        <tr>
                            <td><?php echo $app['appointment_time'] ?? '--:--'; ?></span>
                            <td><?php echo $app['donor_name']; ?></span>
                            <td><?php echo $app['blood_group']; ?></span>
                            <td><?php echo $app['age']; ?></span>
                            <td><?php echo $app['phone']; ?></span>
                            <td><code><?php echo $app['unique_code']; ?></code></span>
                            <td>
                                <?php 
                                if($app['status'] == 'success') echo "<span class='status-badge status-success'>✅ Success</span>";
                                elseif($app['status'] == 'pending') echo "<span class='status-badge status-pending'>⏳ Pending</span>";
                                else echo "<span class='status-badge status-failed'>❌ Failed</span>";
                                ?>
                            </span>
                        </span
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php endif; ?>
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
    </script>
</body>
</html>