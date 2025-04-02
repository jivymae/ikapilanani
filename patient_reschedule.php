<?php
include 'login_check.php'; // Ensure user is logged in
include 'db_config.php';

// Check if the user is logged in and has the correct role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'patient') {
    header('Location: pat_unauthorized.php');
    exit();
}

// Fetch appointment details
$appointment_id = $_GET['appointment_id'];
$stmt = $conn->prepare("SELECT appointment_date, appointment_time FROM appointments WHERE appointment_id = ? AND patient_id = ?");
$stmt->bind_param("ii", $appointment_id, $_SESSION['user_id']);
$stmt->execute();
$stmt->bind_result($appointment_date, $appointment_time);
$stmt->fetch();
$stmt->close();

$success_message = '';
$error_message = '';

// Check if there's an existing pending reschedule request for this appointment
$existing_request = false;
$stmt = $conn->prepare("SELECT request_id FROM appointment_reschedule_requests WHERE appointment_id = ? AND patient_id = ? AND status = 'pending'");
$stmt->bind_param("ii", $appointment_id, $_SESSION['user_id']);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows > 0) {
    // If there is an existing pending request
    $existing_request = true;
}
$stmt->close();

// Handle rescheduling request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_reschedule'])) {
    if ($existing_request) {
        // If a request is already pending, prevent resubmission
        $error_message = "You have already submitted a reschedule request for this appointment. Please wait for approval.";
    } else {
        $new_date = $_POST['new_date'];
        $new_time = $_POST['new_time'];

        // Create a DateTime object for the new date and time
        $new_appointment_datetime = new DateTime("$new_date $new_time");

        // Validate new date and time
        if ($new_date && $new_time) {
            // Insert the request into the reschedule requests table
            $stmt = $conn->prepare("INSERT INTO appointment_reschedule_requests (patient_id, appointment_id, new_date, new_time, status) VALUES (?, ?, ?, ?, 'pending')");
            $stmt->bind_param("iiss", $_SESSION['user_id'], $appointment_id, $new_date, $new_time);

            if ($stmt->execute()) {
                $success_message = "Reschedule request submitted successfully!";
                $existing_request = true; // Update flag to prevent further submission
            } else {
                $error_message = "Error submitting request: " . $stmt->error;
            }
            $stmt->close();
        } else {
            $error_message = "Please provide a valid date and time.";
        }
    }
}

$conn->close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <title>Request Reschedule - Dental Clinic Management System</title>
</head>
<STYLE>
    /* General Styling */
body {
    font-family: Arial, sans-serif;
    margin: 0;
    padding: 0;
    background-color: #f4f4f9;
}

/* Main Content Section */
.main-content {
    padding: 20px;
}

.dashboard-container {
    width: 80%;
    margin: 0 auto;
    max-width: 1200px;
}

/* Request Reschedule Section */
.request-reschedule {
    background-color: #ffffff;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.request-reschedule h2 {
    font-size: 1.5rem;
    margin-bottom: 20px;
    color: #333;
}

.request-reschedule form {
    display: flex;
    flex-direction: column;
}

.request-reschedule label {
    font-weight: bold;
    margin-bottom: 5px;
    margin-top: 15px;
}

.request-reschedule input[type="date"], 
.request-reschedule select, 
.request-reschedule button {
    font-size: 1rem;
    padding: 10px;
    margin-bottom: 15px;
    border: 1px solid #ccc;
    border-radius: 5px;
    box-sizing: border-box;
}

.request-reschedule button {
    background-color: #007BFF;
    color: white;
    cursor: pointer;
    border: none;
    transition: background-color 0.3s ease;
}

.request-reschedule button:hover {
    background-color: #0056b3;
}

/* Success and Error Messages */
.success, .error {
    font-size: 1.1rem;
    padding: 10px;
    border-radius: 5px;
    margin-bottom: 20px;
}

.success {
    background-color: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.error {
    background-color: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

/* Form Input Styles */
.request-reschedule input[type="date"], 
.request-reschedule select {
    width: 100%;
    max-width: 300px;
}

/* Make the form responsive */
@media (max-width: 768px) {
    .dashboard-container {
        width: 95%;
    }

    .request-reschedule {
        padding: 15px;
    }

    .request-reschedule input[type="date"], 
    .request-reschedule select {
        width: 100%;
        max-width: 100%;
    }

    .request-reschedule h2 {
        font-size: 1.2rem;
    }

    .request-reschedule button {
        font-size: 1rem;
    }
}



    </STYLE>
<body>
    <nav class="navbar">
        <!-- Navbar content -->
    </nav>

    <main class="main-content">
        <div class="dashboard-container">
            <section class="request-reschedule">
                <h2>Request Reschedule for Appointment ID: <?php echo htmlspecialchars($appointment_id); ?></h2>

                <?php if ($success_message !== ''): ?>
                    <p class="success"><?php echo htmlspecialchars($success_message); ?></p>
                <?php elseif ($error_message !== ''): ?>
                    <p class="error"><?php echo htmlspecialchars($error_message); ?></p>
                <?php endif; ?>

                <form method="POST">
                    <label for="new_date">New Appointment Date:</label>
                    <input type="date" name="new_date" id="new_date" required min="<?php echo date('Y-m-d'); ?>">

                    <label for="new_time">New Appointment Time:</label>
                    <select name="new_time" id="new_time" required>
                        <option value="" disabled selected>Select Time</option>
                    </select>

                    <button type="submit" name="request_reschedule">Request Reschedule</button>
                </form>
            </section>
        </div>
    </main>

    <footer>
        <p>&copy; <?php echo date("Y"); ?> Dental Clinic. All rights reserved.</p>
    </footer>

    <script>
     document.getElementById('new_date').addEventListener('change', function() {
    const selectedDate = new Date(this.value);
    const dayOfWeek = selectedDate.toLocaleString('en-US', { weekday: 'long' }).toLowerCase(); // Get the day of the week in lowercase (e.g., "monday")
    const selectedDateString = selectedDate.toISOString().split('T')[0]; // Format the date as YYYY-MM-DD

    // Fetch available time slots for the selected day and date
    fetch(`fetch_time_slots.php?day_of_week=${dayOfWeek}&selected_date=${selectedDateString}`)
        .then(response => response.json())
        .then(data => {
            const timeSelect = document.getElementById('new_time');
            timeSelect.innerHTML = '<option value="" disabled selected>Select Time</option>'; // Reset options

            if (data.length === 0) {
                const option = document.createElement('option');
                option.value = '';
                option.textContent = 'No available time slots';
                timeSelect.appendChild(option);
            } else {
                data.forEach(time => {
                    const option = document.createElement('option');
                    option.value = time;
                    option.textContent = time;

                    // Block past time slots
                    const currentDatetime = new Date();  // Get the current date and time
                    const timeParts = time.split(":"); // Split time into hour and minute
                    const timeDate = new Date(selectedDateString);  // Initialize a date object for the selected day
                    timeDate.setHours(timeParts[0], timeParts[1], 0, 0); // Set the hour and minute

                    // Check if the time is in the past
                    if (timeDate <= currentDatetime) {
                        option.disabled = true;  // Disable past times
                    }

                    timeSelect.appendChild(option);
                });
            }
        })
        .catch(error => {
            console.error('Error fetching time slots:', error);
        });
});

// Adding event listener to the form submission for checking conflict
document.querySelector('form').addEventListener('submit', function(event) {
    const selectedDate = document.getElementById('new_date').value;
    const selectedTime = document.getElementById('new_time').value;

    if (selectedDate && selectedTime) {
        fetch(`check_appointment_conflict.php?new_date=${selectedDate}&new_time=${selectedTime}`)
            .then(response => response.json())
            .then(data => {
                if (data.conflict) {
                    event.preventDefault(); // Prevent form submission
                    alert('There is already a pending appointment at the selected date and time. Please choose another time.');
                }
            })
            .catch(error => console.error('Error checking appointment conflict:', error));
    }
});



    </script>
</body>
</html>
