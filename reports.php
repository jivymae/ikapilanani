<?php
include 'login_check.php'; // Ensure user is logged in
include 'db_config.php';
include 'admin_check.php'; // Include the admin check helper

// Include mPDF library
require_once __DIR__ . '/vendor/autoload.php';  // Adjust the path if needed

// Check if the page is allowed
$page = basename($_SERVER['PHP_SELF']);
if (!isAdminPage($page)) {
    header('Location: unauthorized.php'); // Redirect to an error or dashboard page
    exit();
}

// Automatically set default date range (e.g., current month)
if (empty($_POST['start_date']) || empty($_POST['end_date'])) {
    $startDate = date('Y-m-01'); // First day of the current month
    $endDate = date('Y-m-t');   // Last day of the current month
} else {
    $startDate = $_POST['start_date'];
    $endDate = $_POST['end_date'];
}

// Build the SQL query for fetching filtered data
$sql = "SELECT 
    u.user_id AS dentist_id,
    CONCAT(u.first_name, ' ', u.last_name) AS dentist_name,
    u.email AS dentist_email,
    SUM(CASE WHEN a.appointment_status = 'pending' THEN 1 ELSE 0 END) AS pending_appointments,
    SUM(CASE WHEN a.appointment_status = 'completed' THEN 1 ELSE 0 END) AS completed_appointments,
    SUM(CASE WHEN a.appointment_status = 'cancelled' THEN 1 ELSE 0 END) AS cancelled_appointments,
    SUM(CASE WHEN a.appointment_status = 'no_show' THEN 1 ELSE 0 END) AS no_show_appointments
FROM users u
LEFT JOIN appointments a ON u.user_id = a.dentist_id
WHERE u.role = 'dentist'
";

// Add date filter only if both start_date and end_date are provided
if (!empty($startDate) && !empty($endDate)) {
    $sql .= " AND a.appointment_date BETWEEN '$startDate' AND '$endDate'";
}

$sql .= " GROUP BY u.user_id ORDER BY u.first_name, u.last_name";

// Execute the query
$result = $conn->query($sql);

// Fetch all results
$rows = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];



// If the user clicked the "Download PDF" button
if (isset($_POST['download_pdf'])) {
    // Create the HTML content for the PDF
    ob_start();
    ?>

    <h1>Dental Appointment Report</h1>
    <p><strong>Date Range:</strong> <?php echo htmlspecialchars($startDate) . " to " . htmlspecialchars($endDate); ?></p>

    <table border="1" cellpadding="5" cellspacing="0">
        <thead>
            <tr>
                <th>Dentist ID</th>
                <th>Dentist Name</th>
                <th>Dentist Email</th>
                <th>Pending</th>
                <th>Completed</th>
                <th>Cancelled</th>
                <th>No-shows</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($rows): ?>
                <?php foreach ($rows as $row): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['dentist_id']); ?></td>
                        <td><?php echo htmlspecialchars($row['dentist_name']); ?></td>
                        <td><?php echo htmlspecialchars($row['dentist_email']); ?></td>
                        <td><?php echo htmlspecialchars($row['pending_appointments']); ?></td>
                        <td><?php echo htmlspecialchars($row['completed_appointments']); ?></td>
                        <td><?php echo htmlspecialchars($row['cancelled_appointments']); ?></td>
                        <td><?php echo htmlspecialchars($row['no_show_appointments']); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7">No reports available for the selected period.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <?php
    $htmlContent = ob_get_clean();

    // Initialize mPDF
    $mpdf = new \Mpdf\Mpdf();

    // Write HTML content to the PDF
    $mpdf->WriteHTML($htmlContent);

    // Generate a unique filename for the PDF
    $fileName = 'appointment_report_' . time() . '.pdf';
    $filePath = 'uploads/' . $fileName;

    // Output the PDF to the uploads directory
    $mpdf->Output($filePath, 'F'); // 'F' saves the PDF to a file

    // Now insert the report details into the database
    $reportType = 'Dental Appointment Report'; // Define the report type
    $generatedAt = date('Y-m-d H:i:s'); // Current timestamp

    // Insert the report data into the database
    $insertSql = "INSERT INTO reports (report_type, file_path, start_date, end_date, generated_at)
                  VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($insertSql);
    $stmt->bind_param("sssss", $reportType, $filePath, $startDate, $endDate, $generatedAt);

    if ($stmt->execute()) {
        // If the insert is successful, proceed with downloading the file
        // Send the appropriate headers for the download
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . basename($filePath) . '"');
        header('Content-Length: ' . filesize($filePath));

        // Output the file content
        readfile($filePath);

        // Clean up and exit
        exit();
    } else {
        echo "Error saving the report to the database: " . $stmt->error;
    }

    $stmt->close();
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Dental Clinic Management System</title>
    <link rel="stylesheet" type="text/css" href="css/style.css">
</head>
<style>

/* Container */
.container {
    display: flex;
    min-height: 100vh;
    background-color: #fff;
}


/* Main Content */
.main-content {
    flex-grow: 1;
    padding: 20px;
}

#reports {
    background-color: #fff;
    padding: 20px;
    border-radius: 10px;
    box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1);
}

h1 {
    font-size: 28px;
    color: #333;
    margin-bottom: 20px;
}

/* Form Styling */
form {
    margin-bottom: 20px;
    display: flex;
    flex-direction: column;
}

form label {
    font-size: 16px;
    margin-bottom: 5px;
    color: #555;
}

form input[type="date"] {
    padding: 10px;
    margin-bottom: 10px;
    border: 1px solid #ccc;
    border-radius: 5px;
    font-size: 16px;
}

form button {
    padding: 10px;
    background-color: #272aff;
    color: white;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-size: 16px;
}

form button:hover {
    background-color: rgb(89,237,255);
}

/* Print Button */
button {
    background-color: #007bff;
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 5px;
    cursor: pointer;
    margin-bottom: 20px;
}

button:hover {
    background-color: #0056b3;
}

/* Table Styling */
table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
    background-color: #fff;
}

table th, table td {
    padding: 12px;
    text-align: center;
    border: 1px solid #ddd;
}

table th {
    background-color: #f1f1f1;
    color: #333;
    font-size: 18px;
    font-weight: bold;
}

table td {
    color: #666;
    font-size: 16px;
}

/* Table Row Hover Effect */
table tr:hover {
    background-color: #f9f9f9;
}

/* No Reports Available */
table td[colspan="7"] {
    text-align: center;
    font-size: 18px;
    color: #999;
    padding: 20px;
}

/* Responsive Design */
@media (max-width: 768px) {
    .container {
        flex-direction: column;
    }

    .sidebar {
        width: 100%;
        height: auto;
        padding: 10px;
    }

    .main-content {
        padding: 10px;
    }

    table th, table td {
        font-size: 14px;
    }
}
</style>
<body>
    <div class="container">
        <!-- Sidebar Menu -->
        <aside class="sidebar">
            <h2 class="logo">
                <img src="images/lads.png" alt="Dental Clinic Logo">
                <h1>LAD DCAMS</h1>
            </h2>
            <ul>
                <li><a href="admin_dashboard.php">Dashboard</a></li>
                <li><a href="patients.php">Patients</a></li>
                <li><a href="dentists.php">Dentists</a></li>
                <li><a href="appointments.php">Appointments</a></li>
                <li><a href="admin_add_services.php">Add Services</a></li>
                <li><a href="reports.php" class="active">Reports</a></li>
                <li><a href="admin_settings.php">Settings</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <section id="reports">
                <h1>Reports</h1>

                <!-- Date Filter Form -->
                <form method="POST">
                    <label for="start_date">Start Date:</label>
                    <input type="date" name="start_date" id="start_date" value="<?php echo htmlspecialchars($startDate); ?>" required>
                    <label for="end_date">End Date:</label>
                    <input type="date" name="end_date" id="end_date" value="<?php echo htmlspecialchars($endDate); ?>" required>
                    <button type="submit">Filter</button>
                </form>

                <!-- Download PDF Button -->
                <form method="POST">
                    <button type="submit" name="download_pdf" class="download-pdf-btn">Download PDF</button>
                </form>

                <!-- Dentist Report Table -->
                <table>
                    <thead>
                        <tr>
                            <th>Dentist ID</th>
                            <th>Dentist Name</th>
                            <th>Dentist Email</th>
                            <th>Pending</th>
                            <th>Completed</th>
                            <th>Cancelled</th>
                            <th>No-shows</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($rows): ?>
                            <?php foreach ($rows as $row): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['dentist_id']); ?></td>
                                    <td><?php echo htmlspecialchars($row['dentist_name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['dentist_email']); ?></td>
                                    <td><?php echo htmlspecialchars($row['pending_appointments']); ?></td>
                                    <td><?php echo htmlspecialchars($row['completed_appointments']); ?></td>
                                    <td><?php echo htmlspecialchars($row['cancelled_appointments']); ?></td>
                                    <td><?php echo htmlspecialchars($row['no_show_appointments']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7">No reports available for the selected period.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </section>
        </main>
    </div>
</body>
</html>
