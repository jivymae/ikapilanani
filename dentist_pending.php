<?php
session_start();
include 'db_config.php';

// Redirect non-dentists to login page
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'dentist') {
    header('Location: login.php');
    exit();
}

// Fetch dentist details
$dentist_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT username, email FROM users WHERE user_id = ? AND role = 'dentist'");
$stmt->bind_param("i", $dentist_id);
$stmt->execute();
$stmt->bind_result($username, $email);
$stmt->fetch();
$stmt->close();

// Fetch all appointments along with patient details
$appointments = [];

// Handle search query and date filter
$search_query = isset($_GET['search_query']) ? $_GET['search_query'] : '';
$filter_date = isset($_GET['filter_date']) ? $_GET['filter_date'] : '';

// Base SQL query
$sql = "
    SELECT a.appointment_id, a.patient_id, a.dentist_id, a.appointment_date, a.appointment_time, 
           a.appointment_status, a.reason, a.is_emergency, a.appointment_created_at,
           p.First_Name, p.Last_Name, p.Email,
           GROUP_CONCAT(s.service_name SEPARATOR ', ') AS services
    FROM appointments a
    JOIN patients p ON a.patient_id = p.Patient_ID
    LEFT JOIN appointment_services aps ON a.appointment_id = aps.appointment_id
    LEFT JOIN services s ON aps.service_id = s.service_id
    WHERE a.dentist_id = ? AND a.appointment_status = 'pending' 
";

// Append conditions for search query and date filter
if (!empty($search_query)) {
    $sql .= " AND a.appointment_id LIKE ?";
}
if (!empty($filter_date)) {
    $sql .= " AND a.appointment_date = ?";
}

$sql .= " GROUP BY a.appointment_id ORDER BY a.appointment_date DESC, a.appointment_time DESC, a.appointment_id DESC";

// Prepare the statement
$stmt = $conn->prepare($sql);

// Bind parameters dynamically based on filters
if (!empty($search_query) && !empty($filter_date)) {
    $search_query = "$search_query";
    $stmt->bind_param("iss", $dentist_id, $search_query, $filter_date);
} elseif (!empty($search_query)) {
    $search_query = "$search_query";
    $stmt->bind_param("is", $dentist_id, $search_query);
} elseif (!empty($filter_date)) {
    $stmt->bind_param("is", $dentist_id, $filter_date);
} else {
    $stmt->bind_param("i", $dentist_id);
}

$stmt->execute();
$stmt->bind_result(
    $appointment_id, $patient_id, $dentist_id, $appointment_date, $appointment_time, 
    $appointment_status, $reason, $is_emergency, $appointment_created_at, 
    $first_name, $last_name, $email, $services
);

$appointments = [];
while ($stmt->fetch()) {
    $appointments[] = [
        'id' => $appointment_id,
        'patient_id' => $patient_id,
        'dentist_id' => $dentist_id,
        'date' => $appointment_date,
        'time' => $appointment_time,
        'status' => $appointment_status,
        'reason' => $reason,
        'is_emergency' => $is_emergency,
        'created_at' => $appointment_created_at,
        'first_name' => $first_name,
        'last_name' => $last_name,
        'email' => $email,
        'services' => $services
    ];
}

$stmt->close();
$conn->close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>All Appointments</title>
    <link rel="stylesheet" type="text/css" href="css/dentist.css">
</head>
<style>
/* Basic reset */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: Arial, sans-serif;
    background-color: #f4f4f4;
    color: #333;
    display: flex;
    height: 100vh;
}

/* Sidebar styling */
.sidebar {
    width: 250px; /* Sidebar width */
    background-color: #b19cd9;
    color: white;
    display: flex;
    flex-direction: column;
    align-items: center;
    padding-top: 20px;
    position: fixed; /* Fix sidebar to the left */
    height: 100%; /* Make sidebar full height */
}

/* Sidebar logo styling */
.sidebar .logo {
    text-align: center;
    margin-bottom: 30px;
}

.sidebar .logo img {
    width: 80px;
    height: auto;
}

.sidebar .logo h1 {
    font-size: 24px;
    font-weight: bold;
    margin-top: 10px;
}

/* Navbar links in the sidebar */
.navbar {
    width: 100%;
    text-align: center;
    margin-top: 20px;
}

.navbar h2 {
    font-size: 18px;
    margin-bottom: 20px;
}

.navbar a {
    display: block;
    color: white;
    text-decoration: none;
    padding: 12px;
    margin: 5px 0;
    font-size: 16px;
    transition: background-color 0.3s ease;
}

.navbar a:hover,
.navbar a.active {
    background-color: #4c2882;
}

.navbar a.active {
    font-weight: bold;
}

/* Main content styling */

/* Main content styling */
.main-content {
    margin-left: 250px; /* Offset the content by the sidebar width */
    padding: 20px;
    background-color: #f4f4f4; /* Light background to ensure readability */
    min-height: 100vh; /* Ensure content takes full height */
    box-sizing: border-box;
}

/* Header styling */
.main-content h1 {
    font-size: 28px;
    font-weight: bold;
    color: #4c2882; /* Darker shade of the sidebar color */
    margin-bottom: 20px;
}

/* Navigation bar for appointments */
.app-links {
    list-style-type: none;
    margin-bottom: 20px;
    padding: 0;
}

.app-links li {
    display: inline-block;
    margin-right: 20px;
}

.app-links a {
    text-decoration: none;
    font-size: 18px;
    color: #4c2882; /* Matching link color to the sidebar */
    padding: 8px 15px;
    border-radius: 5px;
    transition: background-color 0.3s ease;
}

.app-links a:hover, .app-links a.active {
    background-color: #b19cd9; /* Use sidebar color for hover */
    color: white;
}

.app-links a.active {
    font-weight: bold;
}

/* Appointment table styling */
/* Appointment table styling */
.appointment-list table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 30px;
    background-color: #ffffff; /* Light background for table */
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.1); /* Subtle shadow for better contrast */
}

.appointment-list th, .appointment-list td {
    padding: 15px;
    text-align: left;
    border: 1px solid #ddd; /* Border for cells */
    font-size: 14px; /* Uniform font size for table */
}

.appointment-list th {
    background-color: #4c2882; /* Sidebar color */
    color: white;
    font-size: 16px;
    font-weight: bold;
}

.appointment-list td {
    background-color: #f9f9f9; /* Light background for even rows */
    color: #333; /* Darker text for better readability */
}

/* Hover effect for table rows */
.appointment-list tr:hover {
    background-color: #f1f1f1; /* Light hover effect */
}

/* Table actions styling */
.appointment-list td a {
    display: inline-block;
    padding: 8px 15px;
    background-color: #007bff;
    color: white;
    text-decoration: none;
    border-radius: 5px;
    font-size: 14px;
    transition: background-color 0.3s ease;
}

.appointment-list td a:hover {
    background-color: #0056b3; /* Darker blue on hover */
}


/* Form controls inside table */
.input-container {
    margin-top: 10px;
    display: flex;
    flex-direction: column;
}

textarea {
    width: 100%;
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 5px;
    margin-bottom: 10px;
    font-size: 14px;
}

button {
    background-color: #4c2882;
    color: white;
    padding: 10px 15px;
    border: none;
    border-radius: 5px;
    font-size: 14px;
    cursor: pointer;
    transition: background-color 0.3s ease;
}

button:hover {
    background-color: #3a1f6b; /* Slightly darker shade on hover */
}

/* Checkbox label styling */
label {
    font-size: 14px;
    color: #333;
}

input[type="checkbox"] {
    margin-right: 5px;
}

/* Responsive Design for smaller screens */
@media (max-width: 768px) {
    .main-content {
        margin-left: 0; /* Remove margin on smaller screens */
        padding: 15px;
    }

    .app-links li {
        display: block;
        margin-bottom: 10px;
    }

    .appointment-list th, .appointment-list td {
        font-size: 12px;
    }

    button {
        font-size: 12px;
    }

    .input-container {
        flex-direction: row;
    }
}

/* Responsive design for smaller screens */
@media (max-width: 768px) {
    body {
        flex-direction: column; /* Stack the sidebar and content vertically on small screens */
    }

    .sidebar {
        width: 100%; /* Sidebar will span the full width */
        height: auto; /* Sidebar will be auto-sized on smaller screens */
        position: relative; /* Change sidebar position to relative */
    }

    .main-content {
        margin-left: 0; /* Remove margin when sidebar is on top */
        padding: 15px;
    }

    .navbar a {
        font-size: 14px;
    }
}


 </style>
</head>
<body>
<div class="sidebar">
        <nav class="navbar">
        <div class="logo">
            <img src="images/lads.png" alt="Dental Clinic Logo">
            <h1>LAD DCAMS</h1>
        </div>
        
        <nav>
        
        <a href="dentist_dashboard.php">Dashboard</a>
        <a href="dentist_profile.php">Profile</a>
        <a href="dentist_patient_appointments.php" class="active">Appointments</a>
        <a href="dentist_patient_records.php">Patient Records</a>
        <a href="dentist_message.php">Messages</a>
        <a href="logout.php">Logout</a>

    </div>
    <div class="main-content">
        <h1>Patient Appointments</h1>
        <nav class="app">
            <ul class="app-links">
                <li><a href="dentist_patient_appointments.php">Upcoming Appointment</a></li>
                <li><a href="dentist_pending.php" class="active">Pending</a></li>
                <li><a href="dentist_cancelled.php">Cancelled</a></li>
                <li><a href="dentist_completed.php">Done</a></li>
                <li><a href="dentist_no_show_appointments.php">No Show</a></li>
            </ul>
        </nav>
    <h1>All Appointments</h1>
    <form method="GET" action="" style="margin-bottom: 20px;">
    <input type="text" name="search_query" placeholder="Search by Appointment ID" 
           value="<?php echo htmlspecialchars($search_query); ?>" 
           style="padding: 8px; border-radius: 5px; border: 1px solid #ddd; width: 200px;">
    <input type="date" name="filter_date" value="<?php echo htmlspecialchars($filter_date); ?>" 
           style="padding: 8px; border-radius: 5px; border: 1px solid #ddd; width: 200px;">
    <button type="submit" style="background-color: #4c2882; color: white; border: none; padding: 8px 12px; border-radius: 5px;">Filter</button>
</form>

    <div class="appointment-list">
        <table>
            <thead>
                <tr>
                    <th>Appointment ID</th>
                    <th>Patient Name</th>
                    <th>Patient Email</th>
                    <th>Appointment Date</th>
                    <th>Appointment Time</th>
                    <th>Status</th>
                    <th>Reason</th>
                    <th>Emergency</th>
                    <th>Services</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($appointments as $appointment): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($appointment['id'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($appointment['first_name'] . ' ' . $appointment['last_name'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($appointment['email'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($appointment['date'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($appointment['time'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($appointment['status'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($appointment['reason'] ?? ''); ?></td>
                        <td><?php echo $appointment['is_emergency'] ? 'Yes' : 'No'; ?></td>
                        <td><?php echo htmlspecialchars($appointment['services'] ?? ''); ?></td>
                        <td>
                            <a href="dentist_pending_view.php?appointment_id=<?php echo htmlspecialchars($appointment['id']); ?>" 
                               style="padding: 5px 10px; background-color: #007bff; color: white; text-decoration: none; border-radius: 5px;">
                               View
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
