<?php
include 'login_check.php';  // Ensure user is logged in
include 'db_config.php';

// Check if the user is logged in and has the correct role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'patient') {
    header('Location: pat_unauthorized.php');
    exit();
}

// Fetch patient information
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT username, email FROM users WHERE user_id = ? AND role = 'patient'");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$stmt->bind_result($username, $email);
$stmt->fetch();
$stmt->close();

// Handle appointment booking
$success_message = '';
$error_message = '';
// Handle appointment booking
// Handle appointment booking
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_appointment'])) {
    // Collect form data
    $dentist_id = $_POST['dentist_id'];
    $appointment_date = $_POST['appointment_date'];
    $appointment_time = $_POST['appointment_time'] ?? []; // Collect selected time(s)
    $selected_services = $_POST['services'] ?? [];
    $payment_method_id = $_POST['payment_method']; // Method ID (from the dropdown)

    // Validate and ensure a time is selected
    if (empty($appointment_time)) {
        $error_message = 'Please select an appointment time.';
    } else {
        // Check if the appointment already exists
        $conflict_found = false;
        foreach ($appointment_time as $time) {
            $stmt = $conn->prepare("SELECT appointment_id FROM appointments WHERE dentist_id = ? AND appointment_date = ? AND appointment_time = ?");
            $stmt->bind_param('iss', $dentist_id, $appointment_date, $time);
            $stmt->execute();
            $stmt->store_result();
            
            if ($stmt->num_rows > 0) {
                // A conflict was found
                $conflict_found = true;
                break;
            }
            $stmt->close();
        }

        // If there's a conflict, display an error
        if ($conflict_found) {
            $error_message = 'The selected date and time is already booked. Please choose another slot.';
        } else {
            // Store the appointment data in the session
            $_SESSION['appointment_data'] = [
                'dentist_id' => $dentist_id,
                'appointment_date' => $appointment_date,
                'appointment_time' => $appointment_time, // Store selected times
                'services' => $selected_services,
                'payment_method' => $payment_method_id
            ];

            // Redirect to the appointment summary page
            header('Location: patient_appointment_summary.php');
            exit();
        }
    }
}





// Fetch available dentists for dropdown
$dentists = [];
$stmt = $conn->prepare("SELECT user_id AS dentist_id, username FROM users WHERE role = 'dentist' AND user_id NOT IN (SELECT user_id FROM dentists WHERE archived = 1)");
$stmt->execute();
$stmt->bind_result($dentist_id, $dentist_username);
while ($stmt->fetch()) {
    $dentists[] = ['id' => $dentist_id, 'username' => $dentist_username];
}
$stmt->close();

// Fetch payment methods from the database
$payment_methods = [];
$stmt = $conn->prepare("SELECT method_id, method_name FROM payment_methods");
$stmt->execute();
$stmt->bind_result($method_id, $method_name);
while ($stmt->fetch()) {
    $payment_methods[] = ['id' => $method_id, 'name' => $method_name];
}
$stmt->close();

// Fetch available services for the checkbox dropdown
$services = [];
$stmt = $conn->prepare("SELECT service_id, service_name, price FROM services");
$stmt->execute();
$stmt->bind_result($service_id, $service_name, $price);
while ($stmt->fetch()) {
    $services[] = [
        'service_id' => $service_id,
        'service_name' => $service_name,
        'price' => $price
    ];
}
$stmt->close();

// Fetch available time slots based on the selected day of the week
if (isset($_GET['appointment_date'])) {
    $appointment_date = $_GET['appointment_date'];
    $day_of_week = date('l', strtotime($appointment_date)); // Get the day of the week from the selected date

    // Fetch time slots for the selected day of the week
    $time_slots = [];
    $stmt = $conn->prepare("SELECT start_time FROM timeslot WHERE day_of_week = ?");
    $stmt->bind_param('s', $day_of_week);
    $stmt->execute();
    $stmt->bind_result($start_time);
    while ($stmt->fetch()) {
        $time_slots[] = $start_time;
    }
    $stmt->close();

    // Return time slots as JSON
    echo json_encode(['day' => $day_of_week, 'times' => $time_slots]);
    exit();
}


// Fetch closure dates
$closures = [];
$stmt = $conn->prepare("SELECT closure_date, cause FROM clinic_closures");
$stmt->execute();
$stmt->bind_result($closure_date, $cause);
while ($stmt->fetch()) {
    $closures[] = ['date' => $closure_date, 'cause' => $cause];
}
$stmt->close();


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/patient.css">
    <title>Book Appointment - Dental Clinic Management System</title>
    <script>
      // Disable holiday dates in the date picker
window.onload = function() {
    const closureDates = <?php echo json_encode($closures); ?>; // Pass closures to JS

    const dateInput = document.getElementById('appointment_date');
    const disabledDates = closureDates.map(function(closure) {
        return closure.date; // Ensure closure.date is in 'YYYY-MM-DD' format
    });

    dateInput.setAttribute("min", new Date().toISOString().split("T")[0]); // Disable past dates

    // Block specific dates in the date picker
    dateInput.addEventListener('input', function() {
        if (disabledDates.includes(dateInput.value)) {
            alert('The selected date is a holiday. Please choose another date.');
            dateInput.value = ''; // Clear the invalid date
        }
    });

    // Block specific dates in the date picker
    dateInput.addEventListener('focus', function() {
        disabledDates.forEach(function(date) {
            // Check for disabled dates and make sure input fields are clear if invalid
            if (dateInput.value === date) {
                dateInput.setCustomValidity("The clinic is closed on this date.");
            } else {
                dateInput.setCustomValidity("");
            }
        });
    });

    // Disable timeslots based on current time and selected date
    document.getElementById('appointment_date').addEventListener('change', function() {
        const selectedDate = this.value;
        const currentDate = new Date();
        const selectedDateTime = new Date(`${selectedDate}T00:00:00`);

        if (selectedDateTime < currentDate) {
            alert('You cannot select a past date.');
            document.getElementById('appointment_date').value = '';
        } else {
            // Enable time slots based on the selected day
            updateTimeSlots();
        }
    });

};

// Function to fetch available time slots based on selected date
// Function to fetch available time slots based on selected date
function updateTimeSlots() {
    const appointmentDate = document.getElementById('appointment_date').value;
    const appointmentTimeCheckboxes = document.querySelectorAll('.time-checkbox');

    if (appointmentDate) {
        // Check if the selected date is a clinic closure
        const closureDates = <?php echo json_encode($closures); ?>; // Pass closures to JS
        const isClosed = closureDates.some(function(closure) {
            return closure.date === appointmentDate; // Ensure date format is matching
        });

        if (isClosed) {
            alert('The clinic is closed on the selected date. Please choose another date.');
            document.getElementById('appointment_date').value = ''; // Optionally, clear the selected date
            return;
        }

        // Get the day of the week from the selected date
        const date = new Date(appointmentDate);
        const options = { weekday: 'long' };
        const dayOfWeek = new Intl.DateTimeFormat('en-US', options).format(date); // e.g., "Monday"

        const xhr = new XMLHttpRequest();
        xhr.open('GET', `fetch_time_slots.php?day_of_week=${dayOfWeek}`, true);
        xhr.onload = function() {
            if (xhr.status === 200) {
                const response = JSON.parse(xhr.responseText);
                const timeContainer = document.getElementById('time-checkboxes');
                timeContainer.innerHTML = ''; // Clear previous time slots

                // Populate time slots as checkboxes
                response.forEach(function(time) {
                    const checkboxDiv = document.createElement('div');
                    checkboxDiv.innerHTML = `<label>
                        <input type="checkbox" name="appointment_time[]" value="${time}" class="time-checkbox">
                        ${time}
                    </label>`;
                    timeContainer.appendChild(checkboxDiv);
                });

                // Disable past time slots based on current time and selected date
                const now = new Date();
                const selectedDate = new Date(appointmentDate);

                // Iterate through all the time checkboxes and disable past ones
                const timeCheckboxes = document.querySelectorAll('.time-checkbox');
                timeCheckboxes.forEach(function(checkbox) {
                    const timeSlot = checkbox.value;
                    const timeParts = timeSlot.split(':');  // Assuming time format is 'HH:mm'
                    const timeSlotDate = new Date(selectedDate.setHours(timeParts[0], timeParts[1]));

                    if (timeSlotDate < now) {
                        checkbox.disabled = true;
                        checkbox.parentElement.style.color = '#ccc'; // Disable style
                    } else {
                        checkbox.disabled = false;
                        checkbox.parentElement.style.color = ''; // Reset style
                    }
                });
            }
        };
        xhr.send();
    }
}

// Function to check if time is already booked
function checkAppointmentConflict() {
    const appointmentDate = document.getElementById('appointment_date').value;
    const selectedTimes = [...document.querySelectorAll('.time-checkbox:checked')].map(checkbox => checkbox.value);

    if (appointmentDate && selectedTimes.length > 0) {
        // Fetch appointment conflict from the server
        const xhr = new XMLHttpRequest();
        xhr.open('POST', 'check_appointment_conflict.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onload = function() {
            if (xhr.status === 200) {
                const response = JSON.parse(xhr.responseText);
                if (response.conflict) {
                    alert('The selected time slot is already booked. Please choose another one.');
                }
            }
        };
        xhr.send(`appointment_date=${appointmentDate}&appointment_time=${selectedTimes.join(',')}`);
    }
}


// Function to calculate the total amount based on selected services
function calculateTotal() {
    const checkboxes = document.querySelectorAll('input[name="services[]"]:checked');
    let totalAmount = 0;

    checkboxes.forEach(function(checkbox) {
        const price = parseFloat(checkbox.getAttribute('data-price'));
        if (!isNaN(price)) {
            totalAmount += price;
        }
    });

    // Update the total display and hidden input
    document.getElementById('total-display').textContent = `Total Amount: PHP ${totalAmount.toFixed(2)}`;
    document.getElementById('total-amount').value = totalAmount.toFixed(2);
}

// Add event listeners to checkboxes for calculating total on change
window.onload = function() {
    const serviceCheckboxes = document.querySelectorAll('input[name="services[]"]');
    serviceCheckboxes.forEach(function(checkbox) {
        checkbox.addEventListener('change', calculateTotal);
    });
};

    </script>
</head>

<style>
    /* General Styles */
       /* General Reset */
       body, h1, h2, p, ul, li, table, th, td {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
  font-family: Arial, sans-serif;
  margin: 0;
  padding: 0;
  color: #333;
  background-color: #f4f4f4;
}


        /* Navigation Bar */
        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: #00bfff;
            color: #fff;
            padding: 0.7rem 1rem;
            position: relative;
        }

        .navbar .logo {
            display: flex;
            align-items: center;
        }

        .navbar .logo img {
            height: 50px;
            margin-right: 10px;
        }

        .navbar .logo h1 {
            font-size: 1.5rem;
            font-weight: bold;
        }

        .navbar .nav-links {
            list-style: none;
            display: flex;
            gap: 1rem;
            transition: all 0.3s ease;
        }

        .navbar .nav-links a {
            text-decoration: none;
            color: #fff;
            font-weight: ;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            transition: background-color 0.3s;
        }

        .navbar .nav-links a.active, .navbar .nav-links a:hover {
            background-color: #0056b3;
        }

        /* Hamburger Menu */
        .hamburger {
            display: none;
            flex-direction: column;
            justify-content: space-around;
            width: 30px;
            height: 25px;
            cursor: pointer;
        }

        .hamburger span {
            background-color: #fff;
            height: 3px;
            width: 100%;
            border-radius: 3px;
        }


/* Appointment Booking Container */
.book-appointment {
    background-color: white;
    padding: 30px;
    max-width: 500px;
    margin: 20px auto;
    border-radius: 10px;
    box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
}

.book-appointment h2 {
    text-align: center;
    color: #007bff;
    margin-bottom: 20px;
    font-size: 1.8rem;
}

/* Form Label */
.book-appointment label {
    display: block;
    font-size: 1rem;
    color: #333;
    margin-bottom: 8px;
    margin-top: 15px;
}

/* Select and Input Fields */
.book-appointment select,
.book-appointment input[type="date"],
.book-appointment input[type="text"],
.book-appointment input[type="hidden"] {
    width: 100%;
    padding: 12px;
    font-size: 1rem;
    border: 1px solid #ccc;
    border-radius: 5px;
    margin-bottom: 20px;
    box-sizing: border-box;
    background-color: #f9f9f9;
}

.book-appointment select:focus,
.book-appointment input[type="date"]:focus {
    border-color: #00796b;
    background-color: #ffffff;
    outline: none;
}

/* Checkbox for Services */
.book-appointment input[type="checkbox"] {
    margin-right: 10px;
}

.book-appointment div {
    margin-top: 10px;
}

.book-appointment div label {
    display: block;
    font-size: 1rem;
    color: #333;
    margin-bottom: 10px;
}

/* Payment Method Dropdown */
.book-appointment select[name="payment_method"] {
    padding: 12px;
}

/* Total Amount Display */
#total-display {
    font-size: 1.2rem;
    color: #00bfff;
    font-weight: bold;
}

/* Submit Button */
.book-appointment input[type="submit"] {
    background-color: #00bfff;
    color: white;
    padding: 15px 25px;
    font-size: 1.1rem;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    width: 100%;
    transition: background-color 0.3s ease;
}

/* Submit Button Default Styles */
.book-appointment input[type="submit"] {
    background-color: #00bfff;
    color: white;
    border: none;
    cursor: pointer;
    font-size: 1.1rem;
    padding: 12px;
    border-radius: 5px;
    transition: transform 0.3s ease, background-color 0.3s ease; /* Smooth transition */
}

/* Zoom Effect on Hover */
.book-appointment input[type="submit"]:hover {
    transform: scale(1.1); /* Enlarges the button by 10% */
    background-color: #00bfff; /* Changes color for visual feedback */
}

/* Zoom Effect on Click */
.book-appointment input[type="submit"]:active {
    transform: scale(1.05); /* Slightly smaller zoom when clicked */
    background-color: #00bfff; /* Darker color for active state */
}


/* Error and Success Messages */
.error, .success {
    padding: 10px;
    color: white;
    border-radius: 5px;
    margin: 15px 0;
    font-size: 1rem;
    text-align: center;
}

.error {
    background-color: #e53935; /* Red color for errors */
}

.success {
    background-color: #2e7d32; /* Green color for success */
}
.dentist{
    padding: 15px 25px;
    font-size: 1.1rem;
    border: none;
    border-radius: 5px;
    cursor: pointer;
}

/* Responsive Design */
@media (max-width: 768px) {
            .navbar .nav-links {
                display: none;
                flex-direction: column;
                position: absolute;
                top: 100%;
                left: 0;
                width: 100%;
                background-color: #00bfff;
                padding: 1rem 0;
                z-index: 10;
            }

            .navbar .nav-links.show {
                display: flex;
            }

            .hamburger {
                display: flex;
            }
        }
    

    </style>
<body>
<nav class="navbar">
        <div class="logo">
            <img src="images/lads.png" alt="Dental Clinic Logo">
            <h1>LAD DCAMS</h1>
        </div>
        <div class="hamburger" onclick="toggleMenu()">
            <span></span>
            <span></span>
            <span></span>
        </div>
        <ul class="nav-links">
            <li><a href="patient_dashboard.php">Dashboard</a></li>
            <li><a href="patient_appointments.php" class="active">Appointments</a></li>
            <li><a href="patient_message.php">Messages</a></li>
            <li><a href="patient_profile.php">Profile</a></li>
            <li><a href="logout.php">Logout</a></li>
        </ul>
    </nav>
    <script>
        function toggleMenu() {
            const navLinks = document.querySelector('.nav-links');
            navLinks.classList.toggle('show');
        }
    </script>

    <!-- Display Messages (Error/Success) -->
    <?php if ($error_message): ?>
        <div class="error"><?= htmlspecialchars($error_message) ?></div>
    <?php endif; ?>
    <?php if ($success_message): ?>
        <div class="success"><?= htmlspecialchars($success_message) ?></div>
    <?php endif; ?>

    <!-- Appointment Booking Form -->
   <!-- Updated Form -->
<div class="book-appointment">
    <h2>Book Appointment</h2>
    <form action="patient_appointments.php" method="post" onsubmit="return validateForm()">
        <label for="dentist">Select Dentist:</label>
        <select name="dentist_id" id="dentist" required>
            <option value="">-- Select Dentist --</option>
            <?php foreach ($dentists as $dentist): ?>
                <option value="<?= $dentist['id'] ?>"><?= htmlspecialchars($dentist['username']) ?></option>
            <?php endforeach; ?>
        </select>

        <label>Select Services:</label>
        <div>
            <?php foreach ($services as $service): ?>
                <label>
                    <input type="checkbox" name="services[]" value="<?= $service['service_id'] ?>" data-price="<?= $service['price'] ?>">
                    <?= htmlspecialchars($service['service_name']) ?> - PHP <?= number_format($service['price'], 2) ?>
                </label><br>
            <?php endforeach; ?>
        </div>

        <label for="appointment_date">Date:</label>
        <input type="date" name="appointment_date" id="appointment_date" required onchange="updateTimeSlots()">

        <label for="appointment_time">Time:</label>
    <div id="time-checkboxes">
        <!-- Dynamically generated time checkboxes will appear here -->
    </div>


        <label for="payment_method">Payment Method:</label>
        <select name="payment_method" required>
            <option value="">-- Select Payment Method --</option>
            <?php foreach ($payment_methods as $method): ?>
                <option value="<?= htmlspecialchars($method['id']) ?>"><?= htmlspecialchars($method['name']) ?></option>
            <?php endforeach; ?>
        </select>

        <label for="total-amount"></label>
        <input type="hidden" name="total_amount" id="total-amount" value="0.00">
        <p id="total-display">Total Amount: PHP 0.00</p>

        <input type="submit" name="book_appointment" value="Book Appointment">
    </form>
</div>

<script>
    // Function to validate the form before submission
    function validateForm() {
        const checkboxes = document.querySelectorAll('input[name="services[]"]:checked');
        if (checkboxes.length === 0) {
            alert('Please select at least one service.');
            return false;
        }
        return true;
    }
</script>

    </div>

</body>
</html>
