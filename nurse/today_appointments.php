<?php
session_start();
include '../config/db_connect.php';

if($_SESSION['role'] != 'nurse') {
    header("Location: ../index.php");
    exit();
}

$hospital_id = $_SESSION['hospital_id'];
$today = date('Y-m-d');

$sql = "SELECT a.*, d.first_name, d.last_name, d.blood_group, d.age, d.phone 
        FROM appointments a 
        JOIN donors d ON a.donor_id = d.id 
        WHERE a.hospital_id='$hospital_id' AND a.appointment_date='$today'
        ORDER BY a.appointment_time ASC";
$appointments = mysqli_query($conn, $sql);
if(!$appointments) {
    die("Query failed: " . mysqli_error($conn));
}

$donated_sql = "SELECT a.*, d.first_name, d.last_name, d.blood_group, d.age, d.phone 
                FROM appointments a 
                JOIN donors d ON a.donor_id = d.id 
                WHERE a.hospital_id='$hospital_id' AND a.appointment_date='$today' AND a.status='success'
                ORDER BY a.appointment_time ASC";
$donated_today = mysqli_query($conn, $donated_sql);
$total_today = mysqli_num_rows($appointments);
$pending_count = 0;
$success_count = 0;
$failed_count = 0;
$temp_appointments = $appointments;
mysqli_data_seek($temp_appointments, 0);
while($app = mysqli_fetch_assoc($temp_appointments)) {
    if($app['status'] == 'pending') $pending_count++;
    elseif($app['status'] == 'success') $success_count++;
    elseif($app['status'] == 'failed') $failed_count++;
}
mysqli_data_seek($appointments, 0);
$donated_count = $success_count;
$all_time_total = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM appointments WHERE hospital_id='$hospital_id'"))['count'];
$all_time_success = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM appointments WHERE hospital_id='$hospital_id' AND status='success'"))['count'];
$no_data_message = ($total_today == 0) ? true : false;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Today's Appointments & Donations - Nurse</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .summary-cards {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 15px;
            margin-bottom: 25px;
        }
        .summary-card {
            background: white;
            padding: 15px;
            border-radius: 10px;
            text-align: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .summary-card.total { border-bottom: 3px solid #17a2b8; }
        .summary-card.pending { border-bottom: 3px solid #ffc107; }
        .summary-card.success { border-bottom: 3px solid #28a745; }
        .summary-card.failed { border-bottom: 3px solid #dc3545; }
        .summary-card.donated { border-bottom: 3px solid #28a745; background: #e8f5e9; }
        .summary-card h3 { font-size: 28px; margin: 5px 0; }
        .summary-card.total h3 { color: #17a2b8; }
        .summary-card.pending h3 { color: #ffc107; }
        .summary-card.success h3 { color: #28a745; }
        .summary-card.failed h3 { color: #dc3545; }
        .summary-card.donated h3 { color: #28a745; }
        
        .appointment-table, .donated-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            margin-top: 15px;
        }
        .appointment-table th, .donated-table th {
            background: #8B0000;
            color: white;
            padding: 12px;
            text-align: left;
        }
        .appointment-table td, .donated-table td {
            padding: 10px;
            border-bottom: 1px solid #eee;
        }
        .appointment-table tr:hover td, .donated-table tr:hover td {
            background: #f9f9f9;
        }
        .status-pending {
            background: #ffc107;
            color: #856404;
            padding: 4px 10px;
            border-radius: 15px;
            font-size: 12px;
        }
        .status-success {
            background: #28a745;
            color: white;
            padding: 4px 10px;
            border-radius: 15px;
            font-size: 12px;
        }
        .status-failed {
            background: #dc3545;
            color: white;
            padding: 4px 10px;
            border-radius: 15px;
            font-size: 12px;
        }
        .blood-badge {
            background: #8B0000;
            color: white;
            padding: 3px 10px;
            border-radius: 15px;
            font-size: 12px;
            display: inline-block;
        }
        .section-title {
            margin: 25px 0 15px 0;
            color: #8B0000;
            font-size: 20px;
            border-left: 4px solid #8B0000;
            padding-left: 15px;
        }
        .print-btn {
            background: #28a745;
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 5px;
            cursor: pointer;
            margin-bottom: 20px;
        }
        .btn-back {
            background: #6c757d;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            display: inline-block;
            margin-top: 20px;
        }
        .donated-badge {
            background: #b81515;
            color: white;
            padding: 3px 10px;
            border-radius: 15px;
            font-size: 12px;
            display: inline-block;
        }
        .no-data-box {
            text-align: center;
            padding: 50px;
            background: #f8f9fa;
            border-radius: 10px;
            margin: 20px 0;
        }
        .no-data-box i {
            font-size: 48px;
            color: #17a2b8;
            margin-bottom: 15px;
            display: block;
        }
    </style>
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
            <a href="verify_donor.php" class="menu-item"><i class="fas fa-qrcode"></i><span class="menu-text">Verify Donor</span></a>
            <a href="update_status.php" class="menu-item"><i class="fas fa-edit"></i><span class="menu-text">New Appointments</span></a>
            <a href="today_appointments.php" class="menu-item active"><i class="fas fa-calendar-day"></i><span class="menu-text">Today's List</span></a>
            <a href="send_report.php" class="menu-item">
                <i class="fas fa-paper-plane"></i>
                <span class="menu-text">Send Report</span>
           </a>
        </div>
        <div class="sidebar-footer">
            <a href="../logout.php" class="menu-item"><i class="fas fa-sign-out-alt"></i><span class="menu-text">Logout</span></a>
        </div>
    </div>

    <div class="main-content" id="mainContent">
        <div class="welcome-card" style="background: linear-gradient(135deg, #b9240a 0%, #460c08 100%);">
            <h2>Today's Data</h2>
            <p><?php echo $_SESSION['hospital_name']; ?> | <?php echo date('l, F d, Y'); ?></p>
        </div>
        <div class="summary-cards">
            <div class="summary-card total">
                <p> Total Appointments</p>
                <h3><?php echo $total_today; ?></h3>
            </div>
            <div class="summary-card donated">
                <p>🩸Today's Donations</p>
                <h3><?php echo $donated_count; ?></h3>
            </div>
            <div class="summary-card pending">
                <p>Pending</p>
                <h3><?php echo $pending_count; ?></h3>
            </div>
            <div class="summary-card success">
                <p> Completed</p>
                <h3><?php echo $success_count; ?></h3>
            </div>
            <div class="summary-card failed">
                <p> Failed</p>
                <h3><?php echo $failed_count; ?></h3>
            </div>
        </div>
        <div class="section-title">
            <i class="fas fa-calendar-alt"></i> All Today's Appointments
             <span style="background:#28a745; color:white; padding:2px 10px; border-radius:20px;"><?php echo $donated_count; ?> donors</span>
        </div>
        
        <?php if($total_today == 0): ?>
            <div class="no-data-box">
                <i class="fas fa-calendar-day"></i>
                <h3>No Appointments Today</h3>
                <p>There are no appointments scheduled for <?php echo date('F d, Y'); ?>.</p>
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
                        <td><i class="fas fa-clock"></i> <?php echo date('h:i A', strtotime($app['appointment_time'])); ?></td>
                        <td><strong><?php echo $app['first_name'] . ' ' . $app['last_name']; ?></strong></td>
                        <td><span class="blood-badge"><?php echo $app['blood_group']; ?></span></td>
                        <td><?php echo $app['age']; ?> yrs</span>
                        <td><i class="fas fa-phone"></i> <?php echo $app['phone']; ?></span>
                        <td><code><?php echo $app['unique_code']; ?></code></span>
                        <td>
                            <?php 
                            if($app['status'] == 'pending') {
                                echo "<span class='status-pending'>Pending</span>";
                            } elseif($app['status'] == 'success') {
                                echo "<span class='status-success'> Success</span>";
                            } else {
                                echo "<span class='status-failed'> Failed</span>";
                            }
                            ?>
                        </span>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <script>
        const toggleBtn = document.getElementById('toggleBtn');
        const sidebar = document.getElementById('sidebar');
        const mainContent = document.getElementById('mainContent');
        toggleBtn.addEventListener('click', () => {
            sidebar.classList.toggle('collapsed');
            mainContent.classList.toggle('expanded');
            toggleBtn.classList.toggle('collapsed');
        });
    </script>
 </body>
</html>