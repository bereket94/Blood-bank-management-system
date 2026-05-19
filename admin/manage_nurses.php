<?php
session_start();
include '../config/db_connect.php';

if($_SESSION['role'] != 'admin') {
    header("Location: ../index.php");
    exit();
}

$search = '';
if(isset($_GET['search'])) {
    $search = mysqli_real_escape_string($conn, $_GET['search']);
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_nurse'])) {
    $hospital_id = $_POST['hospital_id'];
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $phone = $_POST['phone'];
    $sql = "INSERT INTO nurses (hospital_id, name, email, password, phone) 
            VALUES ('$hospital_id', '$name', '$email', '$password', '$phone')";
    if(mysqli_query($conn, $sql)) {
        echo "<script>alert('Nurse added successfully!');</script>";
    }
}

if(isset($_GET['delete'])) {
    $id = $_GET['delete'];
    mysqli_query($conn, "DELETE FROM nurses WHERE id='$id'");
    echo "<script>alert('Nurse deleted!'); window.location='manage_nurses.php" . (!empty($search) ? "?search=" . urlencode($search) : "") . "';</script>";
}

$hospitals = mysqli_query($conn, "SELECT id, name FROM hospitals");

if(!empty($search)) {
    $nurses = mysqli_query($conn, "SELECT n.*, h.name as hospital_name FROM nurses n 
                                   JOIN hospitals h ON n.hospital_id = h.id 
                                   WHERE n.email LIKE '%$search%' 
                                   OR n.phone LIKE '%$search%'
                                   ORDER BY n.created_at DESC");
} else {
    $nurses = mysqli_query($conn, "SELECT n.*, h.name as hospital_name FROM nurses n 
                                   JOIN hospitals h ON n.hospital_id = h.id 
                                   ORDER BY n.created_at DESC");
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Nurses - Admin</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
      .search-bar {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            width: 100%;
        }
     .search-bar form {
            display: flex;
            gap: 10px;
            width: 100%;
        }
        .search-bar input {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            box-sizing: border-box;
        }
        .search-bar button {
            padding: 12px 25px;
            background: #1a1a2e;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            white-space: nowrap;
            font-size: 14px;
        }
        .search-bar button:hover {
            background: #16213e;
        }
        .clear-search {
            padding: 12px 25px;
            background: #6c757d;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            white-space: nowrap;
            display: inline-block;
            text-align: center;
            font-size: 14px;
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
        .header {
            background: #720303;
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
        .phone-hint {
            font-size: 11px;
            color: #666;
            margin-top: 5px;
            display: block;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Manage Nurses</h1>
            <a href="dashboard.php">Back to Dashboard</a>
        </div>

        <div class="search-bar">
            <form method="GET" id="searchForm">
                <div style="flex: 1;">
                    <input type="text" name="search" id="searchInput" placeholder="Search by Email or Phone..." value="<?php echo htmlspecialchars($search); ?>" onkeypress="restrictSearchInput(event)">
                    <span class="phone-hint" id="phoneSearchHint"></span>
                </div>
                <button type="submit">Search</button>
                <?php if(!empty($search)): ?>
                    <a href="manage_nurses.php" class="clear-search">Clear</a>
                <?php endif; ?>
            </form>
        </div>
        
        <?php if(isset($_GET['search']) && empty($_GET['search'])): ?>
            <div class="search-info" style="background: #fff3cd; color: #856404;">
                Please enter an email or phone number to search!
            </div>
        <?php elseif(!empty($search) && $nurses): ?>
            <div class="search-info">
            Showing results for: <strong><?php echo htmlspecialchars($search); ?></strong> (<?php echo mysqli_num_rows($nurses); ?> found)
            </div>
        <?php endif; ?>

        <div id="phoneSearchError" style="display: none;" class="search-error"></div>
        
        <h3>All Nurses (<?php echo $nurses ? mysqli_num_rows($nurses) : 0; ?>)</h3>
        <?php if($nurses && mysqli_num_rows($nurses) == 0 && !empty($search)): ?>
            <p style="text-align:center; padding:20px; background:white; border-radius:10px">
                No nurses found matching "<strong><?php echo htmlspecialchars($search); ?></strong>"
            </p>
        <?php elseif($nurses): ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Hospital</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($nurse = mysqli_fetch_assoc($nurses)): ?>
                    <tr>
                        <td><?php echo $nurse['id']; ?></td>
                        <td><strong><?php echo htmlspecialchars($nurse['name']); ?></strong></td>
                        <td><?php echo htmlspecialchars($nurse['email']); ?></td>
                        <td><?php echo htmlspecialchars($nurse['phone']); ?></td>
                        <td><?php echo htmlspecialchars($nurse['hospital_name']); ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    <script>
        function restrictSearchInput(event) {
            var key = event.key;
            var inputField = document.getElementById('searchInput');
            var currentValue = inputField.value;
            if (key === 'Backspace' || key === 'Delete' || key === 'Tab' || key === 'Escape' || key === 'Enter') {
                return true;
            }
            if (event.ctrlKey && (key === 'a' || key === 'c' || key === 'v' || key === 'x')) {
                return true;
            }
            var looksLikePhone = false;
            if (currentValue.length === 0) {
                looksLikePhone = /^[0-9]$/.test(key);
            } else {
                looksLikePhone = /^\d+$/.test(currentValue);
            }
            if (looksLikePhone) {
                if (!/^[0-9]$/.test(key)) {
                    event.preventDefault();
                    showPhoneHint('Switching to numeric validation. Only numbers allowed for phone numbers!', 'red');
                    return false;
                }
                if (currentValue.length >= 10) {
                    event.preventDefault();
                    showPhoneHint('Phone number cannot exceed 10 digits!', 'red');
                    return false;
                }
            } else {
                if (!/^[a-zA-Z0-9@._\-]$/.test(key)) {
                    event.preventDefault();
                    return false;
                }
            }
            showPhoneHint('', '');
            return true;
        }
        function showPhoneHint(message, color) {
            var hintSpan = document.getElementById('phoneSearchHint');
            if (hintSpan) {
                hintSpan.innerHTML = message;
                hintSpan.style.color = color;
                if (message === '') {
                    hintSpan.innerHTML = 'Enter phone number (9-10 digits only) or email address';
                    hintSpan.style.color = '#666';
                }
            }
        }
        document.getElementById('searchInput').addEventListener('input', function() {
            var value = this.value.trim();
            var errorDiv = document.getElementById('phoneSearchError');
            if (value.length > 0 && /^\d+$/.test(value)) {
                if (value.length < 9) {
                    errorDiv.innerHTML = 'Phone number format must be at least 9 digits! Current layout length: ' + value.length;
                    errorDiv.style.display = 'block';
                } else if (value.length > 10) {
                    errorDiv.innerHTML = 'Phone number cannot exceed 10 digits!';
                    errorDiv.style.display = 'block';
                    this.value = value.substring(0, 10);
                } else {
                    errorDiv.style.display = 'none';
                }
            } else {
                errorDiv.style.display = 'none';
            }
        });
        document.getElementById('searchForm').addEventListener('submit', function(e) {
            var searchValue = document.getElementById('searchInput').value.trim();
            
            if (searchValue === '') {
                e.preventDefault();
                alert('Please enter an email or phone number to search!');
                return false;
            }
            if (/^\d+$/.test(searchValue)) {
                if (searchValue.length < 9 || searchValue.length > 10) {
                    e.preventDefault();
                    alert('Invalid configuration! Phone numbers must be between 9 and 10 digits.');
                    return false;
                }
            }
            return true;
        });
        document.addEventListener('DOMContentLoaded', function() {
            showPhoneHint('Enter phone number (9-10 digits only) or email address', '#666');
        });
    </script>
</body>
</html>