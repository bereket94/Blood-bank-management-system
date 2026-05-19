<?php
session_start();
include '../config/db_connect.php';

if($_SESSION['role'] != 'admin') {
    header("Location: ../index.php");
}

$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

$total_donors = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM donors"))['count'];
$total_hospitals = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM hospitals"))['count'];
$total_nurses = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM nurses"))['count'];
$total_appointments = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM appointments"))['count'];
$successful_donations = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM appointments WHERE status='success'"))['count'];
$pending_donations = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM appointments WHERE status='pending'"))['count'];
$failed_donations = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM appointments WHERE status='failed'"))['count'];

$blood_type_stats = mysqli_query($conn, "SELECT blood_group, COUNT(*) as count FROM donors WHERE blood_group IS NOT NULL GROUP BY blood_group");

$monthly_donations = mysqli_query($conn, "SELECT DATE_FORMAT(appointment_date, '%Y-%m') as month, COUNT(*) as count, SUM(CASE WHEN status='success' THEN 1 ELSE 0 END) as success 
    FROM appointments 
    WHERE appointment_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(appointment_date, '%Y-%m')
    ORDER BY month DESC");

$hospital_stats = mysqli_query($conn, "SELECT h.name, COUNT(a.id) as total_donations, SUM(CASE WHEN a.status='success' THEN 1 ELSE 0 END) as successful
    FROM hospitals h
    LEFT JOIN appointments a ON h.id = a.hospital_id
    GROUP BY h.id
    ORDER BY total_donations DESC");
?>
<!DOCTYPE html>
<html>
<head>
    <title>System Reports - Admin</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .report-container {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .report-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #eee;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: #f8f9fa;
            padding: 20px;
            text-align: center;
            border-radius: 10px;
        }
        .stat-card h3 {
            font-size: 32px;
            margin-bottom: 5px;
        }
        .stat-card.success h3 { color: #28a745; }
        .stat-card.danger h3 { color: #dc3545; }
        .stat-card.warning h3 { color: #ffc107; }
        .stat-card.info h3 { color: #17a2b8; }
        .date-filter {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .btn-print {
            background: #28a745;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .btn-export {
            background: #17a2b8;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        .chart-container {
            margin: 20px 0;
        }
        .blood-type-bar {
            background: #e9ecef;
            border-radius: 10px;
            margin: 10px 0;
            overflow: hidden;
        }
        .blood-type-fill {
            background: #dc3545;
            color: white;
            padding: 8px;
            text-align: center;
            transition: width 0.5s;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header" style="background: #8d0606;">
            <h1>System Reports</h1>
            <p>Blood Bank Management System Analytics</p>
            <a href="dashboard.php" class="logout">Back to Dashboard</a>
        </div>
        
        <!-- Date Filter -->
        <div class="date-filter">
            <form method="GET" style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                <label>From:</label>
                <input type="date" name="start_date" value="<?php echo $start_date; ?>">
                <label>To:</label>
                <input type="date" name="end_date" value="<?php echo $end_date; ?>">
                <button type="submit" class="btn">Apply Filter</button>
                <button type="button" class="btn-print" onclick="window.print()"> Print Report</button>
                <button type="button" class="btn-export" onclick="exportToCSV()"> Export to CSV</button>
            </form>
        </div>
        
        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card info">
                <h3><?php echo $total_donors; ?></h3>
                <p>Total Donors</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $total_hospitals; ?></h3>
                <p>Hospitals</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $total_nurses; ?></h3>
                <p>Nurses</p>
            </div>
            <div class="stat-card">
                <h3><?php echo $total_appointments; ?></h3>
                <p>Total Appointments</p>
            </div>
            <div class="stat-card success">
                <h3><?php echo $successful_donations; ?></h3>
                <p>Successful Donations</p>
            </div>
            <div class="stat-card warning">
                <h3><?php echo $pending_donations; ?></h3>
                <p>Pending</p>
            </div>
            <div class="stat-card danger">
                <h3><?php echo $failed_donations; ?></h3>
                <p>Failed</p>
            </div>
        </div>
        
        <!-- Blood Type Distribution -->
        <div class="report-container">
            <div class="report-header">
                <h3>🩸 Blood Type Distribution Among Donors</h3>
            </div>
            <?php while($blood = mysqli_fetch_assoc($blood_type_stats)): 
                $percentage = ($blood['count'] / max($total_donors, 1)) * 100;
            ?>
                <div>
                    <strong><?php echo $blood['blood_group']; ?></strong> (<?php echo $blood['count']; ?> donors)
                    <div class="blood-type-bar">
                        <div class="blood-type-fill" style="width: <?php echo $percentage; ?>%;">
                            <?php echo round($percentage, 1); ?>%
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
        <div class="report-container">
            <div class="report-header">
                <h3>Monthly Donations (Last 6 Months)</h3>
            </div>
            <div class="chart-container">
                <table border="1" cellpadding="10" cellspacing="0" width="100%">
                    <tr bgcolor="#1a1a2e" style="color:white">
                        <th>Month</th>
                        <th>Total Donations</th>
                        <th>Successful</th>
                        <th>Success Rate</th>
                    </tr>
                    <?php while($month = mysqli_fetch_assoc($monthly_donations)): 
                        $rate = $month['count'] > 0 ? round(($month['success'] / $month['count']) * 100, 1) : 0;
                    ?>
                    <tr>
                        <td><?php echo date('F Y', strtotime($month['month'] . '-01')); ?></td>
                        <td><?php echo $month['count']; ?></td>
                        <td style="color: green;"><?php echo $month['success']; ?></td>
                        <td>
                            <span style="background: <?php echo $rate >= 80 ? '#28a745' : ($rate >= 50 ? '#ffc107' : '#dc3545'); ?>; 
                                          color: white; padding: 3px 8px; border-radius: 5px;">
                                <?php echo $rate; ?>%
                            </span>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                </table>
            </div>
        </div>
        
        <!-- Hospital Performance -->
        <div class="report-container">
            <div class="report-header">
                <h3> Hospital Donation Statistics</h3>
            </div>
            <table border="1" cellpadding="10" cellspacing="0" width="100%">
                <tr bgcolor="#1a1a2e" style="color:white">
                    <th>Hospital Name</th>
                    <th>Total Donations</th>
                    <th>Successful</th>
                    <th>Success Rate</th>
                </tr>
                <?php while($hospital = mysqli_fetch_assoc($hospital_stats)): 
                    $rate = $hospital['total_donations'] > 0 ? round(($hospital['successful'] / $hospital['total_donations']) * 100, 1) : 0;
                ?>
                <tr>
                    <td><?php echo $hospital['name']; ?></td>
                    <td><?php echo $hospital['total_donations']; ?></td>
                    <td style="color: green;"><?php echo $hospital['successful']; ?></td>
                    <td><?php echo $rate; ?>%</td>
                </tr>
                <?php endwhile; ?>
            </table>
        </div>
        <div class="report-container">
            <div class="report-header">
                <h3> Executive Summary</h3>
            </div>
            <ul style="padding: 20px; line-height: 1.8;">
                <li> Total donors registered: <strong><?php echo $total_donors; ?></strong></li>
                <li> Total successful blood donations: <strong><?php echo $successful_donations; ?></strong></li>
                <li> Overall success rate: <strong><?php echo $total_appointments > 0 ? round(($successful_donations / $total_appointments) * 100, 1) : 0; ?>%</strong></li>
                <li> Hospitals partnered: <strong><?php echo $total_hospitals; ?></strong></li>
                <li> Active nurses: <strong><?php echo $total_nurses; ?></strong></li>
            </ul>
        </div>
        
        <div style="margin-top: 20px; text-align: center;">
            <a href="dashboard.php" class="btn">Back to Dashboard</a>
        </div>
    </div>
    <script>
        function exportToCSV() {
            let csv = [];
            let rows = document.querySelectorAll('table tr');
            for (let i = 0; i < rows.length; i++) {
                let row = [], cols = rows[i].querySelectorAll('td, th');
                for (let j = 0; j < cols.length; j++) {
                    row.push('"' + cols[j].innerText + '"');
                }
                csv.push(row.join(','));
            }
            let csvFile = new Blob([csv.join('\n')], {type: 'text/csv'});
            let downloadLink = document.createElement('a');
            downloadLink.download = 'blood_bank_report.csv';
            downloadLink.href = URL.createObjectURL(csvFile);
            downloadLink.click();
        }
    </script>
</body>
</html>