<?php
session_start();
include 'db_config.php';

// Redirect non-admin users to their appropriate dashboard
if (isset($_SESSION['role']) && $_SESSION['role'] !== 'admin') {
    if ($_SESSION['role'] === 'dentist') {
        header('Location: dentist_dashboard.php');
    } else {
        header('Location: patient_dashboard.php');
    }
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Prepare and execute the query
    $stmt = $conn->prepare("SELECT user_id, password_hash FROM users WHERE username = ? AND role = 'admin'");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($user_id, $hashed_password);
        $stmt->fetch();

        // Verify the password
        if (password_verify($password, $hashed_password)) {
            // Password is correct, set session variables
            $_SESSION['user_id'] = $user_id;
            $_SESSION['role'] = 'admin';

            // Redirect to the admin dashboard
            header('Location: admin_dashboard.php');
            exit();
        } else {
            $error = "Invalid credentials.";
        }
    } else {
        $error = "Invalid credentials.";
    }

    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Login</title>
</head>
<body>
    <form method="POST" action="">
        <label for="username">Username:</label>
        <input type="text" id="username" name="username" required>
        <label for="password">Password:</label>
        <input type="password" id="password" name="password" required>
        <button type="submit">Login</button>
    </form>
    <?php if (!empty($error)) { echo '<p class="error">' . $error . '</p>'; } ?>
</body>
</html>
