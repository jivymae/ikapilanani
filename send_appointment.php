<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'db_config.php'; // Include your database config

// Initialize variables
$appointment = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['appointment_id'])) {
    $appointment_id = $_POST['appointment_id'];

    // Fetch appointment details along with the payment method name from the payment_method table
    $stmt = $conn->prepare("
        SELECT a.*, p.payment_status, p.method_id, p.transaction_number, p.receipt_path, pm.method_name 
        FROM appointments a
        LEFT JOIN payments p ON a.appointment_id = p.appointment_id
        LEFT JOIN payment_methods pm ON p.method_id = pm.method_id
        WHERE a.appointment_id = ?
    ");
    $stmt->bind_param("i", $appointment_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $appointment = $result->fetch_assoc();
    $stmt->close();
}

if (isset($_POST['upload_receipt']) && isset($_FILES['receipt'])) {
    $receipt = $_FILES['receipt'];
    $transaction_number = $_POST['transaction_number'];

    // Check for upload errors
    if ($receipt['error'] !== UPLOAD_ERR_OK) {
        $error_message = "Upload error: " . $receipt['error'];
    } else {
        $appointment_id = $_POST['appointment_id'];

        // Handle file upload
        $target_dir = "receipts/"; // Ensure this directory exists
        if (!is_dir($target_dir) || !is_writable($target_dir)) {
            $error_message = "Receipts directory does not exist or is not writable.";
        } else {
            $target_file = $target_dir . uniqid() . '_' . basename($receipt["name"]); // Use unique name
            $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

            // Validate image
            if (getimagesize($receipt["tmp_name"]) === false) {
                $error_message = "File is not a valid image.";
            } elseif ($receipt["size"] > 5000000) {
                $error_message = "Sorry, your file is too large.";
            } elseif (move_uploaded_file($receipt["tmp_name"], $target_file)) {
                // Save the receipt path and transaction number to the database
                $stmt = $conn->prepare("
                    UPDATE payments 
                    SET receipt_path = ?, transaction_number = ? 
                    WHERE appointment_id = ?
                ");
                $stmt->bind_param("ssi", $target_file, $transaction_number, $appointment_id);
                if ($stmt->execute()) {
                    $success_message = "The file " . htmlspecialchars(basename($receipt["name"])) . " has been uploaded.";
                } else {
                    $error_message = "Error saving receipt and transaction number to the database: " . $stmt->error;
                }
                $stmt->close();
            } else {
                $error_message = "Sorry, there was an error uploading your file. Check folder permissions.";
            }
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointment Receipt Upload</title>
</head>
<body>
    <h2>Upload Receipt for Appointment</h2>
    
    <?php if (isset($error_message)): ?>
        <div style="color: red;"><?= htmlspecialchars($error_message) ?></div>
    <?php elseif (isset($success_message)): ?>
        <div style="color: green;"><?= htmlspecialchars($success_message) ?></div>
    <?php endif; ?>

    <?php if ($appointment): ?>
        <h3>Appointment Details</h3>
        <p>Dentist ID: <?= htmlspecialchars($appointment['dentist_id'] ?? 'N/A') ?></p>
        <p>Date: <?= htmlspecialchars($appointment['appointment_date'] ?? 'N/A') ?></p>
        <p>Time: <?= htmlspecialchars($appointment['appointment_time'] ?? 'N/A') ?></p>
        <p>Payment Status: <?= htmlspecialchars($appointment['payment_status'] ?? 'N/A') ?></p>
        <p>Payment Method: <?= htmlspecialchars($appointment['method_name'] ?? 'N/A') ?></p>

        <?php if ($appointment['method_name'] === 'Cash'): ?>
            <p>You cannot upload a receipt because the payment method selected is Cash.</p>
        <?php elseif (!empty($appointment['receipt_path'])): ?>
            <p>Receipt has already been uploaded: <a href="<?= htmlspecialchars($appointment['receipt_path']) ?>" target="_blank">View Receipt</a></p>
            <p>Transaction Number: <?= htmlspecialchars($appointment['transaction_number'] ?? 'N/A') ?></p>
        <?php else: ?>
            <!-- Upload form -->
            <form action="" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="appointment_id" value="<?= htmlspecialchars($appointment['appointment_id']) ?>">

                <?php if (empty($appointment['transaction_number'])): ?>
                    <label for="transaction_number">Transaction Number:</label>
                    <input type="text" name="transaction_number" id="transaction_number" required>
                <?php else: ?>
                    <p>Transaction Number: <?= htmlspecialchars($appointment['transaction_number']) ?></p>
                <?php endif; ?>

                <label for="receipt">Upload Receipt:</label>
                <input type="file" name="receipt" accept="image/*" required>
                <button type="submit" name="upload_receipt">Upload Receipt</button>
            </form>
        <?php endif; ?>
    <?php else: ?>
        <p>No appointment found.</p>
    <?php endif; ?>
    <a href="patient_book.php">Back</a>
</body>
</html>
