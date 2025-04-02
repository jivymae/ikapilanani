<?php
session_start();
include 'db_config.php';

// Redirect non-dentists to login page
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'dentist') {
    header('Location: login.php');
    exit();
}

// Fetch dentist ID from session
$dentist_id = $_SESSION['user_id'];

// Initialize the array for appointments
$appointments = [];

// Get the current date
date_default_timezone_set('Asia/Manila');  // Set timezone to your country
$date = date('Y-m-d'); // Current date (today)

// Handle search query if it exists
$search_query = '';
if (isset($_POST['search'])) {
    $search_query = $_POST['search'];
}

// Handle the update status form submission
if (isset($_POST['update_status'])) {
    $appointment_id = $_POST['appointment_id'];
    $new_status = $_POST['appointment_status'];

    // Update the status of the appointment in the database
    $update_sql = "
        UPDATE appointments
        SET appointment_status = ?
        WHERE appointment_id = ? AND dentist_id = ?
    ";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("sii", $new_status, $appointment_id, $dentist_id);
    
    if ($update_stmt->execute()) {
        // If status is updated to "completed", insert into dental history
        if ($new_status === 'completed') {
            // Fetch the necessary information for the dental history record
            $history_sql = "
                SELECT a.patient_id, a.appointment_date, a.reason, 
                       GROUP_CONCAT(s.service_name ORDER BY s.service_name SEPARATOR ', ') AS previous_procedures
                FROM appointments a
                LEFT JOIN appointment_services as ap_s ON a.appointment_id = ap_s.appointment_id
                LEFT JOIN services s ON ap_s.service_id = s.service_id
                WHERE a.appointment_id = ?
                GROUP BY a.appointment_id
            ";
            $history_stmt = $conn->prepare($history_sql);
            $history_stmt->bind_param("i", $appointment_id);
            $history_stmt->execute();
            $history_stmt->bind_result($patient_id, $appointment_date, $reason, $previous_procedures);
            $history_stmt->fetch();
            $history_stmt->close();

            // Insert the fetched data into the dental_history table
            $insert_history_sql = "
                INSERT INTO dental_history (Patient_ID, Previous_Procedures, Last_Dental_Visit, Reason_for_Visit, created_at)
                VALUES (?, ?, ?, ?, NOW())
            ";
            $insert_history_stmt = $conn->prepare($insert_history_sql);
            $insert_history_stmt->bind_param("isss", $patient_id, $previous_procedures, $appointment_date, $reason);
            $insert_history_stmt->execute();
            $insert_history_stmt->close();
        }

        // Status updated successfully, refresh the page to reflect the changes
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    } else {
        // Error handling
        echo "Error updating status.";
    }
}

// SQL query to fetch only "pending" appointments for the specific date
// SQL query to fetch only "pending" appointments for the specific date and prioritize emergency appointments
$sql = "
    SELECT a.appointment_id, a.patient_id, a.dentist_id, a.appointment_date, 
           a.appointment_time, a.appointment_status, a.is_emergency, 
           a.appointment_created_at, p.first_name, p.last_name 
    FROM appointments a
    JOIN patients p ON a.patient_id = p.patient_id
    WHERE a.dentist_id = ? 
    AND a.appointment_status = 'pending' 
    AND a.appointment_date = ? 
    AND (
        p.first_name LIKE ? OR 
        p.last_name LIKE ? OR 
        CONCAT(p.first_name, ' ', p.last_name) LIKE ? OR 
        CONCAT(p.last_name, ' ', p.first_name) LIKE ?
    ) 
    ORDER BY a.is_emergency DESC, a.appointment_time ASC, a.appointment_id ASC
";

$stmt = $conn->prepare($sql);
$search_param = "%$search_query%"; // Prepare the search term with wildcards
$stmt->bind_param("isssss", $dentist_id, $date, $search_param, $search_param, $search_param, $search_param); // Bind dentist_id, date, and search terms
$stmt->execute();
$stmt->bind_result($appointment_id, $patient_id, $dentist_id, $appointment_date, 
                   $appointment_time, $appointment_status, $is_emergency, 
                   $appointment_created_at, $first_name, $last_name);

// Fetch the results and store them in the $appointments array
while ($stmt->fetch()) {
    $appointments[] = [
        'appointment_id' => $appointment_id,  
        'patient_id' => $patient_id,
        'dentist_id' => $dentist_id,
        'date' => $appointment_date,
        'time' => $appointment_time,
        'status' => $appointment_status,
        'is_emergency' => $is_emergency,
        'created_at' => $appointment_created_at,
        'first_name' => $first_name,
        'last_name' => $last_name
    ];
}

$stmt->close();
$conn->close();
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Pending Appointments</title>
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
.appointment-list table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 30px;
}

.appointment-list th, .appointment-list td {
    padding: 12px;
    text-align: left;
    border: 1px solid #ddd;
}

.appointment-list th {
    background-color: #b19cd9; /* Sidebar color */
    color: white;
    font-size: 16px;
}

.appointment-list td {
    background-color: #f9f9f9;
    font-size: 14px;
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
</style>
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
        <a href="logout.php">Logout</a>
    </div>

<div class="main-content">
<h1>Pending Appointments for <?php echo date('F j, Y', strtotime($date)); ?></h1>

<nav class="app">
    <ul class="app-links">
        <li><a href="dentist_patient_appointments.php" class="active">Upcoming Appointments</a></li>
        <li><a href="dentist_pending.php">Pending</a></li>
        <li><a href="dentist_cancelled.php">Cancelled</a></li>
        <li><a href="dentist_completed.php">Done</a></li>
        <li><a href="dentist_no_show_appointments.php">No Show</a></li>
    </ul>
</nav>

<!-- Search Bar -->
<form method="POST" action="">
    <input type="text" name="search" placeholder="Search by Patient Name" value="<?php echo htmlspecialchars($search_query); ?>" style="padding: 10px; width: 250px; margin-bottom: 20px;">
    <button type="submit" style="padding: 10px 15px; background-color: #4c2882; color: white; border: none;">Search</button>
</form>

<div class="appointment-list">
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Patient Name</th>
                <th>Appointment Date</th>
                <th>Appointment Time</th>
                <th>Created At</th>
                <th>Status</th>
                <th>Updates</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
    <?php if (count($appointments) > 0): ?>
        <?php $counter = 1; ?>
        <?php foreach ($appointments as $appointment): ?>
            <tr>
                <!-- Check if the appointment is an emergency -->
                <td style="color: <?php echo ($appointment['is_emergency'] == 1) ? 'red' : 'inherit'; ?>">
                    <?php echo $counter++; ?>
                </td>
                <td><?php echo htmlspecialchars($appointment['first_name'] . ' ' . $appointment['last_name']); ?></td>
                <td><?php echo htmlspecialchars($appointment['date']); ?></td>
                <td><?php echo htmlspecialchars($appointment['time']); ?></td>
                <td><?php echo htmlspecialchars($appointment['created_at']); ?></td>
                <td><?php echo htmlspecialchars($appointment['status']); ?></td>
                <td>
                    <form method="POST">
                        <input type="hidden" name="appointment_id" value="<?php echo htmlspecialchars($appointment['appointment_id']); ?>">
                        <select name="appointment_status">
                            <option value="pending" <?php echo ($appointment['status'] == 'pending') ? 'selected' : ''; ?>>Pending</option>
                            <option value="completed" <?php echo ($appointment['status'] == 'completed') ? 'selected' : ''; ?>>Done</option>
                            <option value="cancelled" <?php echo ($appointment['status'] == 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                            <option value="no_show" <?php echo ($appointment['status'] == 'no_show') ? 'selected' : ''; ?>>No Show</option>
                        </select>
                        <button type="submit" name="update_status">Update</button>
                    </form>
                </td>
                <td>
                    <a href="view_appointment_details.php?appointment_id=<?php echo htmlspecialchars($appointment['appointment_id']); ?>&patient_id=<?php echo htmlspecialchars($appointment['patient_id']); ?>">View Details</a>
                </td>
            </tr>
        <?php endforeach; ?>
    <?php else: ?>
        <tr>
            <td colspan="9">No pending appointments found.</td>
        </tr>
    <?php endif; ?>
</tbody>

    </table>
</div>
</div>
</body>
</html>
