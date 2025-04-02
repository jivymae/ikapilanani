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

// Check if there is a search query, status filter, and date filter
$search = isset($_GET['search']) ? $_GET['search'] : '';
$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';
$dateFilter = isset($_GET['date']) ? $_GET['date'] : '';
$fromDate = isset($_GET['from_date']) ? $_GET['from_date'] : '';
$toDate = isset($_GET['to_date']) ? $_GET['to_date'] : '';
// Modify the SQL query to filter based on search, status, and date
$sql = "SELECT appointments.appointment_id, appointments.appointment_date, appointments.appointment_time, 
               appointments.appointment_status, patients.First_Name AS patient_first_name, patients.Last_Name AS patient_last_name 
        FROM appointments 
        JOIN patients ON appointments.patient_id = patients.Patient_ID";  // Use the correct table and column names

// Add conditions to search by appointment_id or patient name
if (!empty($search)) {
    $search = $conn->real_escape_string($search); // Prevent SQL injection
    $sql .= " AND (appointments.appointment_id LIKE '%$search%' 
                OR CONCAT(patients.First_Name, ' ', patients.Last_Name) LIKE '%$search%')";
}

// Add status filter condition
if (!empty($statusFilter)) {
    $statusFilter = $conn->real_escape_string($statusFilter); // Prevent SQL injection
    $sql .= " AND appointments.appointment_status = '$statusFilter'";
}

// Add date filter condition
if (!empty($dateFilter)) {
    $dateFilter = $conn->real_escape_string($dateFilter); // Prevent SQL injection
    $sql .= " AND appointments.appointment_date = '$dateFilter'";
}
if (!empty($fromDate) && !empty($toDate)) {
    $fromDate = $conn->real_escape_string($fromDate);
    $toDate = $conn->real_escape_string($toDate);
    $sql .= " AND appointments.appointment_date BETWEEN '$fromDate' AND '$toDate'";
}
$result = $conn->query($sql);
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointments - Dental Clinic Management System</title>
    <link rel="stylesheet" type="text/css" href="css/style.css">
</head>
<style>
/* General Styles */
body {
    font-family: Arial, sans-serif;
    background-color: #f4f4f9;
    color: #333;
    margin: 0;
    padding: 0;
}

.container {
    display: flex;
    min-height: 100vh;
}

/* Main Content */
.main-content {
    flex-grow: 1;
    margin-left: 270px; /* Sidebar width */
    padding: 30px;
    background-color: #fff;
}

.main-content h1 {
    font-size: 32px;
    margin-bottom: 30px;
    color: #333;
}

table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
}

table th, table td {
    padding: 12px;
    border: 1px solid #ccc;
    text-align: left;
}

table th {
    background-color: #0066cc;
    color: white;
}

table td {
    background-color: #fafafa;
}

table tr:hover {
    background-color: #f1f1f1;
}

a {
    text-decoration: none;
    color: #0066cc;
}

a:hover {
    color: #005bb5;
}

/* Action Links */
a {
    padding: 5px;
}

a:active {
    color: #004b8d;
}

/* Alert Message */
.alert {
    background-color: #f8d7da;
    color: #721c24;
    padding: 10px;
    border-radius: 5px;
    margin-bottom: 20px;
}
.main-content a {
    display: inline-block; /* Ensures the link looks like a block for better clickability */
    padding: 10px 20px; /* Adds spacing inside the button */
    margin: 10px 5px; /* Adds space between buttons */
    background-color: #3498db; /* Blue background */
    color: white; /* White text */
    text-decoration: none; /* Removes underline */
    font-size: 16px; /* Adjusts text size */
    font-weight: bold; /* Makes the text bold */
    border-radius: 5px; /* Adds rounded corners */
    border: none; /* Removes border */
    transition: background-color 0.3s ease, transform 0.2s ease; /* Adds smooth hover effect */
    cursor: pointer; /* Adds a pointer cursor */
    text-align: center; /* Centers the text inside the button */
}

.main-content a:hover {
    background-color: #2874a6; /* Darker blue on hover */
    transform: scale(1.05); /* Slightly enlarges button on hover */
    color: #fff; /* Ensures text remains white */
}

.main-content a:active {
    transform: scale(0.95); /* Makes the button shrink slightly when clicked */
}



/* Search and Filter Form */
.search-form {
    display: flex;
    gap: 10px;
    align-items: center;
    margin-bottom: 20px;
    flex-wrap: wrap; /* Allows elements to wrap to the next line if they overflow */
}

.search-form input[type="text"], .search-form select, .search-form input[type="date"] {
    padding: 10px;
    border: 1px solid #ccc;
    border-radius: 5px;
    font-size: 14px;
    width: auto; /* Set width to auto to prevent stretching */
    max-width: 300px; /* Optional: Limits max width of inputs */
}

.search-form button {
    background-color: #3498db;
    color: white;
    font-size: 14px;
    font-weight: bold;
    padding: 10px 20px;
    border-radius: 5px;
    margin-top: -0.2%;
    border: none;
    cursor: pointer;
    transition: background-color 0.3s ease, transform 0.2s ease;
}

.search-form button:hover {
    background-color: #2874a6;
    transform: scale(1.05);
}

.search-form button:active {
    transform: scale(0.95);
}

/* Responsive Fix for Small Screens */
@media (max-width: 768px) {
    .search-form {
        flex-direction: column;
        align-items: flex-start;
    }

    .search-form input[type="text"], .search-form select, .search-form input[type="date"], .search-form button {
        width: 100%;
    }
}

/* Action Buttons */
button {
    background-color: #3498db;
    color: white;
    font-size: 16px;
    font-weight: bold;
    padding: 10px 20px;
    border-radius: 5px;
    border: none;
    cursor: pointer;
    transition: background-color 0.3s ease, transform 0.2s ease;
    margin-top: 15px;
    margin-right: 10px;
}

button:hover {
    background-color: #2874a6;
    transform: scale(1.05);
}

button:active {
    transform: scale(0.95);
}

/* Table Styles */
table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
}

table th, table td {
    padding: 12px;
    border: 1px solid #ccc;
    text-align: left;
}

table th {
    background-color: #0066cc;
    color: white;
}

table td {
    background-color: #fafafa;
}

table tr:hover {
    background-color: #f1f1f1;
}

a {
    text-decoration: none;
    color: #0066cc;
}

a:hover {
    color: #005bb5;
}

a:active {
    color: #004b8d;
}

/* Alert Message */
.alert {
    background-color: #f8d7da;
    color: #721c24;
    padding: 10px;
    border-radius: 5px;
    margin-bottom: 20px;
}

/* Responsive Design */
@media (max-width: 768px) {
    .container {
        flex-direction: column;
    }

    .sidebar {
        width: 100%;
        height: auto;
        position: static;
    }

    .main-content {
        margin-left: 0;
        padding: 20px;
    }

    table th, table td {
        font-size: 14px;
    }

    .search-form {
        flex-direction: column;
        align-items: flex-start;
    }

    .search-form input[type="text"], .search-form select, .search-form input[type="date"] {
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
                <li><a href="admin_dashboard.php" class="<?php echo $page == 'admin_dashboard.php' ? 'active' : ''; ?>">Dashboard</a></li>
                <li><a href="patients.php">Patients</a></li>
                <li><a href="dentists.php">Dentists</a></li>
                <li><a href="appointments.php" class="<?php echo $page == 'appointments.php' ? 'active' : ''; ?>">Appointments</a></li>
                <li><a href="admin_add_services">Add Services</a></li>
                <li><a href="reports.php">Reports</a></li>
                <li><a href="admin_settings.php">Settings</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <section id="appointments">
                
                <h1>Appointments</h1>

                <form method="GET" action="" class="search-form">
                    <input type="text" name="search" placeholder="Search by Appointment ID or Patient Name" value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                    <button type="submit">Search</button>

                    <!-- Appointment Status Filter -->
                    <select name="status">
                        <option value="">All Statuses</option>
                        <option value="pending" <?php echo $statusFilter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="completed" <?php echo $statusFilter == 'completed' ? 'selected' : ''; ?>>Done</option>
                        <option value="no_show" <?php echo $statusFilter == 'no_show' ? 'selected' : ''; ?>>No Show</option>
                        <option value="cancelled" <?php echo $statusFilter == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    </select>

                    <!-- Appointment Date Filter -->
                    <input type="date" name="from_date" value="<?php echo isset($_GET['from_date']) ? htmlspecialchars($_GET['from_date']) : ''; ?>">
<input type="date" name="to_date" value="<?php echo isset($_GET['to_date']) ? htmlspecialchars($_GET['to_date']) : ''; ?>">

                    <button type="submit">Filter</button>
                </form>
                <button id="downloadCsv" onclick="downloadCsv()">Download CSV</button>
                <button id="viewPdf" onclick="viewPdf()">View PDF</button>


                <script>
    function downloadCsv() {
        window.location.href = 'download_appointments_csv.php?search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($statusFilter); ?>&date=<?php echo urlencode($dateFilter); ?>';
    }

    function viewPdf() {
    window.open('download_appointments_pdf.php?search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($statusFilter); ?>&date=<?php echo urlencode($dateFilter); ?>&from_date=<?php echo urlencode($fromDate); ?>&to_date=<?php echo urlencode($toDate); ?>', '_blank');
}

</script>


                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Appointment ID</th>
                            <th>Time</th>
                            <th>Patient Name</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <?php echo htmlspecialchars($row['appointment_date']); ?> 
                                </td>
                                <td>(ID: <?php echo htmlspecialchars($row['appointment_id']); ?>)</td>
                                <td><?php echo htmlspecialchars($row['appointment_time']); ?></td>
                                <td><?php echo htmlspecialchars($row['patient_first_name'] . ' ' . $row['patient_last_name']); ?></td>
                                <td>
    <?php 
    // Check if the status is 'completed' and change it to 'done'
    $status = $row['appointment_status'] == 'completed' ? 'done' : $row['appointment_status'];
    echo htmlspecialchars($status); 
    ?>
</td>

                                <td>
                                    <a href="admin_view_appointment.php?id=<?php echo $row['appointment_id']; ?>">View</a> |
                                    <a href="edit_appointment.php?id=<?php echo $row['appointment_id']; ?>">Edit</a> |
                                    <a href="cancel_appointment.php?id=<?php echo $row['appointment_id']; ?>" onclick="return confirm('Are you sure you want to cancel this appointment?');">Cancel</a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6">No appointments found.</td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </section>
        </main>
    </div>
</body>
</html>
