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
$stmt = $conn->prepare("SELECT username FROM users WHERE user_id = ? AND role = 'dentist'");
$stmt->bind_param("i", $dentist_id);
$stmt->execute();
$stmt->bind_result($username);
$stmt->fetch();
$stmt->close();

date_default_timezone_set('Asia/Manila');

// Get today's date
$today = date('Y-m-d');

// Fetch today's appointments for the logged-in dentist
$appointments_stmt = $conn->prepare("
    SELECT 
        a.appointment_id, 
        a.appointment_time, 
        a.appointment_status, 
        p.first_name, 
        p.last_name
    FROM appointments a
    JOIN patients p ON a.patient_id = p.patient_id
    WHERE a.dentist_id = ? AND a.appointment_date = ? 
    ORDER BY a.appointment_time ASC
");
$appointments_stmt->bind_param("is", $dentist_id, $today);


$appointments_stmt->execute();
$appointments_result = $appointments_stmt->get_result();

// Fetch services for each appointment
function getAppointmentServices($appointment_id, $conn) {
    $services_stmt = $conn->prepare("
        SELECT s.service_name 
        FROM appointment_services ap
        JOIN services s ON ap.service_id = s.service_id
        WHERE ap.appointment_id = ?
    ");
    $services_stmt->bind_param("i", $appointment_id);
    $services_stmt->execute();
    $result = $services_stmt->get_result();
    $services = [];
    while ($row = $result->fetch_assoc()) {
        $services[] = $row['service_name'];
    }
    $services_stmt->close();
    return implode(', ', $services);
}

date_default_timezone_set('Asia/Manila');

$profile_image = "uploads/default-dentist.png"; // Default image
$image_stmt = $conn->prepare("SELECT profile_image FROM users WHERE user_id = ? AND role = 'dentist'");
$image_stmt->bind_param("i", $dentist_id);
$image_stmt->execute();
$image_stmt->bind_result($profile_image_db);

if ($image_stmt->fetch() && !empty($profile_image_db)) {
    $profile_image = htmlspecialchars($profile_image_db);
}

$image_stmt->close();

// Check if the file exists
if (!file_exists($profile_image)) {
    $profile_image = "uploads/default-dentist.png";
}

// Fetch appointment statistics for today
// Fetch appointment statistics for today
$total_stmt = $conn->prepare("
    SELECT COUNT(*) 
    FROM appointments 
    WHERE dentist_id = ? 
    AND appointment_date = ?
");
$total_stmt->bind_param("is", $dentist_id, $today);
$total_stmt->execute();
$total_stmt->bind_result($total_appointments);
$total_stmt->fetch();
$total_stmt->close();

$success_stmt = $conn->prepare("
    SELECT COUNT(*) 
    FROM appointments 
    WHERE dentist_id = ? 
    AND appointment_date = ? 
    AND appointment_status = 'completed'
");
$success_stmt->bind_param("is", $dentist_id, $today);
$success_stmt->execute();
$success_stmt->bind_result($successful_appointments);
$success_stmt->fetch();
$success_stmt->close();

$canceled_stmt = $conn->prepare("
    SELECT COUNT(*) 
    FROM appointments 
    WHERE dentist_id = ? 
    AND appointment_date = ? 
    AND appointment_status = 'cancelled'
");
$canceled_stmt->bind_param("is", $dentist_id, $today);
$canceled_stmt->execute();
$canceled_stmt->bind_result($canceled_appointments);
$canceled_stmt->fetch();
$canceled_stmt->close();

// Fetch the count of reschedule requests for the logged-in dentist
$reschedule_stmt = $conn->prepare("
    SELECT COUNT(*) 
    FROM appointment_reschedule_requests arr
    JOIN appointments a ON arr.appointment_id = a.appointment_id
    WHERE a.dentist_id = ?
    AND a.appointment_date = ?
");
$reschedule_stmt->bind_param("is", $dentist_id, $today);
$reschedule_stmt->execute();
$reschedule_stmt->bind_result($reschedule_count);
$reschedule_stmt->fetch();
$reschedule_stmt->close();


// Fetch the count of pending appointments for today
$pending_stmt = $conn->prepare("
    SELECT COUNT(*) 
    FROM appointments 
    WHERE dentist_id = ? 
    AND appointment_date = ? 
    AND appointment_status = 'pending'
");
$pending_stmt->bind_param("is", $dentist_id, $today);
$pending_stmt->execute();
$pending_stmt->bind_result($pending_appointments);
$pending_stmt->fetch();
$pending_stmt->close();


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $appointment_id = $_POST['appointment_id'];
    $appointment_status = $_POST['appointment_status'];

    // Update the appointment status
    $update_stmt = $conn->prepare("
        UPDATE appointments 
        SET appointment_status = ? 
        WHERE appointment_id = ?
    ");
    $update_stmt->bind_param("si", $appointment_status, $appointment_id);

    if ($update_stmt->execute()) {
        $_SESSION['success_message'] = 'Appointment status updated successfully.';
    } else {
        $_SESSION['error_message'] = 'Failed to update appointment status.';
    }

    $update_stmt->close();
    header('Location: dentist_dashboard.php');
    exit();
}


?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dentist Dashboard</title>
    <link rel="stylesheet" type="text/css" href="css/dentist.css">
    <!-- FullCalendar CSS -->
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@3.2.0/dist/fullcalendar.min.css" rel="stylesheet" />

<!-- jQuery (required for FullCalendar) -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- FullCalendar JS -->
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@3.2.0/dist/fullcalendar.min.js"></script>

    <style>
/* Basic Reset */


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
    position: fixed;
    width: 250px;
    height:100%;
    background-color: #b19cd9;
    color: white;
    display: flex;
    flex-direction: column;
    align-items: center;
    padding-top: 20px;
}

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

/* Navbar links */
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

/* Main Content Styling */
.main-content {
    margin-left: 270px; /* Adjust the margin to the right of the sidebar */
    padding: 30px;
    text-align: center;
}

.main-content h1 {
    color: #8878c3;
    font-size: 32px;
    margin-bottom: 10px;
    text-transform: uppercase;
    font-weight: bold;
}

.main-content h2 {
    color: #8878c3;
    font-size: 20px;
    margin-bottom: 20px;
}

.main-content a {
    display: inline-block;
    text-decoration: none;
    background: #8878c3;
    color: #fff;
    padding: 10px 20px;
    border-radius: 5px;
    font-size: 16px;
    font-weight: bold;
    transition: background-color 0.3s ease, transform 0.2s ease;
    margin-bottom: 20px;
}

.main-content a:hover {
    background: #1565c0;
    transform: scale(1.05);
}

.appointment-stats {
    margin-top: 30px;
    text-align: left;
}

.appointment-stats h3 {
    color: #8878c3;
    font-size: 24px;
    margin-bottom: 15px;
    text-transform: uppercase;
}

.stat {
    background: #dcd0ff;
    color: #333;
    padding: 15px;
    margin: 10px 0;
    border-radius: 5px;
    font-size: 18px;
    font-weight: bold;
    display: flex;
    justify-content: space-between;
    align-items: center;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

/* Add some color accents */
.stat:nth-child(1) {
    border-left: 5px solid #29b6f6;
}

.stat:nth-child(2) {
    border-left: 5px solid #8878c3;
}

.stat:nth-child(3) {
    border-left: 5px solid #29b6f6;
}
.today-appointments {
    margin-top: 30px;
    text-align: left;
}

.today-appointments h3 {
    font-size: 24px;
    color: #8878c3;
    margin-bottom: 15px;
}

.today-appointments table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 10px;
}

.today-appointments table th,
.today-appointments table td {
    border: 1px solid #ddd;
    padding: 10px;
    text-align: center;
}

.today-appointments table th {
    background-color: #8878c3;
    color: #fff;
    text-transform: uppercase;
    font-size: 14px;
}

.today-appointments table td {
    font-size: 14px;
    color: #333;
}

.today-appointments table tr:nth-child(even) {
    background-color: #f9f9f9;
}

.today-appointments table tr:hover {
    background-color: #f1f1f1;
}
.today-appointments select {
    padding: 5px 10px;
    font-size: 14px;
    border: 1px solid #ccc;
    border-radius: 5px;
    margin-right: 5px;
}

.today-appointments button {
    background-color: #1e88e5;
    color: #fff;
    border: none;
    padding: 5px 10px;
    font-size: 14px;
    border-radius: 5px;
    cursor: pointer;
    transition: background-color 0.3s ease;
}

.today-appointments button:hover {
    background-color: #1565c0;
}
/* Position profile image and name in the top-right corner */
/* Ensure profile photo and name are fixed at the top-right */
.top-right-profile {
    position: absolute;  /* Fixes it to the top-right corner */
    top: 20px;  /* Adjust the top position */
    right: 20px;  /* Adjust the right position */
    align-items: center;
    justify-content: flex-start;
    z-index: 100;  /* Ensure it stays on top of other content */
    /* Optional: Add background color for better visibility */
    padding: 10px;  /* Optional: Add padding around profile */
    border-radius: 10px;  /* Optional: Add rounded corners */
}

.profile-photo img {
    border-radius: 50%;
    width: 40px;
    height: 40px;
    object-fit: cover;
    margin-right: 10px;  /* Space between the image and name */
}

.profile-name h2 {
    color: #8878c3;
    font-size: 16px;
    margin: 0;
}


/* Make sure the rest of the content has space */
.main-content {
    padding-top: 80px; /* Adjust for profile section's height */
}




/* Responsive Design */
@media (max-width: 768px) {
    .main-content {
        padding: 20px;
    }

    .main-content h1 {
        font-size: 28px;
    }

    .main-content h2 {
        font-size: 18px;
    }

    .stat {
        font-size: 16px;
        padding: 10px;
    }

    .appointment-stats h3 {
        font-size: 20px;
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
        
        <a href="dentist_dashboard.php" class="active">Dashboard</a>
        <a href="dentist_profile.php">Profile</a>
        <a href="dentist_patient_appointments.php">Appointments</a>
        <a href="dentist_patient_records.php">Patient Records</a>
       
        <a href="logout.php">Logout</a>
    </div>

    
    <div class="main-content">
    <div class="welcome-section">
    <div class="welcome-card">
    <div class="top-right-profile">
    <div class="profile-photo">
        <img src="<?php echo $profile_image; ?>" alt="Profile Photo" />
    
    <div class="profile-name">
        <h2>Hello, Dr. <?php echo htmlspecialchars($username); ?></h2>
  


        </div>
</div>
</div>


        <div class="welcome-text">
            
            <p>
                <?php
                date_default_timezone_set('Asia/Manila'); // Replace with the correct timezone
                $hour = date('H');
                if ($hour >= 5 && $hour < 12) {
                    echo "Good morning! Ready to make some smiles shine today?";
                } elseif ($hour >= 12 && $hour < 17) {
                    echo "Good afternoon! Let's keep the smiles coming.";
                } else {
                    echo "Good evening! Hope you had a successful day.";
                }
                ?>
            </p>
        </div>
    </div>
</div>
            
    <h1>Dentist Dashboard</h1>
   


    <div class="appointment-stats">
    <h3>Appointment Statistics</h3>
    <div class="stat">Total Appointments: <?php echo $total_appointments; ?></div>
    <div class="stat">Successful Appointments: <?php echo $successful_appointments; ?></div>
    <div class="stat">Canceled Appointments: <?php echo $canceled_appointments; ?></div>
    <div class="stat">Pending Appointments: <?php echo $pending_appointments; ?></div>
</div>



    <div class="today-appointments">
    <h3>Today's Appointments</h3>
    <table>
    <thead>
        <tr>
            <th>Time</th>
            <th>Patient Name</th>
            <th>Status</th>
            <th>Services</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($appointments_result->num_rows > 0): ?>
            <?php while ($row = $appointments_result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo date('h:i A', strtotime($row['appointment_time'])); ?></td>
                    <td><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></td>
                    <td><?php echo ucfirst($row['appointment_status']); ?></td>
                    <td>
                        <?php 
                        $services = getAppointmentServices($row['appointment_id'], $conn);
                        echo htmlspecialchars($services ?: 'N/A');
                        ?>
                    </td>
                    <td>
                        <form method="POST" action="" style="display: inline-block;">
                            <input type="hidden" name="appointment_id" value="<?php echo $row['appointment_id']; ?>">
                            <select name="appointment_status" required>
                                <option value="" disabled selected>Update Status</option>
                                <option value="completed">Completed</option>
                                <option value="cancelled">Cancelled</option>
                                <option value="no_show">No-Show</option>
                            </select>
                            <button type="submit">Update</button>
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="5">No appointments scheduled for today.</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>

</div>

</div>
<script>
// Fetch appointments for a specific month
function getAppointmentsForMonth($dentist_id, $month, $year, $conn) {
    $start_date = "$year-$month-01";
    $end_date = "$year-$month-" . date('t', strtotime($start_date)); // Get the last day of the month

    $stmt = $conn->prepare("
        SELECT appointment_id, appointment_time, appointment_date, appointment_status, p.first_name, p.last_name
        FROM appointments a
        JOIN patients p ON a.patient_id = p.patient_id
        WHERE a.dentist_id = ? AND a.appointment_date BETWEEN ? AND ?
    ");
    $stmt->bind_param("iss", $dentist_id, $start_date, $end_date);
    $stmt->execute();
    $result = $stmt->get_result();
    $appointments = [];

    while ($row = $result->fetch_assoc()) {
        // Format the appointment for FullCalendar
        $appointments[] = [
            'title' => $row['first_name'] . ' ' . $row['last_name'],
            'start' => $row['appointment_date'] . 'T' . $row['appointment_time'],
            'status' => $row['appointment_status'],
        ];
    }

    $stmt->close();

    return $appointments;
}


</script>
<div id="calendar"></div>

    <script>
        $(document).ready(function() {
            $('#calendar').fullCalendar({
                // Customize FullCalendar options here
                events: function(start, end, timezone, callback) {
                    $.ajax({
                        url: 'get_appointments.php', // Create this endpoint in PHP
                        dataType: 'json',
                        success: function(data) {
                            var events = data.map(function(appointment) {
                                return {
                                    title: appointment.title,
                                    start: appointment.start,
                                    color: appointment.status === 'completed' ? 'green' : (appointment.status === 'cancelled' ? 'red' : 'yellow'),
                                };
                            });
                            callback(events);
                        }
                    });
                },
                header: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'month,agendaWeek,agendaDay'
                },
                selectable: true,
                droppable: true
            });
        });
    </script>
</body>
</html>
