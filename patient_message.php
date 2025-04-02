<?php

include 'login_check.php';  // Ensure user is logged in
include 'db_config.php';

// Check if the user is logged in and has the correct role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'patient') {
    header('Location: pat_unauthorized.php'); // Redirect to an unauthorized page
    exit();
}

// Initialize variables to avoid undefined variable warnings
$first_name = $last_name = $username = $email = '';

// Fetch patient information safely
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT username, email, first_name, last_name FROM users WHERE user_id = ? AND role = 'patient'");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$stmt->store_result();
if ($stmt->num_rows > 0) {
    $stmt->bind_result($username, $email, $first_name, $last_name);
    $stmt->fetch();
} else {
    // If the user is not found, handle the error gracefully (optional)
    $error_message = "Patient not found.";
}
$stmt->close();

// Fetch all messages for the patient
$messages_result = null;  // Initialize to avoid warnings
try {
    $stmt = $conn->prepare("
        SELECT MAX(m.message_id) AS latest_message_id, 
            CASE 
                WHEN m.user_id = ? THEN 'You' 
                ELSE u.username 
            END AS sender_username, 
            MAX(m.created_at) AS last_message_date, 
            m.user_id AS sender_id, 
            m.chat_request, 
            u.first_name AS patient_first_name, 
            u.last_name AS patient_last_name
        FROM messages m 
        JOIN users u ON m.user_id = u.user_id 
        WHERE m.recipient_id = ? 
        AND m.is_deleted = 0 
        GROUP BY m.user_id
        ORDER BY last_message_date DESC
    ");
    $stmt->bind_param('ii', $user_id, $user_id);
    $stmt->execute();
    $messages_result = $stmt->get_result();  // Get the result set
    $stmt->close();
} catch (mysqli_sql_exception $e) {
    $error_message = "Error fetching messages: " . $e->getMessage();
}

// Fetch list of dentists
$dentists_result = null;
try {
    $stmt = $conn->prepare("SELECT user_id, username FROM users WHERE role = 'dentist'");
    $stmt->execute();
    $dentists_result = $stmt->get_result();
    $stmt->close();
} catch (mysqli_sql_exception $e) {
    $error_message = "Error fetching dentists: " . $e->getMessage();
}

// Handle message submission (Chat Request)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $recipient_id = $_POST['recipient_id'];
    $subject = $_POST['subject'];  // Get subject from the form
    $body = $_POST['body'];  // Message body from the form
    $chat_request = 1;  // Flag to indicate this is a chat request

    // Validate that the body is not empty and recipient is selected
    if (!empty($body) && !empty($recipient_id)) {
        try {
            // Insert the message into the database with subject, body, and chat request flag
            $stmt = $conn->prepare("
                INSERT INTO messages 
                (user_id, recipient_id, subject, body, created_at, chat_request, is_deleted, status) 
                VALUES (?, ?, ?, ?, NOW(), ?, 0, 'Pending')
            ");
            $stmt->bind_param('iisss', $user_id, $recipient_id, $subject, $body, $chat_request);
            $stmt->execute();
            $stmt->close();
            $success_message = "Chat request sent successfully!";
        } catch (mysqli_sql_exception $e) {
            $error_message = "Error sending message: " . $e->getMessage();
        }
    } else {
        $error_message = "Please select a dentist, write a subject, and a message.";
    }
}

// Close connection
$conn->close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Messages - Dental Clinic Management System</title>
    <link rel="stylesheet" type="text/css" href="css/patient.css"> <!-- Link to the CSS file -->
</head>
<style>
    /* General Styles */
/* General Styles */
       /* General Reset */
       body, h1, h2, p, ul, li, table, th, td {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
  font-family: Arial, sans-serif;
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
            list-style: none;
            display: flex;
            gap: 1rem;
            transition: all 0.3s ease;
        }

        .navbar .nav-links a {
            text-decoration: none;
            color: #fff;
            font-weight: ;
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

/* Main Content Styles */
.main-content {
    max-width: 900px;
    margin: 20px auto;
    background: #fff;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
}

.messages table {
    width: 100%;
    border-collapse: collapse;
    margin: 20px 0;
}

.messages table th,
.messages table td {
    border: 1px solid #ddd;
    padding: 10px;
    text-align: left;
}

.messages table th {
    background-color: #00bfff;
    font-weight: bold;
}

.messages table tr:nth-child(even) {
    background-color: #f9f9f9;
}

.error {
    color: red;
}

.success {
    color: green;
}

.send-message form {
    margin-top: 20px;
    display: flex;
    flex-direction: column;
}

.send-message label {
    margin-bottom: 8px;
    font-weight: bold;
}

.send-message input,
.send-message textarea,
.send-message select {
    padding: 10px;
    margin-bottom: 20px;
    border: 1px solid #ccc;
    border-radius: 4px;
    width: 100%;
    box-sizing: border-box;
}

.send-message button {
    background-color: #3498db;
    color: #fff;
    border: none;
    padding: 10px 15px;
    border-radius: 4px;
    cursor: pointer;
    transition: background-color 0.3s;
}

.send-message button:hover {
    background-color: #2980b9;
}

/* Responsive Design */
@media (max-width: 768px) {


    .main-content {
        margin: 10px;
        padding: 15px;
    }

    .messages table,
    .messages table th,
    .messages table td {
        font-size: 14px;
    }
}

@media (max-width: 768px) {
 

    .send-message input,
    .send-message textarea,
    .send-message select {
        font-size: 14px;
    }

    .send-message button {
        font-size: 14px;
        padding: 8px 12px;
    }
}
/* Responsive Design */
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
            <li><a href="patient_appointments.php">Appointments</a></li>
            <li><a href="patient_message.php" class="active">Messages</a></li>
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

    <!-- Main Content -->
    <main class="main-content">
     
            <section class="messages">
                <h2>Inbox</h2>
                <?php if (isset($error_message)): ?>
                    <p class="error"><?php echo htmlspecialchars($error_message); ?></p>
                <?php elseif (isset($success_message)): ?>
                    <p class="success"><?php echo htmlspecialchars($success_message); ?></p>
                <?php elseif ($messages_result->num_rows > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Sender</th>
                                <th>Last Message Date</th>
                                <th>Dentist Name</th> <!-- Column for the patient's name -->
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $messages_result->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <a href="view_dentist_message.php?sender_id=<?php echo htmlspecialchars($row['sender_id']); ?>">
                                            <?php echo htmlspecialchars($row['sender_username']); ?>
                                        </a>
                                    </td>
                                    <td><?php echo htmlspecialchars($row['last_message_date']); ?></td>
                                    <td>
                                        <?php 
                                            // If chat_request = 0, show the patient's name
                                            if ($row['chat_request'] == 0) {
                                                echo htmlspecialchars($row['patient_first_name']) . ' ' . htmlspecialchars($row['patient_last_name']);
                                            } else {
                                                echo "No patient information";  // Or you could show the request status
                                            }
                                        ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>No messages in your inbox.</p>
                <?php endif; ?>
            </section>

            <!-- Send Message Section -->
            <section class="send-message">
                <h2>Send a Chat Request to a Dentist</h2>
                <form method="POST" action="patient_message.php">
                    <label for="dentist">Select Dentist:</label>
                    <select name="recipient_id" id="dentist" required>
                        <option value="">-- Select Dentist --</option>
                        <?php while ($dentist = $dentists_result->fetch_assoc()): ?>
                            <option value="<?php echo htmlspecialchars($dentist['user_id']); ?>">
                                <?php echo htmlspecialchars($dentist['username']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                    
                    <label for="subject">Subject:</label>
                    <input type="text" name="subject" id="subject" required>

                    <label for="body">Message:</label>
                    <textarea name="body" id="body" rows="4" required></textarea>

                    <button type="submit" name="send_message">Send Chat Request</button>
                </form>
            </section>
        </div>
    </main>
</body>
</html>
