<?php
include 'login_check.php';  // Ensure user is logged in
include 'db_config.php';    // Database connection
include 'admin_check.php';  // Admin check

// Fetch payment type from the database
$payment_type = [];
$stmt = $conn->prepare("SELECT pstat_id, status_name FROM payment_type");
$stmt->execute();
$stmt->bind_result($id, $status_name);
while ($stmt->fetch()) {
    $payment_type[] = ['id' => $id, 'status_name' => $status_name];
}
$stmt->close();

// Handle adding new payment status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_status'])) {
    $status_name = $_POST['status_name'];

    // Validate input
    if (!empty($status_name)) {
        $stmt = $conn->prepare("INSERT INTO payment_type (status_name) VALUES (?)");
        $stmt->bind_param('s', $status_name);
        if ($stmt->execute()) {
            $success_message = "Payment status added successfully!";
        } else {
            $error_message = "Failed to add payment status. Please try again.";
        }
        $stmt->close();
    } else {
        $error_message = "Status name cannot be empty.";
    }
}

// Handle deleting a payment status
if (isset($_GET['delete'])) {
    $status_id = $_GET['delete'];

    // Delete the payment status
    $stmt = $conn->prepare("DELETE FROM payment_type WHERE id = ?");
    $stmt->bind_param('i', $status_id);
    if ($stmt->execute()) {
        $success_message = "Payment status deleted successfully!";
    } else {
        $error_message = "Failed to delete payment status. Please try again.";
    }
    $stmt->close();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Payment type - Dental Clinic Management System</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <!-- Sidebar Menu -->
        <aside class="sidebar">
            <h2 class="logo">
                <img src="images/cometaicon.png" alt="Dental Clinic Logo">
                <h1>Dental Clinic</h1>
            </h2>
            <ul>
                <li><a href="admin_dashboard.php">Dashboard</a></li>
                <li><a href="patients.php">Patients</a></li>
                <li><a href="dentists.php">Dentists</a></li>
                <li><a href="appointments.php">Appointments</a></li>
                <li><a href="admin_add_services.php">Add Services</a></li>
                <li><a href="reports.php">Reports</a></li>
                <li><a href="profile.php">Profile</a></li>
                <li><a href="settings.php" class="active">Settings</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <section id="payment-type">
                <h1>Manage Payment type</h1>

                <?php if (isset($success_message)): ?>
                    <div class="success"><?= htmlspecialchars($success_message) ?></div>
                <?php endif; ?>
                <?php if (isset($error_message)): ?>
                    <div class="error"><?= htmlspecialchars($error_message) ?></div>
                <?php endif; ?>

                <!-- Add New Status Form -->
                <h2>Add New Payment Status</h2>
                <form action="admin_payment_status.php" method="POST">
                    <label for="status_name">Payment Status Name:</label>
                    <input type="text" name="status_name" id="status_name" required>
                    <input type="submit" name="add_status" value="Add Status">
                </form>

                <!-- Payment type Table -->
                <h2>Current Payment Type</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Payment Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($payment_type as $status): ?>
                            <tr>
                                <td><?= htmlspecialchars($status['status_name']) ?></td>
                                <td>
                                    <a href="admin_payment_status.php?delete=<?= $status['id'] ?>" onclick="return confirm('Are you sure you want to delete this status?');">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </section>
        </main>
    </div>
</body>
</html>
