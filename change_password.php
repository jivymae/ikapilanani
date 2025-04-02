<?php
session_start();
include 'db_config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    header('Location: login.php');
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Fetch current password hash
    $stmt = $conn->prepare("SELECT password_hash FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $patient = $result->fetch_assoc();
    $stmt->close();

    if ($new_password !== $confirm_password) {
        $error = 'New password and confirm password do not match.';
    } elseif (!password_verify($current_password, $patient['password_hash'])) {
        $error = 'Current password is incorrect.';
    } else {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $update_stmt = $conn->prepare("UPDATE users SET password_hash = ? WHERE user_id = ?");
        $update_stmt->bind_param("si", $hashed_password, $_SESSION['user_id']);
        if ($update_stmt->execute()) {
            $success = 'Password changed successfully!';
        } else {
            $error = 'Failed to change password.';
        }
        $update_stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password - Dental Clinic Management System</title>
    <style>
        /* General Styles */
          /* General Styles */
          body, h1, h2, p, ul, li, div, table, th, td {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
  font-family: Arial;
  margin: 0;
  padding: 0;
  color: #333;
  background-color: #f4f4f4;
}


        /* Navigation Bar */
        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: #00bfff;
            color: #fff;
            padding: 0.7rem 1rem;
            position: relative;
        }

        .navbar .logo {
            display: flex;
            align-items: center;
        }

        .navbar .logo img {
            height: 50px;
            margin-right: 10px;
        }

        .navbar .logo h1 {
            font-size: 1.5rem;
            font-weight: bold;
        }

        .navbar .nav-links {
            font-size: 1.2rem;
            list-style: none;
            display: flex;
            gap: 1.7rem;
            transition: all 0.3s ease;
        }

        .navbar .nav-links a {
            text-decoration: none;
            color: #fff;
            font-weight:  ;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            transition: background-color 0.3s;
        }

        .navbar .nav-links a.active, .navbar .nav-links a:hover {
            background-color: #0056b3;
        }

        /* Hamburger Menu */
        .hamburger {
            display: none;
            flex-direction: column;
            justify-content: space-around;
            width: 30px;
            height: 25px;
            cursor: pointer;
        }

        .hamburger span {
            background-color: #fff;
            height: 3px;
            width: 100%;
            border-radius: 3px;
        }
        /* General Styles */
ul.navigation {
    list-style-type: none;
    padding: 0;
    margin: 20px 0;
    display: flex;
    flex-direction: column;
    align-items: center;
}

ul.navigation li {
    margin-bottom: 15px;
}

ul.navigation .btn {
    background-color: #00bfff;
    color: white;
    padding: 12px 25px;
    border-radius: 6px;
    text-decoration: none;
    font-size: 16px;
    display: inline-block;
    width: auto;
    text-align: center;
    transition: background-color 0.3s, transform 0.2s ease-in-out;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

/* Hover and Focus States */
ul.navigation .btn:hover {
    background-color: #0056b3;
    transform: translateY(-2px);
}

ul.navigation .btn:focus {
    outline: none;
    background-color: #0056b3;
}

ul.navigation .btn:active {
    transform: translateY(0);
}

.container {
    max-width: 600px;
    margin: 30px auto;
    background-color: white;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
    
}

.form-group {
    margin-bottom: 20px;
}


label {
    font-weight: bold;
    color: #555;
    font-size: 14px;
    margin-bottom: 5px;
    display: block;
}

input, select, textarea {
    width: 100%;
    padding: 12px;
    border: 1px solid #ddd;
    border-radius: 5px;
    font-size: 14px;
    box-sizing: border-box; /* Includes padding in width */
    transition: border-color 0.3s ease;
    margin-bottom: 10px;
}

input:focus, select:focus, textarea:focus {
    border-color: #00bfff;
    outline: none;
}

textarea {
    resize: vertical;
    min-height: 120px;
}

button {
    background-color: #00bfff;
    color: white;
    padding: 12px 20px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-size: 16px;
    width: 100%;
    transition: background-color 0.3s ease;
}

button:hover {
    background-color: #0056b3;
}

button:focus {
    outline: none;
}

.error {
    color: red;
    font-size: 14px;
    text-align: center;
    margin-bottom: 20px;
}

.success {
    color: green;
    font-size: 14px;
    text-align: center;
    margin-bottom: 20px;
}


/* Responsive Design */
@media (max-width: 768px) {
    .container {
        padding: 15px;
    }
    ul.navigation {
        padding-left: 10px;
        padding-right: 10px;
    }

    ul.navigation .btn {
        font-size: 14px;
        padding: 10px 20px;
    }
}
}

@media (max-width: 480px) {
    h1 {
        font-size: 20px;
    }
    .container {
        padding: 10px;
    }
}
@media (max-width: 768px) {
            .navbar .nav-links {
                display: none;
                flex-direction: column;
                position: absolute;
                top: 100%;
                left: 0;
                width: 100%;
                background-color: #00bfff;
                padding: 1rem 0;
                z-index: 10;
            }

            .navbar .nav-links.show {
                display: flex;
            }

            .hamburger {
                display: flex;
            }
        }
    </style>
</head>
<body>

        <!-- Navigation Bar -->
        <nav class="navbar">
        <div class="logo">
            <img src="images/lads.png" alt="Dental Clinic Logo">
            <h1>LAD DCAMS</h1>
        </div>
        <div class="hamburger" onclick="toggleMenu()">
            <span></span>
            <span></span>
            <span></span>
        </div>
        <ul class="nav-links">
            <li><a href="patient_dashboard.php">Dashboard</a></li>
            <li><a href="patient_appointments.php" >Appointments</a></li>
            <li><a href="patient_message.php">Messages</a></li>
            <li><a href="patient_profile.php">Profile</a></li>
            <li><a href="logout.php">Logout</a></li>
        </ul>
    </nav>
    <script>
        function toggleMenu() {
            const navLinks = document.querySelector('.nav-links');
            navLinks.classList.toggle('show');
        }
    </script>



<div class="container">
<ul class="navigation">
    <li><a href="update_patient_profile.php" class="btn">Update Profile</a>
    <a href="change_password.php" class="btn">Change Password</a></li>
</ul>

<form method="POST" action="">
    <div>
        <label for="current_password">Current Password:</label>
        <input type="password" id="current_password" name="current_password" required>
    </div>
    <div>
        <label for="new_password">New Password:</label>
        <input type="password" id="new_password" name="new_password" required>
    </div>
    <div>
        <label for="confirm_password">Confirm New Password:</label>
        <input type="password" id="confirm_password" name="confirm_password" required>
    </div>
    <button type="submit">Change Password</button>
</form>

<?php if (!empty($error)): ?>
    <p class="error"><?php echo htmlspecialchars($error); ?></p>
<?php endif; ?>
<?php if (!empty($success)): ?>
    <p class="success"><?php echo htmlspecialchars($success); ?></p>
<?php endif; ?>


</body>
</html>
