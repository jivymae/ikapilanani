<?php
include 'login_check.php'; // Ensure user is logged in
include 'db_config.php';

// Check if the user is logged in and has the correct role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'patient') {
    header('Location: pat_unauthorized.php');
    exit();
}

// Fetch the sender ID from the GET request
if (!isset($_GET['sender_id'])) {
    header('Location: patient_message.php'); // Redirect if no sender_id is provided
    exit();
}

$sender_id = (int)$_GET['sender_id'];
$user_id = $_SESSION['user_id'];

// Handle form submission for replies
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject = $_POST['subject'] ?? '';
    $body = $_POST['body'] ?? '';
    $attachments = $_FILES['attachments'] ?? null;

    // Validate and process attachments if provided
    $attachment_path = null;
    if ($attachments && $attachments['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/'; // Ensure this directory exists
        $attachment_path = $upload_dir . basename($attachments['name']);
        move_uploaded_file($attachments['tmp_name'], $attachment_path);
    }

    // Insert the reply into the database
    $stmt = $conn->prepare("INSERT INTO messages (user_id, recipient_id, subject, body, created_at, is_deleted, attachments) VALUES (?, ?, ?, ?, NOW(), 0, ?)");
    $stmt->bind_param('iisss', $user_id, $sender_id, $subject, $body, $attachment_path);
    $stmt->execute();
    $stmt->close();
}

// Fetch all conversation messages between the patient and the dentist
try {
    $stmt = $conn->prepare("
        SELECT m.message_id, m.subject, m.body, m.created_at, 
               u.username AS sender_username, m.attachments
        FROM messages m
        JOIN users u ON m.user_id = u.user_id
        WHERE (m.user_id = ? AND m.recipient_id = ?) OR (m.user_id = ? AND m.recipient_id = ?)
        AND m.is_deleted = 0
        ORDER BY m.created_at ASC
    ");
    $stmt->bind_param('iiii', $sender_id, $user_id, $user_id, $sender_id);
    $stmt->execute();
    $messages_result = $stmt->get_result();
    $stmt->close();
} catch (mysqli_sql_exception $e) {
    $error_message = "Error fetching messages: " . $e->getMessage();
}

// Close connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Message - Dental Clinic Management System</title>
    <link rel="stylesheet" type="text/css" href="css/patient.css">
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
/* main content*/
.main-content {
    padding: 20px;
}

.dashboard-container {
    max-width: 1200px;
    margin: 0 auto;
}

.messages {
    background-color: #fff;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
}

.messages h2 {
    margin-bottom: 20px;
}

.message-container {
    margin-bottom: 20px;
    border-top: 1px solid #ddd;
    padding-top: 10px;
}

.message {
    margin-bottom: 15px;
    padding-bottom: 10px;
    border-bottom: 1px solid #ddd;
}

.message strong {
    display: block;
    margin-bottom: 5px;
}

.message p {
    margin: 5px 0;
}

form label {
    display: block;
    margin: 10px 0 5px;
    font-weight: bold;
}

form input, form textarea, form button {
    width: 100%;
    padding: 10px;
    margin-bottom: 15px;
    border: 1px solid #ddd;
    border-radius: 5px;
    box-sizing: border-box;
}

form button {
    background-color: #004aad;
    color: #fff;
    font-weight: bold;
    cursor: pointer;
    border: none;
    transition: background-color 0.3s ease;
}

form button:hover {
    background-color: #003a8c;
}

/* Responsive Design */
@media (max-width: 768px) {


    .main-content {
        padding: 10px;
    }

    .messages {
        padding: 15px;
    }

    form input, form textarea, form button {
        font-size: 14px;
    }
}

@media (max-width: 480px) {
 
    .messages h2 {
        font-size: 18px;
    }

    form input, form textarea, form button {
        font-size: 12px;
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

    <!-- Main Content -->
    <main class="main-content">
        <div class="dashboard-container">
            <section class="messages">
                <h2>Conversation with Dentist</h2>
                <?php if (isset($error_message)): ?>
                    <p class="error"><?php echo htmlspecialchars($error_message); ?></p>
                <?php elseif ($messages_result->num_rows > 0): ?>
                    <div class="message-container">
                        <?php
                        $firstMessage = true; // Flag to check if it's the first message
                        while ($row = $messages_result->fetch_assoc()): ?>
                            <div class="message">
                                <strong><?php echo htmlspecialchars($row['sender_username']); ?>:</strong>
                                <?php if ($firstMessage): ?>
                                    <p><strong>Subject:</strong> <?php echo htmlspecialchars($row['subject'] ?? 'No Subject'); ?></p>
                                    <?php $firstMessage = false; ?>
                                <?php endif; ?>
                                <p><?php echo htmlspecialchars($row['body'] ?? 'No message content'); ?></p>
                                <small><?php echo htmlspecialchars($row['created_at']); ?></small>
                                <?php if (!empty($row['attachments'])): ?>
                                    <p><strong>Attachment:</strong> <a href="<?php echo htmlspecialchars($row['attachments']); ?>" download>Download</a></p>
                                <?php endif; ?>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <p>No messages in this conversation.</p>
                <?php endif; ?>

                <!-- Reply Form -->
                <h3>Reply to Conversation</h3>
                <form action="" method="post" enctype="multipart/form-data">
                    <label for="subject">Subject:</label>
                    <input type="text" name="subject" id="subject"> <!-- Subject is now optional -->

                    <label for="body">Message:</label>
                    <textarea name="body" id="body" required></textarea>

                    <label for="attachments">Attachments:</label>
                    <input type="file" name="attachments" id="attachments">

                    <button type="submit">Send Reply</button>
                </form>

            </section>
        </div>
    </main>
</body>
</html>
