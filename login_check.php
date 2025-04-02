<?php
session_start();
include 'db_config.php';

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login page if not logged in
    header('Location: login.php');
    exit();
}

// Ensure database connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Fetch the user's role
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT role FROM users WHERE user_id = ?");
if ($stmt === false) {
    die("Prepare failed: " . $conn->error);
}
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stmt->bind_result($role);
$stmt->fetch();

// Check if the role was retrieved
if (!$role) {
    // Optionally handle case where role is not found
    // For example, log this incident or alert the admin
    die("Role not found for the user.");
}

$stmt->close();
$conn->close();

// Now you can use $role to control access or customize the user experience
// For example, you might use it to show different content or restrict access
?>
