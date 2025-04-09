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

// Fetch cancelled appointments with patient and payment details
$cancelled_appointments = [];
$stmt = $conn->prepare("
    SELECT a.appointment_id, a.appointment_date, a.appointment_time, 
           p.first_name, p.last_name, p.email, 
           GROUP_CONCAT(s.service_name SEPARATOR ', ') AS services,
           pay.total_amount, pay.transaction_number, 
           pay.payment_status, pay.method_id
    FROM appointments a
    JOIN patients p ON a.patient_id = p.patient_id
    LEFT JOIN appointment_services aps ON a.appointment_id = aps.appointment_id
    LEFT JOIN services s ON aps.service_id = s.service_id
    LEFT JOIN payments pay ON a.appointment_id = pay.appointment_id
    WHERE a.dentist_id = ? AND a.appointment_status = 'cancelled'
    GROUP BY a.appointment_id
    ORDER BY a.appointment_date DESC, a.appointment_time DESC
");

$stmt->bind_param("i", $dentist_id);

$stmt->execute();

// Updated bind_result() to match the SELECT query's fields
$stmt->bind_result($appointment_id, $appointment_date, $appointment_time, 
                   $first_name, $last_name, $patient_email, $services, 
                   $total_amount, $transaction_number, 
                   $payment_status, $method_id);

while ($stmt->fetch()) {
    $cancelled_appointments[] = [
        'id' => $appointment_id,
        'date' => $appointment_date,
        'time' => $appointment_time,
        'first_name' => $first_name,
        'last_name' => $last_name,
        'email' => $patient_email,
        'services' => $services,
        'total_amount' => $total_amount,
        'transaction_number' => $transaction_number,
        'payment_status' => $payment_status,
        'method_id' => $method_id
    ];
}
$stmt->close();
$conn->close();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Cancelled Appointments</title>
    <link rel="stylesheet" type="text/css" href="css/dentist.css">
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
table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 30px;
}

table th, table td {
    padding: 12px;
    text-align: left;
    border: 1px solid #ddd;
}

table th {
    background-color: #b19cd9; /* Sidebar color */
    color: white;
    font-size: 16px;
}

table td {
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
            <a href="logout.php">Logout</a>
        </nav>
    </div>

    <div class="main-content">
        <h1>Cancelled Appointments</h1>
        <nav class="app">
            <ul class="app-links">
                <li><a href="dentist_patient_appointments.php">Upcoming Appointment</a></li>
                <li><a href="dentist_pending.php">Pending</a></li>
                <li><a href="dentist_cancelled.php" class="active">Cancelled</a></li>
                <li><a href="dentist_completed.php">Done</a></li>
                <li><a href="dentist_no_show_appointments.php">No Show</a></li>
            </ul>
        </nav>

        <table>
            <thead>
                <tr>
                    
                    <th>Patient Name</th>
                    <th>Patient Email</th>
                    <th>Appointment Date</th>
                    <th>Appointment Time</th>
                    <th>Services</th>
                   
                   
                  
                </tr>
            </thead>
            <tbody>
                <?php foreach ($cancelled_appointments as $appointment): ?>
                    <tr>
                      
                        <td><?php echo htmlspecialchars($appointment['first_name'] . ' ' . $appointment['last_name'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($appointment['email'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($appointment['date'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($appointment['time'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($appointment['services'] ?? ''); ?></td>
                      
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
