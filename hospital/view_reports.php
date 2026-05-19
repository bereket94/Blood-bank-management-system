<?php
session_start();
include '../config/db_connect.php';

if($_SESSION['role'] != 'hospital') {
    header("Location: ../login.php");
    exit();
}

$hospital_id = $_SESSION['user_id'];

// Mark report as viewed when clicked
if(isset($_GET['view_id'])) {
    $id = $_GET['view_id'];
    mysqli_query($conn, "UPDATE reports SET status='viewed', viewed_at=NOW() WHERE id='$id' AND hospital_id='$hospital_id'");
}

// Delete report
if(isset($_GET['delete_id'])) {
    $id = $_GET['delete_id'];
    mysqli_query($conn, "DELETE FROM reports WHERE id='$id' AND hospital_id='$hospital_id'");
    echo "<script>alert('Report deleted successfully!'); window.location='view_reports.php';</script>";
}

// Get all reports sent to this hospital
$sql = "SELECT r.*, n.name as nurse_name 
        FROM reports r 
        JOIN nurses n ON r.nurse_id = n.id 
        WHERE r.hospital_id = '$hospital_id' 
        ORDER BY r.created_at DESC";
$reports = mysqli_query($conn, $sql);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Received Reports - Hospital</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .report-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .report-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 12px;
            margin-bottom: 15px;
            flex-wrap: wrap;
        }
        .report-title {
            font-size: 18px;
            font-weight: bold;
            color: #8B0000;
        }
        .report-meta {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
        .badge-sent {
            background: #ffc107;
            color: #856404;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: bold;
        }
        .badge-viewed {
            background: #28a745;
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: bold;
        }
        .report-content {
            margin: 15px 0;
            overflow-x: auto;
        }
        .report-actions {
            text-align: right;
            margin-top: 15px;
            padding-top: 10px;
            border-top: 1px solid #eee;
        }
        .btn-mark-read {
            background: #17a2b8;
            color: white;
            padding: 6px 15px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 12px;
            margin-right: 8px;
        }
        .btn-delete {
            background: #dc3545;
            color: white;
            padding: 6px 15px;
            border-radius: 5px;
            text-decoration: none;
            font-size: 12px;
        }
        .no-reports {
            text-align: center;
            padding: 60px;
            background: white;
            border-radius: 10px;
        }
    </style>
</head>
<body>
    <button class="toggle-btn" id="toggleBtn"><i class="fas fa-bars"></i></button>

    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <i class="fas fa-tint"></i>
            <h3>Blood Bank System</h3>
        </div>
        <div class="sidebar-menu">
            <a href="dashboard.php" class="menu-item"><i class="fas fa-tachometer-alt"></i><span class="menu-text">Dashboard</span></a>
            <a href="manage_nurses.php" class="menu-item"><i class="fas fa-user-nurse"></i><span class="menu-text">Manage Nurses</span></a>
            <a href="view_reports.php" class="menu-item active"><i class="fas fa-inbox"></i><span class="menu-text">Reports</span></a>
        </div>
        <div class="sidebar-footer">
            <a href="../logout.php" class="menu-item"><i class="fas fa-sign-out-alt"></i><span class="menu-text">Logout</span></a>
        </div>
    </div>

    <div class="main-content" id="mainContent">
        <div class="welcome-card" style="background: linear-gradient(135deg, #8B0000 0%, #a00000 100%);">
            <h2>📥 Received Reports</h2>
            <p>Reports sent by nurses</p>
        </div>

        <?php if(mysqli_num_rows($reports) == 0): ?>
            <div class="no-reports">
                <i class="fas fa-inbox" style="font-size: 48px; color: #8B0000; margin-bottom: 15px; display: block;"></i>
                <p>No reports received yet.</p>
                <p>Reports from nurses will appear here.</p>
            </div>
        <?php else: ?>
            <?php while($report = mysqli_fetch_assoc($reports)): ?>
                <div class="report-card">
                    <div class="report-header">
                        <div>
                            <span class="report-title">
                                <i class="fas fa-file-alt"></i> 
                                <?php echo ucfirst($report['report_type']); ?> Report
                            </span>
                            <div class="report-meta">
                                <i class="fas fa-user"></i> Nurse: <?php echo htmlspecialchars($report['nurse_name']); ?> |
                                <i class="fas fa-clock"></i> <?php echo date('F d, Y h:i A', strtotime($report['created_at'])); ?>
                            </div>
                        </div>
                        <div>
                            <?php if($report['status'] == 'sent'): ?>
                                <span class="badge-sent"><i class="fas fa-clock"></i> New</span>
                            <?php else: ?>
                                <span class="badge-viewed"><i class="fas fa-check"></i> Viewed</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- FIXED: Removed htmlspecialchars() so HTML renders properly -->
                    <div class="report-content">
                        <?php echo $report['report_data']; ?>
                    </div>
                    
                    <div class="report-actions">
                        <?php if($report['status'] == 'sent'): ?>
                            <a href="?view_id=<?php echo $report['id']; ?>" class="btn-mark-read">
                                <i class="fas fa-check"></i> Mark as Read
                            </a>
                        <?php endif; ?>
                        <a href="?delete_id=<?php echo $report['id']; ?>" class="btn-delete" onclick="return confirm('Delete this report?')">
                            <i class="fas fa-trash"></i> Delete
                        </a>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php endif; ?>
    </div>

    <script>
        const toggleBtn = document.getElementById('toggleBtn');
        const sidebar = document.getElementById('sidebar');
        const mainContent = document.getElementById('mainContent');

        toggleBtn.addEventListener('click', () => {
            sidebar.classList.toggle('collapsed');
            mainContent.classList.toggle('expanded');
            toggleBtn.classList.toggle('collapsed');
            
            const icon = toggleBtn.querySelector('i');
            if (sidebar.classList.contains('collapsed')) {
                icon.classList.remove('fa-bars');
                icon.classList.add('fa-chevron-right');
            } else {
                icon.classList.remove('fa-chevron-right');
                icon.classList.add('fa-bars');
            }
        });
    </script>
</body>
</html>