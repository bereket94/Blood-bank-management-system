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
$full_name = $donor['first_name'] . ' ' . $donor['last_name'];

$appointments = mysqli_query($conn, "SELECT COUNT(*) as count FROM appointments WHERE donor_id='$donor_id'");
$app_count = mysqli_fetch_assoc($appointments)['count'];

$success_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM appointments WHERE donor_id='$donor_id' AND status='success'"))['count'];
$pending_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM appointments WHERE donor_id='$donor_id' AND status='pending'"))['count'];

$last_donation = mysqli_fetch_assoc(mysqli_query($conn, "SELECT appointment_date FROM appointments WHERE donor_id='$donor_id' AND status='success' ORDER BY appointment_date DESC LIMIT 1"));
$last_donation_date = $last_donation ? $last_donation['appointment_date'] : 'Never';

$pending_sql = "SELECT a.*, h.name as hospital_name 
                FROM appointments a 
                JOIN hospitals h ON a.hospital_id = h.id 
                WHERE a.donor_id='$donor_id' AND a.status='pending'";
$pending_result = mysqli_query($conn, $pending_sql);
$has_pending = mysqli_num_rows($pending_result) > 0;
$pending_appointment = mysqli_fetch_assoc($pending_result);

$last_donation_db = $donor['last_donation_date'];
$can_donate = true;
$days_remaining = 0;
$days_since = 0;

if (!$has_pending) {
    if ($last_donation_db && $last_donation_db != '0000-00-00' && $last_donation_db != NULL) {
        $last_date = new DateTime($last_donation_db);
        $today = new DateTime();
        $days_since = $today->diff($last_date)->days;
        
        if ($days_since < 90) {
            $can_donate = false;
            $days_remaining = 90 - $days_since;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Donor Dashboard - Blood Bank</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
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
            <a href="check_eligibility.php" class="menu-item">
                <i class="fas fa-check-circle"></i>
                <span class="menu-text">Check Eligibility</span>
            </a>
            <a href="book_appointment.php" class="menu-item">
                <i class="fas fa-calendar-plus"></i>
                <span class="menu-text">Book Appointment</span>
            </a>
            <a href="my_appointments.php" class="menu-item">
                <i class="fas fa-list-alt"></i>
                <span class="menu-text">My Appointments</span>
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
        <div class="welcome-card">
            <h2>Welcome, <?php echo $full_name; ?>!</h2>
            <p>Thank you for being a blood donor. Your generosity saves lives!</p>
        </div>
        <?php if($has_pending): ?>
        <?php endif; ?>
        <div class="status-card <?php echo (!$has_pending && $can_donate) ? 'eligible' : 'not-eligible'; ?>">
            <?php if($has_pending): ?>
                <p>
                    <i class="fas fa-clock"></i> <strong>Pending Appointment</strong><br>
                    You have a pending appointment that needs to be completed.<br>
                    Please visit the hospital on your scheduled date.
                </p>
            <?php elseif($can_donate): ?>
                <p>
                    <i class="fas fa-check-circle"></i> <strong>You are eligible to donate!</strong><br>
                    <?php if($last_donation_db): ?>
                        Your last donation was <span class="days-count"><?php echo $days_since; ?> days ago</span>.
                    <?php else: ?>
                        This will be your first donation. Thank you!
                    <?php endif; ?>
                </p>
            <?php else: ?>
                <p>
                    <i class="fas fa-clock"></i> <strong>90-Day Waiting Period</strong><br>
                    You need to wait <span class="days-count"><?php echo $days_remaining; ?> more days</span> before your next donation.<br>
                    Last donation: <?php echo date('F d, Y', strtotime($last_donation_db)); ?>
                </p>
            <?php endif; ?>
        </div>

        <div class="stats">
            <div class="stat-box">
                <h3><?php echo $donor['blood_group']; ?></h3>
                <p>Blood Group</p>
            </div>
            <div class="stat-box">
                <h3><?php echo $app_count; ?></h3>
                <p>Total Appointments</p>
            </div>
            <div class="stat-box green">
                <h3><?php echo $success_count; ?></h3>
                <p>Successful Donations</p>
            </div>
            <div class="stat-box orange">
                <h3><?php echo $pending_count; ?></h3>
                <p>Pending</p>
            </div>
        </div>
        <div class="donor-info">
            <h3><i class="fas fa-user"></i> My Information</h3>
            <table class="info-table">
                <tr>
                    <th>Full Name</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Age</th>
                    <th> Weight</th>
                    <th>Blood Group</th>
                <tr>
                    <td><?php echo $full_name; ?></td>
                    <td><?php echo $donor['username']; ?></td>
                    <td><?php echo $donor['email']; ?></td>
                    <td><?php echo $donor['phone']; ?></td>
                    <td><?php echo $donor['age']; ?> yrs</span>
                    <td><?php echo $donor['weight']; ?> kg</span>
                    <td><span class="blood-badge"><?php echo $donor['blood_group']; ?></span></span>
                </tr>
                <tr>
                    <th> Total Donations</th>
                    <th> Last Donation</th>
                    <th> Medical Conditions</th>
                    <th> Registered On</th>
                    <th> Account Status</th>
                    <th></th>
                    <th></th>
                </tr>
                <tr>
                    <td><?php echo $donor['total_donations']; ?> times</span>
                    <td><?php echo $last_donation_date; ?></span>
                    <td><?php echo $donor['diseases'] ?: 'None'; ?></span>
                    <td><?php echo date('F d, Y', strtotime($donor['created_at'])); ?></span>
                    <td><?php echo ($donor['is_active'] == 1) ? '<span class="status-badge"> Active</span>' : '<span class="status-badge" style="background:#dc3545;">Inactive</span>'; ?></span>
                    <td></td>
                    <td></td>
                </tr>
            </table>
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