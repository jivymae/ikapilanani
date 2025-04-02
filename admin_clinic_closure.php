<?php
include 'login_check.php';  // Ensure user is logged in
include 'db_config.php';    // Database connection
include 'admin_check.php';  // Admin check

// Fetch existing closure dates and causes
$closure_dates = [];
$stmt = $conn->prepare("SELECT closure_id, closure_date, cause FROM clinic_closures ORDER BY closure_date DESC");
$stmt->execute();
$stmt->bind_result($closure_id, $closure_date, $cause);
while ($stmt->fetch()) {
    $closure_dates[] = [
        'closure_id' => $closure_id,
        'closure_date' => $closure_date,
        'cause' => $cause
    ];
}
$stmt->close();

// Handle adding a new closure date
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_closure_date'])) {
    $closure_date = $_POST['closure_date'];
    $cause = $_POST['cause'];

    // Simple validation
    if (!empty($closure_date) && !empty($cause)) {
        $stmt = $conn->prepare("INSERT INTO clinic_closures (closure_date, cause) VALUES (?, ?)");
        $stmt->bind_param('ss', $closure_date, $cause);
        if ($stmt->execute()) {
            header("Location: admin_clinic_closure.php");
            exit();
        } else {
            $error_message = "Error adding closure date.";
        }
        $stmt->close();
    } else {
        $error_message = "Please enter both closure date and cause.";
    }
}

// Handle updating a closure date and cause
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_closure_date'])) {
    $closure_id = $_POST['closure_id'];
    $closure_date = $_POST['closure_date'];
    $cause = $_POST['cause'];

    // Simple validation
    if (!empty($closure_date) && !empty($cause)) {
        $stmt = $conn->prepare("UPDATE clinic_closures SET closure_date = ?, cause = ? WHERE closure_id = ?");
        $stmt->bind_param('ssi', $closure_date, $cause, $closure_id);
        if ($stmt->execute()) {
            header("Location: admin_clinic_closure.php");
            exit();
        } else {
            $error_message = "Error updating closure date.";
        }
        $stmt->close();
    } else {
        $error_message = "Please enter both closure date and cause.";
    }
}

// Handle deleting a closure date
if (isset($_GET['delete_id'])) {
    $closure_id = $_GET['delete_id'];
    $stmt = $conn->prepare("DELETE FROM clinic_closures WHERE closure_id = ?");
    $stmt->bind_param('i', $closure_id);
    if ($stmt->execute()) {
        header("Location: admin_clinic_closure.php");
        exit();
    } else {
        $error_message = "Error deleting closure date.";
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Clinic Closure Dates - Dental Clinic Management System</title>
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
                <li><a href="admin_settings">Settings</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <section id="clinic-closures">
                <h1>Manage Clinic Closure Dates</h1>

                <?php if (isset($error_message)): ?>
                    <div class="error"><?= htmlspecialchars($error_message) ?></div>
                <?php endif; ?>

                <!-- Add Closure Date Form -->
                <h2>Add New Closure Date</h2>
                <form action="admin_clinic_closure.php" method="POST">
                    <label for="closure_date">Closure Date:</label>
                    <input type="date" name="closure_date" id="closure_date" required>

                    <label for="cause">Cause:</label>
                    <input type="text" name="cause" id="cause" required>

                    <button type="submit" name="add_closure_date">Add Closure Date</button>
                </form>

                <!-- Display Existing Closure Dates -->
                <h2>Existing Closure Dates</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Closure Date</th>
                            <th>Cause</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($closure_dates as $closure): ?>
                            <tr>
                                <td><?= htmlspecialchars(date('F j, Y', strtotime($closure['closure_date']))) ?></td>
                                <td><?= htmlspecialchars($closure['cause']) ?></td>
                                <td>
                                    <a href="admin_clinic_closure.php?edit_id=<?= $closure['closure_id'] ?>">Edit</a> 
                                    <a href="admin_clinic_closure.php?delete_id=<?= $closure['closure_id'] ?>" hidden="return confirm('Are you sure you want to delete this closure date?')"></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </section>

            <!-- Edit Closure Date Section -->
            <?php if (isset($_GET['edit_id'])): 
                // Fetch the closure date for editing
                $closure_id = $_GET['edit_id'];
                $stmt = $conn->prepare("SELECT closure_date, cause FROM clinic_closures WHERE closure_id = ?");
                $stmt->bind_param('i', $closure_id);
                $stmt->execute();
                $stmt->bind_result($closure_date, $cause);
                $stmt->fetch();
                $stmt->close();
            ?>
            <section id="edit-closure">
                <h2>Edit Closure Date</h2>
                <form action="admin_clinic_closure.php" method="POST">
                    <input type="hidden" name="closure_id" value="<?= $closure_id ?>">

                    <label for="closure_date">Closure Date:</label>
                    <input type="date" name="closure_date" id="closure_date" value="<?= htmlspecialchars($closure_date) ?>" required>

                    <label for="cause">Cause:</label>
                    <input type="text" name="cause" id="cause" value="<?= htmlspecialchars($cause) ?>" required>

                    <button type="submit" name="update_closure_date">Update Closure Date</button>
                </form>
            </section>
            <?php endif; ?>

        </main>
    </div>
</body>
</html>
