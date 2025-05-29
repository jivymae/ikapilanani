<?php
session_start();
include 'db_config.php';

// Redirect admins to admin login page
if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    header('Location: admin_dashboard.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Prepare and execute the query to fetch user data
    $stmt = $conn->prepare("SELECT user_id, password_hash, role FROM users WHERE username = ? AND role != 'admin'");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($user_id, $hashed_password, $role);
        $stmt->fetch();

        // Verify the password
        if (password_verify($password, $hashed_password)) {
            // Password is correct, set session variables
            $_SESSION['user_id'] = $user_id;
            $_SESSION['role'] = $role;

            // Redirect to the appropriate dashboard based on the role
            if ($role === 'dentist') {
                header('Location: dentist_dashboard.php');
            } else {
                header('Location: patient_dashboard.php'); // Adjust this to your patient dashboard
            }
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
    <title>Login</title>
<style>
    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background-color: #e8f4f8;  /* Light dental blue */
        margin: 0;
        padding: 0;
        display: flex;
        justify-content: center;
        align-items: center;
        min-height: 100vh;
        color: #2a6273;  /* Dark dental blue */
        background-image: 
            radial-gradient(circle at 90% 80%, rgba(173, 216, 230, 0.3) 0%, transparent 25%),
            radial-gradient(circle at 10% 20%, rgba(173, 216, 230, 0.3) 0%, transparent 25%);
    }

    .container {
        background-color: white;
        padding: 2.5rem;
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(42, 98, 115, 0.1);
        width: 100%;
        max-width: 400px;
        position: relative;
        overflow: hidden;
    }

    .container::before {
        content: "";
        position: absolute;
        top: 0;
        right: 0;
        width: 60px;
        height: 60px;
        background-color: #f0f9fc;
        border-radius: 0 0 0 100%;
        z-index: 0;
    }

    h1 {
        text-align: center;
        margin-bottom: 1.8rem;
        color: #1a4a5a;
        position: relative;
        font-weight: 600;
    }

    h1::after {
        content: "";
        display: block;
        width: 50px;
        height: 3px;
        background: #4ecdc4;  /* Dental teal */
        margin: 0.5rem auto 0;
    }

    form {
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
        position: relative;
        z-index: 1;
    }

    label {
        font-weight: 600;
        margin-bottom: -1rem;
        color: #2a6273;
        font-size: 0.95rem;
    }

    input {
        padding: 0.9rem;
        border: 1px solid #c5e3ed;
        border-radius: 6px;
        font-size: 1rem;
        transition: all 0.3s;
        background-color: #f9fdfe;
    }

    input:focus {
        border-color: #4ecdc4;
        outline: none;
        box-shadow: 0 0 0 3px rgba(78, 205, 196, 0.2);
        background-color: white;
    }

    button {
        background-color: #4ecdc4;  /* Dental teal */
        color: white;
        padding: 0.9rem;
        border: none;
        border-radius: 6px;
        font-size: 1rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s;
        margin-top: 0.5rem;
        letter-spacing: 0.5px;
    }

    button:hover {
        background-color: #3ab7ad;
        transform: translateY(-2px);
    }

    .error {
        color: #e74c3c;
        text-align: center;
        margin: 1rem 0;
        padding: 0.8rem;
        background-color: #fadbd8;
        border-radius: 6px;
        border-left: 4px solid #e74c3c;
    }

    a {
        display: block;
        text-align: center;
        color: #2a6273;
        text-decoration: none;
        margin-top: 1rem;
        transition: all 0.3s;
        font-size: 0.9rem;
    }

    a:hover {
        color: #4ecdc4;
        text-decoration: underline;
    }

    .forgot-password-link {
        margin-top: 1.8rem;
    }

    .register-link {
        margin-top: 0.8rem;
        font-weight: 600;
        color: #4ecdc4;
    }

    /* Tooth icon decoration */
    .container::after {
        content: "🦷";
        position: absolute;
        bottom: 15px;
        right: 15px;
        font-size: 1.2rem;
        opacity: 0.1;
    }
</style>
</head>
<body>
    <div class="container">
        <h1>Login</h1>
        <form method="POST" action="">
            <label for="username">Username:</label>
            <input type="text" id="username" name="username" required>
            <label for="password">Password:</label>
            <input type="password" id="password" name="password" required>
            <button type="submit">Login</button>
        </form>

        <!-- Display error message -->
        <?php if (!empty($error)) { echo '<p class="error">' . htmlspecialchars($error) . '</p>'; } ?>

        <a href="request_reset.php" class="forgot-password-link">Forgot your password?</a>
        
    </div>
</body>
</html>
