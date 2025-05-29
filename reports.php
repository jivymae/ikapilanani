<?php
include 'login_check.php';
include 'db_config.php';
include 'admin_check.php';

require_once __DIR__ . '/vendor/autoload.php';

$page = basename($_SERVER['PHP_SELF']);
if (!isAdminPage($page)) {
    header('Location: unauthorized.php');
    exit();
}

// Set default date range
if (empty($_POST['start_date']) || empty($_POST['end_date'])) {
    $startDate = date('Y-m-01');
    $endDate = date('Y-m-t');
    $isDefaultDate = true;
} else {
    $startDate = $_POST['start_date'];
    $endDate = $_POST['end_date'];
    $isDefaultDate = false;
}

// Dentist appointments query
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
WHERE u.role = 'dentist'";

if (!empty($startDate) && !empty($endDate)) {
    $sql .= " AND a.appointment_date BETWEEN '$startDate' AND '$endDate'";
}

$sql .= " GROUP BY u.user_id ORDER BY u.first_name, u.last_name";

$result = $conn->query($sql);
$rows = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

// Financial data - only query if dates are not default
if (!$isDefaultDate) {
    $financialSql = "SELECT 
        SUM(t.transaction_amount) AS total_revenue,
        COUNT(DISTINCT p.payment_id) AS total_transaction,
        COUNT(DISTINCT a.appointment_id) AS total_appointments
    FROM payments p
    JOIN transaction t ON p.payment_id = t.payment_id
    JOIN appointments a ON p.appointment_id = a.appointment_id
    WHERE p.payment_status = 'paid'
    AND a.appointment_date BETWEEN '$startDate' AND '$endDate'";

    $financialResult = $conn->query($financialSql);
    $financialData = $financialResult ? $financialResult->fetch_assoc() : [
        'total_revenue' => 0, 
        'total_transaction' => 0,
        'total_appointments' => 0
    ];
} else {
    $financialData = [
        'total_revenue' => 0, 
        'total_transaction' => 0,
        'total_appointments' => 0
    ];
}

// Services breakdown
$servicesSql = "SELECT 
    s.service_id,
    s.service_name,
    s.price AS service_price,
    COUNT(asr.service_id) AS service_count,
    SUM(s.price) AS service_total
FROM services s
JOIN appointment_services asr ON s.service_id = asr.service_id
JOIN appointments a ON asr.appointment_id = a.appointment_id
JOIN payments p ON a.appointment_id = p.appointment_id
WHERE p.payment_status = 'paid'";

if (!$isDefaultDate) {
    $servicesSql .= " AND a.appointment_date BETWEEN '$startDate' AND '$endDate'";
}

$servicesSql .= " GROUP BY s.service_id ORDER BY service_count DESC";

$servicesResult = $conn->query($servicesSql);
$servicesData = $servicesResult ? $servicesResult->fetch_all(MYSQLI_ASSOC) : [];

// Top performing services
$topServicesSql = "SELECT 
    s.service_id,
    s.service_name,
    COUNT(asr.service_id) AS service_count
FROM services s
JOIN appointment_services asr ON s.service_id = asr.service_id
JOIN appointments a ON asr.appointment_id = a.appointment_id
JOIN payments p ON a.appointment_id = p.appointment_id
WHERE p.payment_status = 'paid'";

if (!$isDefaultDate) {
    $topServicesSql .= " AND a.appointment_date BETWEEN '$startDate' AND '$endDate'";
}

$topServicesSql .= " GROUP BY s.service_id ORDER BY service_count DESC LIMIT 5";

$topServicesResult = $conn->query($topServicesSql);
$topServicesData = $topServicesResult ? $topServicesResult->fetch_all(MYSQLI_ASSOC) : [];

// PDF Generation
if (isset($_POST['download_pdf'])) {
    ob_start();
    ?>
    <h1>Dental Appointment Report</h1>
    <p><strong>Date Range:</strong> <?php echo htmlspecialchars($startDate) . " to " . htmlspecialchars($endDate); ?></p>
    
    <h2>Financial Summary</h2>
    <p><strong>Total Revenue:</strong> ₱<?php echo number_format((float)$financialData['total_revenue'], 2); ?></p>
    <p><strong>Total Transactions:</strong> <?php echo $financialData['total_transaction']; ?></p>
    <p><strong>Total Appointments:</strong> <?php echo $financialData['total_appointments']; ?></p>
    
    <h3>Services Breakdown</h3>
    <table border="1" cellpadding="5" cellspacing="0">
        <thead>
            <tr>
                <th>Service ID</th>
                <th>Service Name</th>
                <th>Price</th>
                <th>Count</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($servicesData): ?>
                <?php foreach ($servicesData as $service): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($service['service_id']); ?></td>
                        <td><?php echo htmlspecialchars($service['service_name']); ?></td>
                        <td>₱<?php echo number_format((float)$service['service_price'], 2); ?></td>
                        <td><?php echo htmlspecialchars($service['service_count']); ?></td>
                        <td>₱<?php echo number_format((float)$service['service_total'], 2); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5">No services rendered in this period.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <h3>Top 5 Services</h3>
    <table border="1" cellpadding="5" cellspacing="0">
        <thead>
            <tr>
                <th>Service ID</th>
                <th>Service Name</th>
                <th>Times Performed</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($topServicesData): ?>
                <?php foreach ($topServicesData as $service): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($service['service_id']); ?></td>
                        <td><?php echo htmlspecialchars($service['service_name']); ?></td>
                        <td><?php echo htmlspecialchars($service['service_count']); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="3">No services data available.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <h2>Dentist Appointments Summary</h2>
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
    $mpdf = new \Mpdf\Mpdf();
    $mpdf->WriteHTML($htmlContent);
    $fileName = 'appointment_report_' . time() . '.pdf';
    $filePath = 'uploads/' . $fileName;
    $mpdf->Output($filePath, 'F');

    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . basename($filePath) . '"');
    header('Content-Length: ' . filesize($filePath));
    readfile($filePath);
    exit();
}

// Print Report
if (isset($_POST['print_report'])) {
    ob_start();
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Dental Clinic Report</title>
        <style>
            body { font-family: Arial; margin: 20px; }
            h1 { color: #333; }
            table { width: 100%; border-collapse: collapse; margin-top: 20px; }
            th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
            th { background-color: #f2f2f2; }
            .summary { display: flex; margin-bottom: 20px; }
            .summary-item { flex: 1; padding: 10px; background: #f8f9fa; margin-right: 10px; }
            @media print {
                .no-print { display: none; }
                body { margin: 0; padding: 10px; }
            }
        </style>
    </head>
    <body>
        <h1>Dental Clinic Report</h1>
        <p><strong>Date Range:</strong> <?php echo htmlspecialchars($startDate) . " to " . htmlspecialchars($endDate); ?></p>
        
        <div class="summary">
            <div class="summary-item">
                <h3>Total Revenue</h3>
                <p>₱<?php echo number_format((float)$financialData['total_revenue'], 2); ?></p>
            </div>
            <div class="summary-item">
                <h3>Total Transactions</h3>
                <p><?php echo $financialData['total_transaction']; ?></p>
            </div>
            <div class="summary-item">
                <h3>Total Appointments</h3>
                <p><?php echo $financialData['total_appointments']; ?></p>
            </div>
        </div>

        <h2>Services Breakdown</h2>
        <table>
            <thead>
                <tr>
                    <th>Service ID</th>
                    <th>Service Name</th>
                    <th>Price</th>
                    <th>Count</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($servicesData): ?>
                    <?php foreach ($servicesData as $service): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($service['service_id']); ?></td>
                            <td><?php echo htmlspecialchars($service['service_name']); ?></td>
                            <td>₱<?php echo number_format((float)$service['service_price'], 2); ?></td>
                            <td><?php echo htmlspecialchars($service['service_count']); ?></td>
                            <td>₱<?php echo number_format((float)$service['service_total'], 2); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5">No services rendered in this period.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <h2>Dentist Appointments</h2>
        <table>
            <thead>
                <tr>
                    <th>Dentist Name</th>
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
                            <td><?php echo htmlspecialchars($row['dentist_name']); ?></td>
                            <td><?php echo htmlspecialchars($row['pending_appointments']); ?></td>
                            <td><?php echo htmlspecialchars($row['completed_appointments']); ?></td>
                            <td><?php echo htmlspecialchars($row['cancelled_appointments']); ?></td>
                            <td><?php echo htmlspecialchars($row['no_show_appointments']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5">No appointments in this period.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <script>window.print();</script>
    </body>
    </html>
    <?php
    echo ob_get_clean();
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - Dental Clinic</title>
    <link rel="stylesheet" type="text/css" href="css/style.css">
  <style>
/* Container */
/* Base Styling */
body {
    font-family: Arial, sans-serif;
    background-color: #f4f4f9;
    margin: 0;
    padding: 0;
    color: #333;
}


.main-content {
    padding: 30px;
    background-color: #ffffff;
    max-width: 1200px;
    margin: 350 auto;
}

/* Headings */
h1, h2, h3 {
    color: #004080;
    margin-bottom: 20px;
}

/* Form Styling */
form {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
    margin-bottom: 30px;
    background-color: #f9f9f9;
    padding: 15px;
    border-radius: 8px;
    border: 1px solid #ddd;
}

form div {
    display: flex;
    flex-direction: column;
    flex: 1;
    min-width: 200px;
}

label {
    margin-bottom: 5px;
    font-weight: bold;
}

input[type="date"] {
    padding: 8px;
    border: 1px solid #ccc;
    border-radius: 4px;
}

button {
    background-color: #0077cc;
    color: white;
    padding: 10px 20px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-weight: bold;
    transition: background-color 0.3s ease;
    margin-top: 25px;
}

button:hover {
    background-color: #005fa3;
}

/* Summary Cards */
.summary-cards {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
    margin-bottom: 30px;
}

.summary-card {
    background-color: #eaf4ff;
    border-left: 5px solid #0077cc;
    padding: 20px;
    border-radius: 8px;
    flex: 1;
    min-width: 250px;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
}

.summary-card h3 {
    margin-bottom: 10px;
    font-size: 18px;
    color: #004080;
}

.summary-card p {
    font-size: 24px;
    font-weight: bold;
    color: #2b4d70;
}

/* Table Styling */
table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
    background-color: white;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.03);
}

table thead {
    background-color:#272aff;
    color: white;
}

table th,
table td {
    padding: 12px 15px;
    border: 1px solid #ddd;
    text-align: left;
}

table tbody tr:hover {
    background-color: #f1f9ff;
}

/* Utilities */
a {
    color: #0077cc;
    text-decoration: none;
}

a:hover {
    text-decoration: underline;
}

/* Hide elements when printing */
@media print {
    .no-print {
        display: none;
    }

    body {
        background-color: white;
        color: black;
    }

    .main-content {
        padding: 0;
        box-shadow: none;
    }

    table, .summary-cards, form {
        page-break-inside: avoid;
    }
}

/* Responsive */
@media screen and (max-width: 768px) {
    .summary-cards {
        flex-direction: column;
    }

    form {
        flex-direction: column;
    }

    button {
        width: 100%;
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

        <main class="main-content">
            <h1>Reports</h1>
            
            <form method="POST">
                <div>
                    <label for="start_date">Start Date:</label>
                    <input type="date" name="start_date" id="start_date" value="<?php echo htmlspecialchars($startDate); ?>" required>
                </div>
                <div>
                    <label for="end_date">End Date:</label>
                    <input type="date" name="end_date" id="end_date" value="<?php echo htmlspecialchars($endDate); ?>" required>
                </div>
                <button type="submit">Filter</button>
                <button type="submit" name="download_pdf">Download PDF</button>
                <button type="submit" name="print_report" class="no-print">Print Report</button>
            </form>

            <div class="summary-cards">
                <div class="summary-card">
                    <h3>Total Revenue</h3>
                    <p>₱<?php echo number_format((float)$financialData['total_revenue'], 2); ?></p>
                </div>
                <div class="summary-card">
                    <h3>Total Transactions</h3>
                    <p><?php echo $financialData['total_transaction']; ?></p>
                </div>
                <div class="summary-card">
                    <h3>Total Appointments</h3>
                    <p><?php echo $financialData['total_appointments']; ?></p>
                </div>
            </div>

            <h2>Services Breakdown</h2>
            <table>
                <thead>
                    <tr>
                        <th>Service ID</th>
                        <th>Service Name</th>
                        <th>Price</th>
                        <th>Count</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($servicesData): ?>
                        <?php foreach ($servicesData as $service): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($service['service_id']); ?></td>
                                <td><?php echo htmlspecialchars($service['service_name']); ?></td>
                                <td>₱<?php echo number_format((float)$service['service_price'], 2); ?></td>
                                <td><?php echo htmlspecialchars($service['service_count']); ?></td>
                                <td>₱<?php echo number_format((float)$service['service_total'], 2); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5">No services data available.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <h2>Dentist Appointments</h2>
            <table>
                <thead>
                    <tr>
                        <th>Dentist Name</th>
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
                                <td><?php echo htmlspecialchars($row['dentist_name']); ?></td>
                                <td><?php echo htmlspecialchars($row['pending_appointments']); ?></td>
                                <td><?php echo htmlspecialchars($row['completed_appointments']); ?></td>
                                <td><?php echo htmlspecialchars($row['cancelled_appointments']); ?></td>
                                <td><?php echo htmlspecialchars($row['no_show_appointments']); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5">No appointments data available.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </main>
    </div>
</body>
</html>
<?php
$conn->close();
?>