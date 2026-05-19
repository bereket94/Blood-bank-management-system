<?php
session_start();
include '../config/db_connect.php';

if($_SESSION['role'] != 'donor') {
    header("Location: ../index.php");
    exit();
}

$donor_id = $_SESSION['user_id'];
$sql = "SELECT * FROM donors WHERE id='$donor_id'";
$result = mysqli_query($conn, $sql);
$donor = mysqli_fetch_assoc($result);

$pending_sql = "SELECT a.*, h.name as hospital_name 
                FROM appointments a 
                JOIN hospitals h ON a.hospital_id = h.id 
                WHERE a.donor_id='$donor_id' AND a.status='pending'";
$pending_result = mysqli_query($conn, $pending_sql);
$has_pending = mysqli_num_rows($pending_result) > 0;
$pending_appointment = mysqli_fetch_assoc($pending_result);

$eligible = true;
$messages = [];
$can_book = true;
if($has_pending) {
    $eligible = false;
    $can_book = false;
    $messages[] = "You already have a PENDING appointment!";
    $messages[] = "Date: " . date('F d, Y', strtotime($pending_appointment['appointment_date']));
    $messages[] = "Time: " . date('h:i A', strtotime($pending_appointment['appointment_time']));
    $messages[] = "Hospital: " . $pending_appointment['hospital_name'];
    $messages[] = "Donation Code: " . $pending_appointment['unique_code'];
    $messages[] = "Please complete your pending appointment before booking a new one.";
}

if($donor['age'] < 18 || $donor['age'] > 65) {
    $eligible = false;
    $messages[] = "Age must be between 18-65 years. Your age: " . $donor['age'];
}

if($donor['weight'] < 50) {
    $eligible = false;
    $messages[] = "Minimum weight required is 50 kg. Your weight: " . $donor['weight'] . " kg";
}
$disease_clean = strtolower(trim($donor['diseases']));
if($disease_clean != 'none' && !empty($donor['diseases'])) {
    $eligible = false;
    $messages[] = "You have medical conditions: " . $donor['diseases'];
}

if(!$has_pending) {
    $last_donation = $donor['last_donation_date'];
    $days_since = 0;
    $days_remaining = 0;

    if($last_donation && $last_donation != '0000-00-00' && $last_donation != NULL) {
        $last_date = new DateTime($last_donation);
        $today = new DateTime();
        $days_since = $today->diff($last_date)->days;
        
        if($days_since < 90) {
            $eligible = false;
            $can_book = false;
            $days_remaining = 90 - $days_since;
            $messages[] = "You CANNOT donate yet! You need to wait {$days_remaining} more days.";
            $messages[] = "Your last donation was on: " . date('F d, Y', strtotime($last_donation));
            $messages[] = "Days since last donation: {$days_since} days";
            $messages[] = "Minimum 90 days gap required.";
        } else {
            $messages[] = "You have met the 90-day gap requirement. ({$days_since} days since last donation)";
        }
    } else {
        $messages[] = "No previous donation found. You are eligible to donate!";
    }
}

if($eligible && !$has_pending) {
    $messages[] = "You are eligible to donate blood.";
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Check Eligibility</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .eligibility-box {
            background: white;
            padding: 30px;
            border-radius: 10px;
            text-align: center;
            margin: 20px auto;
            max-width: 600px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .eligible {
            color: #28a745;
            font-size: 24px;
            margin-bottom: 20px;
        }
        .not-eligible {
            color: #dc3545;
            font-size: 24px;
            margin-bottom: 20px;
        }
        .message-list {
            text-align: left;
            margin: 20px 0;
        }
        .message-list p {
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }
        .btn {
            display: inline-block;
            background: #8B0000;
            color: white;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 5px;
            margin: 10px;
        }
        .btn:hover {
            background: #a00000;
        }
        .btn-back {
            display: inline-block;
            background: #6c757d;
            color: white;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 5px;
            margin: 10px;
        }
        .btn-back:hover {
            background: #5a6268;
        }
        .pending-info {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="eligibility-box">
            <?php if($eligible && !$has_pending): ?>
                <div class="eligible">ELIGIBLE TO DONATE</div>
            <?php else: ?>
                <div class="not-eligible">NOT ELIGIBLE TO DONATE</div>
            <?php endif; ?>
            
            <div class="message-list">
                <?php foreach($messages as $msg): ?>
                    <p><?php echo htmlspecialchars($msg); ?></p>
                <?php endforeach; ?>
            </div>
            
            <?php if($eligible && $can_book && !$has_pending): ?>
                <a href="book_appointment.php" class="btn">Book Appointment Now</a>
                <a href="dashboard.php" class="btn-back">Back to Dashboard</a>
            <?php else: ?>
                <a href="dashboard.php" class="btn-back">Back to Dashboard</a>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>