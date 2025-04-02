<?php
include 'login_check.php';  // Ensure the user is logged in
include 'db_config.php';    // Include database configuration
include 'admin_check.php';  // Admin check (ensure the user is an admin)

// Fetch the logged-in user ID from the session
$user_id = $_SESSION['user_id'];  // Assuming the user ID is stored in the session after login

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Error handling
    $error_message = '';
    $success_message = '';

    // Fetch the stored password hash from the database
    $sql = "SELECT password_hash FROM users WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    // Verify current password
    if (password_verify($current_password, $user['password_hash'])) {
        // Check if new password and confirm password match
        if ($new_password === $confirm_password) {
            // Password strength check (minimum 8 characters, at least one number, one lowercase and one uppercase letter)
            if (preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[A-Za-z\d]{8,}$/', $new_password)) {
                // Hash the new password
                $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);

                // Update the password in the database
                $update_sql = "UPDATE users SET password_hash = ? WHERE user_id = ?";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param("si", $new_password_hash, $user_id);

                if ($update_stmt->execute()) {
                    $success_message = "Password updated successfully!";
                } else {
                    $error_message = "Error updating password. Please try again.";
                }
            } else {
                $error_message = "New password must be at least 8 characters long, with at least one number, one lowercase letter, and one uppercase letter.";
            }
        } else {
            $error_message = "New password and confirm password do not match.";
        }
    } else {
        $error_message = "Current password is incorrect.";
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password - Dental Clinic Management System</title>
    <link rel="stylesheet" type="text/css" href="css/style.css">
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
}



.main-content {
    margin-left: 260px; /* Offset for sidebar */
    padding: 40px;
    width: 100%;
}

#change-password {
    max-width: 700px;
    margin: 0 auto;
    background-color: #ffffff;
    padding: 30px;
    border-radius: 8px;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.main-content h1 {
    font-size: 32px;
    margin-bottom: 30px;
    color: #333;
    text-align: center;
}

form {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

label {
    font-size: 16px;
    font-weight: bold;
    color: #444;
}

input {
    padding: 12px;
    font-size: 16px;
    border-radius: 5px;
    border: 1px solid #ccc;
    width: 100%;
    box-sizing: border-box;
}

button {
    background-color: #0066cc;
    color: white;
    padding: 12px 20px;
    border: none;
    border-radius: 5px;
    font-size: 16px;
    cursor: pointer;
    transition: background-color 0.3s;
}

button:hover {
    background-color: #005bb5;
}

/* Alert Styles */
.alert {
    padding: 15px;
    margin-bottom: 20px;
    border-radius: 5px;
}

.alert.success {
    background-color: #4caf50;
    color: white;
}

.alert.error {
    background-color: #f44336;
    color: white;
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
            <section id="change-password">
                <h1>Change Password</h1>

                <!-- Display Success or Error Message -->
                <?php if (isset($success_message)): ?>
                    <div class="alert success"><?php echo $success_message; ?></div>
                <?php elseif (isset($error_message)): ?>
                    <div class="alert error"><?php echo $error_message; ?></div>
                <?php endif; ?>

                <!-- Change Password Form -->
                <form action="admin_change_password.php" method="POST">
                    <div class="form-group">
                        <label for="current_password">Current Password</label>
                        <input type="password" id="current_password" name="current_password" required>
                    </div>

                    <div class="form-group">
                        <label for="new_password">New Password</label>
                        <input type="password" id="new_password" name="new_password" required 
                               pattern="^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[A-Za-z\d]{8,}$" 
                               title="Password must be at least 8 characters long, with at least one number, one lowercase letter, and one uppercase letter.">
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" required>
                    </div>

                    <div class="form-group">
                        <button type="submit" name="update_password" class="btn btn-primary">Update Password</button>
                    </div>
                </form>
            </section>
        </main>
    </div>
</body>
</html>

<?php
// Close database connection at the end
$conn->close();
?>
