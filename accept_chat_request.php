<?php
session_start();
include 'db_config.php'; // Ensure this file initializes $conn

// Redirect non-dentists to login page
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'dentist') {
    header('Location: login.php');
    exit();
}

if (isset($_GET['message_id'])) {
    $message_id = $_GET['message_id'];

    // Update chat request status to accepted (chat_request = 0)
    $stmt = $conn->prepare("UPDATE messages SET chat_request = 0 WHERE message_id = ? AND recipient_id = ?");
    $stmt->bind_param("ii", $message_id, $_SESSION['user_id']);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        $_SESSION['success'] = "Chat request accepted successfully.";
    } else {
        $_SESSION['error'] = "Failed to accept the chat request.";
    }

    $stmt->close();
    header('Location: view_chat_requests.php'); // Redirect back to chat requests page
    exit();
} else {
    $_SESSION['error'] = "Invalid request.";
    header('Location: dentist_message.php');
    exit();
}
?>
