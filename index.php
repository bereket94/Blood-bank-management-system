<?php
session_start();
include 'config/db_connect.php';
$inventory = mysqli_query($conn, "SELECT * FROM blood_inventory WHERE quantity_units > 0");
?>
<!DOCTYPE html>
<html>

<head>
    <title>Blood Bank Management System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>

<body background-image: none !important; background: #f5f5f5 !important;>
    <div class="container">
        <div class="header">
            <h1 class="glow-text">🩸 Blood Bank Management System</h1>
            <p class="sliding-text">Saving Lives Through Blood Donation</p>
        </div>
        <div class="hero">
            <h2>🏥 Welcome to Blood Bank System </h2>
            <p class="static-text">Your blood donation can save lives. Join us in this noble cause</p>
        </div>
        <div class="requirements">
            <h3>📋 Blood Donation Requirements</h3>
            <ol type="I">
                <li>Age between 18-65 years</li>
                <li>Weight at least 50 kg (110 lbs)</li>
                <li>No chronic diseases (HIV, Hepatitis, etc.)</li>
                <li>Should be healthy on donation day</li>
                <li>Minimum 90 days gap between donations</li>
            </ol>
        </div>
        <div class="role-selection">
            <div class="role-card" onclick="location.href='donor/login.php'">
                <div class="role-icon">🩸</div>
                <h3>Donor</h3>
                <p>Register as a blood donor or login to book appointments</p>
            </div>

            <div class="role-card" onclick="location.href='nurse/login.php'">
                <div class="role-icon">👩‍⚕️</div>
                <h3>Nurse</h3>
                <p>Verify donors and update donation status</p>
            </div>

            <div class="role-card" onclick="location.href='hospital/login.php'">
                <div class="role-icon">🏥</div>
                <h3>Hospital Admin</h3>
                <p>Manage nurses and view donation records</p>
            </div>

            <div class="role-card" onclick="location.href='admin/login.php'">
                <div class="role-icon">👨‍💼</div>
                <h3>System Admin</h3>
                <p>Full system control, manage all users</p>
            </div>
        </div>
        <div class="inventory-section">
            <h3>Current Blood Stock</h3>
            <div class="inventory-grid">
                <?php while ($blood = mysqli_fetch_assoc($inventory)): ?>
                    <div class="blood-card">
                        <div class="blood-type"><?php echo $blood['blood_group']; ?></div>
                        <div class="blood-units"><?php echo $blood['quantity_units']; ?> Units</div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>
    <footer>
        <div class="footer-container">
            <div class="footer-title">
                <h3>🩸 Blood Donation Appointment Management System</h3>
                <p>Department of Computer Science</p>
                <p>Wolkite University - College of Computing and Informatics</p>
            </div>

            <div class="team-section">
                <div>
                    <strong>Team Members</strong><br><br>
                    Filseta Tadesse<br>
                    Mihiret Getu<br>
                    Samrawit Lema
                </div>
                <div>
                    <strong>Team Members</strong><br><br>
                    Bersufikad Kalsa<br>
                    Bereket Asfaw<br>
                    Mesud Nuredin
                </div>
            </div>

            <div class="copyright">
                <p>© <?php echo date("Y"); ?> All Rights Reserved | Blood Donation Management System</p>
            </div>
        </div>
    </footer>

    <script>
        console.log("Blood Donation Management System - Footer Loaded");
    </script>
</body>

</html>