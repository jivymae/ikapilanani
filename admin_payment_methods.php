<?php
include 'login_check.php';  // Ensure user is logged in
include 'db_config.php';    // Database connection
include 'admin_check.php';  // Admin check

// Fetch payment methods from the database
$payment_methods = [];
$stmt = $conn->prepare("SELECT method_id, method_name, description FROM payment_methods ORDER BY method_name ASC");
$stmt->execute();
$stmt->bind_result($method_id, $method_name, $description);
while ($stmt->fetch()) {
    $payment_methods[] = [
        'method_id' => $method_id,
        'method_name' => $method_name,
        'description' => $description
    ];
}
$stmt->close();

// Handle adding a new payment method
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_method'])) {
    $method_name = $_POST['method_name'];
    $description = $_POST['description'];

    // Simple validation
    if (!empty($method_name)) {
        $stmt = $conn->prepare("INSERT INTO payment_methods (method_name, description) VALUES (?, ?)");
        $stmt->bind_param('ss', $method_name, $description);
        if ($stmt->execute()) {
            header("Location: admin_payment_methods.php");
            exit();
        } else {
            $error_message = "Error adding payment method.";
        }
        $stmt->close();
    } else {
        $error_message = "Please enter a payment method name.";
    }
}

// Handle updating a payment method
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_method'])) {
    $method_id = $_POST['method_id'];
    $method_name = $_POST['method_name'];
    $description = $_POST['description'];

    // Simple validation
    if (!empty($method_name)) {
        $stmt = $conn->prepare("UPDATE payment_methods SET method_name = ?, description = ? WHERE method_id = ?");
        $stmt->bind_param('ssi', $method_name, $description, $method_id);
        if ($stmt->execute()) {
            header("Location: admin_payment_methods.php");
            exit();
        } else {
            $error_message = "Error updating payment method.";
        }
        $stmt->close();
    } else {
        $error_message = "Please enter a payment method name.";
    }
}

// Handle deleting a payment method
if (isset($_GET['delete_id'])) {
    $method_id = $_GET['delete_id'];
    $stmt = $conn->prepare("DELETE FROM payment_methods WHERE method_id = ?");
    $stmt->bind_param('i', $method_id);
    if ($stmt->execute()) {
        header("Location: admin_payment_methods.php");
        exit();
    } else {
        $error_message = "Error deleting payment method.";
    }
    $stmt->close();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Payment Methods - Dental Clinic Management System</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<style>
    /* General Styles */
body {
    font-family: Arial, sans-serif;
    background-color: #f4f4f9;
    margin: 0;
    padding: 0;
    color: #333;
}

.container {
    display: flex;
    min-height: 100vh;
}



/* Main Content Styles */
.main-content {
    margin-left: 250px;
    padding: 40px;
    width: 100%;
    background-color: #ecf0f1;
}

#settings {
    max-width: 900px;
    margin: 0 auto;
    background-color: #ffffff;
    padding: 30px;
    border-radius: 8px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

#settings h1 {
    font-size: 32px;
    margin-bottom: 30px;
    text-align: center;
    color: #333;
}

#settings form {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

#settings form label {
    font-size: 16px;
    font-weight: bold;
    color: #444;
}

#settings form input,
#settings form textarea {
    padding: 12px;
    font-size: 16px;
    border-radius: 5px;
    border: 1px solid #ccc;
    width: 100%;
    box-sizing: border-box;
}
form button {
    background-color: #3498db;
    color: white;
    padding: 12px 20px;
    border: none;
    border-radius: 5px;
    font-size: 16px;
    cursor: pointer;
    transition: background-color 0.3s ease;
}
table a {
    background-color: #3498db;
    color: white;
    padding: 12px 20px;
    border: none;
    border-radius: 5px;
    font-size: 16px;
    cursor: pointer;
    transition: background-color 0.3s ease;
}

#settings form button {
    background-color: #3498db;
    color: white;
    padding: 12px 20px;
    border: none;
    border-radius: 5px;
    font-size: 16px;
    cursor: pointer;
    transition: background-color 0.3s ease;
}

#settings form button:hover {
    background-color: #2980b9;
}

/* Message Styles */
.message {
    margin: 20px 0;
    padding: 10px;
    border-radius: 5px;
    text-align: center;
}

.message.success {
    background-color: #d4edda;
    border: 1px solid #c3e6cb;
    color: #155724;
}

.message.error {
    background-color: #f8d7da;
    border: 1px solid #f5c6cb;
    color: #721c24;
}

/* Table Styles */
table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 30px;
}

table th,
table td {
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

table a.remove-link {
    color: #d9534f;
    text-decoration: none;
    font-size: 14px;
}

table a.remove-link:hover {
    text-decoration: underline;
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

    input, button {
        font-size: 14px;
        padding: 10px;
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
            <section id="payment-methods">
                <h1>Manage Payment Methods</h1>

                <?php if (isset($error_message)): ?>
                    <div class="error"><?= htmlspecialchars($error_message) ?></div>
                <?php endif; ?>

                <!-- Add Payment Method Form -->
                <h2>Add New Payment Method</h2>
                <form action="admin_payment_methods.php" method="POST">
                    <label for="method_name">Payment Method Name:</label>
                    <input type="text" name="method_name" id="method_name" required>
                    
                    <label for="description">Description:</label>
                    <textarea name="description" id="description"></textarea>

                    <button type="submit" name="add_method">Add Payment Method</button>
                </form>

                <h2>Existing Payment Methods</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Description</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($payment_methods as $method): ?>
                            <tr>
                                <td><?= htmlspecialchars($method['method_name']) ?></td>
                                <td><?= htmlspecialchars($method['description']) ?></td>
                                <td>
                                    <a href="admin_payment_methods.php?edit_id=<?= $method['method_id'] ?>">Edit</a>
                                    <a href="admin_payment_methods.php?delete_id=<?= $method['method_id'] ?>" onclick="return confirm('Are you sure you want to delete this payment method?')">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </section>

            <?php if (isset($_GET['edit_id'])): 
                // Fetch method details for editing
                $method_id = $_GET['edit_id'];
                $stmt = $conn->prepare("SELECT method_name, description FROM payment_methods WHERE method_id = ?");
                $stmt->bind_param('i', $method_id);
                $stmt->execute();
                $stmt->bind_result($method_name, $description);
                $stmt->fetch();
                $stmt->close();
            ?>
            <section id="edit-payment-method">
                <h2>Edit Payment Method</h2>
                <form action="admin_payment_methods.php" method="POST">
                    <input type="hidden" name="method_id" value="<?= $method_id ?>">

                    <label for="method_name">Payment Method Name:</label>
                    <input type="text" name="method_name" id="method_name" value="<?= htmlspecialchars($method_name) ?>" required>
                    
                    <label for="description">Description:</label>
                    <textarea name="description" id="description"><?= htmlspecialchars($description) ?></textarea>

                    <button type="submit" name="update_method">Update Payment Method</button>
                </form>
            </section>
            <?php endif; ?>

        </main>
    </div>
</body>
</html>
