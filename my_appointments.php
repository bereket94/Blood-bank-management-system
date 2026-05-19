<?php
session_start();
include '../config/db_connect.php';

if($_SESSION['role'] != 'donor') {
    header("Location: ../index.php");
    exit();
}
$donor_id = $_SESSION['user_id'];
$sql = "SELECT a.*, h.name as hospital_name 
        FROM appointments a 
        JOIN hospitals h ON a.hospital_id = h.id 
        WHERE a.donor_id='$donor_id' 
        ORDER BY a.appointment_date DESC";
$appointments = mysqli_query($conn, $sql);
?>
<!DOCTYPE html>
<html>
<head>
    <title>My Appointments - Blood Bank</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .appointments-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .page-header {
            background: linear-gradient(135deg, #8B0000 0%, #a00000 100%);
            color: white;
            padding: 25px 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .page-header h2 {
            margin: 0;
            font-size: 24px;
        }
        
        .page-header p {
            margin: 5px 0 0;
            opacity: 0.9;
            font-size: 14px;
        }
   
        .btn-book {
            background: #8B0000;
            color: white;
            padding: 10px 25px;
            border-radius: 5px;
            text-decoration: none;
            display: inline-block;
            transition: background 0.3s;
        }
        
        .btn-book:hover {
            background: #a00000;
        }
        
        @media (max-width: 768px) {
            .appointments-table {
                display: block;
                overflow-x: auto;
            }
            
            .stats-row {
                flex-direction: column;
            }
            
            .page-header {
                flex-direction: column;
                text-align: center;
                gap: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="appointments-container">
        <div class="page-header">
            <div>
                <h2><i class="fas fa-calendar-alt"></i> My Donation Appointments</h2>
                <p>View and track your blood donation history</p>
            </div>
            <a href="dashboard.php" class="btn-back"> Back to Dashboard</a>
        </div>
        <?php 
        $total = mysqli_num_rows($appointments);
        $pending = 0;
        $success = 0;
        $failed = 0;
        mysqli_data_seek($appointments, 0);
        while($app = mysqli_fetch_assoc($appointments)) {
            if($app['status'] == 'pending') $pending++;
            elseif($app['status'] == 'success') $success++;
            elseif($app['status'] == 'failed') $failed++;
        }
        mysqli_data_seek($appointments, 0);
        ?>
        <?php if($total == 0): ?>
            <div class="no-appointments">
                <i class="fas fa-calendar-times"></i>
                <p>You haven't booked any appointments yet.</p>
                <a href="book_appointment.php" class="btn-book">
                    <i class="fas fa-calendar-plus"></i> Book Your First Appointment
                </a>
            </div>
        <?php else: ?>
            <div class="appointments-card">
                <div class="card-header">
                    <h3><i class="fas fa-list"></i> Appointment History</h3>
                </div>
                <table class="appointments-table">
                    <thead>
                        <tr>
                            <th><i class="fas fa-calendar-day"></i> Date</th>
                            <th><i class="fas fa-clock"></i> Time</th>
                            <th><i class="fas fa-hospital"></i> Hospital</th>
                            <th><i class="fas fa-qrcode"></i> Donation Code</th>
                            <th><i class="fas fa-info-circle"></i> Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($app = mysqli_fetch_assoc($appointments)): ?>
                        <tr>
                            <td>
                                <i class="fas fa-calendar-alt" style="color: #8B0000; margin-right: 5px;"></i>
                                <?php echo date('F d, Y', strtotime($app['appointment_date'])); ?>
                             </span>
                            <td>
                                <i class="fas fa-clock" style="color: #8B0000; margin-right: 5px;"></i>
                                <?php echo date('h:i A', strtotime($app['appointment_time'])); ?>
                             </span>
                            <td>
                                <i class="fas fa-hospital" style="color: #8B0000; margin-right: 5px;"></i>
                                <?php echo $app['hospital_name']; ?>
                             </span>
                            <td>
                                <span class="unique-code"><?php echo $app['unique_code']; ?></span>
                             </span>
                            <td>
                                <?php 
                                if($app['status'] == 'pending') {
                                    echo "<span class='status-badge status-pending'><i class='fas fa-hourglass-half'></i> Pending</span>";
                                } elseif($app['status'] == 'success') {
                                    echo "<span class='status-badge status-success'><i class='fas fa-check-circle'></i> Success</span>";
                                } else {
                                    echo "<span class='status-badge status-failed'><i class='fas fa-times-circle'></i> Failed</span>";
                                }
                                ?>
                             </span>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <div style="text-align: center; margin-top: 25px;">
                <a href="book_appointment.php" class="btn-book"> Book New Appointment </a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>