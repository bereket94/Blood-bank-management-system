<?php
session_start();
include '../config/db_connect.php';

if ($_SESSION['role'] != 'admin') {
    header("Location: ../index.php");
    exit();
}
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    mysqli_query($conn, "DELETE FROM donors WHERE id='$id'");
    mysqli_query($conn, "INSERT INTO system_logs (user_id, user_type, action, details) 
                         VALUES ('{$_SESSION['user_id']}', 'admin', 'delete_donor', 'Deleted donor ID: $id')");
    echo "<script>alert('Donor deleted successfully!'); window.location='manage_donors.php';</script>";
}
if (isset($_POST['update_donor'])) {
    $id = $_POST['donor_id'];
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $age = $_POST['age'];
    $weight = $_POST['weight'];
    $blood_group = $_POST['blood_group'];
    $diseases = $_POST['diseases'];
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $update_sql = "UPDATE donors SET 
                   first_name='$first_name', 
                   last_name='$last_name', 
                   email='$email', 
                   phone='$phone', 
                   age='$age', 
                   weight='$weight', 
                   blood_group='$blood_group', 
                   diseases='$diseases',
                   is_active='$is_active'
                   WHERE id='$id'";
    if (mysqli_query($conn, $update_sql)) {
        mysqli_query($conn, "INSERT INTO system_logs (user_id, user_type, action, details) 
                             VALUES ('{$_SESSION['user_id']}', 'admin', 'update_donor', 'Updated donor ID: $id')");
        echo "<script>alert('Donor updated successfully!'); window.location='manage_donors.php';</script>";
    } else {
        echo "<script>alert('Update failed: " . mysqli_error($conn) . "');</script>";
    }
}
$search = isset($_GET['search']) ? $_GET['search'] : '';
if ($search) {
    $sql = "SELECT *, CONCAT(first_name, ' ', last_name) as full_name 
            FROM donors 
            WHERE id LIKE '%$search%' 
            OR first_name LIKE '%$search%' 
            OR last_name LIKE '%$search%' 
            OR CONCAT(first_name, ' ', last_name) LIKE '%$search%'
            OR email LIKE '%$search%' 
            OR blood_group LIKE '%$search%'";
} else {
    $sql = "SELECT *, CONCAT(first_name, ' ', last_name) as full_name 
            FROM donors 
            ORDER BY created_at DESC";
}
$donors = mysqli_query($conn, $sql);
$edit_donor = null;
if (isset($_GET['edit_id'])) {
    $edit_id = $_GET['edit_id'];
    $edit_query = mysqli_query($conn, "SELECT * FROM donors WHERE id='$edit_id'");
    $edit_donor = mysqli_fetch_assoc($edit_query);
}
if (!$donors) {
    die("Query failed: " . mysqli_error($conn));
}
?>
<!DOCTYPE html>
<html>

<head>
    <title>Manage Donors - Admin</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .modal {
            display:
                <?php echo isset($edit_donor) ? 'flex' : 'none'; ?>
            ;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
        .header {
            background: #570f0f;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .header h1 {
            margin: 0;
            color: white;
        }
        .header a {
            color: white;
            background: rgba(255,255,255,0.2);
            padding: 8px 15px;
            border-radius: 5px;
            text-decoration: none;
        }
    </style>
</head>
s
<body>
    <div class="container">
        <div class="header" >
            <h1>🩸 Manage Blood Donors</h1>
            <a href="dashboard.php">Back to Dashboard</a>
        </div>
        <div class="search-box">
            <form method="GET">
                <input type="text" name="search" placeholder="Search by ID, Name, Email, or Blood Group..."
                    value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit"><i class="fas fa-search"></i> Search</button>
                <?php if ($search): ?>
                    <a href="manage_donors.php" class="btn-small">Clear</a>
                <?php endif; ?>
            </form>
        </div>
        <div style="font-size:25px;display:flex; flex-direction:row;gap:500px">
            <h2 style="color:red;margin-top: 11px;">All Registered Donors </h2>
            <p style="justify-content:flex-end;color :green;margin-top: 15px; "><strong>Total Donors:</strong>
                <?php echo mysqli_num_rows($donors); ?></p>
        </div>
        <?php if (mysqli_num_rows($donors) == 0): ?>
            <p style="text-align: center; padding: 40px; background: white; border-radius: 10px;">No donors found.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Age</th>
                        <th>Blood Group</th>
                        <th>Total Donations</th>
                        <th>Registered</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($donor = mysqli_fetch_assoc($donors)): ?>
                        <tr>
                            <td><?php echo $donor['id']; ?></td>
                            <td><strong><?php echo $donor['full_name']; ?></strong></td>
                            <td><?php echo $donor['email']; ?></td>
                            <td><?php echo $donor['phone']; ?></td>
                            <td><?php echo $donor['age']; ?></td>
                            <td><span class="blood-badge"><?php echo $donor['blood_group']; ?></span></td>
                            <td><?php echo $donor['total_donations']; ?></td>
                            <td><?php echo date('Y-m-d', strtotime($donor['created_at'])); ?></td>
                            <td class="action-links">
                                <a href="?edit_id=<?php echo $donor['id']; ?>" style="color: #28a745;">Edit</a> |
                                <a href="view_donor.php?id=<?php echo $donor['id']; ?>" style="color: #17a2b8;"> View</a> |
                                <a href="?delete=<?php echo $donor['id']; ?>" onclick="return confirm('Delete this donor?')"
                                    style="color: #dc3545;">Delete</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
            <p style="margin-top: 20px;"><strong>Total Donors:</strong> <?php echo mysqli_num_rows($donors); ?></p>
        <?php endif; ?>
    </div>
    <?php if ($edit_donor): ?>
        <div class="modal" style="display: flex;">
            <div class="modal-content">
                <div class="modal-header">
                    <h3>Edit Donor: <?php echo $edit_donor['first_name'] . ' ' . $edit_donor['last_name']; ?></h3>
                    <a href="manage_donors.php" class="close">&times;</a>
                </div>
                <form method="POST">
                    <input type="hidden" name="donor_id" value="<?php echo $edit_donor['id']; ?>">
                    <div class="modal-body">
                        <label>First Name</label>
                        <input type="text" name="first_name" value="<?php echo $edit_donor['first_name']; ?>" required>

                        <label>Last Name</label>
                        <input type="text" name="last_name" value="<?php echo $edit_donor['last_name']; ?>" required>
                        <label>Email</label>
                        <input type="email" name="email" value="<?php echo $edit_donor['email']; ?>" required>

                        <label>Phone</label>
                        <input type="text" name="phone" value="<?php echo $edit_donor['phone']; ?>" required>

                        <label>Age</label>
                        <input type="number" name="age" value="<?php echo $edit_donor['age']; ?>" required>

                        <label>Weight (kg)</label>
                        <input type="number" step="0.01" name="weight" value="<?php echo $edit_donor['weight']; ?>"
                            required>
                        <label>Blood Group</label>
                        <select name="blood_group" required>
                            <option value="A+" <?php echo ($edit_donor['blood_group'] == 'A+') ? 'selected' : ''; ?>>A+
                            </option>
                            <option value="A-" <?php echo ($edit_donor['blood_group'] == 'A-') ? 'selected' : ''; ?>>A-
                            </option>
                            <option value="B+" <?php echo ($edit_donor['blood_group'] == 'B+') ? 'selected' : ''; ?>>B+
                            </option>
                            <option value="B-" <?php echo ($edit_donor['blood_group'] == 'B-') ? 'selected' : ''; ?>>B-
                            </option>
                            <option value="O+" <?php echo ($edit_donor['blood_group'] == 'O+') ? 'selected' : ''; ?>>O+
                            </option>
                            <option value="O-" <?php echo ($edit_donor['blood_group'] == 'O-') ? 'selected' : ''; ?>>O-
                            </option>
                            <option value="AB+" <?php echo ($edit_donor['blood_group'] == 'AB+') ? 'selected' : ''; ?>>AB+
                            </option>
                            <option value="AB-" <?php echo ($edit_donor['blood_group'] == 'AB-') ? 'selected' : ''; ?>>AB-
                            </option>
                        </select>
                        <label>Diseases</label>
                        <textarea name="diseases" rows="2"><?php echo $edit_donor['diseases']; ?></textarea>
                        <div class="checkbox-group">
                            <input type="checkbox" name="is_active" value="1" <?php echo ($edit_donor['is_active'] == 1) ? 'checked' : ''; ?>>
                            <label>Active Donor</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <a href="manage_donors.php" class="btn-cancel"
                            style="padding: 10px 20px; text-decoration: none; border-radius: 5px;">Cancel</a>
                        <button type="submit" name="update_donor" class="btn-save">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>
</body>

</html>