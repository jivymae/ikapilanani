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

// Handle form submission
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $service_name = trim($_POST['service_name']);
    $price = trim($_POST['price']);
    
    // Get selected specialization IDs as an array
    $selected_spec_ids = $_POST['spec_id'] ?? []; // Use null coalescing to avoid errors

    // Validate inputs
    if (empty($service_name) || empty($price) || empty($selected_spec_ids)) {
        $error_message = "Please fill in all fields correctly.";
    } elseif (!is_numeric($price) || $price <= 0) {
        $error_message = "Please enter a valid price.";
    } else {
        // Insert the service into the database
        $stmt = $conn->prepare("INSERT INTO services (service_name, price) VALUES (?, ?)");
        $stmt->bind_param('sd', $service_name, $price);
        
        if ($stmt->execute()) {
            $service_id = $stmt->insert_id; // Get the ID of the newly added service

            // Insert into service_specialization for each selected specialization
           // Insert into service_specialization for each selected specialization
foreach ($selected_spec_ids as $spec_id) {
  $stmt2 = $conn->prepare("INSERT INTO service_specialization (service_id, spec_id) VALUES (?, ?)");
  
  // Store intval in a separate variable
  $spec_id_int = intval($spec_id);
  
  $stmt2->bind_param('ii', $service_id, $spec_id_int); // Use the separate variable here
  $stmt2->execute();
  $stmt2->close();
}


            $success_message = "Service added successfully!";
        } else {
            $error_message = "Error adding service: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Fetch specializations for checkboxes
$specializations = [];
$stmt = $conn->prepare("SELECT spec_id, spec_name FROM specializations");
$stmt->execute();
$stmt->bind_result($spec_id, $spec_name);
while ($stmt->fetch()) {
    $specializations[] = ['id' => $spec_id, 'name' => $spec_name];
}
$stmt->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Service - Dental Clinic Management System</title>
    <link rel="stylesheet" type="text/css" href="css/style.css">
</head>
<style>
    /* General Styles */
body {
    font-family: Arial, sans-serif;
    background-color: #f4f4f9;
    color: #333;
    margin: 0;
    padding: 0;
}

main {
    padding: 40px 0;
    background-color: #f9f9f9;
    text-align: center;
}

.main-content {
    max-width: 100%;
    margin-left: 20%;
    padding: 30px;
    background-color: #ffffff;
    border-radius: 8px;
    border: 1px solid #ddd;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

h1 {
    font-size: 32px;
    margin-bottom: 20px;
    color: #333;
}

.main-content a {
    display: inline-block;
    margin-bottom: 20px;
    text-align: center;
    padding: 10px 20px;
    background-color: #0066cc;
    color: white;
    text-decoration: none;
    border-radius: 5px;
    font-size: 16px;
    transition: background-color 0.3s;
}

.main-content a:hover {
    background-color: #005bb5;
}

form {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

form label {
    font-size: 16px;
    font-weight: bold;
    color: #444;
    text-align: left;
}

form input {
    padding: 12px;
    font-size: 16px;
    border-radius: 5px;
    border: 1px solid #ccc;
    width: 100%;
    box-sizing: border-box;
}

form button {
    padding: 12px;
    font-size: 16px;
    background-color: #0066cc;
    color: white;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    transition: background-color 0.3s;
}

form button:hover {
    background-color: #005bb5;
}

/* Message Styles */
.error, .success {
    padding: 10px;
    margin-bottom: 15px;
    border-radius: 5px;
    font-size: 16px;
    text-align: center;
}

.error {
    background-color: #ffdddd;
    color: #d8000c;
    border: 1px solid #d8000c;
}

.success {
    background-color: #ddffdd;
    color: #4caf50;
    border: 1px solid #4caf50;
}

/* Responsive Design */
@media (max-width: 768px) {
    .main-content {
        padding: 20px;
        max-width: 90%;
    }

    form input,
    form button {
        padding: 10px;
    }
}

    </style>
<body>
    <div class="container">
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
                <li><a href="admin_add_services.php" class="active">Add Services</a></li>
                <li><a href="reports.php">Reports</a></li>
                
                <li><a href="admin_settings.php">Settings</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </aside>

        <main class="main-content">
            <section id="add-service">
                <h1>Add Service</h1>
                <a href="admin_show_services.php">View Services</a>
                
                <?php if ($error_message): ?>
                    <div class="error"><?php echo htmlspecialchars($error_message); ?></div>
                <?php endif; ?>
                <?php if ($success_message): ?>
                    <div class="success"><?php echo htmlspecialchars($success_message); ?></div>
                <?php endif; ?>

                <form action="admin_add_services.php" method="POST">
                    <label for="service_name">Service Name:</label>
                    <input type="text" name="service_name" id="service_name" required>

                    <label for="price">Price:</label>
                    <input type="text" name="price" id="price" required>

                    <label for="spec_id">Specializations:</label><br>
                    <?php foreach ($specializations as $specialization): ?>
                        <input type="checkbox" name="spec_id[]" value="<?php echo htmlspecialchars($specialization['id']); ?>" id="spec_<?php echo htmlspecialchars($specialization['id']); ?>">
                        <label for="spec_<?php echo htmlspecialchars($specialization['id']); ?>"><?php echo htmlspecialchars($specialization['name']); ?></label><br>
                    <?php endforeach; ?>

                    <button type="submit">Add Service</button>
                </form>
            </section>
        </main>
    </div>
</body>
</html>
