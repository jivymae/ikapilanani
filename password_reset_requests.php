<?php
include 'login_check.php'; // Ensure user is logged in and is an admin
include 'db_config.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['complete_request'])) {
    $request_id = $_POST['request_id'];

    // Mark the request as completed
    $stmt = $conn->prepare("UPDATE password_reset_requests SET status = 'completed' WHERE id = ?");
    $stmt->bind_param("i", $request_id);
    $stmt->execute();

    $message = "Password reset request marked as completed.";
    $stmt->close();
}

// Fetch pending reset requests
$result = $conn->query("SELECT id, username, request_time FROM password_reset_requests WHERE status = 'pending' ORDER BY request_time DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Password Reset Requests</title>
</head>
<body>
    <h1>Password Reset Requests</h1>
    <?php if (!empty($message)) { echo '<p>' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</p>'; } ?>
    <table>
        <thead>
            <tr>
                <th>Username</th>
                <th>Request Time</th>
                <th>Action</th>
                <th>Generate Reset Code</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['username'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars($row['request_time'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td>
                        <form method="POST" action="">
                            <input type="hidden" name="request_id" value="<?php echo $row['id']; ?>">
                            <button type="submit" name="complete_request">Mark as Completed</button>
                        </form>
                    </td>
                    <td>
                        <form method="GET" action="admin_generate_reset_code.php">
                            <input type="hidden" name="username" value="<?php echo htmlspecialchars($row['username'], ENT_QUOTES, 'UTF-8'); ?>">
                            <button type="submit">Generate Reset Code</button>
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    <a href="admin_dashboard.php">Back to Dashboard</a>
</body>
</html>

<?php
$conn->close();
?>
