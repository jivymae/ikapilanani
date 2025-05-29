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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | Dental Clinic</title>
    <style>
        :root {
            --primary-color: #2b7dc3;
            --primary-dark: #1a5a8a;
            --secondary-color: #e0f2fe;
            --accent-color: #4fc3a1;
            --text-color: #333;
            --light-gray: #f8f9fa;
            --white: #ffffff;
            --error-color: #e74c3c;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: var(--secondary-color);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            background-image: 
                radial-gradient(circle at 10% 20%, rgba(91, 173, 254, 0.1) 0%, rgba(91, 173, 254, 0.1) 90%),
                radial-gradient(circle at 90% 80%, rgba(79, 195, 161, 0.1) 0%, rgba(79, 195, 161, 0.1) 90%);
        }

        .login-container {
            background-color: var(--white);
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
            padding: 40px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .login-container::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 8px;
            background: linear-gradient(90deg, var(--primary-color), var(--accent-color));
        }

        .logo {
            margin-bottom: 30px;
        }

        .logo i {
            font-size: 50px;
            color: var(--primary-color);
            margin-bottom: 10px;
        }

        .logo h1 {
            color: var(--primary-color);
            font-size: 24px;
            font-weight: 600;
            margin-top: 10px;
        }

        .logo span {
            color: var(--accent-color);
        }

        .login-form {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .form-group {
            text-align: left;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--text-color);
            font-weight: 500;
        }

        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e1e5ee;
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.3s;
        }

        .form-group input:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 3px rgba(43, 125, 195, 0.2);
        }

        .form-group input::placeholder {
            color: #aaa;
        }

        .submit-btn {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: white;
            border: none;
            padding: 14px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .submit-btn:hover {
            background: linear-gradient(135deg, var(--primary-dark), var(--primary-color));
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(43, 125, 195, 0.3);
        }

        .error {
            color: var(--error-color);
            margin-top: 15px;
            font-size: 14px;
            padding: 10px;
            background-color: rgba(231, 76, 60, 0.1);
            border-radius: 5px;
            border-left: 3px solid var(--error-color);
        }

        .dental-icon {
            font-size: 24px;
            margin-right: 8px;
            vertical-align: middle;
        }

        @media (max-width: 480px) {
            .login-container {
                padding: 30px 20px;
                margin: 0 15px;
            }
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <div class="login-container">
        <div class="logo">
            <i class="fas fa-tooth"></i>
            <h1>LAD Dental<span></span> Admin</h1>
        </div>
        
        <form class="login-form" method="POST" action="">
            <div class="form-group">
                <label for="username"><i class="fas fa-user dental-icon"></i>Username</label>
                <input type="text" id="username" name="username" placeholder="Enter your username" required>
            </div>
            
            <div class="form-group">
                <label for="password"><i class="fas fa-lock dental-icon"></i>Password</label>
                <input type="password" id="password" name="password" placeholder="Enter your password" required>
            </div>
            
            <button type="submit" class="submit-btn">
                <i class="fas fa-sign-in-alt"></i> Login
            </button>
            
            <?php if (!empty($error)) { echo '<p class="error"><i class="fas fa-exclamation-circle"></i> ' . $error . '</p>'; } ?>
        </form>
    </div>
</body>
</html>