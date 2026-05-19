<?php
session_start();
include '../config/db_connect.php';

if($_SESSION['role'] != 'admin') {
    header("Location: ../index.php");
    exit();
}
$donor_id = isset($_GET['id']) ? $_GET['id'] : 0;
$sql = "SELECT *, CONCAT(first_name, ' ', last_name) as full_name FROM donors WHERE id='$donor_id'";
$result = mysqli_query($conn, $sql);
$donor = mysqli_fetch_assoc($result);
$donations = mysqli_query($conn, "SELECT a.*, h.name as hospital_name 
    FROM appointments a 
    JOIN hospitals h ON a.hospital_id = h.id 
    WHERE a.donor_id='$donor_id' 
    ORDER BY a.appointment_date DESC");
if(!$donor) {
    header("Location: manage_donors.php");
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>View Donor  <?php echo $donor['full_name']; ?></title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="container">
        <div class="header" style="background: #5a0505; display: flex; justify-content: space-between; align-items: center;">
            <h1>🩸 Donor Details: <?php echo $donor['full_name']; ?></h1>
            <a href="manage_donors.php" style="color: white; background: rgba(255,255,255,0.2); padding: 8px 15px; border-radius: 5px; text-decoration: none;">Back to Donors</a>
        </div>
        <div class="donor-info">
            <h3 class="section-title">Personal Information</h3>
            <table class="info-table">
                <tr>
                    <th>Full Name</th>
                    <td><?php echo $donor['full_name']; ?></td>
                </tr>
                <tr>
                    <th>Username</th>
                    <td><?php echo $donor['username']; ?></td>
                </tr>
                <tr>
                    <th>Email Address</th>
                    <td><?php echo $donor['email']; ?></td>
                </tr>
                <tr>
                    <th>Phone Number</th>
                    <td><?php echo $donor['phone']; ?></td>
                </tr>
                <tr>
                    <th>Age</th>
                    <td><?php echo $donor['age']; ?> years</span>
                </tr>
                <tr>
                    <th>Weight</th>
                    <td><?php echo $donor['weight']; ?> kg</span>
                </tr>
                <tr>
                    <th>🩸 Blood Group</th>
                    <td><span class="blood-group"><?php echo $donor['blood_group']; ?></span></span>
                </tr>
                <tr>
                    <th> Total Donations</th>
                    <td><?php echo $donor['total_donations']; ?> times</span>
                </tr>
                <tr>
                    <th>Last Donation Date</th>
                    <td><?php echo $donor['last_donation_date'] ?? 'Never donated yet'; ?></span>
                </tr>
                <tr>
                    <th>Registered On</th>
                    <td><?php echo date('F d, Y', strtotime($donor['created_at'])); ?></span>
                </tr>
                <tr>
                    <th>Status</th>
                    <td>
                        <?php if($donor['is_active'] == 1): ?>
                            <span class="status-active">Active Donor</span>
                        <?php else: ?>
                            <span class="status-active" style="background:#dc3545;"> Inactive</span>
                        <?php endif; ?>
                        </span>
                </tr>
            </table>
        </div>
        <h3 class="section-title">Donation History</h3>
        <?php if(mysqli_num_rows($donations) == 0): ?>
            <p style="text-align:center; padding:30px; background:white; border-radius:10px">
                No donation records found for this donor.
            </p>
        <?php else: ?>
            <table class="donation-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Date</th>
                        <th>Hospital</th>
                        <th>Unique Code</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $count = 1; while($donation = mysqli_fetch_assoc($donations)): ?>
                    <tr>
                        <td><?php echo $count++; ?></td>
                        <td><?php echo date('Y-m-d', strtotime($donation['appointment_date'])); ?></td>
                        <td><?php echo $donation['hospital_name']; ?></td>
                        <td><code><?php echo $donation['unique_code']; ?></code></td>
                        <td>
                            <?php 
                            if($donation['status'] == 'success') {
                                echo "<span style='color:green'>Success</span>";
                            } elseif($donation['status'] == 'pending') {
                                echo "<span style='color:orange'>Pending</span>";
                            } else {
                                echo "<span style='color:red'>Failed</span>";
                            }
                            ?>
                         </span>
                    </span>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php endif; ?>
        
        <div style="margin-top: 20px; text-align: center;">
            <a href="manage_donors.php" class="btn">Back to Donors</a>
        </div>
    </div>
</body>
</html>