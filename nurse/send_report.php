<?php
session_start();
include '../config/db_connect.php';

if($_SESSION['role'] != 'nurse') {
    header("Location: ../index.php");
    exit();
}

$nurse_id = $_SESSION['user_id'];
$hospital_id = $_SESSION['hospital_id'];
$hospital_name = $_SESSION['hospital_name'];
$nurse_name = $_SESSION['user_name'];
$message = '';
$error = '';
$report_type = isset($_GET['type']) ? $_GET['type'] : 'daily';
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all'; // all, success, failed
$today = date('Y-m-d');

$status_condition = "";
if($status_filter == 'success') {
    $status_condition = "AND a.status='success'";
} elseif($status_filter == 'failed') {
    $status_condition = "AND a.status='failed'";
}

if($report_type == 'daily') {
    $sql = "SELECT a.*, d.first_name, d.last_name, d.blood_group, d.age, d.phone, d.diseases
            FROM appointments a 
            JOIN donors d ON a.donor_id = d.id 
            WHERE a.hospital_id='$hospital_id' AND a.appointment_date='$today' $status_condition
            ORDER BY a.appointment_time ASC";
    $result = mysqli_query($conn, $sql);
    if (!$result) {
        die("Query failed: " . mysqli_error($conn));
    }
    $appointments = mysqli_fetch_all($result, MYSQLI_ASSOC);
    $total = count($appointments);
    $success = 0;
    $pending = 0;
    $failed = 0;
    foreach($appointments as $app) {
        if($app['status'] == 'success') $success++;
        elseif($app['status'] == 'pending') $pending++;
        else $failed++;
    }
    $report_title = "Daily Report - " . date('F d, Y');
    $report_period = "Date: " . date('F d, Y');
} elseif($report_type == 'weekly') {
    $sql = "SELECT a.*, d.first_name, d.last_name, d.blood_group, d.age, d.phone, d.diseases
            FROM appointments a 
            JOIN donors d ON a.donor_id = d.id 
            WHERE a.hospital_id='$hospital_id' 
            AND a.appointment_date >= DATE_SUB('$today', INTERVAL 7 DAY) $status_condition
            ORDER BY a.appointment_date DESC, a.appointment_time ASC";
    $result = mysqli_query($conn, $sql);
    
    if (!$result) {
        die("Query failed: " . mysqli_error($conn));
    }
    $appointments = mysqli_fetch_all($result, MYSQLI_ASSOC);
    $total = count($appointments);
    $success = 0;
    $pending = 0;
    $failed = 0;
    foreach($appointments as $app) {
        if($app['status'] == 'success') $success++;
        elseif($app['status'] == 'pending') $pending++;
        else $failed++;
    }
    $report_title = "Weekly Report (Last 7 Days)";
    $report_period = "Period: " . date('F d', strtotime('-7 days')) . " - " . date('F d, Y');
} else {
    $sql = "SELECT a.*, d.first_name, d.last_name, d.blood_group, d.age, d.phone, d.diseases
            FROM appointments a 
            JOIN donors d ON a.donor_id = d.id 
            WHERE a.hospital_id='$hospital_id' 
            AND a.appointment_date >= DATE_SUB('$today', INTERVAL 30 DAY) $status_condition
            ORDER BY a.appointment_date DESC, a.appointment_time ASC";
    $result = mysqli_query($conn, $sql);
    if (!$result) {
        die("Query failed: " . mysqli_error($conn));
    }
    $appointments = mysqli_fetch_all($result, MYSQLI_ASSOC);
    $total = count($appointments);
    $success = 0;
    $pending = 0;
    $failed = 0;
    foreach($appointments as $app) {
        if($app['status'] == 'success') $success++;
        elseif($app['status'] == 'pending') $pending++;
        else $failed++;
    }
    $report_title = "Monthly Report (Last 30 Days)";
    $report_period = "Period: " . date('F d', strtotime('-30 days')) . " - " . date('F d, Y');
}

$is_empty_report = ($total == 0);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if($is_empty_report) {
        $error = "Cannot send empty report";
    } else {
        $selected_type = $_POST['report_type'];
        $selected_status = $_POST['status_filter'];
        
        $status_cond = "";
        if($selected_status == 'success') {
            $status_cond = "AND a.status='success'";
        } elseif($selected_status == 'failed') {
            $status_cond = "AND a.status='failed'";
        }
        
        if($selected_type == 'daily') {
            $sql = "SELECT a.*, d.first_name, d.last_name, d.blood_group, d.age, d.phone, d.diseases
                    FROM appointments a 
                    JOIN donors d ON a.donor_id = d.id 
                    WHERE a.hospital_id='$hospital_id' AND a.appointment_date='$today' $status_cond
                    ORDER BY a.appointment_time ASC";
            $result = mysqli_query($conn, $sql);
            $report_appointments = mysqli_fetch_all($result, MYSQLI_ASSOC);
            $report_total = count($report_appointments);
            $report_success = 0;
            $report_pending = 0;
            $report_failed = 0;
            foreach($report_appointments as $app) {
                if($app['status'] == 'success') $report_success++;
                elseif($app['status'] == 'pending') $report_pending++;
                else $report_failed++;
            }
            $report_title = "Daily Report - " . date('F d, Y');
            $report_period_text = "Date: " . date('F d, Y');
        } elseif($selected_type == 'weekly') {
            $sql = "SELECT a.*, d.first_name, d.last_name, d.blood_group, d.age, d.phone, d.diseases
                    FROM appointments a 
                    JOIN donors d ON a.donor_id = d.id 
                    WHERE a.hospital_id='$hospital_id' 
                    AND a.appointment_date >= DATE_SUB('$today', INTERVAL 7 DAY) $status_cond
                    ORDER BY a.appointment_date DESC, a.appointment_time ASC";
            $result = mysqli_query($conn, $sql);
            $report_appointments = mysqli_fetch_all($result, MYSQLI_ASSOC);
            $report_total = count($report_appointments);
            $report_success = 0;
            $report_pending = 0;
            $report_failed = 0;
            foreach($report_appointments as $app) {
                if($app['status'] == 'success') $report_success++;
                elseif($app['status'] == 'pending') $report_pending++;
                else $report_failed++;
            }
            $report_title = "Weekly Report (Last 7 Days)";
            $report_period_text = "Period: " . date('F d', strtotime('-7 days')) . " - " . date('F d, Y');
        } else {
            $sql = "SELECT a.*, d.first_name, d.last_name, d.blood_group, d.age, d.phone, d.diseases
                    FROM appointments a 
                    JOIN donors d ON a.donor_id = d.id 
                    WHERE a.hospital_id='$hospital_id' 
                    AND a.appointment_date >= DATE_SUB('$today', INTERVAL 30 DAY) $status_cond
                    ORDER BY a.appointment_date DESC, a.appointment_time ASC";
            $result = mysqli_query($conn, $sql);
            $report_appointments = mysqli_fetch_all($result, MYSQLI_ASSOC);
            $report_total = count($report_appointments);
            $report_success = 0;
            $report_pending = 0;
            $report_failed = 0;
            foreach($report_appointments as $app) {
                if($app['status'] == 'success') $report_success++;
                elseif($app['status'] == 'pending') $report_pending++;
                else $report_failed++;
            }
            
            $report_title = "Monthly Report (Last 30 Days)";
            $report_period_text = "Period: " . date('F d', strtotime('-30 days')) . " - " . date('F d, Y');
        }
        
        $status_label = "";
        if($selected_status == 'success') {
            $status_label = "(Success Only)";
        } elseif($selected_status == 'failed') {
            $status_label = "(Failed Only)";
        } else {
            $status_label = "(All Donations)";
        }
        
        $report_html = '<div style="font-family: Arial, sans-serif; max-width: 1200px; margin: 0 auto;">
            <div style="background: linear-gradient(135deg, #8B0000 0%, #a00000 100%); color: white; padding: 20px; text-align: center; border-radius: 10px;">
                <h2>🩸Blood Bank Management System</h2>
                <p>Donation Report ' . $status_label . '</p>
            </div>
            <div style="background: #f8f9fa; padding: 15px; margin: 20px 0; border-radius: 10px;">
                <table style="width: 100%; border-collapse: collapse;">
                    <tr><td style="padding: 8px;"><strong>Report sent by:</strong></td><td>Nurse ' . $nurse_name . '</span></tr>
                    <tr><td style="padding: 8px;"><strong>Hospital:</strong></td><td>' . $hospital_name . '</span></tr>
                    <tr><td style="padding: 8px;"><strong>Generated:</strong></span><td>' . date('Y-m-d H:i:s') . '</span></tr>
                    <tr><td style="padding: 8px;"><strong>Report Type:</strong></td><td>' . $report_title . '</span></tr>
                    <tr><td style="padding: 8px;"><strong>Period:</strong></span><td>' . $report_period_text . '</span></tr>
                    <tr><td style="padding: 8px;"><strong>Filter:</strong></span><td>' . $status_label . '</span></tr>
                </table>
            </div>
            <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin: 20px 0;">
                <div style="background: #17a2b8; color: white; padding: 20px; text-align: center; border-radius: 10px;">
                    <h3 style="font-size: 32px; margin: 0;">' . $report_total . '</h3>
                    <p style="margin: 5px 0 0;">Total Donations</p>
                </div>
                <div style="background: #28a745; color: white; padding: 20px; text-align: center; border-radius: 10px;">
                    <h3 style="font-size: 32px; margin: 0;">' . $report_success . '</h3>
                    <p style="margin: 5px 0 0;"> Successful</p>
                </div>
                <div style="background: #ffc107; color: #856404; padding: 20px; text-align: center; border-radius: 10px;">
                    <h3 style="font-size: 32px; margin: 0;">' . $report_pending . '</h3>
                    <p style="margin: 5px 0 0;"> Pending</p>
                </div>
                <div style="background: #dc3545; color: white; padding: 20px; text-align: center; border-radius: 10px;">
                    <h3 style="font-size: 32px; margin: 0;">' . $report_failed . '</h3>
                    <p style="margin: 5px 0 0;"> Failed</p>
                </div>
            </div>
            
            <div style="background: white; border-radius: 10px; overflow-x: auto; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background: #8B0000; color: white;">
                            <th style="padding: 12px; text-align: left;">Date</th>
                            <th style="padding: 12px; text-align: left;">Time</th>
                            <th style="padding: 12px; text-align: left;">Donor Name</th>
                            <th style="padding: 12px; text-align: left;">Blood Group</th>
                            <th style="padding: 12px; text-align: left;">Age</th>
                            <th style="padding: 12px; text-align: left;">Phone</th>
                            <th style="padding: 12px; text-align: left;">Donation Code</th>
                            <th style="padding: 12px; text-align: left;">Status</th>
                        </tr>
                    </thead>
                    <tbody>';
        
        foreach($report_appointments as $app) {
            $date = date('Y-m-d', strtotime($app['appointment_date']));
            $time = $app['appointment_time'] ? date('h:i A', strtotime($app['appointment_time'])) : '--:--';
            $name = $app['first_name'] . ' ' . $app['last_name'];
            $blood = $app['blood_group'];
            $age = $app['age'];
            $phone = $app['phone'];
            $code = $app['unique_code'];
            $status = $app['status'];
            
            $status_color = ($status == 'success') ? '#28a745' : (($status == 'pending') ? '#ffc107' : '#dc3545');
            $status_text = ($status == 'success') ? ' Success' : (($status == 'pending') ? ' Pending' : ' Failed');
            
            $report_html .= '
                        <tr style="border-bottom: 1px solid #eee;">
                            <td style="padding: 10px;">' . $date . '</span>
                            <td style="padding: 10px;">' . $time . '</span>
                            <td style="padding: 10px;"><strong>' . $name . '</strong></span>
                            <td style="padding: 10px;"><span style="background: #8B0000; color: white; padding: 3px 10px; border-radius: 15px; font-size: 12px;">' . $blood . '</span></span>
                            <td style="padding: 10px;">' . $age . '</span>
                            <td style="padding: 10px;">' . $phone . '</span>
                            <td style="padding: 10px;"><code>' . $code . '</code></span>
                            <td style="padding: 10px;"><span style="background: ' . $status_color . '; color: white; padding: 3px 10px; border-radius: 15px; font-size: 12px;">' . $status_text . '</span></span>
                        </tr';
        }
        
        $report_html .= '
                    </tbody>
                </table>
            </div>
            <div style="text-align: center; margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 10px;">
                <p style="margin: 0;"> Success Rate: <strong>' . ($report_total > 0 ? round(($report_success/$report_total)*100,1) : 0) . '%</strong></p>
                <p style="margin: 5px 0 0; font-size: 12px; color: #666;">Generated by Blood Bank System</p>
            </div>
        </div>';
        $insert = "INSERT INTO reports (nurse_id, hospital_id, report_type, report_data, status) 
                   VALUES ('$nurse_id', '$hospital_id', '$selected_type', '$report_html', 'sent')";
        
        if(mysqli_query($conn, $insert)) {
            $message = "Report sent";
        } else {
            $error = "Failed to send report";
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Send Report</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .filter-group {
            display: flex;
            gap: 20px;
            justify-content: center;
            margin: 20px 0;
            flex-wrap: wrap;
        }
        .filter-option {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s;
        }
        .filter-option input {
            cursor: pointer;
        }
        .filter-option label {
            cursor: pointer;
        }
        .filter-option:hover {
            background: #e8f4f8;
        }
        .filter-option.active {
            background: #17a2b8;
            color: white;
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
            <a href="send_report.php" class="menu-item active"><i class="fas fa-paper-plane"></i><span class="menu-text">Send Report</span></a>
        </div>
        <div class="sidebar-footer">
            <a href="../logout.php" class="menu-item"><i class="fas fa-sign-out-alt"></i><span class="menu-text">Logout</span></a>
        </div>
    </div>
    <div class="main-content" id="mainContent">
        <div class="welcome-card" style="background: linear-gradient(135deg, #8a0e0e 0%, #5f1104 100%);">
            <h2>Send Report to Hospital Admin</h2>
            <p><?php echo $hospital_name; ?>  Nurse: <?php echo $nurse_name; ?></p>
        </div>
        <div class="report-type-buttons">
            <a href="?type=daily&status=<?php echo $status_filter; ?>" class="report-type-btn <?php echo $report_type == 'daily' ? 'active' : ''; ?>">Daily Report</a>
            <a href="?type=weekly&status=<?php echo $status_filter; ?>" class="report-type-btn <?php echo $report_type == 'weekly' ? 'active' : ''; ?>">Weekly Report</a>
            <a href="?type=monthly&status=<?php echo $status_filter; ?>" class="report-type-btn <?php echo $report_type == 'monthly' ? 'active' : ''; ?>">Monthly Report</a>
        </div>
        <div class="filter-group">
            <div class="filter-option">
                <input type="radio" name="status_filter_radio" id="filter_all" value="all" <?php echo ($status_filter == 'all') ? 'checked' : ''; ?> onchange="changeFilter('all')">
                <label for="filter_all">All Donations</label>
            </div>
            <div class="filter-option">
                <input type="radio" name="status_filter_radio" id="filter_success" value="success" <?php echo ($status_filter == 'success') ? 'checked' : ''; ?> onchange="changeFilter('success')">
                <label for="filter_success">Success Only</label>
            </div>
            <div class="filter-option">
                <input type="radio" name="status_filter_radio" id="filter_failed" value="failed" <?php echo ($status_filter == 'failed') ? 'checked' : ''; ?> onchange="changeFilter('failed')">
                <label for="filter_failed">Failed Only</label>
            </div>
        </div>

        <?php if($message): ?>
            <div class="message-success">
                <i class="fas fa-check-circle"></i> <?php echo $message; ?>
                <br><br>
                <a href="dashboard.php" class="btn">Back to Dashboard</a>
            </div>
        <?php elseif($error): ?>
            <div class="message-error">
                <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
                <br><br>
                <a href="dashboard.php" class="btn">Back to Dashboard</a>
            </div>
        <?php else: ?>
            
            <?php if($is_empty_report): ?>
                <div class="empty-warning">
                    <i class="fas fa-info-circle"></i> 
                    <strong>No donations found for this period with selected filter!</strong><br>
                    Please select a different report type or filter.
                </div>
            <?php endif; ?>
            <div class="stats-cards">
                <div class="stat-card total">
                    <h3><?php echo $total; ?></h3>
                    <p>Total Donations</p>
                </div>
                <div class="stat-card success">
                    <h3><?php echo $success; ?></h3>
                    <p>Successful</p>
                </div>
                <div class="stat-card pending">
                    <h3><?php echo $pending; ?></h3>
                    <p>Pending</p>
                </div>
                <div class="stat-card failed">
                    <h3><?php echo $failed; ?></h3>
                    <p>Failed</p>
                </div>
            </div>
            <div class="report-preview">
                <strong>Report Preview:</strong><br><br>
                <?php if($total > 0): ?>
                    <table class="preview-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Donor Name</th>
                                <th>Blood</th>
                                <th>Age</th>
                                <th>Phone</th>
                                <th>Code</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($appointments as $app): ?>
                            <tr>
                                <td><?php echo date('Y-m-d', strtotime($app['appointment_date'])); ?></td>
                                <td><?php echo $app['appointment_time'] ? date('h:i A', strtotime($app['appointment_time'])) : '--:--'; ?></td>
                                <td><strong><?php echo $app['first_name'] . ' ' . $app['last_name']; ?></strong></td>
                                <td><span style="background:#8B0000; color:white; padding:2px 8px; border-radius:12px; font-size:11px;"><?php echo $app['blood_group']; ?></span></td>
                                <td><?php echo $app['age']; ?></td>
                                <td><?php echo $app['phone']; ?></td>
                                <td><code><?php echo $app['unique_code']; ?></code></td>
                                <td>
                                    <?php 
                                    if($app['status'] == 'success') echo "<span style='color:#28a745;'> Success</span>";
                                    elseif($app['status'] == 'pending') echo "<span style='color:#ffc107;'>Pending</span>";
                                    else echo "<span style='color:#dc3545;'>Failed</span>";
                                    ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p style="text-align: center; padding: 40px;">No donations found for this filter.</p>
                <?php endif; ?>
            </div>

            <form method="POST" style="text-align: center;">
                <input type="hidden" name="report_type" value="<?php echo $report_type; ?>">
                <input type="hidden" name="status_filter" id="status_filter_input" value="<?php echo $status_filter; ?>">
                <button type="submit" class="btn-send" <?php echo $is_empty_report ? 'disabled' : ''; ?>>
                    <i class="fas fa-paper-plane"></i> Send Report
                </button>
            </form>
            <div style="text-align: center; margin-top: 20px;">
                <a href="dashboard.php" class="btn">Cancel</a>
            </div>
        <?php endif; ?>
    </div>
    <script>
        function changeFilter(filter) {
            const currentType = '<?php echo $report_type; ?>';
            window.location.href = '?type=' + currentType + '&status=' + filter;
        }
        document.querySelectorAll('input[name="status_filter_radio"]').forEach(radio => {
            radio.addEventListener('change', function() {
                document.getElementById('status_filter_input').value = this.value;
            });
        });
    </script>
</body>
</html>