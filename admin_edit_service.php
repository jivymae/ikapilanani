<?php
include 'login_check.php'; // Ensure user is logged in
include 'db_config.php';
include 'admin_check.php'; // Include the admin check helper

// Check if the page is allowed
$page = basename($_SERVER['PHP_SELF']);
if (!isAdminPage($page)) {
    header('Location: unauthorized.php');
    exit();
}

// Initialize messages
$success_message = '';
$error_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $service_id = intval($_POST['service_id']);
    $service_name = trim($_POST['service_name']);
    $price = trim($_POST['price']);
    
    // Get selected specialization IDs
    $selected_spec_ids = $_POST['spec_id'] ?? [];
    
    // Remove previous specializations from the service_specialization table
    $stmt = $conn->prepare("DELETE FROM service_specialization WHERE service_id = ?");
    $stmt->bind_param('i', $service_id);
    $stmt->execute();
    $stmt->close();

    // Update the service in the database
    $stmt = $conn->prepare("UPDATE services SET service_name = ?, price = ? WHERE service_id = ?");
    $stmt->bind_param('sdi', $service_name, $price, $service_id);

    if ($stmt->execute()) {
        // Insert the new specializations
        foreach ($selected_spec_ids as $spec_id) {
            $insert_stmt = $conn->prepare("INSERT INTO service_specialization (service_id, spec_id) VALUES (?, ?)");
            $insert_stmt->bind_param('ii', $service_id, $spec_id);
            $insert_stmt->execute();
            $insert_stmt->close();
        }
        $success_message = "Service updated successfully!";
    } else {
        $error_message = "Error updating service: " . $stmt->error;
    }
    $stmt->close();
}

// Fetch the service details
if (isset($_GET['id'])) {
    $service_id = intval($_GET['id']);
    
    $stmt = $conn->prepare("SELECT service_name, price FROM services WHERE service_id = ?");
    $stmt->bind_param('i', $service_id);
    $stmt->execute();
    $stmt->bind_result($service_name, $price);
    
    if (!$stmt->fetch()) {
        $error_message = "Service not found.";
    }
    $stmt->close();
} else {
    // Redirect or handle the case where 'id' is not provided
    $error_message = "Service ID not specified.";
    header('Location: admin_show_services.php'); // Change to your desired page
    exit();
}

// Fetch current specializations for the service
$current_specs = [];
$stmt = $conn->prepare("SELECT spec_id FROM service_specialization WHERE service_id = ?");
$stmt->bind_param('i', $service_id);
$stmt->execute();
$stmt->bind_result($spec_id);
while ($stmt->fetch()) {
    $current_specs[] = $spec_id;
}
$stmt->close();

// Fetch specializations for dropdown
$specializations = [];
$stmt = $conn->prepare("SELECT spec_id, spec_name FROM specializations");
$stmt->execute();
$stmt->bind_result($spec_id, $spec_name);
while ($stmt->fetch()) {
    $specializations[] = ['id' => $spec_id, 'name' => $spec_name];
}
$stmt->close();

// Close the database connection
$conn->close();
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Service - Dental Clinic Management System</title>
    <link rel="stylesheet" type="text/css" href="css/style.css">
</head>
<body>
    <div class="container">
        <aside class="sidebar">
            <h2 class="logo">
                <img src="images/lads.png" alt="Dental Clinic Logo">
                <h1>Dental Clinic</h1>
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

        <main class="main-content">
            <section id="edit-service">
                <h1>Edit Service</h1>
                
                <?php if ($error_message): ?>
                    <div class="error"><?php echo htmlspecialchars($error_message); ?></div>
                <?php endif; ?>
                <?php if ($success_message): ?>
                    <div class="success"><?php echo htmlspecialchars($success_message); ?></div>
                <?php endif; ?>

                <form action="admin_edit_service.php" method="POST">
                    <input type="hidden" name="service_id" value="<?php echo htmlspecialchars($service_id); ?>">
                    <label for="service_name">Service Name:</label>
                    <input type="text" name="service_name" id="service_name" value="<?php echo htmlspecialchars($service_name); ?>" required>

                    <label for="price">Price:</label>
                    <input type="text" name="price" id="price" value="<?php echo htmlspecialchars($price); ?>" required>

                    <label for="spec_id">Specializations:</label><br>
                    <?php foreach ($specializations as $specialization): ?>
                        <input type="checkbox" name="spec_id[]" value="<?php echo htmlspecialchars($specialization['id']); ?>" id="spec_<?php echo htmlspecialchars($specialization['id']); ?>"
                        <?php echo in_array($specialization['id'], $current_specs) ? 'checked' : ''; ?>>
                        <label for="spec_<?php echo htmlspecialchars($specialization['id']); ?>"><?php echo htmlspecialchars($specialization['name']); ?></label><br>
                    <?php endforeach; ?>

                    <button type="submit">Update Service</button>
                </form>
            </section>
        </main>
    </div>
</body>
</html>
