<?php
session_start();
include '../config/db_connect.php';

if($_SESSION['role'] != 'admin') {
    header("Location: ../index.php");
}

$edit_hospital = null;
if(isset($_GET['edit'])) {
    $edit_id = $_GET['edit'];
    $edit_query = mysqli_query($conn, "SELECT * FROM hospitals WHERE id='$edit_id'");
    $edit_hospital = mysqli_fetch_assoc($edit_query);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_hospital'])) {
    $hospital_id = $_POST['hospital_id'];
    $name = $_POST['name'];
    $email = $_POST['email'];
    $location = $_POST['location'];
    $phone = $_POST['phone'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (!empty($new_password)) {
        if ($new_password === $confirm_password) {
            $hashed_password = md5($new_password);
            $sql = "UPDATE hospitals SET name='$name', email='$email', password='$hashed_password', location='$location', phone='$phone' WHERE id='$hospital_id'";
        } else {
            echo "<script>alert('Passwords do not match!');</script>";
            $sql = "UPDATE hospitals SET name='$name', email='$email', location='$location', phone='$phone' WHERE id='$hospital_id'";
        }
    } else {
        $sql = "UPDATE hospitals SET name='$name', email='$email', location='$location', phone='$phone' WHERE id='$hospital_id'";
    }
    
    if(mysqli_query($conn, $sql)) {
        mysqli_query($conn, "INSERT INTO system_logs (user_id, user_type, action, details) 
                             VALUES ('{$_SESSION['user_id']}', 'admin', 'update_hospital', 'Updated hospital: $name')");
        echo "<script>alert('Hospital updated successfully!'); window.location='manage_hospitals.php';</script>";
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_hospital'])) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = md5($_POST['password']);
    $location = $_POST['location'];
    $phone = $_POST['phone'];
    
    $sql = "INSERT INTO hospitals (name, email, password, location, phone) 
            VALUES ('$name', '$email', '$password', '$location', '$phone')";
    
    if(mysqli_query($conn, $sql)) {
        mysqli_query($conn, "INSERT INTO system_logs (user_id, user_type, action, details) 
                             VALUES ('{$_SESSION['user_id']}', 'admin', 'add_hospital', 'Added hospital: $name')");
        echo "<script>alert('Hospital added successfully!');</script>";
    }
}

// Delete Hospital
if(isset($_GET['delete'])) {
    $id = $_GET['delete'];
    mysqli_query($conn, "DELETE FROM hospitals WHERE id='$id'");
    echo "<script>alert('Hospital deleted!'); window.location='manage_hospitals.php';</script>";
}

$hospitals = mysqli_query($conn, "SELECT * FROM hospitals ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Hospitals - Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .password-wrapper {
            position: relative;
            width: 100%;
        }
        .password-wrapper input {
            width: 100%;
            padding: 12px;
            padding-right: 45px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            box-sizing: border-box;
        }
        .password-toggle {
            position: absolute !important;
            right: 15px !important;
            top: 50% !important;
            transform: translateY(-50%) !important;
            width: auto !important;
            height: auto !important;
            background: none !important;
            border: none !important;
            cursor: pointer !important;
            color: #1a1a2e !important;
            font-size: 18px !important;
            padding: 0 !important;
            margin: 0 !important;
        }
        .password-toggle:hover {
            color: #e74c3c !important;
        }
        .field-error {
            color: red;
            font-size: 12px;
            display: block;
            margin-top: -5px;
            margin-bottom: 8px;
        }
        .input-error {
            border-color: #dc3545 !important;
            background-color: #fff3f3 !important;
        }
        .success-text {
            color: green;
            font-size: 12px;
            display: block;
            margin-top: -5px;
            margin-bottom: 8px;
        }
        .modal {
            display: <?php echo isset($edit_hospital) ? 'flex' : 'none'; ?>;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
        .modal-content {
            background: white;
            width: 500px;
            max-width: 90%;
            border-radius: 10px;
            overflow: hidden;
            animation: fadeIn 0.3s ease;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .modal-header {
            background: #460303;
            color: white;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .modal-header h3 {
            margin: 0;
        }
        .modal-header .close {
            color: white;
            font-size: 24px;
            cursor: pointer;
            text-decoration: none;
        }
        .modal-body {
            padding: 20px;
        }
        .modal-body input {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .modal-body label {
            font-weight: bold;
            margin-bottom: 5px;
            display: block;
        }
        .modal-footer {
            padding: 15px 20px;
            background: #f8f9fa;
            text-align: right;
            border-top: 1px solid #eee;
        }
        .btn-save {
            background: #28a745;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
        }
        .btn-cancel {
            background: #6c757d;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            margin-right: 10px;
        }
        .btn-edit {
            color: #28a745;
            text-decoration: none;
            margin-right: 10px;
        }
        .btn-delete {
            color: red;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header" style="background: #660404;">
            <h1>Manage Hospitals</h1>
            <a href="dashboard.php" class="logout">Back to Dashboard</a>
        </div>
        
        <div class="form-container" style="max-width:100%; margin-bottom:30px">
            <h3>Add New Hospital</h3>
            <form method="POST" id="hospitalForm">
                <div style="display:grid; grid-template-columns:repeat(2,1fr); gap:15px">
                    <div>
                        <input type="text" name="name" id="name" placeholder="Hospital Name" required>
                        <span id="name_error" class="field-error"></span>
                    </div>
                    <div>
                        <input type="email" name="email" id="email" placeholder="Email" required>
                        <span id="email_error" class="field-error"></span>
                    </div>
                    <div>
                        <div class="password-wrapper">
                            <input type="password" name="password" id="password" placeholder="Password" required>
                            <button type="button" class="password-toggle" onclick="togglePassword('password')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <span id="password_error" class="field-error"></span>
                    </div>
                    <div>
                        <div class="password-wrapper">
                            <input type="password" name="confirm_password" id="confirm_password" placeholder="Confirm Password" required>
                            <button type="button" class="password-toggle" onclick="togglePassword('confirm_password')">
                                <i class="fas fa-eye"></i>
                            </button>
                        </div>
                        <span id="confirm_error" class="field-error"></span>
                    </div>
                    <div>
                        <input type="text" name="location" id="location" placeholder="Location" required>
                        <span id="location_error" class="field-error"></span>
                    </div>
                    <div>
                        <input type="text" name="phone" id="phone" placeholder="Phone Number" required>
                        <span id="phone_error" class="field-error"></span>
                    </div>
                </div>
                <button type="submit" name="add_hospital">Add Hospital</button>
            </form>
        </div>
        <h3>Registered Hospitals</h3>
        <table border="1" cellpadding="10" cellspacing="0" width="100%">
            <tr bgcolor="#1a1a2e" style="color:white">
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Location</th>
                <th>Phone</th>
                <th>Action</th>
            </tr>
            <?php while($hospital = mysqli_fetch_assoc($hospitals)): ?>
            <tr>
                <td><?php echo $hospital['id']; ?></td>
                <td><strong><?php echo $hospital['name']; ?></strong></td>
                <td><?php echo $hospital['email']; ?></td>
                <td><?php echo $hospital['location']; ?></td>
                <td><?php echo $hospital['phone']; ?></td>
                <td>
                    <a href="?edit=<?php echo $hospital['id']; ?>" class="btn-edit">Edit</a>
                    <a href="?delete=<?php echo $hospital['id']; ?>" onclick="return confirm('Delete this hospital?')" class="btn-delete">Delete</a>
                </td>
            </tr>
            <?php endwhile; ?>
        </table>
    </div>
    
    <!-- Edit Hospital Modal -->
    <?php if($edit_hospital): ?>
    <div class="modal" style="display: flex;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit Hospital: <?php echo htmlspecialchars($edit_hospital['name']); ?></h3>
                <a href="manage_hospitals.php" class="close">&times;</a>
            </div>
            <form method="POST">
                <input type="hidden" name="hospital_id" value="<?php echo $edit_hospital['id']; ?>">
                <div class="modal-body">
                    <label>Hospital Name</label>
                    <input type="text" name="name" value="<?php echo htmlspecialchars($edit_hospital['name']); ?>" required>
                    <label>Email Address</label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($edit_hospital['email']); ?>" required>
                    <label>Location</label>
                    <input type="text" name="location" value="<?php echo htmlspecialchars($edit_hospital['location']); ?>" required>
                    <label>Phone Number</label>
                    <input type="text" name="phone" value="<?php echo htmlspecialchars($edit_hospital['phone']); ?>" required>
                    <hr style="margin: 15px 0;">
                    <label style="color: #17a2b8;">Reset Password (Optional)</label>
                    <div class="password-wrapper">
                        <input type="password" name="new_password" id="edit_password" placeholder="New Password (leave blank to keep current)">
                        <button type="button" class="password-toggle" onclick="togglePassword('edit_password')">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <div class="password-wrapper">
                        <input type="password" name="confirm_password" id="edit_confirm_password" placeholder="Confirm New Password">
                        <button type="button" class="password-toggle" onclick="togglePassword('edit_confirm_password')">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <div class="password-hint" style="font-size:12px; color:#666; margin-top:5px;">Leave blank to keep current password. Enter new password to change it.</div>
                </div>
                <div class="modal-footer">
                    <a href="manage_hospitals.php" class="btn-cancel" style="padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block;">Cancel</a>
                    <button type="submit" name="update_hospital" class="btn-save">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>
    <script>
        function togglePassword(fieldId) {
            var field = document.getElementById(fieldId);
            var button = field.parentElement.querySelector('.password-toggle');
            var icon = button.querySelector('i');
            
            if (field.type === "password") {
                field.type = "text";
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                field.type = "password";
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
        function validateName() {
            var name = document.getElementById('name').value.trim();
            var errorSpan = document.getElementById('name_error');
            if (name === '') {
                errorSpan.innerHTML = 'Hospital name is required';
                errorSpan.style.color = 'red';
                document.getElementById('name').classList.add('input-error');
                return false;
            } else if (name.length < 2) {
                errorSpan.innerHTML = 'Hospital name must be at least 2 characters';
                errorSpan.style.color = 'red';
                document.getElementById('name').classList.add('input-error');
                return false;
            } else {
                errorSpan.innerHTML = 'Valid';
                errorSpan.style.color = 'green';
                document.getElementById('name').classList.remove('input-error');
                return true;
            }
        }
        function validateEmail() {
            var email = document.getElementById('email').value.trim();
            var errorSpan = document.getElementById('email_error');
            var emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (email === '') {
                errorSpan.innerHTML = 'Email is required';
                errorSpan.style.color = 'red';
                document.getElementById('email').classList.add('input-error');
                return false;
            } else if (!emailPattern.test(email)) {
                errorSpan.innerHTML = 'Enter a valid email address';
                errorSpan.style.color = 'red';
                document.getElementById('email').classList.add('input-error');
                return false;
            } else {
                errorSpan.innerHTML = 'Valid';
                errorSpan.style.color = 'green';
                document.getElementById('email').classList.remove('input-error');
                return true;
            }
        }
        function validatePassword() {
            var password = document.getElementById('password').value;
            var errorSpan = document.getElementById('password_error');
            if (password === '') {
                errorSpan.innerHTML = 'Password is required';
                errorSpan.style.color = 'red';
                document.getElementById('password').classList.add('input-error');
                return false;
            } else if (password.length < 6) {
                errorSpan.innerHTML = 'Password must be at least 6 characters';
                errorSpan.style.color = 'red';
                document.getElementById('password').classList.add('input-error');
                return false;
            } else {
                errorSpan.innerHTML = 'Strong';
                errorSpan.style.color = 'green';
                document.getElementById('password').classList.remove('input-error');
                return true;
            }
        }
        function validateConfirmPassword() {
            var password = document.getElementById('password').value;
            var confirm = document.getElementById('confirm_password').value;
            var errorSpan = document.getElementById('confirm_error');
            if (confirm === '') {
                errorSpan.innerHTML = 'Please confirm your password';
                errorSpan.style.color = 'red';
                document.getElementById('confirm_password').classList.add('input-error');
                return false;
            } else if (password !== confirm) {
                errorSpan.innerHTML = 'Passwords do not match';
                errorSpan.style.color = 'red';
                document.getElementById('confirm_password').classList.add('input-error');
                return false;
            } else {
                errorSpan.innerHTML = 'Matches';
                errorSpan.style.color = 'green';
                document.getElementById('confirm_password').classList.remove('input-error');
                return true;
            }
        }
        
        function validateLocation() {
            var location = document.getElementById('location').value.trim();
            var errorSpan = document.getElementById('location_error');
            if (location === '') {
                errorSpan.innerHTML = 'Location is required';
                errorSpan.style.color = 'red';
                document.getElementById('location').classList.add('input-error');
                return false;
            } else {
                errorSpan.innerHTML = 'Valid';
                errorSpan.style.color = 'green';
                document.getElementById('location').classList.remove('input-error');
                return true;
            }
        }
        
        function validatePhone() {
            var phone = document.getElementById('phone').value.trim();
            var errorSpan = document.getElementById('phone_error');
            if (phone === '') {
                errorSpan.innerHTML = 'Phone number is required';
                errorSpan.style.color = 'red';
                document.getElementById('phone').classList.add('input-error');
                return false;
            } else if (!/^[0-9]{9}$/.test(phone)) {
                errorSpan.innerHTML = 'Enter valid phone number(9 digits) and start with 9 ';
                errorSpan.style.color = 'red';
                document.getElementById('phone').classList.add('input-error');
                return false;
            } else {
                errorSpan.innerHTML = 'Valid';
                errorSpan.style.color = 'green';
                document.getElementById('phone').classList.remove('input-error');
                return true;
            }
        }
        
        function validateForm() {
            var isValid = true;
            isValid = validateName() && isValid;
            isValid = validateEmail() && isValid;
            isValid = validatePassword() && isValid;
            isValid = validateConfirmPassword() && isValid;
            isValid = validateLocation() && isValid;
            isValid = validatePhone() && isValid;
            if (!isValid) {
                alert('Please fix all errors before submitting.');
            }
            return isValid;
        }
        
        document.getElementById('hospitalForm').addEventListener('submit', function(e) {
            if (!validateForm()) {
                e.preventDefault();
            }
        });
        
        document.getElementById('name').addEventListener('keyup', validateName);
        document.getElementById('name').addEventListener('blur', validateName);
        document.getElementById('email').addEventListener('keyup', validateEmail);
        document.getElementById('email').addEventListener('blur', validateEmail);
        document.getElementById('password').addEventListener('keyup', validatePassword);
        document.getElementById('password').addEventListener('blur', validatePassword);
        document.getElementById('confirm_password').addEventListener('keyup', validateConfirmPassword);
        document.getElementById('confirm_password').addEventListener('blur', validateConfirmPassword);
        document.getElementById('location').addEventListener('keyup', validateLocation);
        document.getElementById('location').addEventListener('blur', validateLocation);
        document.getElementById('phone').addEventListener('keyup', validatePhone);
        document.getElementById('phone').addEventListener('blur', validatePhone);
    </script>
</body>
</html>