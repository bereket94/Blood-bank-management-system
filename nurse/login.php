<?php
session_start();
include '../config/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $password = md5($_POST['password']);

    $log_stmt = $conn->prepare("INSERT INTO system_logs (user_id, user_type, action, details, ip_address) VALUES (?, 'nurse', 'login', ?, ?)");
    $log_details = "Nurse staff logged in successfully";
    $log_stmt->bind_param("iss", $user['id'], $log_details, $_SERVER['REMOTE_ADDR']);
    $log_stmt->execute();
    $log_stmt->close();
    $sql = "SELECT n.*, h.name as hospital_name, h.id as hospital_id 
            FROM nurses n 
            JOIN hospitals h ON n.hospital_id = h.id 
            WHERE n.email='$email' AND n.password='$password'";
    $result = mysqli_query($conn, $sql);

    if (mysqli_num_rows($result) == 1) {
        $user = mysqli_fetch_assoc($result);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['hospital_id'] = $user['hospital_id'];
        $_SESSION['hospital_name'] = $user['hospital_name'];
        $_SESSION['role'] = 'nurse';
        header("Location: dashboard.php");
        exit();
    } else {
        $error = "Invalid email or password!";
    }

}
?>
<!DOCTYPE html>
<html>

<head>
    <title>Nurse Login - Blood Bank</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
            <div class="role-badge">Nurse Login</div>
            <h2>Nurse Access Only</h2>

            <?php if (isset($error))
                echo "<p class='error'>$error</p>"; ?>
            <form method="POST">
                <input type="email" name="email" placeholder="Email Address" value="sarah.nurse@gmail.com" required>
                <div class="password-wrapper">
                    <input type="password" name="password" id="password" placeholder="Password" value="nurse123"
                        required>
                    <button type="button" class="password-toggle" onclick="togglePassword('password')">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
                <button type="submit">Login</button>
            </form>

            <p class="switch-role">
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