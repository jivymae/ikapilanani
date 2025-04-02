<?php
include 'login_check.php'; // Ensure user is logged in
include 'db_config.php';
include 'admin_check.php'; // Include the admin check helper

// Fetch successful appointments count for the day
$sql = "SELECT COUNT(*) as total_successful FROM appointments WHERE appointment_status = 'completed' AND appointment_date = CURDATE()";
$result = $conn->query($sql);
$row = $result->fetch_assoc();
$total_successful = $row['total_successful'];

// Fetch total patients count (who have appointments today) - based on patient_id
$sql = "SELECT COUNT(DISTINCT patient_id) as total_patients_today FROM appointments WHERE appointment_date = CURDATE()";
$result = $conn->query($sql);
$row = $result->fetch_assoc();
$total_patients_today = $row['total_patients_today'];

// Fetch total upcoming appointments count (pending appointments for today)
$sql = "SELECT COUNT(*) as total_upcoming FROM appointments WHERE appointment_status = 'pending' AND appointment_date = CURDATE()";
$result = $conn->query($sql);
$row = $result->fetch_assoc();
$total_upcoming = $row['total_upcoming'];

// Fetch total canceled appointments count for the day
$sql = "SELECT COUNT(*) as total_cancelled FROM appointments WHERE appointment_status = 'cancelled' AND appointment_date = CURDATE()";
$result = $conn->query($sql);
$row = $result->fetch_assoc();
$total_cancelled = $row['total_cancelled'];

// Fetch total reschedule requests count for the day
$sql = "SELECT COUNT(*) as total_reschedule_requests FROM appointment_reschedule_requests WHERE status IN ('pending', 'approved') AND request_date = CURDATE()";
$result = $conn->query($sql);
$row = $result->fetch_assoc();
$total_reschedule_requests = $row['total_reschedule_requests'];

// Fetch pending appointments for the current month along with patient names and appointment times
$start_date = date('Y-m-01');  // First day of the current month
$end_date = date('Y-m-t');    // Last day of the current month

$sql = "
    SELECT a.appointment_id, a.appointment_date, a.appointment_time, p.first_name, p.last_name
    FROM appointments a
    JOIN patients p ON a.patient_id = p.patient_id
    WHERE a.appointment_date BETWEEN '$start_date' AND '$end_date' 
    AND a.appointment_status = 'pending'
";
$upcoming_appointments_result = $conn->query($sql);

// Store appointments in an array with 24-hour time format
$appointments_for_month = [];
while ($row = $upcoming_appointments_result->fetch_assoc()) {
    // Format time to 24-hour format
    $formatted_time = date('H:i', strtotime($row['appointment_time']));
    
    // Key appointments by date
    $appointments_for_month[$row['appointment_date']][] = [
        'appointment_id' => $row['appointment_id'],
        'appointment_time' => $formatted_time,
        'patient_name' => $row['first_name'] . ' ' . $row['last_name']
    ];
}

$conn->close();
?>




<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Dental Clinic Management System</title>
    <link rel="stylesheet" type="text/css" href="css/style.css">
    <style>
       
   /* General Styles for Container */
/* General Styles for Container */
.container {
    display: flex; /* Use flexbox for layout */
    height: 100vh; /* Full height of the screen */
    overflow: hidden; /* Prevent content overflow */
}

/* Main Content Area Styling */
.main-content {
    flex: 1; /* Allow main content to take remaining space */
    padding: 30px;
    background-color: #f9f9f9; /* Light background */
    overflow-y: auto; /* Enable scrolling for content */
    box-sizing: border-box; /* Include padding in width/height calculations */
}

/* Dashboard Header */
#dashboard h1 {
    font-size: 32px;
    font-weight: bold;
    color: #333;
    margin-bottom: 30px;
}

/* Analytics Section */
.analytics {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); /* Responsive grid */
    gap: 20px; /* Space between boxes */
    margin-bottom: 30px;
}

.box {
    background-color: #fff; /* White background for boxes */
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 6px 12px rgba(0, 0, 0, 0.1); /* Subtle shadow */
    text-align: center;
    transition: transform 0.3s ease, box-shadow 0.3s ease; /* Add transition effects */
}

.box h3 {
    font-size: 18px;
    color: #555;
    margin-bottom: 10px;
}

.box p {
    font-size: 24px;
    font-weight: bold;
    color: #333;
}

.box:hover {
    transform: translateY(-8px); /* Lift the box on hover */
    box-shadow: 0 12px 18px rgba(0, 0, 0, 0.15); /* Increased shadow on hover */
}

/* Calendar Styling */
table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
    border-radius: 12px;
    overflow: hidden;
}

th, td {
    padding: 12px;
    text-align: center;
    width: 14.28%;
    height: 80px;
    font-size: 16px;
}

th {
    background-color: #f4f4f4;
    color: #555;
    font-weight: bold;
    border-bottom: 2px solid #ddd;
}

/* Calendar cells (dates) */
td {
    position: relative;
    font-size: 14px;
    padding: 15px;
    text-align: center;
    background-color: #fff;
    border: 1px solid #ddd;
    height: 60px;
    width: 60px;
    cursor: pointer;
    vertical-align: top;
}

/* Highlighted Days with Appointments */
td.highlight {
    background-color: #f0f8ff; /* Light blue for days with events */
    border: 2px solid #3498db; /* Blue border */
}

/* Style for event blocks inside the date */
/* Event block styling for appointments */
.event-block {
    background-color: #3498db; /* Blue background */
    color: white;
    font-size: 12px;
    padding: 6px;
    margin: 4px 0;
    border-radius: 5px;
    text-align: left;
    display: block;
    box-sizing: border-box;
    overflow: hidden;
    max-height: 40px;
    white-space: nowrap;
    text-overflow: ellipsis; /* Truncate long names or times */
}

/* Styling for patient name inside the event block */
.event-block .patient-name {
    font-weight: bold;
    font-size: 12px;
}

/* Styling for appointment time inside the event block */
.event-block .appointment-time {
    font-size: 11px;
    color: #f1f1f1;
}


/* Hover effect for calendar cells */
td:hover {
    background-color: #f9f9f9;
    cursor: pointer;
}

td.highlight:hover {
    background-color: #e1f5fe; /* Lighter blue on hover */
}

/* General style for the calendar header */
.calendar-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

#month-name {
    font-size: 24px;
    font-weight: bold;
    color: #333;
}

button {
    padding: 10px;
    font-size: 16px;
    background-color: #4caf50; /* Green button */
    color: white;
    border: none;
    cursor: pointer;
}

button:hover {
    background-color: #45a049;
}

/* Style for calendar */
/* Fixing the calendar container layout */
.calendar {
    width: 100%; /* Ensure it takes the full width of the container */
    max-width: 100%; /* Ensure no overflow beyond container */
    margin: 0 auto; /* Center the calendar */
    overflow-x: auto;
    box-sizing: border-box; /* Prevent any unexpected overflow */
}

/* Calendar Table styling */
table {
    width: 100%; /* Full width */
    border-collapse: collapse;
    margin-top: 20px;
    border-radius: 12px;
    overflow: hidden;
    box-sizing: border-box; /* Ensure proper box sizing */
}

th, td {
    padding: 12px;
    text-align: center;
    width: 14.28%; /* Equal width for each column */
    height: 80px;
    font-size: 16px;
}

th {
    background-color: #f4f4f4;
    color: #555;
    font-weight: bold;
    border-bottom: 2px solid #ddd;
}

td {
    position: relative;
    font-size: 14px;
    padding: 15px;
    text-align: center;
    background-color: #fff;
    border: 1px solid #ddd;
    height: 60px;
    width: 60px;
    cursor: pointer;
    vertical-align: top;
}

/* Additional changes to ensure calendar fits well */
td.highlight {
    background-color: #f0f8ff;
    border: 2px solid #3498db;
}

.event-block {
    background-color: #3498db;
    color: white;
    font-size: 12px;
    padding: 6px;
    margin: 4px 0;
    border-radius: 5px;
    text-align: left;
    display: block;
    box-sizing: border-box;
    overflow: hidden;
    max-height: 40px;
    white-space: nowrap;
    text-overflow: ellipsis;
}

.event-block .patient-name {
    font-weight: bold;
    font-size: 12px;
}

.event-block .appointment-time {
    font-size: 11px;
    color: #f1f1f1;
}

/* Style for the calendar header */
.calendar-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

#month-name {
    font-size: 24px;
    font-weight: bold;
    color: #333;
}

button {
    padding: 10px;
    font-size: 16px;
    background-color: #4caf50;
    color: white;
    border: none;
    cursor: pointer;
}

button:hover {
    background-color: #45a049;
}

/* Fix the width of the calendar table cells */
td {
    font-size: 14px;
    height: 60px;
    padding: 10px;
    width: 14%; /* Make each cell fill 14% of the row */
}

/* Responsive Design */
@media screen and (max-width: 768px) {
    .analytics {
        grid-template-columns: 1fr 1fr;
    }
    
    .calendar-header {
        flex-direction: column;
        align-items: flex-start;
    }
    
    button {
        font-size: 14px;
        padding: 8px;
    }
    
    td {
        font-size: 14px;
        height: 60px;
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
            <section id="dashboard">
                <h1>Dashboard</h1>
              <!-- Analytics Section -->
<!-- Analytics Section -->
<div class="analytics">
<div class="box">
    <h3>Completed Appointments</h3>
    <p><?php echo $total_successful; ?></p>
</div>


    <div class="box">
        <h3>Total Patients with Appointments Today</h3>
        <p><?php echo $total_patients_today; ?></p>
    </div>

    <div class="box">
        <h3>Total Pending Appointments</h3>
        <p><?php echo $total_upcoming; ?></p>
    </div>

    <div class="box">
        <h3>Total Canceled Appointments</h3>
        <p><?php echo $total_cancelled; ?></p>
    </div>

    <!-- New Box for Reschedule Requests -->
   
</div>

                <div class="calendar">
                    <div class="calendar-header">
                        <h2 id="month-name"></h2>
                        <button onclick="changeMonth(-1)">&#8249;</button>
                        <button onclick="changeMonth(1)">&#8250;</button>
                    </div>

                    <table class="calendar-table">
                        <thead>
                            <tr>
                                <th>Sun</th>
                                <th>Mon</th>
                                <th>Tue</th>
                                <th>Wed</th>
                                <th>Thu</th>
                                <th>Fri</th>
                                <th>Sat</th>
                            </tr>
                        </thead>
                        <tbody id="calendar-body">
                            <!-- Calendar days will be populated here by JavaScript -->
                        </tbody>
                    </table>

                    <!-- Modal for adding an appointment -->
                    <div id="appointment-modal" style="display: none;">
                        <input type="text" id="appointment-time" placeholder="Enter time (e.g., 10:00 AM)">
                        <button id="save-appointment" class="add-appointment">Add Appointment</button>
                    </div>
                </div>
            </section>
        </main>
    </div>

    <script>
 let currentDate = new Date();
let appointmentsForMonth = <?php echo json_encode($appointments_for_month); ?>;
let selectedDate = null;

function changeMonth(direction) {
    // Move to the next or previous month
    currentDate.setMonth(currentDate.getMonth() + direction);
    loadCalendar(); // Reload calendar with the new month
}

function loadCalendar() {
    const monthName = currentDate.toLocaleString('default', { month: 'long' });
    const year = currentDate.getFullYear();
    document.getElementById('month-name').innerText = `${monthName} ${year}`;
    
    const daysInMonth = new Date(year, currentDate.getMonth() + 1, 0).getDate();
    const firstDay = new Date(year, currentDate.getMonth(), 1).getDay();
    
    // Fetch appointments for the new month via AJAX
    fetchAppointmentsForMonth(year, currentDate.getMonth() + 1)
        .then(newAppointmentsForMonth => {
            appointmentsForMonth = newAppointmentsForMonth;
            renderCalendar(daysInMonth, firstDay, year);
        });
}

// Fetch the appointments for the given month and year
function fetchAppointmentsForMonth(year, month) {
    return new Promise((resolve, reject) => {
        // Construct the date range for the month
        const startDate = `${year}-${String(month).padStart(2, '0')}-01`;
        const endDate = `${year}-${String(month).padStart(2, '0')}-${new Date(year, month, 0).getDate()}`;
        
        // Make an AJAX request to fetch appointments for the given date range
        const xhr = new XMLHttpRequest();
        xhr.open('GET', `get_appointments.php?start_date=${startDate}&end_date=${endDate}`, true);
        xhr.onload = function() {
            if (xhr.status === 200) {
                const data = JSON.parse(xhr.responseText);
                resolve(data);
            } else {
                reject('Failed to fetch appointments');
            }
        };
        xhr.send();
    });
}

// Render the calendar based on the fetched appointments
function renderCalendar(daysInMonth, firstDay, year) {
    let calendarBody = '';
    let currentDay = 1;
    for (let i = 0; i < 6; i++) {
        calendarBody += '<tr>';
        for (let j = 0; j < 7; j++) {
            if (i === 0 && j < firstDay) {
                calendarBody += '<td></td>';
            } else if (currentDay <= daysInMonth) {
                const dateString = `${year}-${String(currentDate.getMonth() + 1).padStart(2, '0')}-${String(currentDay).padStart(2, '0')}`;
                const appointmentsForDay = appointmentsForMonth[dateString] || [];
                const hasAppointments = appointmentsForDay.length > 0;

                calendarBody += `<td class="${hasAppointments ? 'highlight' : ''}" onclick="showAppointmentModal(${currentDay}, '${dateString}')">${currentDay}`;

                if (hasAppointments) {
                    appointmentsForDay.forEach(appointment => {
                        const formattedTime = convertTo12HourFormat(appointment.appointment_time);
                        calendarBody += `
                            <div class="event-block">
                                <span class="patient-name">${appointment.patient_name}</span>
                                <span class="appointment-time">${formattedTime}</span>
                            </div>
                        `;
                    });
                }
                calendarBody += '</td>';
                currentDay++;
            } else {
                calendarBody += '<td></td>';
            }
        }
        calendarBody += '</tr>';
    }
    document.getElementById('calendar-body').innerHTML = calendarBody;
}

// Convert 24-hour time to 12-hour format
function convertTo12HourFormat(time) {
    let [hours, minutes] = time.split(':');
    hours = parseInt(hours);
    let period = hours >= 12 ? 'PM' : 'AM';
    hours = hours % 12;
    hours = hours ? hours : 12; // If hour is 0 (midnight), set it to 12
    minutes = minutes.padStart(2, '0');
    return `${hours}:${minutes} ${period}`;
}

function showAppointmentModal(day, dateString) {
    selectedDate = dateString;
    document.getElementById('appointment-modal').style.display = 'block';
}

document.getElementById('save-appointment').onclick = function() {
    const appointmentTime = document.getElementById('appointment-time').value;
    if (selectedDate && appointmentTime) {
        console.log(`Saving appointment for ${selectedDate} at ${appointmentTime}`);
        alert('Appointment saved!');
        document.getElementById('appointment-modal').style.display = 'none';
    } else {
        alert('Please select a valid time');
    }
};

loadCalendar(); // Initialize the calendar when the page loads
// Initialize the calendar when the page loads
 // Initialize the calendar when the page loads
    </script>
</body>
</html>
