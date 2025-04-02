<?php
session_start();
include 'db_config.php'; // Ensure this file initializes $conn

// Redirect non-dentists to login page
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'dentist') {
    header('Location: login.php');
    exit();
}

$dentist_id = $_SESSION['user_id'];

// Fetch chat requests where chat_request = 1
$chat_requests = [];
$stmt = $conn->prepare("
    SELECT m.user_id AS sender_id, 
           m.recipient_id AS patient_id, 
           u.username,  -- Fetch patient's username
           u.first_name, 
           u.last_name, 
           m.message_id, 
           m.subject, 
           m.body
    FROM messages m
    JOIN users u ON m.user_id = u.user_id
    WHERE m.recipient_id = ? 
      AND m.chat_request = 1
      AND m.is_deleted = 0
    GROUP BY m.user_id
");
$stmt->bind_param("i", $dentist_id);
$stmt->execute();
$stmt->bind_result($sender_id, $patient_id, $username, $first_name, $last_name, $message_id, $subject, $body);

while ($stmt->fetch()) {
    $chat_requests[] = [
        'sender_id' => $sender_id,
        'patient_id' => $patient_id,
        'patient_username' => htmlspecialchars($username),  // Store the username
        'patient_name' => htmlspecialchars($first_name . ' ' . $last_name),
        'message_id' => $message_id,
        'subject' => htmlspecialchars($subject),
        'body' => htmlspecialchars($body)
    ];
}

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Chat Requests</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css"> <!-- Font Awesome -->
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
            flex-direction: column;
        }
        .sidebar {
            width: 200px;
            background-color: #f4f4f4;
            padding: 15px;
            box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
        }
        .sidebar a {
            display: block;
            padding: 10px;
            text-decoration: none;
            color: #333;
        }
        .sidebar a:hover {
            background-color: #ddd;
        }
        .main-content {
            flex: 1;
            padding: 20px;
        }
        .patient-list {
            margin-top: 20px;
            overflow-x: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        table, th, td {
            border: 1px solid #ddd;
        }
        th, td {
            padding: 12px;
            text-align: left;
        }
        th {
            background-color: #f4f4f4;
        }
        .action-buttons a {
            text-decoration: none;
            padding: 5px 10px;
            background-color: #007bff;
            color: white;
            border-radius: 5px;
            margin: 5px;
        }
        .action-buttons a.decline {
            background-color: #dc3545;
        }
        .action-buttons a.accept {
            background-color: #28a745;
        }
    </style>
</head>
<body>

<div class="main-content">
    <h1>Chat Requests</h1>

    <?php if (!empty($chat_requests)): ?>
        <div class="patient-list">
            <table>
                <thead>
                    <tr>
                        <th>Patient Username</th> <!-- Add the Username column -->
                        <th>Patient Name</th>
                        <th>Subject</th>
                        <th>Message Body</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($chat_requests as $request): ?>
                        <tr>
                            <td><?php echo $request['patient_username']; ?></td> <!-- Display Username -->
                            <td><?php echo $request['patient_name']; ?></td>
                            <td><?php echo $request['subject']; ?></td>
                            <td><?php echo substr($request['body'], 0, 100) . (strlen($request['body']) > 100 ? '...' : ''); ?></td>
                            <td class="action-buttons">
                                <a href="accept_chat_request.php?message_id=<?php echo $request['message_id']; ?>" class="accept">Accept</a>
                                <a href="decline_chat_request.php?message_id=<?php echo $request['message_id']; ?>" class="decline">Decline</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p>No chat requests at the moment.</p>
    <?php endif; ?>
</div>

</body>
</html>
