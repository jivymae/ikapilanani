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


body {
    font-family: 'Arial', sans-serif;
    background-color: #f4f4f4;
    margin: 0;
    padding: 0;
    color: #333;
}



/* Main Content */


.main-content h1,
.main-content h2 {
    color: #004080;
    margin-bottom: 20px;
}

/* Button */
button {
    background-color: #0077cc;
    color: white;
    padding: 10px 20px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-weight: bold;
    transition: background-color 0.3s ease;
}

button:hover {
    background-color: #005fa3;
}

a button {
    text-decoration: none;
}

/* Messages */
.error {
    background-color: #ffe0e0;
    color: #a94442;
    padding: 10px;
    margin: 15px 0;
    border: 1px solid #f5c6cb;
    border-radius: 4px;
}

.success {
    background-color: #e0ffe0;
    color: #2e7d32;
    padding: 10px;
    margin: 15px 0;
    border: 1px solid #c3e6cb;
    border-radius: 4px;
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
    border: 1px solid #e0e0e0;
}

.form-group {
    flex: 1;
    min-width: 200px;
    display: flex;
    flex-direction: column;
}

label {
    margin-bottom: 5px;
    font-weight: 600;
}

input[type="text"],
input[type="date"] {
    padding: 8px;
    border: 1px solid #ccc;
    border-radius: 4px;
}

/* Table Styling */
table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
}

table thead {
    background-color: #004080;
    color: white;
}

table th,
table td {
    padding: 12px 15px;
    border: 1px solid #ddd;
    text-align: left;
}

table tbody tr:hover {
    background-color: #eef7ff;
}

a {
    color: #0077cc;
    text-decoration: none;
}

a:hover {
    text-decoration: underline;
}

/* Responsive (optional) */
@media screen and (max-width: 768px) {
    .container {
        flex-direction: column;
    }

    .sidebar {
        width: 100%;
        flex-direction: row;
        overflow-x: auto;
    }

    .sidebar ul {
        display: flex;
        flex-direction: row;
        gap: 10px;
    }

    .sidebar ul li {
        margin: 0;
    }

    .main-content {
        padding: 20px;
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
