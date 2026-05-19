<?php
session_start();
include '../config/db_connect.php';

if($_SESSION['role'] != 'nurse') {
    header("Location: ../index.php");
    exit();
}

$hospital_id = $_SESSION['hospital_id'];
$today = date('Y-m-d');

// Handle bulk status update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_changes'])) {
    $updates = $_POST['status'] ?? [];
    $nurse_id = $_SESSION['user_id'];
    $success_count = 0;
    $error_count = 0;
    
    foreach($updates as $appointment_id => $status) {
        // Check if appointment date is valid (on or before today)
        $check_sql = "SELECT appointment_date FROM appointments WHERE id='$appointment_id'";
        $check_result = mysqli_query($conn, $check_sql);
        $appointment = mysqli_fetch_assoc($check_result);
        
        if($appointment['appointment_date'] <= $today) {
            $sql = "UPDATE appointments SET status='$status', nurse_id='$nurse_id' WHERE id='$appointment_id'";
            
            if(mysqli_query($conn, $sql)) {
                if($status == 'success') {
                    $get_donor = mysqli_query($conn, "SELECT donor_id FROM appointments WHERE id='$appointment_id'");
                    $donor = mysqli_fetch_assoc($get_donor);
                    mysqli_query($conn, "UPDATE donors SET last_donation_date=CURDATE(), total_donations=total_donations+1 WHERE id='{$donor['donor_id']}'");
                }
                $success_count++;
            } else {
                $error_count++;
            }
        } else {
            $error_count++;
        }
    }
    
    if($success_count > 0) {
        echo "<script>alert('✅ $success_count appointment(s) updated successfully!'); window.location='today_appointments.php';</script>";
    } else {
        echo "<script>alert('❌ Cannot update future appointments! You can only update appointments on or after their scheduled date.'); window.location='new_appointments.php';</script>";
    }
    exit();
}

$sql = "SELECT a.id, a.unique_code, a.appointment_date, a.appointment_time, a.created_at,
               d.first_name, d.last_name, d.blood_group, d.age, d.phone
        FROM appointments a 
        JOIN donors d ON a.donor_id = d.id 
        WHERE a.hospital_id='$hospital_id' AND a.status='pending'
        ORDER BY a.appointment_date ASC, a.appointment_time ASC, a.created_at ASC";
$appointments = mysqli_query($conn, $sql);
$pending_count = mysqli_num_rows($appointments);
?>
<!DOCTYPE html>
<html>
<head>
    <title>New Appointments - Nurse</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            background: #f5f5f5;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }
        h2 {
            color: #8B0000;
            margin-bottom: 10px;
            font-size: 24px;
        }
        .subtitle {
            color: #666;
            margin-bottom: 20px;
            font-size: 14px;
        }
        .stats-badge {
            background: #17a2b8;
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            display: inline-block;
            font-size: 14px;
            margin-bottom: 20px;
        }
        .btn-back {
            display: inline-block;
            background: #6c757d;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
            text-align: center;
        }
        .btn-back:hover {
            background: #5a6268;
        }
        
        .btn-save {
            background: #28a745;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 20px;
        }
        
        .btn-save:hover {
            background: #218838;
        }
        
        .appointments-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .appointments-table th {
            background: #8B0000;
            color: white;
            padding: 14px 12px;
            text-align: left;
            font-weight: 600;
            font-size: 14px;
        }
        
        .appointments-table td {
            padding: 12px;
            border-bottom: 1px solid #eee;
            font-size: 14px;
            vertical-align: middle;
        }
        
        .appointments-table tr:hover td {
            background: #f8f9fa;
        }
        
        .blood-badge {
            background: #8B0000;
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            display: inline-block;
        }
        
        .status-select {
            padding: 6px 10px;
            border-radius: 5px;
            border: 1px solid #ddd;
            font-size: 12px;
            cursor: pointer;
        }
        
        .status-select option[value="success"] { background: #d4edda; color: #155724; }
        .status-select option[value="failed"] { background: #f8d7da; color: #721c24; }
        
        .priority-badge {
            background: #ffc107;
            color: #856404;
            padding: 3px 8px;
            border-radius: 15px;
            font-size: 11px;
            font-weight: bold;
            display: inline-block;
        }
        
        .priority-1 {
            background: #28a745;
            color: white;
        }
        
        .no-data {
            text-align: center;
            padding: 50px;
            background: #f8f9fa;
            border-radius: 10px;
            color: #666;
        }
        
        code {
            background: #f0f0f0;
            padding: 4px 8px;
            border-radius: 4px;
            font-family: monospace;
            font-size: 12px;
        }
        
        .time-slot-info {
            font-size: 11px;
            color: #666;
            margin-top: 3px;
        }
        
        .checkbox-col {
            text-align: center;
            width: 40px;
        }
        
        .date-restricted {
            color: #dc3545;
            font-size: 12px;
            margin-top: 5px;
            font-weight: bold;
        }
        
        .future-date {
            opacity: 0.7;
            background-color: #fff3cd;
        }
        
        .warning-text {
            color: #dc3545;
            font-size: 13px;
            font-weight: bold;
            margin-top: 5px;
        }
        
        .info-note {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: bold;
        }
        
        @media (max-width: 768px) {
            .appointments-table {
                display: block;
                overflow-x: auto;
            }
            
            .appointments-table th,
            .appointments-table td {
                white-space: nowrap;
            }
            
            .status-select {
                padding: 4px 8px;
                font-size: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h2><i class="fas fa-calendar-plus"></i> New Appointments</h2>
        <p class="subtitle">View and manage pending donation requests</p>
        <div class="info-note">
            <i class="fas fa-exclamation-triangle"></i> <strong>Note:</strong> You can ONLY update status on or after the appointment date. Future appointments cannot be processed.
        </div>
        
        <div class="stats-badge">
            <i class="fas fa-clock"></i> <strong><?php echo $pending_count; ?> Pending Appointment(s)</strong>
        </div>
        
        <?php if($pending_count == 0): ?>
            <div class="no-data">
                <i class="fas fa-check-circle" style="font-size: 48px; color: #28a745; margin-bottom: 15px; display: block;"></i>
                No pending appointments found.
                <p style="margin-top: 10px;">All caught up! Check back later for new donation requests.</p>
            </div>
        <?php else: ?>
            <form method="POST" id="updateForm">
                <div style="text-align: right; margin-bottom: 15px;">
                    <button type="submit" name="save_changes" class="btn-save" onclick="return confirm('Save all changes?')">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                    <a href="dashboard.php" class="btn-back"> Back to Dashboard</a>
                </div>
                <table class="appointments-table" id="appointmentsTable">
                    <thead>
                        <tr>
                            <th class="checkbox-col">
                                <input type="checkbox" id="selectAll" onclick="toggleAll(this)">
                            </th>
                            <th>#</th>
                            <th>Date</th>
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
                        <?php 
                        $counter = 1;
                        $current_time_slot = '';
                        $position = 1;
                        $today_date = date('Y-m-d');
                        
                        while($app = mysqli_fetch_assoc($appointments)): 
                            $time_slot = $app['appointment_time'];
                            $appointment_date = $app['appointment_date'];
                            $is_future = ($appointment_date > $today_date);
                            
                            if($current_time_slot != $time_slot) {
                                $position = 1;
                                $current_time_slot = $time_slot;
                            }
                            $priority_msg = '';
                            if($position > 1) {
                                $priority_msg = '<div class="time-slot-info"><strong> Position #' . $position . ' in queue</strong></div>';
                            }   
                            $row_class = $is_future ? 'future-date' : '';
                        ?>
                        <tr class="<?php echo $row_class; ?>">
                            <td class="checkbox-col">
                                <input type="checkbox" name="selected[]" value="<?php echo $app['id']; ?>" class="appointment-checkbox" <?php echo $is_future ? 'disabled' : ''; ?>>
                                <input type="hidden" name="status[<?php echo $app['id']; ?>]" id="status_<?php echo $app['id']; ?>" value="">
                            </td>
                            <td style="text-align: center;">
                                <?php echo $counter++; ?>
                                <?php if($position == 1 && !$is_future): ?>
                                    <br><span class="priority-badge priority-1"><strong>NEXT</strong></span>
                                <?php elseif($position > 1 && !$is_future): ?>
                                    <br><span class="priority-badge"><strong>#<?php echo $position; ?></strong></span>
                                <?php endif; ?>
                                <?php if($is_future): ?>
                                    <br><span class="date-restricted"><strong>FUTURE DATE</strong></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <i class="fas fa-calendar-alt"></i> <strong><?php echo date('Y-m-d', strtotime($app['appointment_date'])); ?></strong>
                            </td>
                            <td>
                                <i class="fas fa-clock"></i> <?php echo date('h:i A', strtotime($app['appointment_time'])); ?>
                                <?php echo $priority_msg; ?>
                            </td>
                            <td><strong><?php echo $app['first_name'] . ' ' . $app['last_name']; ?></strong></td>
                            <td><span class="blood-badge"><?php echo $app['blood_group']; ?></span></td>
                            <td><?php echo $app['age']; ?> yrs</span>
                            <td><i class="fas fa-phone"></i> <?php echo $app['phone']; ?></span>
                            <td><code><?php echo $app['unique_code']; ?></code></span>
                            <td>
                                <select class="status-select" data-id="<?php echo $app['id']; ?>" onchange="updateStatus(<?php echo $app['id']; ?>, this.value)" <?php echo $is_future ? 'disabled' : ''; ?>>
                                    <option value="">Select</option>
                                    <option value="success">Success</option>
                                    <option value="failed">Failed</option>
                                </select>
                                <?php if($is_future): ?>
                                    <div class="warning-text"><i class="fas fa-clock"></i>wait until appointment date</div>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php 
                            $position++;
                        endwhile; 
                        ?>
                    </tbody>
                </table>
            </form>
        <?php endif; ?>
        <div style="text-align: center; margin-top: 20px;">
            <a href="dashboard.php" class="btn-back">Back to Dashboard</a>
        </div>
    </div>
    <script>
        function updateStatus(appointmentId, status) {
            document.getElementById('status_' + appointmentId).value = status;
            const row = document.querySelector(`input[value="${appointmentId}"]`);
            if (row) {
                row.checked = true;
            }
        }
        function toggleAll(source) {
            const checkboxes = document.querySelectorAll('.appointment-checkbox:not([disabled])');
            checkboxes.forEach(checkbox => {
                checkbox.checked = source.checked;
            });
        }
        document.getElementById('updateForm').addEventListener('submit', function(e) {
            const checkboxes = document.querySelectorAll('.appointment-checkbox:checked');
            let hasSelected = false;
            
            checkboxes.forEach(checkbox => {
                const id = checkbox.value;
                const status = document.getElementById('status_' + id).value;
                if (status === '') {
                    alert('Please select a status for the selected appointments');
                    e.preventDefault();
                    return;
                }
                hasSelected = true;
            });
            if (!hasSelected) {
                alert('Please select at least one appointment to update (future appointments cannot be updated)');
                e.preventDefault();
            }
        });
    </script>
</body>
</html>