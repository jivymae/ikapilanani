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

// Fetch all services with their associated specializations
$services = [];
$stmt = $conn->prepare("
    SELECT services.service_id, services.service_name, services.price, GROUP_CONCAT(specializations.spec_name SEPARATOR ', ') AS specializations 
    FROM services 
    LEFT JOIN service_specialization ON services.service_id = service_specialization.service_id
    LEFT JOIN specializations ON service_specialization.spec_id = specializations.spec_id
    GROUP BY services.service_id
");
$stmt->execute();
$stmt->bind_result($service_id, $service_name, $price, $specializations);
while ($stmt->fetch()) {
    $services[] = [
        'id' => $service_id,
        'name' => $service_name,
        'price' => $price,
        'specialization' => $specializations ?: 'None' // Use 'None' if no specialization
    ];
}
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Services - Dental Clinic Management System</title>
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

/* Container Layout */
.container {
    display: flex;
    min-height: 100vh;
}


/* Main Content */
.main-content {
    flex-grow: 1;
    padding: 40px;
    background-color: #ffffff;
}

h1 {
    font-size: 32px;
    margin-bottom: 20px;
    color: #333;
}

table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
}

table, th, td {
    border: 1px solid #ccc;
}

th, td {
    padding: 12px;
    text-align: left;
}

th {
    background-color: #0066cc;
    color: white;
}

td {
    background-color: #f9f9f9;
}

td a {
    color: #0066cc;
    text-decoration: none;
    margin: 0 5px;
}

td a:hover {
    text-decoration: underline;
}

button {
    background-color: #0066cc;
    color: white;
    padding: 10px 20px;
    font-size: 16px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    transition: background-color 0.3s;
}

button:hover {
    background-color: #005bb5;
}

/* Responsive Design */
@media (max-width: 768px) {
    .container {
        flex-direction: column;
    }

    .sidebar {
        width: 100%;
        height: auto;
        padding: 15px;
    }

    .main-content {
        padding: 20px;
    }

    table {
        font-size: 14px;
    }

    table th, table td {
        padding: 8px;
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
                <li><a href="admin_add_services.php">Add Service</a></li>
                <li><a href="reports.php">Reports</a></li>
                <li><a href="settings.php">Settings</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </aside>

        <main class="main-content">
            <section id="services-list">
                <h1>All Services</h1>
                <table>
                    <thead>
                        <tr>
                            <th>Service ID</th>
                            <th>Service Name</th>
                            <th>Price</th>
                            <th>Specialization(s)</th>
                            <th>Actions</th> <!-- New column for actions -->
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($services)): ?>
                            <?php foreach ($services as $service): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($service['id']); ?></td>
                                    <td><?php echo htmlspecialchars($service['name']); ?></td>
                                    <td><?php echo htmlspecialchars($service['price']); ?></td>
                                    <td><?php echo htmlspecialchars($service['specialization']); ?></td>
                                    <td>
                                        <a href="admin_edit_service.php?id=<?php echo $service['id']; ?>">Edit</a> | 
                                        <a href="admin_delete_service.php?id=<?php echo $service['id']; ?>" onclick="return confirm('Are you sure you want to delete this service?');">Delete</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5">No services found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </section>
        </main>
    </div>
</body>
</html>
