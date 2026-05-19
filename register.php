<?php
session_start();
include '../config/db_connect.php';

$error = '';
$success = '';
$first_name = '';
$last_name = '';
$username = '';
$email = '';
$phone = '';
$age = '';
$weight = '';
$blood_group = '';
$diseases = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $phone = trim($_POST['phone']);
    $age = intval($_POST['age']);
    $weight = floatval($_POST['weight']);
    $blood_group = $_POST['blood_group'];
    $diseases = trim($_POST['diseases']);
    $errors = [];
    
    if (empty($first_name) || !preg_match("/^[a-zA-Z]+$/", $first_name) || strlen($first_name) < 3 || strlen($first_name) > 20) {
        $errors[] = "Invalid first name";
    }
    if (empty($last_name) || !preg_match("/^[a-zA-Z]+$/", $last_name) || strlen($last_name) < 3 || strlen($last_name) > 20) {
        $errors[] = "Invalid last name";
    }
    if (empty($username) || !preg_match("/^[a-zA-Z0-9._]+$/", $username) || strlen($username) < 3 || strlen($username) > 30) {
        $errors[] = "Invalid username";
    }
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email";
    }
    if (empty($password) || strlen($password) < 6) {
        $errors[] = "Invalid password";
    }
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match";
    }
    if (empty($phone) || !preg_match("/^[0-9]{10}$/", $phone)) {
        $errors[] = "Invalid phone number";
    }
    if (empty($age) || $age < 18 || $age > 65) {
        $errors[] = "Invalid age";
    }
    if (empty($weight) || $weight < 50) {
        $errors[] = "Invalid weight";
    }
    $valid_blood = ['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-'];
    if (empty($blood_group) || !in_array($blood_group, $valid_blood)) {
        $errors[] = "Invalid blood group";
    }
    if (empty($diseases)) {
        $errors[] = "Please enter disease information (write 'None' if none)";
    } elseif (strlen($diseases) < 2) {
        $errors[] = "Disease information must be at least 2 characters";
    }
    
    if (empty($errors)) {
        $hashed_password = md5($password);
        
        $check_sql = "SELECT id FROM donors WHERE email = ? OR username = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("ss", $email, $username);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if($check_result->num_rows > 0) {
            $error = "Email or Username already registered!";
        } else {
            $insert_sql = "INSERT INTO donors (first_name, last_name, username, email, password, phone, age, weight, blood_group, diseases) 
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $insert_stmt = $conn->prepare($insert_sql);
            $insert_stmt->bind_param("ssssssidss", $first_name, $last_name, $username, $email, $hashed_password, $phone, $age, $weight, $blood_group, $diseases);
            
            if ($insert_stmt->execute()) {
                echo "<script>alert('Registration Successful! Please login.'); window.location='login.php';</script>";
                exit();
            } else {
                $error = "Registration failed!";
            }
        }
    } else {
        $error = implode("<br>", $errors);
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Donor Registration</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <style>
          .error-list {
            color: red;
            text-align: center;
            padding: 10px;
            background: #ffe6e6;
            border-radius: 5px;
            margin-bottom: 15px;
        }
        .password-hint, .phone-hint {
            font-size: 12px;
            color: #666;
            margin-top: -8px;
            margin-bottom: 10px;
        }
        .name-row {
            display: flex;
            gap: 15px;
        }
        .name-row input {
            flex: 1;
        }
        .phone-input-group {
            display: flex;
            align-items: center;
            gap: 5px;
            margin: 10px 0;
            width: 100%;
        }
        .country-code {
            background: #f5f5f5;
            padding: 12px 15px;
            border-radius: 8px;
            border: 1px solid #ddd;
            font-weight: bold;
            color: #333;
            font-size: 16px;
            min-width: 70px;
            text-align: center;
        }
        .phone-input-group input {
            flex: 1;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 16px;
            font-family: inherit;
        }
        .phone-hint {
            font-size: 12px;
            color: #666;
            margin-top: -5px;
            margin-bottom: 10px;
            margin-left: 5px;
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
       
        /* Error message styles */
        .error-message {
            font-size: 11px;
            margin-top: -5px;
            margin-bottom: 8px;
            display: block;
        }
        
        .input-error {
            border-color: #dc3545 !important;
            background-color: #fff3f3 !important;
        }
        
        @media (max-width: 768px) {
            .phone-input-group {
                gap: 3px;
            }
            .country-code {
                padding: 10px 12px;
                min-width: 60px;
                font-size: 14px;
            }
            .phone-input-group input {
                padding: 10px;
                font-size: 14px;
            }
            .name-row {
                flex-direction: column;
                gap: 2px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="form-container">
            <div class="role-badge">🩸 Donor Registration</div>
            <h2>Register to Save Lives</h2>
            
            <?php if($error): ?>
                <div class="error-list"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="POST" onsubmit="return validateForm()">
                <div class="name-row">
                    <div style="flex: 1;">
                        <input type="text" name="first_name" id="first_name" placeholder="First Name" value="<?php echo htmlspecialchars($first_name); ?>" required maxlength="20" onkeyup="validateFirstName()" onblur="validateFirstName()">
                        <span id="first_name_error" class="error-message"></span>
                    </div>
                    <div style="flex: 1;">
                        <input type="text" name="last_name" id="last_name" placeholder="Last Name" value="<?php echo htmlspecialchars($last_name); ?>" required maxlength="20" onkeyup="validateLastName()" onblur="validateLastName()">
                        <span id="last_name_error" class="error-message"></span>
                    </div>
                </div>
                
                <div>
                    <input type="text" name="username" id="username" placeholder="Username" value="<?php echo htmlspecialchars($username); ?>" required onkeyup="validateUsername()" onblur="validateUsername()">
                    <span id="username_error" class="error-message"></span>
                </div>
                
                <div>
                    <input type="email" name="email" id="email" placeholder="Email Address" value="<?php echo htmlspecialchars($email); ?>" required maxlength="100" onkeyup="validateEmail()" onblur="validateEmail()">
                    <span id="email_error" class="error-message"></span>
                </div>
                
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
                <span id="confirm_error" class="error-message"></span>
                
                <div class="phone-input-group">
                    <div class="country-code">+251</div>
                    <input type="tel" name="phone" id="phone" placeholder="912345678" value="<?php echo htmlspecialchars($phone); ?>" required onkeyup="validatePhone()" onblur="validatePhone()">
                </div>
                <span id="phone_error" class="error-message"></span>
                <div class="phone-hint">Enter 10 digits after +251 (e.g., 912345678)</div>
                
                <div>
                    <input type="number" name="age" id="age" placeholder="Age (18-65)" min="18" max="65" value="<?php echo $age; ?>" required onkeyup="validateAge()" onblur="validateAge()">
                    <span id="age_error" class="error-message"></span>
                </div>
                
                <div>
                    <input type="number" step="0.01" name="weight" id="weight" placeholder="Weight (kg)" min="45" max="200" value="<?php echo $weight; ?>" required onkeyup="validateWeight()" onblur="validateWeight()">
                    <span id="weight_error" class="error-message"></span>
                </div>
                
                <div>
                    <select name="blood_group" id="blood_group" required onchange="validateBloodGroup()">
                        <option value="">Select Blood Group</option>
                        <option value="A+" <?php echo ($blood_group == 'A+') ? 'selected' : ''; ?>>A+</option>
                        <option value="A-" <?php echo ($blood_group == 'A-') ? 'selected' : ''; ?>>A-</option>
                        <option value="B+" <?php echo ($blood_group == 'B+') ? 'selected' : ''; ?>>B+</option>
                        <option value="B-" <?php echo ($blood_group == 'B-') ? 'selected' : ''; ?>>B-</option>
                        <option value="O+" <?php echo ($blood_group == 'O+') ? 'selected' : ''; ?>>O+</option>
                        <option value="O-" <?php echo ($blood_group == 'O-') ? 'selected' : ''; ?>>O-</option>
                        <option value="AB+" <?php echo ($blood_group == 'AB+') ? 'selected' : ''; ?>>AB+</option>
                        <option value="AB-" <?php echo ($blood_group == 'AB-') ? 'selected' : ''; ?>>AB-</option>
                    </select>
                    <span id="blood_group_error" class="error-message"></span>
                </div>
                
                <div>
                    <textarea name="diseases" id="diseases" placeholder="Any diseases? (If none, write 'None')" maxlength="255" required onkeyup="validateDiseases()" onblur="validateDiseases()"><?php echo htmlspecialchars($diseases); ?></textarea>
                    <span id="diseases_error" class="error-message"></span>
                </div>
                
                <button type="submit">Register</button>
                <button type="reset" style="background: #f44336; margin-top: 10px;">Reset</button>
            </form>
            
            <p class="switch-role">
                Already have an account? <a href="login.php">Login</a><br>  
                <a href="../index.php">Back to Home</a>
            </p>
        </div>
    </div>
    
    <script>
        function togglePassword(fieldId) {
            var field = document.getElementById(fieldId);
            var toggleBtn = field.nextElementSibling;
            var icon = toggleBtn.querySelector('i');
            
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
        
        function validateFirstName() {
            var firstName = document.getElementById('first_name').value.trim();
            var errorSpan = document.getElementById('first_name_error');
            
            if (firstName === '') {
                errorSpan.innerHTML = 'First name is required';
                errorSpan.style.color = 'red';
                return false;
            } else if (!/^[a-zA-Z]+$/.test(firstName)) {
                errorSpan.innerHTML = 'First name can only contain letters';
                errorSpan.style.color = 'red';
                return false;
            } else if (firstName.length < 3 || firstName.length > 20) {
                errorSpan.innerHTML = 'First name must be between 3 and 20 characters';
                errorSpan.style.color = 'red';
                return false;
            } else {
                errorSpan.innerHTML = 'Valid';
                errorSpan.style.color = 'green';
                return true;
            }
        }
        
        function validateLastName() {
            var lastName = document.getElementById('last_name').value.trim();
            var errorSpan = document.getElementById('last_name_error');
            
            if (lastName === '') {
                errorSpan.innerHTML = 'Last name is required';
                errorSpan.style.color = 'red';
                return false;
            } else if (!/^[a-zA-Z]+$/.test(lastName)) {
                errorSpan.innerHTML = 'Last name can only contain letters';
                errorSpan.style.color = 'red';
                return false;
            } else if (lastName.length < 3 || lastName.length > 20) {
                errorSpan.innerHTML = 'Last name must be between 3 and 20 characters';
                errorSpan.style.color = 'red';
                return false;
            } else {
                errorSpan.innerHTML = 'Valid';
                errorSpan.style.color = 'green';
                return true;
            }
        }
        
        function validateUsername() {
            var username = document.getElementById('username').value.trim();
            var errorSpan = document.getElementById('username_error');
            
            if (username === '') {
                errorSpan.innerHTML = 'Username is required';
                errorSpan.style.color = 'red';
                return false;
            } else if (!/^[a-zA-Z0-9._]+$/.test(username)) {
                errorSpan.innerHTML = 'Username can only contain letters, numbers, dots and underscores';
                errorSpan.style.color = 'red';
                return false;
            } else if (username.length < 3 || username.length > 30) {
                errorSpan.innerHTML = 'Username must be between 3 and 30 characters';
                errorSpan.style.color = 'red';
                return false;
            } else {
                errorSpan.innerHTML = 'Available';
                errorSpan.style.color = 'green';
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
                return false;
            } else if (!emailPattern.test(email)) {
                errorSpan.innerHTML = 'Please enter a valid email address';
                errorSpan.style.color = 'red';
                return false;
            } else {
                errorSpan.innerHTML = 'Valid';
                errorSpan.style.color = 'green';
                return true;
            }
        }
        
        function validatePassword() {
            var password = document.getElementById('password').value;
            var errorSpan = document.getElementById('password_error');
            
            if (password === '') {
                errorSpan.innerHTML = 'Password is required';
                errorSpan.style.color = 'red';
                return false;
            } else if (password.length < 6) {
                errorSpan.innerHTML = 'Password must be at least 6 characters';
                errorSpan.style.color = 'red';
                return false;
            } else if (password.length > 20) {
                errorSpan.innerHTML = 'Password is too long';
                errorSpan.style.color = 'red';
                return false;
            } else {
                errorSpan.innerHTML = 'Strong';
                errorSpan.style.color = 'green';
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
                return false;
            } else if (password !== confirm) {
                errorSpan.innerHTML = 'Passwords do not match';
                errorSpan.style.color = 'red';
                return false;
            } else {
                errorSpan.innerHTML = 'Matches';
                errorSpan.style.color = 'green';
                return true;
            }
        }
        
        function validatePhone() {
            var phone = document.getElementById('phone').value.trim();
            var errorSpan = document.getElementById('phone_error');
            
            if (phone === '') {
                errorSpan.innerHTML = 'Phone number is required';
                errorSpan.style.color = 'red';
                return false;
            } else if (!/^[0-9]{9,10}$/.test(phone)) {
                errorSpan.innerHTML = 'Phone number must be only numbers and  9-10 digits';
                errorSpan.style.color = 'red';
                return false;
            } else {
                errorSpan.innerHTML = 'Valid';
                errorSpan.style.color = 'green';
                return true;
            }
        }
        
        function validateAge() {
            var age = document.getElementById('age').value;
            var errorSpan = document.getElementById('age_error');
            
            if (age === '') {
                errorSpan.innerHTML = 'Age is required';
                errorSpan.style.color = 'red';
                return false;
            } else if (age < 18 || age > 65) {
                errorSpan.innerHTML = 'Age must be between 18 and 65 years';
                errorSpan.style.color = 'red';
                return false;
            } else {
                errorSpan.innerHTML = 'Valid';
                errorSpan.style.color = 'green';
                return true;
            }
        }
        
        function validateWeight() {
            var weight = document.getElementById('weight').value;
            var errorSpan = document.getElementById('weight_error');
            
            if (weight === '') {
                errorSpan.innerHTML = 'Weight is required';
                errorSpan.style.color = 'red';
                return false;
            } else if (weight < 50) {
                errorSpan.innerHTML = 'Weight must be at least 50 kg';
                errorSpan.style.color = 'red';
                return false;
            } else {
                errorSpan.innerHTML = 'Valid';
                errorSpan.style.color = 'green';
                return true;
            }
        }
        
        function validateBloodGroup() {
            var bloodGroup = document.getElementById('blood_group').value;
            var errorSpan = document.getElementById('blood_group_error');
            
            if (bloodGroup === '') {
                errorSpan.innerHTML = 'Please select a blood group';
                errorSpan.style.color = 'red';
                return false;
            } else {
                errorSpan.innerHTML = 'Selected';
                errorSpan.style.color = 'green';
                return true;
            }
        }
        
        function validateDiseases() {
            var diseases = document.getElementById('diseases').value.trim();
            var errorSpan = document.getElementById('diseases_error');
            
            if (diseases === '') {
                errorSpan.innerHTML = ' Please enter disease information';
                errorSpan.style.color = 'red';
                return false;
            } else if (diseases.toLowerCase() === 'none') {
                errorSpan.innerHTML = 'Valid';
                errorSpan.style.color = 'green';
                return true;
            } else if (diseases.length < 2) {
                errorSpan.innerHTML = 'Disease information must be at least 2 characters';
                errorSpan.style.color = 'red';
                return false;
            } else {
                errorSpan.innerHTML = 'Valid';
                errorSpan.style.color = 'green';
                return true;
            }
        }
        function validateForm() {
            var isValid = true;
            isValid = validateFirstName() && isValid;
            isValid = validateLastName() && isValid;
            isValid = validateUsername() && isValid;
            isValid = validateEmail() && isValid;
            isValid = validatePassword() && isValid;
            isValid = validateConfirmPassword() && isValid;
            isValid = validatePhone() && isValid;
            isValid = validateAge() && isValid;
            isValid = validateWeight() && isValid;
            isValid = validateBloodGroup() && isValid;
            isValid = validateDiseases() && isValid;
            
            if (!isValid) {
                alert('Please fix all errors before submitting.');
            }
            return isValid;
        }
    </script>
</body>
</html>