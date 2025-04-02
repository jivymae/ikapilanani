<?php
include 'login_check.php'; // Ensure user is logged in
include 'db_config.php';
include 'admin_check.php'; // Include the admin check helper

// Check if the page is allowed
$page = basename($_SERVER['PHP_SELF']);
if (!isAdminPage($page)) {
    header('Location: unauthorized.php'); // Redirect to an error or dashboard page
    exit();
}

// Fetch report data from the database
$sql = "SELECT 
            s.service_name,
            asrv.service_id,
            SUM(p.payment_amount) AS total_revenue,         -- Total revenue from completed appointments
            COUNT(DISTINCT a.appointment_id) AS total_appointments  -- Count of total appointments
        FROM appointment_services asrv
        JOIN services s ON asrv.service_id = s.service_id
        JOIN appointments a ON asrv.appointment_id = a.appointment_id
        LEFT JOIN payments p ON a.appointment_id = p.appointment_id
        WHERE a.appointment_status = 'completed'
        GROUP BY asrv.service_id"; // Group by service to aggregate results

$result = $conn->query($sql);

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Service Reports - Dental Clinic Management System</title>
    <link rel="stylesheet" type="text/css" href="css/style.css">
    <script>
        function printReport() {
            window.print();
        }
    </script>
    <style>
        .print-button {
            background-color: #4CAF50; /* Green */
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin-bottom: 20px;
        }

        .print-button:hover {
            background-color: #45a049;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Sidebar Menu -->
        <aside class="sidebar">
            <h2 class="logo">
                <img src="images/cometaicon.png" alt="Dental Clinic Logo">
                <h1>Dental Clinic</h1>
            </h2>
            <ul>
                <li><a href="admin_dashboard.php" class="<?php echo $page == 'admin_dashboard.php' ? 'active' : ''; ?>">Dashboard</a></li>
                <li><a href="patients.php">Patients</a></li>
                <li><a href="dentists.php">Dentists</a></li>
                <li><a href="appointments.php">Appointments</a></li>
                <li><a href="admin_add_services.php">Add Services</a></li>
                <li><a href="reports.php">Reports</a></li>
                <li><a href="appointment_reports.php" class="<?php echo $page == 'appointment_reports.php' ? 'active' : ''; ?>">Service Reports</a></li>
                <li><a href="profile.php">Profile</a></li>
                <li><a href="settings.php">Settings</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <section id="service-reports">
                <h1>Service Reports</h1>
                <button onclick="printReport()" class="print-button">Print Report</button> <!-- Print Button -->
                <table>
                    <thead>
                        <tr>
                            <th>Service ID</th>
                            <th>Service Name</th>
                            <th>Total Revenue</th>
                            <th>Total Appointments</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result->num_rows > 0): ?>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['service_id']); ?></td>
                                    <td><?php echo htmlspecialchars($row['service_name']); ?></td>
                                    <td><?php echo htmlspecialchars(number_format($row['total_revenue'], 2)); ?></td>
                                    <td><?php echo htmlspecialchars($row['total_appointments']); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4">No completed services available.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </section>
        </main>
    </div>
</body>
</html>
