<?php
include 'login_check.php'; // Ensure user is logged in
include 'db_config.php';
include 'admin_check.php'; // Include the admin check helper

// Check if the appointment ID is provided
if (!isset($_GET['id'])) {
    header('Location: appointments.php');
    exit();
}

$appointment_id = $_GET['id'];

// Fetch the appointment details
$sql = "SELECT appointment_date, appointment_time, appointment_status, patient_id, dentist_id FROM appointments WHERE appointment_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $appointment_id);
$stmt->execute();
$result = $stmt->get_result();
$appointment = $result->fetch_assoc();
$stmt->close();

if (!$appointment) {
    header('Location: appointments.php');
    exit();
}

// Fetch the already assigned services for this appointment
$assigned_services_sql = "SELECT service_id FROM appointment_services WHERE appointment_id = ?";
$assigned_services_stmt = $conn->prepare($assigned_services_sql);
$assigned_services_stmt->bind_param("i", $appointment_id);
$assigned_services_stmt->execute();
$assigned_services_result = $assigned_services_stmt->get_result();
$assigned_services = [];
while ($row = $assigned_services_result->fetch_assoc()) {
    $assigned_services[] = $row['service_id'];
}
$assigned_services_stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $appointment_date = $_POST['appointment_date'];
    $appointment_time = $_POST['appointment_time'];
    $appointment_status = $_POST['appointment_status'];
    $selected_services = isset($_POST['services']) ? $_POST['services'] : [];
    $payment_method = $_POST['payment_method']; // Get the selected payment method

    // Update the appointment
    $update_sql = "UPDATE appointments SET appointment_date = ?, appointment_time = ?, appointment_status = ? WHERE appointment_id = ?";
    $update_stmt = $conn->prepare($update_sql);
    $update_stmt->bind_param("sssi", $appointment_date, $appointment_time, $appointment_status, $appointment_id);
    $update_stmt->execute();
    $update_stmt->close();

    // If the status is 'paid', update the payment_status and payment method in the payments table
    if ($appointment_status === 'paid') {
        $patient_id = $appointment['patient_id'];
        
        $payment_update_sql = "UPDATE payments SET payment_status = 'paid', method_id = ? WHERE patient_id = ? AND appointment_id = ?";
        $payment_update_stmt = $conn->prepare($payment_update_sql);
        $payment_update_stmt->bind_param("iii", $payment_method, $patient_id, $appointment_id);
        $payment_update_stmt->execute();
        $payment_update_stmt->close();
    }

    

    // Remove unselected services
    foreach ($assigned_services as $service_id) {
        if (!in_array($service_id, $selected_services)) {
            $remove_service_sql = "DELETE FROM appointment_services WHERE appointment_id = ? AND service_id = ?";
            $remove_service_stmt = $conn->prepare($remove_service_sql);
            $remove_service_stmt->bind_param("ii", $appointment_id, $service_id);
            $remove_service_stmt->execute();
            $remove_service_stmt->close();
        }
    }

    // Add newly selected services
    foreach ($selected_services as $service_id) {
        if (!in_array($service_id, $assigned_services)) {
            $add_service_sql = "INSERT INTO appointment_services (appointment_id, service_id) VALUES (?, ?)";
            $add_service_stmt = $conn->prepare($add_service_sql);
            $add_service_stmt->bind_param("ii", $appointment_id, $service_id);
            $add_service_stmt->execute();
            $add_service_stmt->close();
        }
    }

    // Calculate the updated total amount
    $total_amount = 0;
    $fetch_services_sql = "
        SELECT SUM(s.price) AS total 
        FROM services s 
        JOIN appointment_services asv ON s.service_id = asv.service_id 
        WHERE asv.appointment_id = ?";
    $fetch_services_stmt = $conn->prepare($fetch_services_sql);
    $fetch_services_stmt->bind_param("i", $appointment_id);
    $fetch_services_stmt->execute();
    $fetch_services_result = $fetch_services_stmt->get_result();
    if ($row = $fetch_services_result->fetch_assoc()) {
        $total_amount = $row['total'];
    }
    $fetch_services_stmt->close();

    // Update the total amount in the payments table
    $update_payment_sql = "UPDATE payments SET total_amount = ? WHERE appointment_id = ?";
    $update_payment_stmt = $conn->prepare($update_payment_sql);
    $update_payment_stmt->bind_param("di", $total_amount, $appointment_id);
    $update_payment_stmt->execute();
    $update_payment_stmt->close();

    // Redirect with a success message
    header('Location: appointments.php?msg=Appointment updated successfully.');
    exit();
}

// Fetch the available services for the dentist
$dentist_id = $appointment['dentist_id'];
$services_sql = "SELECT s.service_id, s.service_name, s.price FROM services s 
                 JOIN dentist_services ds ON s.service_id = ds.service_id 
                 WHERE ds.user_id = ?";
$services_stmt = $conn->prepare($services_sql);
$services_stmt->bind_param("i", $dentist_id);
$services_stmt->execute();
$services_result = $services_stmt->get_result();
$services = [];
while ($row = $services_result->fetch_assoc()) {
    $services[] = $row;
}
$services_stmt->close();


// Fetch available payment methods
$payment_methods_sql = "SELECT method_id, method_name FROM payment_methods";
$payment_methods_stmt = $conn->prepare($payment_methods_sql);
$payment_methods_stmt->execute();
$payment_methods_result = $payment_methods_stmt->get_result();
$payment_methods = [];
while ($row = $payment_methods_result->fetch_assoc()) {
    $payment_methods[] = $row;
}
$payment_methods_stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Appointment</title>
    <link rel="stylesheet" type="text/css" href="css/style.css">
</head>
<style>
        /* General Styles */
        <style>
/* General Styles */
.main-content {
    max-width: 500px; /* Adjusted for a wider layout */
    margin: 5% auto; /* Center the content vertically and horizontally */
    padding: 30px;
    background-color: #ffffff;
    border: 2px solid #0066cc; /* Add border to make it a box */
    border-radius: 8px; /* Rounded corners */
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); /* Optional shadow for depth */
}

.main-content h1 {
    font-size: 32px;
    margin-bottom: 20px;
    color: #333;
    text-align: center;
}

form {
    display: flex;
    flex-direction: column;
    gap: 20px; /* Added more space between form fields */
}

form label {
    font-size: 16px;
    font-weight: bold;
    color: #444;
}

form input,
form select,
form button {
    padding: 12px;
    font-size: 16px;
    border-radius: 5px;
    border: 1px solid #ccc;
    width: 100%;
}

form button {
    background-color: #0066cc;
    color: white;
    cursor: pointer;
    font-size: 16px;
    transition: background-color 0.3s;
}

form button:hover {
    background-color: #005bb5;
}

/* Services Section Styles */
#services {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); /* Responsive grid */
    gap: 10px; /* Space between items */
}

#services label {
    display: flex;
    align-items: center;
    background-color: #f9f9f9;
    padding: 10px;
    border-radius: 5px;
    border: 1px solid #ddd;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1); /* Subtle shadow */
    transition: background-color 0.3s, box-shadow 0.3s;
}

#services label:hover {
    background-color: #f0f8ff; /* Light blue hover effect */
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.15); /* More pronounced shadow */
}

#services input[type="checkbox"] {
    margin-right: 10px;
    accent-color: #0066cc; /* Checkbox color */
}

#services span {
    font-size: 14px;
    color: #555;
}

/* Link Styles */
.main-content a {
    display: inline-block;
    margin-top: 20px;
    text-align: center;
    padding: 10px 20px;
    background-color: #777;
    color: white;
    text-decoration: none;
    border-radius: 5px;
    font-size: 16px;
    transition: background-color 0.3s;
}

.main-content a:hover {
    background-color: #555;
}
/* Back Button Styles */
.back-button {
    display: inline-block;
    margin-bottom: 20px;
    padding: 10px 15px;
    background-color: #3498db;
    color: #fff;
    text-decoration: none;
    border-radius: 5px;
    font-size: 14px;
    transition: background-color 0.3s ease;
}

.back-button:hover {
    background-color: #2980b9;
}

.back-button i {
    margin-right: 5px;
}

/* Responsive Design */
@media (max-width: 768px) {
    .main-content {
        padding: 20px;
        max-width: 90%; /* Make it smaller on mobile */
    }

    form {
        gap: 15px; /* Adjusted gap for smaller screens */
    }

    form input,
    form select,
    form button {
        padding: 10px;
    }

    #services {
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); /* Adjust grid for smaller screens */
    }
}
</style>


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

        <div class="main-content">
        <a href="appointments.php" class="back-button">
                <i class="fas fa-arrow-left"></i> Back
            </a>
    <h1>Edit Appointment</h1>
    <form action="edit_appointment.php?id=<?php echo $appointment_id; ?>" method="post">
        <label for="appointment_date">Date:</label>
        <input type="date" name="appointment_date" id="appointment_date" value="<?php echo htmlspecialchars($appointment['appointment_date']); ?>" required>
        
        <label for="appointment_time">Time:</label>
        <input type="time" name="appointment_time" id="appointment_time" value="<?php echo htmlspecialchars($appointment['appointment_time']); ?>" required>

        <label for="appointment_status">Status:</label>
        <select name="appointment_status" id="appointment_status" required>
            <option value="pending" <?php echo $appointment['appointment_status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
            <option value="cancelled" <?php echo $appointment['appointment_status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
            <option value="no_show" <?php echo $appointment['appointment_status'] === 'no_show' ? 'selected' : ''; ?>>No Show</option>
        </select>

        <label for="payment_method">Payment Method:</label>
<select name="payment_method" id="payment_method" required>
    <?php foreach ($payment_methods as $method): ?>
        <option value="<?php echo $method['method_id']; ?>">
            <?php echo htmlspecialchars($method['method_name']); ?>
        </option>
    <?php endforeach; ?>
</select>

        <label for="services">Add Services:</label>
        <div id="services">
            <?php foreach ($services as $service): ?>
                <label>
                    <input type="checkbox" name="services[]" value="<?php echo $service['service_id']; ?>"
                    <?php echo in_array($service['service_id'], $assigned_services) ? 'checked' : ''; ?> />
                    <?php echo htmlspecialchars($service['service_name']); ?> - $<?php echo number_format($service['price'], 2); ?>
                </label><br>
            <?php endforeach; ?>
        </div>

        <button type="submit">Update Appointment</button>
    </form>
    <a href="appointments.php">Back to Appointments</a>
</div>
</body>
</html>
