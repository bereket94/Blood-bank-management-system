<?php
session_start();
include '../config/db_connect.php';

if($_SESSION['role'] != 'donor') {
    header("Location: ../index.php");
    exit();
}

$donor_id = $_SESSION['user_id'];
$error = '';
$can_book = true;
$show_modal = false;
$new_code = '';
$new_date = '';
$new_time = '';
$new_hospital = '';

$sql = "SELECT * FROM donors WHERE id='$donor_id'";
$result = mysqli_query($conn, $sql);
$donor = mysqli_fetch_assoc($result);

$full_name = $donor['first_name'] . ' ' . $donor['last_name'];
$pending_sql = "SELECT a.*, h.name as hospital_name 
                FROM appointments a 
                JOIN hospitals h ON a.hospital_id = h.id 
                WHERE a.donor_id='$donor_id' AND a.status='pending'";
$pending_result = mysqli_query($conn, $pending_sql);
$has_pending = mysqli_num_rows($pending_result) > 0;
$pending_appointment = mysqli_fetch_assoc($pending_result);

if($has_pending) {
    $can_book = false;
    $error = "You already have a PENDING appointment!<br>
              Date: " . date('F d, Y', strtotime($pending_appointment['appointment_date'])) . "<br>
              Time: " . date('h:i A', strtotime($pending_appointment['appointment_time'])) . "<br>
              Hospital: " . $pending_appointment['hospital_name'] . "<br>
              Code: " . $pending_appointment['unique_code'] . "<br><br>
              Please complete your pending appointment before booking a new one.";
}
if(!$has_pending) {
    $last_donation = $donor['last_donation_date'];
    $days_since = 0;
    $days_remaining = 0;

    if ($last_donation && $last_donation != '0000-00-00' && $last_donation != NULL) {
        $last_date = new DateTime($last_donation);
        $today = new DateTime();
        $days_since = $today->diff($last_date)->days;
        
        if ($days_since < 90) {
            $can_book = false;
            $days_remaining = 90 - $days_since;
            $error = "You CANNOT book an appointment!<br>
                      Your last donation was on: <strong>" . date('F d, Y', strtotime($last_donation)) . "</strong><br>
                      Days since last donation: <strong>{$days_since} days</strong><br>
                      You need to wait <strong>{$days_remaining} more days</strong> before your next donation.<br>
                      Minimum required gap is <strong>90 days</strong> between donations.";
        }
    }
}
$hospitals = mysqli_query($conn, "SELECT * FROM hospitals");
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $can_book && !$has_pending) {
    $hospital_id = $_POST['hospital_id'];
    $appointment_date = $_POST['appointment_date'];
    $appointment_time = $_POST['appointment_time'];
    $unique_code = "DON" . rand(10000, 99999) . time();
    $hospital_name_query = mysqli_query($conn, "SELECT name FROM hospitals WHERE id='$hospital_id'");
    $hospital_data = mysqli_fetch_assoc($hospital_name_query);
    $check_sql = "SELECT id FROM appointments WHERE donor_id='$donor_id' AND status='pending'";
    $check_result = mysqli_query($conn, $check_sql);
    
    if (mysqli_num_rows($check_result) > 0) {
        $error = "You already have a pending appointment. Please complete it before booking a new one.";
    } 
    elseif (strtotime($appointment_date) < strtotime(date('Y-m-d'))) {
        $error = "You cannot book an appointment for a past date!";
    }
    elseif (isTimeSlotBooked($hospital_id, $appointment_date, $appointment_time, $conn)) {
        $error = "The selected time slot is already booked! Please choose a different time.";
    }
    else {
        $sql = "INSERT INTO appointments (donor_id, hospital_id, appointment_date, appointment_time, unique_code, status) 
                VALUES ('$donor_id', '$hospital_id', '$appointment_date', '$appointment_time', '$unique_code', 'pending')";
        
        if(mysqli_query($conn, $sql)) {
            $show_modal = true;
            $new_code = $unique_code;
            $new_date = $appointment_date;
            $new_time = $appointment_time;
            $new_hospital = $hospital_data['name'];
        } else {
            $error = "Appointment booking failed. Please try again.";
        }
    }
}
function isTimeSlotBooked($hospital_id, $date, $time, $conn) {
    $check_sql = "SELECT id FROM appointments 
                  WHERE hospital_id='$hospital_id' 
                  AND appointment_date='$date' 
                  AND appointment_time='$time' 
                  AND status != 'cancelled'";
    $check_result = mysqli_query($conn, $check_sql);
    return mysqli_num_rows($check_result) > 0;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Book Appointment</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .info-box {
            background: #e8f4f8;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .restriction-box {
            background: #f8d7da;
            color: #721c24;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            border-left: 4px solid #dc3545;
            text-align: center;
        }
        .pending-box {
            background: #fff3cd;
            color: #856404;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            border-left: 4px solid #ffc107;
            text-align: center;
        }
        .days-count {
            font-size: 24px;
            font-weight: bold;
            color: #dc3545;
        }
        .btn-back {
            display: inline-block;
            background: #6c757d;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 15px;
            text-align: center;
        }
        .btn-back:hover {
            background: #5a6268;
        }
        label {
            font-weight: bold;
            display: block;
            margin-top: 10px;
        }
        .modal {
            display: <?php echo $show_modal ? 'flex' : 'none'; ?>;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }
        .modal-content {
            background: white;
            width: 450px;
            max-width: 90%;
            border-radius: 15px;
            overflow: hidden;
            animation: fadeIn 0.3s ease;
            text-align: center;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: scale(0.9); }
            to { opacity: 1; transform: scale(1); }
        }
        .modal-header {
            background: linear-gradient(135deg, #28a745 0%, #1e7e34 100%);
            color: white;
            padding: 20px;
        }
        .modal-header i {
            font-size: 60px;
            margin-bottom: 10px;
        }
        .modal-header h3 {
            margin: 0;
            font-size: 24px;
        }
        .modal-body {
            padding: 25px;
        }
        .code-box {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            margin: 15px 0;
            border: 2px dashed #28a745;
        }
        .code-box span {
            font-size: 20px;
            font-weight: bold;
            font-family: monospace;
            letter-spacing: 2px;
            background: #fff;
            padding: 10px 15px;
            border-radius: 8px;
            display: inline-block;
        }
        .appointment-details {
            text-align: left;
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            margin: 15px 0;
        }
        .appointment-details p {
            margin: 8px 0;
        }
        .btn-copy {
            background: #17a2b8;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            margin: 5px;
            font-size: 14px;
        }
        .btn-copy:hover {
            background: #138496;
        }
        .btn-close {
            background: #28a745;
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 5px;
            cursor: pointer;
            margin: 5px;
            font-size: 16px;
        }
        .btn-close:hover {
            background: #1e7e34;
        }
        .btn-print {
            background: #6c757d;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            margin: 5px;
            font-size: 14px;
        }
        .btn-print:hover {
            background: #5a6268;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="form-container">
            <div class="role-badge">Book Donation Appointment</div>
            <h2>Schedule Your Blood Donation</h2>
            <div class="info-box">
                <strong>Donor Information:</strong><br>
                Name: <?php echo $full_name; ?><br>
                Blood Group: <strong><?php echo $donor['blood_group']; ?></strong><br>
                Last Donation: <?php echo $donor['last_donation_date'] ? date('F d, Y', strtotime($donor['last_donation_date'])) : 'Never donated'; ?>
            </div>
            <?php if($has_pending): ?>
                <div class="pending-box">
                    <strong>PENDING APPOINTMENT EXISTS</strong><br><br>
                    <?php echo $error; ?>
                    <br><br>
                    <a href="my_appointments.php" class="btn">View My Appointments</a>
                    <a href="dashboard.php" class="btn-back">Back to Dashboard</a>
                </div>
            <?php elseif(!$can_book): ?>
                <div class="restriction-box">
                    <strong>BOOKING RESTRICTED</strong><br><br>
                    <?php echo $error; ?>
                    <br><br>
                    <div class="days-count">⏰ <?php echo $days_remaining; ?> days remaining</div>
                    <br>
                    <a href="dashboard.php" class="btn">Back to Dashboard</a>
                </div>
            <?php else: ?>
                <div class="info-box">
                    <strong>You are eligible to donate!</strong><br>
                    <?php if($donor['last_donation_date']): ?>
                        Your last donation was <?php echo $days_since; ?> days ago. 
                        You have met the 90-day requirement.
                    <?php else: ?>
                        This will be your first donation. Thank you for your generosity!
                    <?php endif; ?>
                </div>
                <?php if($error): ?>
                    <div class="error"><?php echo $error; ?></div>
                <?php endif; ?>
                <form method="POST">
                    <select name="hospital_id" required>
                        <option value="">Select Hospital</option>
                        <?php while($h = mysqli_fetch_assoc($hospitals)): ?>
                            <option value="<?php echo $h['id']; ?>"><?php echo $h['name']; ?> - <?php echo $h['location']; ?></option>
                        <?php endwhile; ?>
                    </select>  
                    <label>Appointment Date</label>
                    <input type="date" name="appointment_date" required min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
                    <label>Preferred Time</label>
                    <select name="appointment_time" required>
                        <option value="">Select Time</option>
                        <option value="09:00:00">09:00 AM</option>
                        <option value="10:00:00">10:00 AM</option>
                        <option value="11:00:00">11:00 AM</option>
                        <option value="14:00:00">02:00 PM</option>
                        <option value="15:00:00">03:00 PM</option>
                        <option value="16:00:00">04:00 PM</option>
                    </select>
                    <button type="submit">Book Appointment</button>
                    <a href="dashboard.php" class="btn">Back to Dashboard</a>
                </form>
            <?php endif; ?>
        </div>
    </div>
    <?php if($show_modal): ?>
    <div class="modal" id="successModal">
        <div class="modal-content">
            <div class="modal-header">
                <i class="fas fa-check-circle"></i>
                <h3>Appointment Booked!</h3>
            </div>
            <div class="modal-body">
                <p>Your blood donation appointment has been successfully scheduled.</p>
                
                <div class="code-box">
                    <strong>Your Donation Code:</strong><br>
                    <span id="donationCode"><?php echo $new_code; ?></span>
                    <br><br>
                    <button class="btn-copy" onclick="copyCode()">
                        <i class="fas fa-copy"></i> Copy Code
                    </button>
                    <button class="btn-print" onclick="printCode()">
                        <i class="fas fa-print"></i> Print
                    </button>
                </div>
                
                <div class="appointment-details">
                    <strong>Appointment Details:</strong>
                    <p><i class="fas fa-calendar-alt"></i> <strong>Date:</strong> <?php echo date('F d, Y', strtotime($new_date)); ?></p>
                    <p><i class="fas fa-clock"></i> <strong>Time:</strong> <?php echo date('h:i A', strtotime($new_time)); ?></p>
                    <p><i class="fas fa-hospital"></i> <strong>Hospital:</strong> <?php echo $new_hospital; ?></p>
                </div>
                
                <p style="color: #dc3545; font-size: 12px; margin-top: 10px;">
                    <i class="fas fa-exclamation-triangle"></i> Please save this code! You will need it for verification at the hospital.
                </p>
                
                <button class="btn-close" onclick="closeModal()">
                    <i class="fas fa-arrow-right"></i> Go to My Appointments
                </button>
            </div>
        </div>
    </div>
    
    <script>
        function copyCode() {
            var code = document.getElementById('donationCode').innerText;
            navigator.clipboard.writeText(code);
            alert('Code copied: ' + code);
        }
        
        function printCode() {
            var code = document.getElementById('donationCode').innerText;
            var printWindow = window.open('', '_blank');
            printWindow.document.write(`
                <html>
                <head>
                    <title>Donation Code</title>
                    <style>
                        body { font-family: Arial, sans-serif; text-align: center; padding: 50px; }
                        .code { font-size: 32px; font-weight: bold; font-family: monospace; letter-spacing: 3px; background: #f0f0f0; padding: 20px; border-radius: 10px; }
                        .details { margin-top: 30px; font-size: 16px; }
                        .header { color: #8B0000; }
                    </style>
                </head>
                <body>
                    <h1 class="header">Blood Bank System</h1>
                    <h2>Your Donation Code</h2>
                    <div class="code">${code}</div>
                    <div class="details">
                        <p><strong>Date:</strong> <?php echo date('F d, Y', strtotime($new_date)); ?></p>
                        <p><strong>Time:</strong> <?php echo date('h:i A', strtotime($new_time)); ?></p>
                        <p><strong>Hospital:</strong> <?php echo $new_hospital; ?></p>
                    </div>
                    <p>Please bring this code to your appointment.</p>
                    <p>Thank you for saving lives!</p>
                </body>
                </html>
            `);
            printWindow.print();
            printWindow.close();
        }
        
        function closeModal() {
            window.location.href = 'my_appointments.php';
        }
        document.getElementById('successModal').addEventListener('click', function(e) {
            if (e.target === this) {
            }
        });
    </script>
    <?php endif; ?>
</body>
</html>