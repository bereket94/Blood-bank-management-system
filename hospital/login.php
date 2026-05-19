<?php
session_start();
include '../config/db_connect.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $email = $_POST['email'];
    $password = $_POST['password'];

    $sql = "SELECT * FROM hospitals 
            WHERE email='$email' AND password='$password'";

    $result = mysqli_query($conn, $sql);

    if (mysqli_num_rows($result) == 1) {

        $user = mysqli_fetch_assoc($result);

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['hospital_id'] = $user['id'];
        $_SESSION['role'] = 'hospital';

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
    <title>Wolkite University Hospital - Login</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="container">
        <div class="form-container">
            <div class="role-badge">🏥 Wolkite University Hospital</div>
            <h2>Hospital Admin Login</h2>
            
            <?php if(isset($error)) echo "<p class='error'>$error</p>"; ?>
            
            <form method="POST">
                <input type="email" name="email" placeholder="Hospital Email" value="wolkite.hospital@gmail.com" required>
                <input type="password" name="password" placeholder="Password" value="hospital123" required>
                <button type="submit">Login to Dashboard</button>
            </form>
            
            <p class="switch-role">
                <a href="../index.php">Back to Home</a>
            </p>
        </div>
    </div>
</body>
</html>