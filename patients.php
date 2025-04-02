<?php
session_start();
include 'db_config.php';



$error = '';
$success = '';
$search = '';
$startDate = '';
$endDate = '';

// Handle search and date filtering
// Handle search and date filtering
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$startDate = isset($_GET['start_date']) ? trim($_GET['start_date']) : '';
$endDate = isset($_GET['end_date']) ? trim($_GET['end_date']) : '';

// Prepare SQL query to fetch patients
$query = "SELECT Patient_ID, Last_Name, First_Name, Created_At FROM patients";

$conditions = [];
$params = [];
$types = '';  // Initialize $types

if ($search) {
    // Search by full name (last name and first name), first name, last name, or contact information
    $conditions[] = "(CONCAT(Last_Name, ' ', First_Name) LIKE ? OR First_Name LIKE ? OR Last_Name LIKE ? OR Contact_Information LIKE ?)";
    $params[] = "%$search%";  // Full name match
    $params[] = "%$search%";  // First name match
    $params[] = "%$search%";  // Last name match
    $params[] = "%$search%";  // Contact information match
    $types .= 'ssss';         // Bind four string parameters
}

if ($startDate) {
    $conditions[] = "Created_At >= ?";
    $params[] = $startDate . ' 00:00:00'; // Ensure the start date includes time
    $types .= 's';
}

if ($endDate) {
    $conditions[] = "Created_At <= ?";
    $params[] = $endDate . ' 23:59:59'; // Ensure the end date includes time
    $types .= 's';
}

if ($conditions) {
    $query .= " WHERE " . implode(" AND ", $conditions);
}

$query .= " ORDER BY Created_At ASC"; // Ascending order

$stmt = $conn->prepare($query);

if ($stmt === false) {
    $error = 'Database prepare failed: ' . $conn->error;
} else {
    if ($params) {
        $stmt->bind_param($types, ...$params);
    }

    if ($stmt->execute()) {
        $result = $stmt->get_result();
    } else {
        $error = 'Database error: ' . $stmt->error;
    }

    $stmt->close();
}

// Close the connection when done
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patients - Dental Clinic Management System</title>
    <link rel="stylesheet" type="text/css" href="css/style.css">
</head>

<style>
/* Your existing CSS styles here */

/* styles.css */
body {
    font-family: Arial, sans-serif;
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

.container {
    display: flex;
    min-height: 100vh;
}

.sidebar {
    width: 250px;
    background: linear-gradient(216deg, rgba(89,237,255,1) 0%, rgba(45,116,255,1) 100%);
    color: #ecf0f1;
    padding: 15px;
    position: fixed;
    height: 100%;
    overflow-y: auto;
    z-index: 1000;
}

.sidebar h2.logo {
    display: flex;
    align-items: center;
    margin-bottom: 20px;
}

.sidebar h2.logo img {
    max-width: 80px; /* Adjust size as needed */
    height: auto;
    margin-right: 10px;
}

.sidebar h1 {
    font-size: 24px;
    margin: 0;
    color: #ecf0f1;
    font-weight: bold;
}

.sidebar ul {
    list-style-type: none;
    padding: 0;
}

.sidebar ul li {
    margin: 5px 0;
}

.sidebar ul li a {
    color: #ecf0f1;
    text-decoration: none;
    font-size: 18px;
    display: block; /* Ensure full width clickable area */
    padding: 10px;
    border-radius: 5px; /* Add rounded corners */
}

.sidebar ul li a:hover,
.sidebar ul li a.active {
    background-color: #272aff; /* Darker shade for active or hover state */
    color: #ecf0f1;
    text-decoration: none;
}

.sidebar ul li:last-child {
    margin-top: auto; /* Pushes Logout to the bottom */
}

.main-content {
    margin-left: 280px; /* Width of the sidebar */
    padding: 20px;
    width: calc(100% - 250px); /* Remaining width after sidebar */
    background-color: #f9f9f9;
}

h1 {
    font-size: 24px;
    color: #2c3e50;
}


table a {
    display: inline-block; /* Makes the link look like a button */
    padding: 5px 10px; /* Adds some inner spacing */
    background-color: #3498db; /* Blue background */
    color: white; /* White text color */
    text-decoration: none; /* Removes underline */
    font-size: 14px; /* Adjusts text size */
    font-weight: bold; /* Makes the text bold */
    border-radius: 3px; /* Slightly rounded corners */
    transition: background-color 0.3s ease, transform 0.2s ease; /* Adds hover and click effects */
    text-align: center; /* Centers text within the button */
    cursor: pointer; /* Changes the cursor to pointer */
    border: none; /* Removes border */
}

table a:hover {
    background-color: #2874a6; /* Darker blue on hover */
    transform: scale(1.05); /* Slightly enlarges button on hover */
}

table a:active {
    transform: scale(0.95); /* Shrinks button slightly on click */
}

table td {
    text-align: center; /* Centers the button within the table cell */
}
table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 20px;
}

table, th, td {
    border: 1px solid #ddd;
}

th, td {
    padding: 10px;
    text-align: left;
}

th {
    background: #f2f2f2;
}

.form-group {
    margin-bottom: 15px;
}

label {
    display: block;
    margin-bottom: 5px;
}

input, textarea {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

button {
    padding: 10px 15px;
    background: #007bff;
    border: none;
    color: #fff;
    font-size: 16px;
    border-radius: 4px;
    cursor: pointer;
}

button:hover {
    background: #0056b3;
}

.error {
    color: red;
    margin-bottom: 10px;
}

.success {
    color: green;
    margin-bottom: 10px;
}
/* Additional CSS for form and table */
form {
    margin-bottom: 20px;
}

input[type="text"], input[type="date"] {
    width: calc(100% - 22px); /* Adjusting width for padding */
    padding: 10px;
}

button[type="submit"] {
    background-color: #007bff;
    color: #fff;
    border: none;
    padding: 10px 20px;
    border-radius: 5px;
    font-size: 16px;
    cursor: pointer;
}

button[type="submit"]:hover {
    background-color: #0056b3;
}

table th, table td {
    text-align: left;
}

.table {
    margin-top: 20px;
    border-collapse: collapse;
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
            <?php
            $page = basename($_SERVER['PHP_SELF']); // Get the current page
            ?>
            <li><a href="admin_dashboard.php" class="<?php echo $page == 'admin_dashboard.php' ? 'active' : ''; ?>">Dashboard</a></li>
            <li><a href="patients.php" class="<?php echo $page == 'patients.php' ? 'active' : ''; ?>">Patients</a></li>
            <li><a href="dentists.php" class="<?php echo $page == 'dentists.php' ? 'active' : ''; ?>">Dentists</a></li>
            <li><a href="appointments.php" class="<?php echo $page == 'appointments.php' ? 'active' : ''; ?>">Appointments</a></li>
            <li><a href="admin_add_services.php" class="<?php echo $page == 'admin_add_services.php' ? 'active' : ''; ?>">Add Services</a></li>
            <li><a href="reports.php" class="<?php echo $page == 'reports.php' ? 'active' : ''; ?>">Reports</a></li>
            <li><a href="admin_settings.php" class="<?php echo $page == 'settings.php' ? 'active' : ''; ?>">Settings</a></li>
            <li><a href="logout.php">Logout</a></li>
        </ul>
    </aside>

    <!-- Main Content -->
    <main class="main-content">
        <h1>Patients</h1>
        <a href="admin_register_patient.php"><button type="button">Add New Patients</button></a>

        <!-- Display error or success messages -->
        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <!-- Search and Date Filter Form -->
        <form method="GET" action="">
            <div class="form-group">
                <label for="search">Search:</label>
                <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="form-group">
                <label for="start_date">Start Date:</label>
                <input type="date" id="start_date" name="start_date" value="<?php echo htmlspecialchars($startDate); ?>">
            </div>
            <div class="form-group">
                <label for="end_date">End Date:</label>
                <input type="date" id="end_date" name="end_date" value="<?php echo htmlspecialchars($endDate); ?>">
            </div>
            <button type="submit">Filter</button>
        </form>

        <!-- Patient List -->
        <h2>Registered Patients</h2>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Last Name</th>
                    <th>First Name</th>
                    <th>Date Added</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($result): ?>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['Patient_ID']); ?></td>
                            <td><?php echo htmlspecialchars($row['Last_Name']); ?></td>
                            <td><?php echo htmlspecialchars($row['First_Name']); ?></td>
                            <td><?php echo htmlspecialchars(date('Y-m-d', strtotime($row['Created_At']))); ?></td>
                            <td><a href="patient_detail.php?patient_id=<?php echo $row['Patient_ID']; ?>">View Details</a>

                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5">No patients found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </main>
</div>
</body>
</html>
