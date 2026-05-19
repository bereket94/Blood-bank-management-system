<?php
session_start();
include '../config/db_connect.php';

if($_SESSION['role'] != 'admin') {
    header("Location: ../index.php");
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    foreach($_POST['quantity'] as $blood_group => $quantity) {
        mysqli_query($conn, "UPDATE blood_inventory SET quantity_units='$quantity' WHERE blood_group='$blood_group'");
    }
    echo "<script>alert('Inventory updated successfully!');</script>";
}
$inventory = mysqli_query($conn, "SELECT * FROM blood_inventory ORDER BY blood_group");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Blood Inventory - Admin</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="container">
        <div class="header" style="background: #6e0f02;">
            <h1>Blood Inventory Management</h1>
            <a href="dashboard.php" class="logout">Back to Dashboard</a>
        </div>
        
        <div class="form-container" style="max-width:100%">
            <form method="POST">
                <table border="1" cellpadding="15" cellspacing="0" width="90%">
                    <tr bgcolor="#849784" style="color:white">
                        <th>Blood Group</th>
                        <th>Current Stock (Units)</th>
                        <th>Update Quantity</th>
                    </tr>
                    <?php while($blood = mysqli_fetch_assoc($inventory)): ?>
                    <tr>
                        <td><strong><?php echo $blood['blood_group']; ?></strong></td>
                        <td><?php echo $blood['quantity_units']; ?> units</td>
                        <td>
                            <input type="number" name="quantity[<?php echo $blood['blood_group']; ?>]" 
                                   value="<?php echo $blood['quantity_units']; ?>" style="width:100px">
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </table>
                <button type="submit" style="margin-top:20px">Update Inventory</button>
            </form>
        </div>
    </div>
</body>
</html>