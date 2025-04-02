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
        /* General reset and box-sizing */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            background-color: #f4f7fc;
            color: #333;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }

        .container {
            background-color: #fff;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
            text-align: center;
        }

        h1 {
            text-align: center;
            color: #27C5F5;
            margin-bottom: 20px;
            font-size: 24px;
        }

        form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        label {
            font-size: 14px;
            color: #555;
            margin-bottom: 5px;
        }

        input {
            padding: 12px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 16px;
            background-color: #f9f9f9;
            transition: all 0.3s ease;
        }

        input:focus {
            outline: none;
            border-color: #27C5F5;
            background-color: #e9f7e8;
        }

        button {
            padding: 12px;
            background-color: #27C5F5;
            color: #fff;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        button:hover {
            background-color: #0D9BC6;
        }

        .register-link, .forgot-password-link {
            margin-top: 10px;
            display: block;
            text-align: center;
            font-size: 14px;
            color: #0D9BC6;
            text-decoration: none;
        }

        .register-link:hover, .forgot-password-link:hover {
            text-decoration: underline;
        }

        /* Error message styling */
        .error {
            color: red;
            font-size: 14px;
            text-align: center;
            margin-top: 10px;
        }

        /* Responsive styling */
        @media (max-width: 1024px) {  /* Tablets and smaller laptops */
            .container {
                padding: 30px;
                max-width: 90%;
            }

            h1 {
                font-size: 22px;
            }

            input, button {
                font-size: 14px;
                padding: 10px;
            }

            label {
                font-size: 13px;
            }
        }

        @media (max-width: 768px) {  /* Small tablets and below */
            body {
                padding: 10px;
            }

            .container {
                padding: 20px;
                max-width: 95%;
            }

            h1 {
                font-size: 20px;
            }

            input, button {
                font-size: 14px;
                padding: 10px;
            }

            label {
                font-size: 12px;
            }
        }

        @media (max-width: 480px) {  /* Mobile phones */
            .container {
                padding: 15px;
                max-width: 100%;
            }

            h1 {
                font-size: 18px;
            }

            input, button {
                font-size: 14px;
                padding: 12px;
            }

            label {
                font-size: 12px;
            }

            .register-link, .forgot-password-link {
                font-size: 12px;
            }
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
        <a href="register_patient.php" class="register-link">Not registered? Register now</a>
    </div>
</body>
</html>
