<?php
include 'db_config.php'; // Include the database configuration file

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if required data is present
    if (isset($_POST['patient_id']) && isset($_POST['scid'])) {
        $patient_id = $_POST['patient_id'];
        $scid = $_POST['scid'];

        // Validate input
        if (empty($patient_id) || empty($scid)) {
            echo 'Patient ID and SCID are required.';
            exit;
        }

        // Update the patient's SCID in the database
        $stmt = $conn->prepare("UPDATE patients SET SCID = ? WHERE Patient_ID = ?");
        if ($stmt) {
            $stmt->bind_param('si', $scid, $patient_id); // Bind parameters
            if ($stmt->execute()) {
                echo 'SCID saved successfully.';
            } else {
                echo 'Failed to save SCID: ' . $stmt->error;
            }
            $stmt->close();
        } else {
            echo 'Database error: ' . $conn->error;
        }
    } else {
        echo 'Invalid data received.';
    }
} else {
    echo 'Invalid request method.';
}
?>