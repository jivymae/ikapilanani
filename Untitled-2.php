<?php
include 'login_check.php'; // Ensure user is logged in
include 'db_config.php';

// Check if the user is logged in and has the correct role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'patient') {
    header('Location: unauthorized.php');
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

// Initialize available services
$available_services = [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_appointment'])) {
    $appointment_date = $_POST['appointment_date'];
    $appointment_time = $_POST['appointment_time'];
    $dentist_id = $_POST['dentist_id'];
    $selected_services = $_POST['services'] ?? [];

    // Fetch specializations for the selected dentist
    $stmt = $conn->prepare("SELECT spec_id FROM dentist_services WHERE dentist_id = ?");
    $stmt->bind_param('i', $dentist_id);
    $stmt->execute();
    $stmt->bind_result($spec_id);

    $specialization_ids = [];
    while ($stmt->fetch()) {
        $specialization_ids[] = $spec_id;
    }
    $stmt->close();

    // If there are specializations, fetch services related to them
    if (!empty($specialization_ids)) {
        $placeholders = str_repeat('?,', count($specialization_ids) - 1) . '?';
        $stmt_services = $conn->prepare("
            SELECT svc.service_name, svc.price 
            FROM service_specialization ss
            JOIN services svc ON ss.service_id = svc.service_id 
            WHERE ss.spec_id IN ($placeholders)");

        // Bind parameters for specializations
        $stmt_services->bind_param(str_repeat('i', count($specialization_ids)), ...$specialization_ids);
        $stmt_services->execute();
        $stmt_services->bind_result($service_name, $service_price);

        while ($stmt_services->fetch()) {
            $available_services[] = ['name' => $service_name, 'price' => $service_price];
        }
        $stmt_services->close();
    }

    // Calculate total payment amount based on selected services
    $total_payment_amount = 0.00;
    foreach ($selected_services as $service_name) {
        foreach ($available_services as $service) {
            if ($service['name'] === $service_name) {
                $total_payment_amount += $service['price'];
            }
        }
    }

    $payment_method = $_POST['payment_method'];

    // Check if the appointment is at least 2 days away
    $current_date = new DateTime();
    $minimum_date = new DateTime();
    $minimum_date->modify('+2 days');

    if (new DateTime($appointment_date) < $minimum_date) {
        $error_message = "You must choose a date at least 2 days from today.";
    } else {
        // Check appointment slot availability
        $stmt = $conn->prepare("SELECT COUNT(*) FROM appointments WHERE appointment_date = ? AND appointment_time = ? AND dentist_id = ?");
        $stmt->bind_param('ssi', $appointment_date, $appointment_time, $dentist_id);
        $stmt->execute();
        $stmt->bind_result($count);
        $stmt->fetch();
        $stmt->close();

        // Prevent booking if two appointments already exist
        if ($count >= 2) {
            $error_message = "This appointment slot is already taken by two patients. Please choose a different time.";
        } else {
            // Insert the appointment
            $stmt = $conn->prepare("INSERT INTO appointments (patient_id, dentist_id, appointment_date, appointment_time) VALUES (?, ?, ?, ?)");
            $stmt->bind_param('iiss', $user_id, $dentist_id, $appointment_date, $appointment_time);

            if ($stmt->execute()) {
                $appointment_id = $stmt->insert_id;
                $stmt->close();

                // Insert payment record with total amount for services
                $stmt = $conn->prepare("INSERT INTO payments (appointment_id, patient_id, payment_amount, payment_status, payment_method, payment_date) VALUES (?, ?, ?, 'pending', ?, NOW())");
                $stmt->bind_param('iids', $appointment_id, $user_id, $total_payment_amount, $payment_method);

                if ($stmt->execute()) {
                    // Insert selected services into appointment_services
                    foreach ($selected_services as $service_name) {
                        $stmt = $conn->prepare("SELECT service_id FROM services WHERE service_name = ?");
                        $stmt->bind_param('s', $service_name);
                        $stmt->execute();
                        $stmt->bind_result($service_id);
                        if ($stmt->fetch()) {
                            $stmt->close();
                            $stmt = $conn->prepare("INSERT INTO appointment_services (appointment_id, service_id) VALUES (?, ?)");
                            $stmt->bind_param('ii', $appointment_id, $service_id);
                            $stmt->execute();
                        }
                        $stmt->close();
                    }

                    $success_message = "Appointment booked successfully! Payment of PHP " . htmlspecialchars(number_format($total_payment_amount, 2)) . " is pending confirmation.";
                } else {
                    $error_message = "Error booking appointment: " . $stmt->error;
                }
            }
        }
    }
}

// Fetch available dentists for the dropdown
$dentists = [];
$stmt = $conn->prepare("SELECT user_id, username FROM users WHERE role = 'dentist'");
$stmt->execute();
$stmt->bind_result($dentist_id, $dentist_username);
while ($stmt->fetch()) {
    $dentists[] = ['id' => $dentist_id, 'username' => $dentist_username];
}
$stmt->close();

// Generate time slots from 9:00 AM to 4:30 PM (30-minute intervals), excluding 12 PM to 1 PM
$time_slots = [];
$start_time = new DateTime('09:00 AM');
$end_time = new DateTime('04:30 PM');

while ($start_time <= $end_time) {
    if ($start_time->format('h:i A') === '12:00 PM') {
        $start_time->modify('+1 hour'); // Skip to 1:00 PM
        continue;
    }
    
    $time_slots[] = $start_time->format('h:i A');
    $start_time->modify('+30 minutes');
}

// Fetch patient appointments along with payment status
$appointments = [];
$stmt = $conn->prepare("
    SELECT a.appointment_id, a.dentist_id, a.appointment_date, a.appointment_time, 
           p.payment_status 
    FROM appointments a
    LEFT JOIN payments p ON a.appointment_id = p.appointment_id 
    WHERE a.patient_id = ? AND a.appointment_status NOT IN ('cancelled', 'completed')");

$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($appointment_id, $dentist_id, $appointment_date, $appointment_time, $payment_status);
while ($stmt->fetch()) {
    $appointments[] = [
        'id' => $appointment_id,
        'dentist_id' => $dentist_id,
        'date' => $appointment_date,
        'time' => $appointment_time,
        'payment_status' => $payment_status
    ];
}
$stmt->close();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" type="text/css" href="css/patient.css">
    <title>Book Appointment - Dental Clinic Management System</title>
    <style>
        /* Your existing styles */
        <style>
        /* Your existing styles */
        .book-appointment, .appointment-list {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        h2 {
            margin-top: 0;
        }
        label {
            display: block;
            margin: 10px 0 5px;
        }
        input[type="text"], input[type="date"], input[type="time"], select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        button {
            padding: 10px 15px;
            background-color: #28a745;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        button:hover {
            background-color: #218838;
        }
        .error {
            color: red;
        }
        .success {
            color: green;
        }
        .book-appointment, .appointment-list {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        h2 {
            margin-top: 0;
        }
        label {
            display: block;
            margin: 10px 0 5px;
        }
        input, select {
            width: 100%;
            padding: 10px;
            margin-bottom: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        button {
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            background-color: #28a745;
            color: white;
            cursor: pointer;
        }
        button:hover {
            background-color: #218838;
        }
        .success {
            color: green;
        }
        .error {
            color: red;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        /* General styles for the form container */
form {
    background: white;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
    margin-bottom: 20px;
}

/* Style for headings within the form */
form h2 {
    margin-top: 0;
    color: #333;
}

/* Style for labels */
form label {
    display: block;
    margin: 15px 0 5px;
    font-weight: bold;
    color: #555;
}

/* Style for input fields and selects */
form input[type="date"],
form select {
    width: 100%;
    padding: 10px;
    margin-bottom: 15px;
    border: 1px solid #ccc;
    border-radius: 4px;
    font-size: 16px;
    transition: border-color 0.3s;
}

/* Focus effect for input fields and selects */
form input[type="date"]:focus,
form select:focus {
    border-color: #007bff;
    outline: none;
}

/* Style for checkboxes */
form input[type="checkbox"] {
    margin-right: 10px;
}

/* Style for the booking button */
form button {
    width: 100%;
    padding: 10px;
    background-color: #28a745;
    border: none;
    border-radius: 4px;
    color: white;
    font-size: 16px;
    cursor: pointer;
    transition: background-color 0.3s;
}

/* Hover effect for the booking button */
form button:hover {
    background-color: #218838;
}

/* Success and error messages */
.success {
    color: green;
    margin-top: 15px;
}

.error {
    color: red;
    margin-top: 15px;
}

/* Responsive design */
@media (max-width: 600px) {
    form {
        padding: 15px;
    }

    form button {
        font-size: 14px;
    }
}
    </style>
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="logo">
            <img src="images/cometaicon.png" alt="Dental Clinic Logo">
            <h1>Dental Clinic</h1>
        </div>
        <ul class="nav-links">
            <li><a href="patient_dashboard.php">Dashboard</a></li>
            <li><a href="patient_appointments.php" class="active">Appointments</a></li>
            <li><a href="patient_message.php">Messages</a></li>
            <li><a href="patient_profile.php">Profile</a></li>
            <li><a href="logout.php">Logout</a></li>
        </ul>
    </nav>

    <?php if ($error_message): ?>
        <div class="error"><?php echo htmlspecialchars($error_message); ?></div>
    <?php endif; ?>
    <?php if ($success_message): ?>
        <div class="success"><?php echo htmlspecialchars($success_message); ?></div>
    <?php endif; ?>

    <div class="book-appointment">
        <h2>Book Appointment</h2>
        <form action="patient_appointments.php" method="post">
            <label for="dentist">Select Dentist:</label>
            <select name="dentist_id" id="dentist" required onchange="fetchServices()">
                <option value="">-- Select Dentist --</option>
                <?php foreach ($dentists as $dentist): ?>
                    <option value="<?php echo $dentist['id']; ?>"><?php echo htmlspecialchars($dentist['username']); ?></option>
                <?php endforeach; ?>
            </select>

            <div id="services-container" style="display:none;">
                <label>Select Services (Maximum 2 required):</label>
                <div id="services">
                    <?php foreach ($available_services as $service): ?>
                        <div>
                            <input type="checkbox" name="services[]" id="<?php echo htmlspecialchars($service['name']); ?>" value="<?php echo htmlspecialchars($service['name']); ?>">
                            <label for="<?php echo htmlspecialchars($service['name']); ?>">
                                <?php echo htmlspecialchars($service['name']); ?> - PHP <?php echo htmlspecialchars(number_format($service['price'], 2)); ?>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <label for="appointment_date">Date:</label>
            <input type="date" name="appointment_date" required>

            <label for="appointment_time">Time:</label>
            <select name="appointment_time" required>
                <option value="">-- Select Time --</option>
                <?php foreach ($time_slots as $time_slot): ?>
                    <option value="<?php echo htmlspecialchars($time_slot); ?>"><?php echo htmlspecialchars($time_slot); ?></option>
                <?php endforeach; ?>
            </select>

            <label for="payment_method">Payment Method:</label>
            <select name="payment_method" required>
                <option value="credit_card">Credit Card</option>
                <option value="cash">Cash</option>
                <option value="insurance">Insurance</option>
            </select>

            <input type="submit" name="book_appointment" value="Book Appointment">
        </form>
    </div>

    <script>
        function fetchServices() {
            const dentistId = document.getElementById('dentist').value;
            const servicesContainer = document.getElementById('services-container');

            if (dentistId) {
                const xhr = new XMLHttpRequest();
                xhr.open('POST', 'fetch_services.php', true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                xhr.onload = function() {
                    if (this.status === 200) {
                        const response = JSON.parse(this.responseText);
                        const servicesDiv = document.getElementById('services');
                        servicesDiv.innerHTML = ''; // Clear existing options

                        response.forEach(function(service) {
                            const div = document.createElement('div');
                            div.innerHTML = `
                                <input type="checkbox" name="services[]" id="${service.name}" value="${service.name}">
                                <label for="${service.name}">${service.name} - PHP ${parseFloat(service.price).toFixed(2)}</label>
                            `;
                            servicesDiv.appendChild(div);
                        });

                        servicesContainer.style.display = 'block'; // Show services container
                    }
                };
                xhr.send('dentist_id=' + dentistId);
            } else {
                servicesContainer.style.display = 'none'; // Hide services container if no dentist selected
            }
        }

        document.querySelector('form').onsubmit = function(e) {
            const selectedServices = Array.from(document.querySelectorAll('input[name="services[]"]:checked'));
            
            if (selectedServices.length > 2) {
                e.preventDefault(); // Prevent form submission
                alert('You can select a maximum of 2 services.');
            } else if (selectedServices.length < 1) {
                e.preventDefault(); // Prevent form submission
                alert('Please select at least 1 service.');
            }
        };
    </script>

    <section class="appointment-list">
        <h2>Your Appointments</h2>
        <table>
            <thead>
                <tr>
                    <th>Appointment ID</th>
                    <th>Date</th>
                    <th>Time</th>
                    <th>Payment Status</th>
                    <th>Action</th>
                    <th>Send Appointment</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($appointments)): ?>
                    <tr>
                        <td colspan="6">No appointments booked.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($appointments as $appointment): ?>
                        <tr>
                            <td>
                                <a href="patient_reschedule.php?appointment_id=<?php echo htmlspecialchars($appointment['id']); ?>">
                                    <?php echo htmlspecialchars($appointment['id']); ?>
                                </a>
                            </td>
                            <td><?php echo htmlspecialchars($appointment['date']); ?></td>
                            <td><?php echo htmlspecialchars($appointment['time']); ?></td>
                            <td><?php echo htmlspecialchars($appointment['payment_status']); ?></td>
                            <td>
                                <form method="POST">
                                    <input type="hidden" name="appointment_id" value="<?php echo htmlspecialchars($appointment['id']); ?>">
                                    <button type="submit" name="cancel_appointment">Cancel Appointment</button>
                                </form>
                            </td>
                            <td>
                                <form method="POST" action="send_appointment.php">
                                    <input type="hidden" name="appointment_id" value="<?php echo htmlspecialchars($appointment['id']); ?>">
                                    <?php if ($appointment['payment_status'] !== 'Cash'): ?>
                                        <button type="submit" name="send_appointment">Send Appointment</button>
                                    <?php endif; ?>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </section>
</body>
</html>
