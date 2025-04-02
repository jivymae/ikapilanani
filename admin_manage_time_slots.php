<?php
include 'login_check.php';  // Ensure user is logged in
include 'db_config.php';
include 'admin_check.php';  // Admin check

// Define default appointment length (e.g., 30 minutes) and buffer time (e.g., 10 minutes)
$default_appointment_length = 30; // in minutes
$buffer_time = 10; // in minutes

// Handle form submission for adding new time slot
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_time_slot'])) {
    $day_of_week = $_POST['day_of_week'];
    $start_time = $_POST['start_time'];
    $appointment_length = $_POST['appointment_length'] ?? $default_appointment_length;  // Default to 30 minutes
    $end_time = $_POST['end_time'];
    
    // Validate the input
    if (empty($day_of_week) || empty($start_time) || empty($end_time)) {
        $message = "All fields are required!";
    } else {
        // Calculate end time based on the appointment length and buffer time
        $start_time_obj = DateTime::createFromFormat('H:i', $start_time);
        $start_time_obj->add(new DateInterval("PT{$appointment_length}M")); // Add appointment length
        $start_time_obj->add(new DateInterval("PT{$buffer_time}M")); // Add buffer time
        $calculated_end_time = $start_time_obj->format('H:i');

        // Insert the new time slot into the database
        $query = "INSERT INTO time_slots (day_of_week, start_time, end_time) 
                  VALUES ('$day_of_week', '$start_time', '$calculated_end_time')";
        if (mysqli_query($conn, $query)) {
            $message = "Time slot added successfully!";
        } else {
            $message = "Error adding time slot: " . mysqli_error($conn);
        }
    }
}

// Handle form submission for deleting a time slot
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $delete_id = $_GET['delete'];

    // Delete the time slot
    $delete_query = "DELETE FROM time_slots WHERE id = $delete_id";
    if (mysqli_query($conn, $delete_query)) {
        $message = "Time slot deleted successfully!";
    } else {
        $message = "Error deleting time slot: " . mysqli_error($conn);
    }
}

// Fetch existing time slots from the database
// Fetch existing time slots from the database, grouped by day_of_week
$query = "SELECT * FROM time_slots 
          ORDER BY FIELD(day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'), start_time";
$result = mysqli_query($conn, $query);
$time_slots = mysqli_fetch_all($result, MYSQLI_ASSOC);

// Group time slots by day_of_week
$grouped_time_slots = [];
foreach ($time_slots as $slot) {
    $grouped_time_slots[$slot['day_of_week']][] = $slot;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Time Slots - Dental Clinic Management System</title>
    <link rel="stylesheet" type="text/css" href="css/style.css">
    <style>
        /* Collapsible section styles */
        .day-section {
            background-color: #f4f4f9;
            padding: 10px;
            margin: 5px 0;
            cursor: pointer;
            font-weight: bold;
            text-align: center;
            border: 1px solid #ddd;
            border-radius: 5px;
        }

        .time-slot-list {
            display: none;
            padding-left: 20px;
        }

        .time-slot-item {
            padding: 8px;
            margin: 5px 0;
            background-color: #e9ecef;
            border-radius: 5px;
        }

        .delete-link {
            color: #d9534f;
            text-decoration: none;
        }

        .delete-link:hover {
            text-decoration: underline;
        }
        
.main-content {
    margin-left: 260px; /* Offset for sidebar */
    padding: 40px;
    width: 100%;
}

#manage-time-slots {
    max-width: 800px;
    margin: 0 auto;
    background-color: #ffffff;
    padding: 30px;
    border-radius: 8px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.main-content h1 {
    font-size: 32px;
    margin-bottom: 30px;
    color: #333;
    text-align: center;
}

.main-content h2 {
    font-size: 24px;
    margin-bottom: 20px;
    color: #444;
}

form {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

label {
    font-size: 16px;
    font-weight: bold;
    color: #444;
}

input, select {
    padding: 12px;
    font-size: 16px;
    border-radius: 5px;
    border: 1px solid #ccc;
    width: 100%;
    box-sizing: border-box;
}

button {
    background-color: #0066cc;
    color: white;
    padding: 12px 20px;
    border: none;
    border-radius: 5px;
    font-size: 16px;
    cursor: pointer;
    transition: background-color 0.3s;
}

button:hover {
    background-color: #005bb5;
}

table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 30px;
}

table th, table td {
    padding: 12px;
    text-align: center;
    border: 1px solid #ddd;
}

table th {
    background-color: #f4f4f9;
    font-weight: bold;
}

table tr:nth-child(even) {
    background-color: #f9f9f9;
}

table a {
    color: #d9534f;
    text-decoration: none;
    font-size: 14px;
}

table a:hover {
    text-decoration: underline;
}

.message {
    margin: 20px 0;
    padding: 10px;
    background-color: #f9f9f9;
    border: 1px solid #ddd;
    border-radius: 5px;
    color: #444;
    text-align: center;
}

/* Responsive Design */
@media (max-width: 768px) {
    .sidebar {
        width: 200px;
    }

    .main-content {
        margin-left: 0;
        padding: 20px;
    }

    h1 {
        font-size: 28px;
    }

    h2 {
        font-size: 20px;
    }

    form {
        gap: 15px;
    }

    input, select, button {
        font-size: 14px;
        padding: 10px;
    }
}

    </style>
</head>
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
                <li><a href="reports.php">Reports</a></li>
                <li><a href="admin_settings.php">Settings</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <section id="manage-time-slots">
                <h1>Manage Time Slots</h1>

                <?php
                // Show message if any (success/error)
                if (isset($message)) {
                    echo "<p class='message'>$message</p>";
                }
                ?>

                <!-- Form to Add New Time Slot -->
                <h2>Add New Time Slot</h2>
                <form action="admin_manage_time_slots.php" method="POST">
                    <label for="day_of_week">Day of Week:</label>
                    <select name="day_of_week" id="day_of_week" required>
                        <option value="Monday">Monday</option>
                        <option value="Tuesday">Tuesday</option>
                        <option value="Wednesday">Wednesday</option>
                        <option value="Thursday">Thursday</option>
                        <option value="Friday">Friday</option>
                        <option value="Saturday">Saturday</option>
                        <option value="Sunday">Sunday</option>
                    </select>

                    <label for="start_time">Start Time:</label>
                    <input type="time" name="start_time" id="start_time" required>

                    <label for="appointment_length">Appointment Length (minutes):</label>
                    <input type="number" name="appointment_length" id="appointment_length" value="<?php echo $default_appointment_length; ?>" min="10" required>

                    <label for="end_time">End Time (auto-calculated):</label>
                    <input type="time" name="end_time" id="end_time" value="" readonly>

                    <button type="submit" name="add_time_slot">Add Time Slot</button>
                </form>

                <!-- Display Existing Time Slots -->
                <h2>Existing Time Slots</h2>
                <?php if (!empty($grouped_time_slots)): ?>
                    <?php foreach ($grouped_time_slots as $day => $slots): ?>
                        <div class="day-section" onclick="toggleDay('<?php echo $day; ?>')">
                            <?php echo $day; ?>
                        </div>
                        <div id="time-slots-<?php echo $day; ?>" class="time-slot-list">
                            <?php foreach ($slots as $slot): ?>
                                <div class="time-slot-item">
                                    <span><?php echo $slot['start_time']; ?> - <?php echo $slot['end_time']; ?></span>
                                    <a href="admin_manage_time_slots.php?delete=<?php echo $slot['id']; ?>" class="delete-link" 
                                       onclick="return confirm('Are you sure you want to delete this time slot?')">Delete</a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>No time slots available. Please add new time slots.</p>
                <?php endif; ?>
            </section>
        </main>
    </div>

    <script>
        // JavaScript to toggle the visibility of time slots for each day
        function toggleDay(day) {
            var element = document.getElementById('time-slots-' + day);
            if (element.style.display === "none" || element.style.display === "") {
                element.style.display = "block";
            } else {
                element.style.display = "none";
            }
        }

        // JavaScript to auto-calculate end time based on start time and appointment length
        document.getElementById('start_time').addEventListener('input', function() {
            var startTime = document.getElementById('start_time').value;
            var appointmentLength = document.getElementById('appointment_length').value;
            if (startTime && appointmentLength) {
                var startTimeObj = new Date('1970-01-01T' + startTime + 'Z');
                startTimeObj.setMinutes(startTimeObj.getMinutes() + parseInt(appointmentLength) + 10); // Add buffer time
                var hours = startTimeObj.getUTCHours().toString().padStart(2, '0');
                var minutes = startTimeObj.getUTCMinutes().toString().padStart(2, '0');
                document.getElementById('end_time').value = hours + ':' + minutes;
            }
        });
    </script>
</body>
</html>
