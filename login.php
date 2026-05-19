<?php
session_start();
include '../config/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $login = trim($_POST['login']);
    $password = md5($_POST['password']);
    
    if (filter_var($login, FILTER_VALIDATE_EMAIL)) {
        $sql = "SELECT * FROM donors WHERE email = ? AND password = ?";
    } else {
        $sql = "SELECT * FROM donors WHERE username = ? AND password = ?";
    }
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $login, $password);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if (mysqli_num_rows($result) == 1) {
        $user = mysqli_fetch_assoc($result);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = 'donor';
        header("Location: dashboard.php");
        exit();
    } else {
        $error = "Invalid email/username or password!";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Donor Login</title>
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
<style>
 .password-wrapper {
            position: relative;
            width: 100%;
            margin: 10px 0;
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
            color: #f50909 !important;
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
    /* Make sure container has position relative if needed */
    .form-container {
        position: relative;
    }
</style>
</head>
<body>
    <div class="container">
        <div class="form-container">
            <div class="role-badge">🩸 Donor Login</div>
            <h2>Welcome Back, Donor!</h2>
            <?php if(isset($error)) echo "<p class='error'>$error</p>"; ?>
            
            <form method="POST">
                <input type="text" name="login" placeholder="Email or Username" required>
                
                <div class="password-wrapper">
                    <input type="password" name="password" id="password" placeholder="Password (min 6 characters)" required>
                    <button type="button" class="password-toggle" onclick="togglePassword('password')">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
                
                <button type="submit">Login</button>
            </form>
            
            <p class="switch-role">
                New donor? <a href="register.php">Register</a><br>
                <a href="forgot_password.php">Forgot Password?</a><br>
                <a href="../index.php">Back to Home</a>
            </p>
        </div>
    </div>
    
    <script>
    function togglePassword() {
        var field = document.getElementById('password');
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
    </script>
</body>
</html>