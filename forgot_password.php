<?php
session_start();
include '../config/db_connect.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    $errors = [];
    if (empty($email)) {
        $errors[] = "Email is required!";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address!";
    }
    
    if (empty($new_password)) {
        $errors[] = "Password is required!";
    } elseif (strlen($new_password) < 6) {
        $errors[] = "Password must be at least 6 characters long!";
    } elseif (preg_match('/\s/', $new_password)) {
        $errors[] = "Password cannot contain spaces!";
    }
    if (empty($confirm_password)) {
        $errors[] = "Please confirm your password!";
    } elseif ($new_password !== $confirm_password) {
        $errors[] = "Passwords do not match!";
    }
    if (empty($errors)) {
        $sql = "SELECT id, first_name, last_name, email FROM donors WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if (mysqli_num_rows($result) == 1) {
            $donor = mysqli_fetch_assoc($result);
            $md5_password = md5($new_password);
            $update_sql = "UPDATE donors SET password = ? WHERE id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("si", $md5_password, $donor['id']);
            
            if ($update_stmt->execute()) {
                $success = "Password reset successful! You can now login with your new password.";
            } else {
                $error = "Failed to reset password. Please try again.";
            }
        } else {
            $error = "Email not found in our records!";
        }
    } else {
        $error = implode("<br>", $errors);
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Forgot Password - Blood Bank</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .info-text {
            font-size: 14px;
            color: #666;
            margin-bottom: 20px;
            text-align: center;
        }
        .success-box {
            background: #d4edda;
            color: #155724;
            padding: 20px;
            border-radius: 10px;
            margin-top: 20px;
            text-align: center;
            border: 1px solid #c3e6cb;
        }
        .password-requirements {
            font-size: 12px;
            color: #666;
            margin-top: -8px;
            margin-bottom: 15px;
        }
        .error-message {
            color: red;
            font-size: 12px;
            margin-top: -8px;
            margin-bottom: 10px;
            display: block;
        }
        .input-error {
            border-color: #dc3545 !important;
            background-color: #fff3f3 !important;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="form-container">
            <div class="role-badge">Reset Password</div>
            <h2>Reset Your Password</h2>
            <div class="info-text">
                Enter your registered email and set a new password.
            </div>
            <?php if($error): ?>
                <div class="error"><?php echo $error; ?></div>
            <?php endif; ?>
            <?php if($success): ?>
                <div class="success-box">
                    <i class="fas fa-check-circle" style="font-size: 48px; color: #28a745; margin-bottom: 15px; display: block;"></i>
                    <?php echo $success; ?>
                </div>
                <a href="login.php" class="btn" style="display: inline-block; margin-top: 20px;">Go to Login</a>
            <?php else: ?>
                <form method="POST" id="resetForm">
                    <input type="email" name="email" id="email" placeholder="Enter your registered email" required>
                    <span id="email_error" class="error-message"></span>
                    <input type="password" name="new_password" id="new_password" placeholder="New Password (min 6 characters)" required>
                    <span id="password_error" class="error-message"></span>
                    <input type="password" name="confirm_password" id="confirm_password" placeholder="Confirm New Password" required>
                    <span id="confirm_error" class="error-message"></span>
                    <div class="password-requirements">Password must be at least 6 characters | No spaces allowed</div>
                    <button type="submit">Reset Password</button>
                </form>
                <p class="switch-role">
                    <a href="login.php">Back to Login</a>
                </p>
            <?php endif; ?>
        </div>
    </div>
    <script>
        function validateEmail() {
            var email = document.getElementById('email').value.trim();
            var errorSpan = document.getElementById('email_error');
            var emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            
            if (email === '') {
                errorSpan.innerHTML = 'Email is required';
                errorSpan.style.color = 'red';
                return false;
            } else if (!emailPattern.test(email)) {
                errorSpan.innerHTML = 'Please enter a valid email address';
                errorSpan.style.color = 'red';
                return false;
            } else {
                errorSpan.innerHTML = 'Valid email';
                errorSpan.style.color = 'green';
                return true;
            }
        }
        function validatePassword() {
            var password = document.getElementById('new_password').value;
            var errorSpan = document.getElementById('password_error');
            
            if (password === '') {
                errorSpan.innerHTML = 'Password is required';
                errorSpan.style.color = 'red';
                return false;
            } else if (password.length < 6) {
                errorSpan.innerHTML = 'Password must be at least 6 characters';
                errorSpan.style.color = 'red';
                return false;
            } else if (/\s/.test(password)) {
                errorSpan.innerHTML = 'Password cannot contain spaces';
                errorSpan.style.color = 'red';
                return false;
            } else {
                errorSpan.innerHTML = 'Strong password';
                errorSpan.style.color = 'green';
                return true;
            }
        }
        
        function validateConfirmPassword() {
            var password = document.getElementById('new_password').value;
            var confirm = document.getElementById('confirm_password').value;
            var errorSpan = document.getElementById('confirm_error');
            
            if (confirm === '') {
                errorSpan.innerHTML = ' Please confirm your password';
                errorSpan.style.color = 'red';
                return false;
            } else if (password !== confirm) {
                errorSpan.innerHTML = 'Passwords do not match';
                errorSpan.style.color = 'red';
                return false;
            } else {
                errorSpan.innerHTML = 'Passwords match';
                errorSpan.style.color = 'green';
                return true;
            }
        }
        
        document.getElementById('new_password').addEventListener('keypress', function(e) {
            if (e.key === ' ') {
                e.preventDefault();
                alert('Spaces are not allowed in password!');
            }
        });
        
        document.getElementById('email').addEventListener('blur', validateEmail);
        document.getElementById('email').addEventListener('keyup', validateEmail);
        document.getElementById('new_password').addEventListener('blur', validatePassword);
        document.getElementById('new_password').addEventListener('keyup', validatePassword);
        document.getElementById('confirm_password').addEventListener('blur', validateConfirmPassword);
        document.getElementById('confirm_password').addEventListener('keyup', validateConfirmPassword);

        document.getElementById('resetForm').addEventListener('submit', function(e) {
            var isEmailValid = validateEmail();
            var isPasswordValid = validatePassword();
            var isConfirmValid = validateConfirmPassword();
            
            if (!isEmailValid || !isPasswordValid || !isConfirmValid) {
                e.preventDefault();
                alert('Please fix all errors before submitting.');
            }
        });
    </script>
</body>
</html>