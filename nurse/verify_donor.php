<?php
session_start();
include '../config/db_connect.php';

if($_SESSION['role'] != 'nurse') {
    header("Location: ../index.php");
    exit();
}

$donor_info = null;
$message = '';
$error = '';
$today = date('Y-m-d');

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $appointment_id = $_POST['appointment_id'];
    $status = $_POST['status'];
    $nurse_id = $_SESSION['user_id'];

    $date_check = mysqli_query($conn, "SELECT appointment_date FROM appointments WHERE id='$appointment_id'");
    $appointment_data = mysqli_fetch_assoc($date_check);
    $appointment_date = $appointment_data['appointment_date'];
    
    if($appointment_date > $today) {
        $error = "Cannot update status! This appointment is scheduled for " . date('F d, Y', strtotime($appointment_date)) . ". You can only update on or after the appointment date.";
    } else {
        $update_sql = "UPDATE appointments SET status='$status', nurse_id='$nurse_id' WHERE id='$appointment_id'";
        
        if(mysqli_query($conn, $update_sql)) {
            if($status == 'success') {
                $get_donor = mysqli_query($conn, "SELECT donor_id FROM appointments WHERE id='$appointment_id'");
                $donor = mysqli_fetch_assoc($get_donor);
                mysqli_query($conn, "UPDATE donors SET last_donation_date=CURDATE(), total_donations=total_donations+1 WHERE id='{$donor['donor_id']}'");
            }
            $message = "Donation status updated to " . ucfirst($status) . "!";
            $donor_info['status'] = $status;
        } else {
            $error = "Failed to update status!";
        }
    }
}
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['verify_code'])) {
    $code = $_POST['code'];
    $hospital_id = $_SESSION['hospital_id'];
    
    $sql = "SELECT a.*, d.first_name, d.last_name, d.blood_group, d.age, d.weight, d.phone, d.diseases
            FROM appointments a 
            JOIN donors d ON a.donor_id = d.id 
            WHERE a.unique_code='$code' AND a.hospital_id='$hospital_id'";
    
    $result = mysqli_query($conn, $sql);
    if(mysqli_num_rows($result) > 0) {
        $donor_info = mysqli_fetch_assoc($result);
        $_SESSION['appointment_id'] = $donor_info['id'];
    } else {
        $error = "Invalid donation code!";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Verify Donor - Nurse</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .verify-box {
            background: white;
            border-radius: 10px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .verify-box h3 {
            color: #8B0000;
            margin-bottom: 15px;
        }
        .form-group {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }
        .form-group input {
            flex: 2;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .form-group button {
            background: #8B0000;
            color: white;
            border: none;
            padding: 10px 25px;
            border-radius: 5px;
            cursor: pointer;
        }
        .form-group button:hover {
            background: #a00000;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 5px;
            margin-top: 15px;
        }
        .success {
            background: #d4edda;
            color: #155724;
            padding: 10px;
            border-radius: 5px;
            margin-top: 15px;
        }
        .warning {
            background: #fff3cd;
            color: #856404;
            padding: 10px;
            border-radius: 5px;
            margin-top: 15px;
        }
        .donor-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            margin-top: 20px;
        }
        .donor-table th {
            background: #8B0000;
            color: white;
            padding: 12px;
            text-align: left;
        }
        .donor-table td {
            padding: 10px;
            border-bottom: 1px solid #eee;
        }
        .donor-table tr:hover td {
            background: #f9f9f9;
        }
        .blood-badge {
            background: #8B0000;
            color: white;
            padding: 3px 10px;
            border-radius: 15px;
            font-size: 12px;
            display: inline-block;
        }
        .status-pending {
            background: #ffc107;
            color: #856404;
            padding: 3px 10px;
            border-radius: 15px;
            font-size: 12px;
            display: inline-block;
        }
        .status-success {
            background: #28a745;
            color: white;
            padding: 3px 10px;
            border-radius: 15px;
            font-size: 12px;
            display: inline-block;
        }
        .status-failed {
            background: #dc3545;
            color: white;
            padding: 3px 10px;
            border-radius: 15px;
            font-size: 12px;
            display: inline-block;
        }
        .btn-update {
            background: #28a745;
            color: white;
            padding: 8px 20px;
            text-decoration: none;
            border-radius: 5px;
            display: inline-block;
            border: none;
            cursor: pointer;
            font-size: 14px;
        }
        .btn-update:hover {
            background: #218838;
        }
        .btn-update:disabled {
            background: #6c757d;
            cursor: not-allowed;
        }
        .btn-back {
            background: #6c757d;
            color: white;
            padding: 8px 20px;
            text-decoration: none;
            border-radius: 5px;
            display: inline-block;
        }
        .btn-back:hover {
            background: #5a6268;
        }
        .action-buttons {
            margin-top: 20px;
            text-align: center;
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }
        .update-section {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-top: 20px;
            border-top: 2px solid #8B0000;
        }
        .update-section h4 {
            margin-bottom: 15px;
            color: #8B0000;
        }
        .update-form {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }
        .update-form select {
            flex: 1;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .code-highlight {
            background: #f0f0f0;
            padding: 5px 10px;
            border-radius: 5px;
            font-family: monospace;
            font-weight: bold;
        }
        .date-warning {
            background: #fff3cd;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
            text-align: center;
        }
        @media (max-width: 768px) {
            .donor-table {
                display: block;
                overflow-x: auto;
            }
            .update-form {
                flex-direction: column;
            }
            .update-form select, .update-form button {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h2><i class="fas fa-qrcode"></i> Verify Donor by Code</h2>
        <div class="verify-box">
            <h3>Enter Donation Code</h3>
            <form method="POST">
                <div class="form-group">
                    <input type="text" name="code" placeholder="Enter Donation Code (DON12345...)" required>
                    <button type="submit" name="verify_code">Verify Donor</button>
                </div>
                        <a href="dashboard.php" class="btn-back"> Back to Dashboard</a>
            </form>
        </div>
        <?php if($donor_info): 
            $full_name = $donor_info['first_name'] . ' ' . $donor_info['last_name'];
            $appointment_date = $donor_info['appointment_date'];
            $is_future = ($appointment_date > $today);
            $can_update = !$is_future && ($donor_info['status'] == 'pending' || $donor_info['status'] == 'approved');
        ?>
            <?php if($is_future): ?>
                <div class="date-warning">
                    <i class="fas fa-calendar-day"></i> 
                    <strong>Future Appointment</strong><br>
                    This appointment is scheduled for <strong><?php echo date('F d, Y', strtotime($appointment_date)); ?></strong>.<br>
                    You can only update the status on or after the appointment date.
                </div>
            <?php endif; ?>
            <div class="donor-table-container">
                <table class="donor-table">
                    <thead>
                        <tr>
                            <th>Donor Name</th>
                            <th>Blood Group</th>
                            <th>Age</th>
                            <th>Weight</th>
                            <th>Phone</th>
                            <th>Medical Conditions</th>
                            <th>Appointment Date</th>
                            <th>Time</th>
                            <th>Donation Code</th>
                            <th>Current Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong><?php echo $full_name; ?></strong></td>
                            <td><span class="blood-badge"><?php echo $donor_info['blood_group']; ?></span></td>
                            <td><?php echo $donor_info['age']; ?> yrs</span>
                            <td><?php echo $donor_info['weight']; ?> kg</span>
                            <td><?php echo $donor_info['phone']; ?></span>
                            <td><?php echo $donor_info['diseases'] ?: 'None'; ?></span>
                            <td>
                                <?php echo date('F d, Y', strtotime($appointment_date)); ?>
                                <?php if($is_future): ?>
                                    <span style="color: #dc3545; font-size: 11px; display: block;">Future Date</span>
                                <?php endif; ?>
                             </span>
                            <td><?php echo date('h:i A', strtotime($donor_info['appointment_time'])); ?></span>
                            <td><code class="code-highlight"><?php echo $donor_info['unique_code']; ?></code></span>
                            <td>
                                <?php 
                                if($donor_info['status'] == 'pending') {
                                    echo "<span class='status-pending'>Pending</span>";
                                } elseif($donor_info['status'] == 'success') {
                                    echo "<span class='status-success'>Success</span>";
                                } else {
                                    echo "<span class='status-failed'>Failed</span>";
                                }
                                ?>
                             </span>
                                </tr>
                    </tbody>
                </table>
                
                <?php if($can_update): ?>
                    <div class="update-section">
                        <h4><i class="fas fa-edit"></i> Update Donation Status</h4>
                        <form method="POST" class="update-form">
                            <input type="hidden" name="appointment_id" value="<?php echo $donor_info['id']; ?>">
                            <select name="status" required>
                                <option value="">Select Status</option>
                                <option value="success"> Succes</option>
                                <option value="failed">Failed</option>
                            </select>
                            <button type="submit" name="update_status" class="btn-update">
                                <i class="fas fa-save"></i> Update Status
                            </button>
                        </form>
                        <p style="font-size: 12px; color: #666; margin-top: 10px;">
                            <i class="fas fa-info-circle"></i> After donation, update status to "Success" or "Failed"
                        </p>
                    </div>
                <?php elseif($is_future && $donor_info['status'] == 'pending'): ?>
                    <div class="update-section" style="background: #fff3cd;">
                        <h4 style="color: #856404;"><i class="fas fa-clock"></i> Cannot Update Yet</h4>
                        <p>This appointment is for <strong><?php echo date('F d, Y', strtotime($appointment_date)); ?></strong>.</p>
                        <p>Please come back on or after the appointment date to update the donation status.</p>
                    </div>
                <?php endif; ?>
                
                <div class="action-buttons">
                    <a href="dashboard.php" class="btn-back"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
                    <a href="verify_donor.php" class="btn-back"><i class="fas fa-search"></i> Verify Another Donor</a>
                </div>
            </div>
        <?php endif; ?>
        <?php if(!$donor_info && !isset($error) && !isset($message)): ?>
            <div style="text-align: center; padding: 60px; background: white; border-radius: 10px;">
                <i class="fas fa-qrcode" style="font-size: 64px; color: #8B0000; margin-bottom: 20px; display: block;"></i>
                <h3>Enter a Donation Code</h3>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>