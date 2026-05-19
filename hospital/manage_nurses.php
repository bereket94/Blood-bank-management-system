<?php
session_start();
include '../config/db_connect.php';

if($_SESSION['role'] != 'hospital') {
    header("Location: ../index.php");
    exit();
}

$hospital_id = $_SESSION['user_id'];
$success = '';
$error = '';
$name = '';
$email = '';
$phone = '';

// Field-specific errors
$name_error = '';
$email_error = '';
$password_error = '';
$confirm_error = '';
$phone_error = '';

// Search variable
$search = '';
if(isset($_GET['search'])) {
    $search = mysqli_real_escape_string($conn, $_GET['search']);
}

// Edit nurse data
$edit_nurse = null;
if(isset($_GET['edit'])) {
    $edit_id = $_GET['edit'];
    $edit_query = mysqli_query($conn, "SELECT * FROM nurses WHERE id='$edit_id' AND hospital_id='$hospital_id'");
    $edit_nurse = mysqli_fetch_assoc($edit_query);
}

// Show password (popup window)
if(isset($_GET['show_password'])) {
    $show_id = $_GET['show_password'];
    $pass_query = mysqli_query($conn, "SELECT name, password FROM nurses WHERE id='$show_id' AND hospital_id='$hospital_id'");
    $pass_data = mysqli_fetch_assoc($pass_query);
    
    if($pass_data) {
        echo "<!DOCTYPE html>
        <html>
        <head>
            <title>Password Details</title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    height: 100vh;
                    margin: 0;
                    background: rgba(0,0,0,0.5);
                }
                .popup {
                    background: white;
                    padding: 30px;
                    border-radius: 10px;
                    text-align: center;
                    box-shadow: 0 4px 20px rgba(0,0,0,0.3);
                    min-width: 300px;
                }
                .popup h3 {
                    color: #8B0000;
                    margin-bottom: 20px;
                }
                .popup p {
                    margin: 10px 0;
                    font-size: 16px;
                }
                .popup .password {
                    background: #f0f0f0;
                    padding: 10px;
                    border-radius: 5px;
                    font-size: 18px;
                    font-weight: bold;
                    margin: 15px 0;
                }
                .close-btn {
                    background: #8B0000;
                    color: white;
                    border: none;
                    padding: 10px 20px;
                    border-radius: 5px;
                    cursor: pointer;
                    margin-top: 10px;
                    text-decoration: none;
                    display: inline-block;
                }
                .close-btn:hover {
                    background: #a00000;
                }
            </style>
        </head>
        <body>
            <div class='popup'>
                <h3>👩‍⚕️ Nurse Password</h3>
                <p><strong>Name:</strong> " . htmlspecialchars($pass_data['name']) . "</p>
                <p><strong>Password:</strong></p>
                <div class='password'>" . htmlspecialchars($pass_data['password']) . "</div>
                <a href='manage_nurses.php' class='close-btn'>Close</a>
            </div>
        </body>
        </html>";
        exit();
    }
}

// Update nurse (including password if changed)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_nurse'])) {
    $nurse_id = $_POST['nurse_id'];
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $new_password = md5($_POST['new_password']);
    $confirm_password = md5($_POST['confirm_password']);
    
    $is_valid = true;
    
    // Name validation
    if (empty($name)) {
        $name_error = "Name is required";
        $is_valid = false;
    } elseif (!preg_match("/^[a-zA-Z\s]+$/", $name)) {
        $name_error = "Name can only contain letters and spaces";
        $is_valid = false;
    } elseif (strlen($name) < 2 || strlen($name) > 50) {
        $name_error = "Name must be between 2 and 50 characters";
        $is_valid = false;
    }

    if (empty($email)) {
        $email_error = "Email is required";
        $is_valid = false;
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $email_error = "Please enter a valid email address";
        $is_valid = false;
    }
    
    if (empty($phone)) {
        $phone_error = "Phone number is required";
        $is_valid = false;
    } elseif (!preg_match("/^[0-9]{9,10}$/", $phone)) {
        $phone_error = "Enter valid phone number (9-10 digits)";
        $is_valid = false;
    }
    if (!empty($new_password)) {
        if (strlen($new_password) < 6) {
            $password_error = "Password must be at least 6 characters";
            $is_valid = false;
        } elseif ($new_password !== $confirm_password) {
            $confirm_error = "Passwords do not match";
            $is_valid = false;
        }
    }
    if ($is_valid) {
        $check = mysqli_query($conn, "SELECT id FROM nurses WHERE email='$email' AND id != '$nurse_id'");
        if(mysqli_num_rows($check) > 0) {
            $email_error = "Email already exists!";
            $is_valid = false;
        }
    }
    if ($is_valid) {
        if (!empty($new_password)) {
            $update_sql = "UPDATE nurses SET name='$name', email='$email', phone='$phone', password='$new_password' WHERE id='$nurse_id' AND hospital_id='$hospital_id'";
        } else {
            $update_sql = "UPDATE nurses SET name='$name', email='$email', phone='$phone' WHERE id='$nurse_id' AND hospital_id='$hospital_id'";
        }
        if(mysqli_query($conn, $update_sql)) {
            $success = "Nurse updated successfully!";
            $edit_nurse = null;
            echo "<script>window.location='manage_nurses.php" . (!empty($search) ? "?search=" . urlencode($search) : "") . "';</script>";
            exit();
        } else {
            $error = "Failed to update nurse!";
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_nurse'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = md5($_POST['password']);
    $confirm_password =md5($_POST['confirm_password']);
    $phone = trim($_POST['phone']);
    
    $is_valid = true;
    if (empty($name)) {
        $name_error = "Name is required";
        $is_valid = false;
    } elseif (!preg_match("/^[a-zA-Z\s]+$/", $name)) {
        $name_error = "Name can only contain letters and spaces";
        $is_valid = false;
    } elseif (strlen($name) < 2 || strlen($name) > 50) {
        $name_error = "Name must be between 2 and 50 characters";
        $is_valid = false;
    }
    if (empty($email)) {
        $email_error = "Email is required";
        $is_valid = false;
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $email_error = "Please enter a valid email address";
        $is_valid = false;
    } elseif (strlen($email) > 100) {
        $email_error = "Email is too long";
        $is_valid = false;
    }
    
    if (empty($password)) {
        $password_error = "Password is required";
        $is_valid = false;
    } elseif (strlen($password) < 6) {
        $password_error = "Password must be at least 6 characters";
        $is_valid = false;
    }

    if (empty($confirm_password)) {
        $confirm_error = "Please confirm your password";
        $is_valid = false;
    } elseif ($password !== $confirm_password) {
        $confirm_error = "Passwords do not match";
        $is_valid = false;
    }
    
    if (empty($phone)) {
        $phone_error = "Phone number is required";
        $is_valid = false;
    } elseif (!preg_match("/^[0-9]{9,10}$/", $phone)) {
        $phone_error = "Enter valid phone number (9-10 digits)";
        $is_valid = false;
    }
    if ($is_valid) {
        $check = mysqli_query($conn, "SELECT id FROM nurses WHERE email='$email'");
        if(mysqli_num_rows($check) > 0) {
            $email_error = "Email already exists!";
            $is_valid = false;
        }
    }
    if ($is_valid) {
        $sql = "INSERT INTO nurses (hospital_id, name, email, password, phone) 
                VALUES ('$hospital_id', '$name', '$email', '$password', '$phone')";
        
        if(mysqli_query($conn, $sql)) {
            $success = "Nurse added successfully!";
            $name = $email = $phone = '';
            echo "<script>window.location='manage_nurses.php" . (!empty($search) ? "?search=" . urlencode($search) : "") . "';</script>";
            exit();
        } else {
            $error = "Failed to add nurse!";
        }
    }
}
if(isset($_GET['delete'])) {
    $id = $_GET['delete'];
    mysqli_query($conn, "DELETE FROM nurses WHERE id='$id' AND hospital_id='$hospital_id'");
    echo "<script>alert('Nurse deleted successfully!'); window.location='manage_nurses.php" . (!empty($search) ? "?search=" . urlencode($search) : "") . "';</script>";
    exit();
}
if(!empty($search)) {
    $nurses = mysqli_query($conn, "SELECT * FROM nurses WHERE hospital_id='$hospital_id' AND (name LIKE '%$search%' OR email LIKE '%$search%' OR phone LIKE '%$search%') ORDER BY created_at DESC");
} else {
    $nurses = mysqli_query($conn, "SELECT * FROM nurses WHERE hospital_id='$hospital_id' ORDER BY created_at DESC");
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Nurses - <?php echo $_SESSION['user_name']; ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .success-msg {
            color: green;
            background: #e6ffe6;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
        }
        .error-msg {
            color: red;
            background: #ffe6e6;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
        }
        .field-error {
            color: red;
            font-size: 12px;
            display: block;
            margin-top: -5px;
            margin-bottom: 8px;
        }
        .password-hint, .phone-hint {
            font-size: 12px;
            color: #666;
            margin-top: -5px;
            margin-bottom: 10px;
        }
               .password-wrapper {
            position: relative;
            width: 100%;
            margin: 5px 0;
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
            top: 0 !important;
            bottom: 0 !important;
            width: auto !important;
            height: auto !important;
            background: none !important;
            border: none !important;
            box-shadow: none !important;
            border-radius: 0 !important;
            cursor: pointer !important;
            color: #d84e4e !important;
            font-size: 18px !important;
            padding: 0 !important;
            margin: 0 !important;
            z-index: 10 !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
        }
        .password-toggle:hover {
            color: #000000 !important;
            background: none !important;
            box-shadow: none !important;
        }
        .input-error {
            border-color: #dc3545 !important;
            background-color: #fff3f3 !important;
        }
        .form-container {
            background: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .form-container input {
            width: 100%;
            padding: 12px;
            margin: 8px 0;
            border: 1px solid #ddd;
            border-radius: 8px;
        }
        .form-container button {
            width: 100%;
            padding: 12px;
            background: #8B0000;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            margin-top: 10px;
        }
        .form-container button:hover {
            background: #a00000;
        }
        .modal {
            display: <?php echo isset($edit_nurse) ? 'flex' : 'none'; ?>;
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
            background: #8B0000;
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
        table {
            width: 100%;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            margin-top: 15px;
        }
        th {
            background: #8B0000;
            color: white;
            padding: 12px;
            text-align: left;
        }
        td {
            padding: 10px;
            border-bottom: 1px solid #eee;
        }
        tr:hover td {
            background: #f9f9f9;
        }
        .action-links a {
            margin: 0 5px;
            text-decoration: none;
        }
        .btn-view {
            background: #17a2b8;
            color: white;
            padding: 5px 10px;
            font-size: 12px;
            border-radius: 4px;
            text-decoration: none;
        }
        .btn-view:hover {
            background: #138496;
        }
        .btn {
            display: inline-block;
            background: #6c757d;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        }
        .password-actions {
            text-align: center;
        }
        .search-bar {
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .search-bar input {
            flex: 1;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
        }
        .search-bar button {
            padding: 12px 25px;
            background: #8B0000;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
        }
        .search-bar button:hover {
            background: #a00000;
        }
        .clear-search {
            background: #6c757d;
            padding: 12px 25px;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            display: inline-block;
        }
        .clear-search:hover {
            background: #5a6268;
        }
        .search-info {
            background: #e7f3ff;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 15px;
            display: inline-block;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header" style="background: #8B0000; padding: 20px; border-radius: 10px; margin-bottom: 20px;">
            <h1>Manage Nurses</h1>
            <p><?php echo $_SESSION['user_name']; ?></p>
            <a href="dashboard.php" style="color: white; background: rgba(255,255,255,0.2); padding: 8px 15px; border-radius: 5px; text-decoration: none;">Back to Dashboard</a>
        </div>
        <div class="form-container" style="max-width:600px;">
            <h3>➕ Add New Nurse</h3>
            <?php if(isset($success) && $success): ?>
                <div class="success-msg"><?php echo $success; ?></div>
            <?php endif; ?>
            <?php if(isset($error) && $error): ?>
                <div class="error-msg"><?php echo $error; ?></div>
            <?php endif; ?>
            <form method="POST">
                <input type="text" name="name" id="name" placeholder="Full Name" value="<?php echo htmlspecialchars($name); ?>" 
                       onkeyup="validateName()" onblur="validateName()">
                <span id="name_error" class="field-error"></span>
                <input type="email" name="email" id="email" placeholder="Email Address" value="<?php echo htmlspecialchars($email); ?>" 
                       onkeyup="validateEmail()" onblur="validateEmail()">
                <span id="email_error" class="field-error"></span>
                <div class="password-wrapper">
                    <input type="password" name="password" id="password" placeholder="Password (min 6 characters)" required onkeyup="validatePassword()" onblur="validatePassword()">
                    <button type="button" class="password-toggle" onclick="togglePassword('password')">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
                <span id="password_error" class="error-message"></span>
                <div class="password-hint">Password must be at least 6 characters</div>
                
                <div class="password-wrapper">
                    <input type="password" name="confirm_password" id="confirm_password" placeholder="Confirm Password" required onkeyup="validateConfirmPassword()" onblur="validateConfirmPassword()">
                    <button type="button" class="password-toggle" onclick="togglePassword('confirm_password')">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
                <input type="tel" name="phone" id="phone" placeholder="Phone Number (e.g., 0912345678)" value="<?php echo htmlspecialchars($phone); ?>" 
                       onkeyup="validatePhone()" onblur="validatePhone()">
                <span id="phone_error" class="field-error"></span>
                <div class="phone-hint">Enter phone number (9-10 digits)</div>
                
                <button type="submit" name="add_nurse">Add Nurse</button>
            </form>
        </div>
        <div class="search-bar">
            <form method="GET" style="display: flex; gap: 10px; width: 100%;" id="searchForm">
                <input type="text" name="search" id="searchInput" placeholder="Search by Name, Email or Phone..." value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit">Search</button>
                <?php if(!empty($search)): ?>
                    <a href="manage_nurses.php" class="clear-search">Clear</a>
                <?php endif; ?>
            </form>
        </div>
        <?php if(isset($_GET['search']) && empty($_GET['search'])): ?>
            <div class="search-info" style="background: #fff3cd; color: #856404;">
                Please enter a name, email, or phone number to search!
            </div>
        <?php elseif(!empty($search)): ?>
            <div class="search-info">
                Showing results for: <strong><?php echo htmlspecialchars($search); ?></strong> <?php echo mysqli_num_rows($nurses); ?> found
            </div>
        <?php endif; ?>
        
        <h3>Current Nurses (<?php echo mysqli_num_rows($nurses); ?>)</h3>
        
        <?php if(mysqli_num_rows($nurses) == 0): ?>
            <p style="text-align:center; padding:20px; background:white; border-radius:10px">
                <?php if(!empty($search)): ?>
                    No nurses found matching "<strong><?php echo htmlspecialchars($search); ?></strong>"
                <?php else: ?>
                    No nurses added yet. Click "Add Nurse" to add your first nurse.
                <?php endif; ?>
            </p>
        <?php else: ?>
            <table>
                <thead>
                    <tr bgcolor="#8B0000" style="color:white">
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Registered On</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($nurse = mysqli_fetch_assoc($nurses)): ?>
                    <tr>
                        <td><?php echo $nurse['id']; ?></td>
                        <td><strong><?php echo htmlspecialchars($nurse['name']); ?></strong></td>
                        <td><?php echo htmlspecialchars($nurse['email']); ?></td>
                        <td><?php echo htmlspecialchars($nurse['phone']); ?></td>
                        <td><?php echo date('Y-m-d', strtotime($nurse['created_at'])); ?></td>
                        <td class="action-links">
                            <a href="?edit=<?php echo $nurse['id']; ?>&search=<?php echo urlencode($search); ?>" style="color: #28a745;">Edit</a> |
                            <a href="?delete=<?php echo $nurse['id']; ?>&search=<?php echo urlencode($search); ?>" onclick="return confirm('Are you sure you want to delete this nurse?')" style="color: #dc3545;">Delete</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php endif; ?>
        <div style="margin-top: 20px; text-align: center;">
            <a href="dashboard.php" class="btn">Back to Dashboard</a>
        </div>
    </div>
    <?php if($edit_nurse): ?>
    <div class="modal" style="display: flex;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Edit Nurse: <?php echo htmlspecialchars($edit_nurse['name']); ?></h3>
                <a href="manage_nurses.php<?php echo !empty($search) ? '?search=' . urlencode($search) : ''; ?>" class="close">&times;</a>
            </div>
            <form method="POST">
                <input type="hidden" name="nurse_id" value="<?php echo $edit_nurse['id']; ?>">
                <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                <div class="modal-body">
                    <label>Full Name</label>
                    <input type="text" name="name" value="<?php echo htmlspecialchars($edit_nurse['name']); ?>" required>
                    <label>Email Address</label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($edit_nurse['email']); ?>" required>   
                    <label>Phone Number</label>
                    <input type="tel" name="phone" value="<?php echo htmlspecialchars($edit_nurse['phone']); ?>" required>
                    <div class="password-wrapper">
                        <input type="password" name="new_password" id="edit_password" placeholder="New Password (min 6 characters)" onkeyup="validateEditPassword()" onblur="validateEditPassword()">
                        <button type="button" class="password-toggle" onclick="togglePassword('edit_password')">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <span id="edit_password_error" class="error-message"></span>
                    <div class="password-hint">Password must be at least 6 characters</div>

                    <div class="password-wrapper">
                        <input type="password" name="confirm_password" id="edit_confirm_password" placeholder="Confirm New Password" onkeyup="validateEditConfirmPassword()" onblur="validateEditConfirmPassword()">
                        <button type="button" class="password-toggle" onclick="togglePassword('edit_confirm_password')">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                <div class="modal-footer">
                    <a href="manage_nurses.php<?php echo !empty($search) ? '?search=' . urlencode($search) : ''; ?>" class="btn-cancel" style="padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block;">Cancel</a>
                    <button type="submit" name="update_nurse" class="btn-save">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>
    
    <script>
        function validateName() {
            var name = document.getElementById('name').value.trim();
            var errorSpan = document.getElementById('name_error');
            if (name === '') {
                errorSpan.innerHTML = 'Name is required';
                errorSpan.style.color = 'red';
                document.getElementById('name').classList.add('input-error');
                return false;
            } else if (!/^[a-zA-Z\s]+$/.test(name)) {
                errorSpan.innerHTML = 'Name can only contain letters and spaces';
                errorSpan.style.color = 'red';
                document.getElementById('name').classList.add('input-error');
                return false;
            } else if (name.length < 2 || name.length > 50) {
                errorSpan.innerHTML = 'Name must be between 2 and 50 characters';
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
                errorSpan.innerHTML = 'Please enter a valid email address';
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
                function validateEditPassword() {
        var password = document.getElementById('edit_password').value;
        var errorSpan = document.getElementById('edit_password_error');
        
        if (password.length > 0 && password.length < 6) {
            errorSpan.innerHTML = 'Password must be at least 6 characters';
            errorSpan.style.color = 'red';
            document.getElementById('edit_password').classList.add('input-error');
            return false;
        } else if (password.length >= 6) {
            errorSpan.innerHTML = 'Strong';
            errorSpan.style.color = 'green';
            document.getElementById('edit_password').classList.remove('input-error');
            return true;
        } else {
            errorSpan.innerHTML = '';
            return true;
        }
    }

    function validateEditConfirmPassword() {
        var password = document.getElementById('edit_password').value;
        var confirm = document.getElementById('edit_confirm_password').value;
        var errorSpan = document.getElementById('edit_confirm_error');
        
        if (confirm.length > 0 && password !== confirm) {
            errorSpan.innerHTML = 'Passwords do not match';
            errorSpan.style.color = 'red';
            document.getElementById('edit_confirm_password').classList.add('input-error');
            return false;
        } else if (confirm.length > 0 && password === confirm) {
            errorSpan.innerHTML = 'Matches';
            errorSpan.style.color = 'green';
            document.getElementById('edit_confirm_password').classList.remove('input-error');
            return true;
        } else {
            errorSpan.innerHTML = '';
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
            } else if (!/^[0-9]{9,10}$/.test(phone)) {
                errorSpan.innerHTML = 'Phone number must contain only numbers 9-10 digits';
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
        
        document.getElementById('searchForm').addEventListener('submit', function(e) {
            var searchInput = document.getElementById('searchInput');
            if (searchInput.value.trim() === '') {
                e.preventDefault();
                var existingMsg = document.querySelector('.empty-search-error');
                if(existingMsg) existingMsg.remove();
                var errorMsg = document.createElement('div');
                errorMsg.className = 'search-info empty-search-error';
                errorMsg.style.background = '#fff3cd';
                errorMsg.style.color = '#856404';
                errorMsg.style.marginBottom = '15px';
                errorMsg.innerHTML = 'Please enter a name, email, or phone number to search!';
                this.parentNode.parentNode.insertBefore(errorMsg, this.parentNode.nextSibling);
                setTimeout(function() {
                    errorMsg.remove();
                }, 3000);
            }
        });
        function validateForm() {
            var isValid = true;
            isValid = validateName() && isValid;
            isValid = validateEmail() && isValid;
            isValid = validatePassword() && isValid;
            isValid = validateConfirmPassword() && isValid;
            isValid = validatePhone() && isValid;
            if (!isValid) {
                alert('Please fix all errors before submitting.');
            }
            return isValid;
        }
        document.querySelector('form').addEventListener('submit', function(e) {
            if (!validateForm()) {
                e.preventDefault();
            }
        });
    </script>
</body>
</html>