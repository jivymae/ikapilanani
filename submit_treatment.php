<?php
session_start();
include 'db_config.php';

// Check if form data exists
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  $appointment_id = isset($_GET['appointment_id']) ? $_GET['appointment_id'] : null;
  $patient_id = isset($_GET['patient_id']) ? $_GET['patient_id'] : null;
   // Same as above
    $diagnosis_list = isset($_POST['diagnosis_list']) ? json_decode($_POST['diagnosis_list'], true) : [];
    $treatment_list = isset($_POST['treatment_list']) ? json_decode($_POST['treatment_list'], true) : [];
    $medication_list = isset($_POST['medication_list']) ? json_decode($_POST['medication_list'], true) : [];
    $upper_teeth_left = $_POST['upper_teeth'];
    $lower_teeth_left = $_POST['lower_teeth'];
    $teeth_part = $_POST['teeth_part'];
    $follow_up_date = $_POST['follow_up'];

    // Image upload handling
    $image = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $imageTmpName = $_FILES['image']['tmp_name'];
        $imageName = time() . '_' . basename($_FILES['image']['name']);
        $imagePath = 'uploads/' . $imageName;

        // Move the uploaded image to the server's upload directory
        if (move_uploaded_file($imageTmpName, $imagePath)) {
            $image = $imagePath; // Store the image path
        }
    }

    // Prepare and execute database insert for each treatment record
    foreach ($diagnosis_list as $diagnosis) {
        foreach ($treatment_list as $treatment) {
            foreach ($medication_list as $medication) {
              $stmt = $conn->prepare("INSERT INTO treatment_records (appointment_id, patient_id, diagnosis, image, treatment_performed, medication_prescribed, upper_teeth_left, lower_teeth_left, teeth_part, follow_up_date, created_at, updated_at)
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");

if ($stmt === false) {
die('Error preparing query: ' . $conn->error);
}

$stmt->bind_param("iissiiiss", $appointment_id, $patient_id, $diagnosis, $image, $treatment, $medication, $upper_teeth_left, $lower_teeth_left, $teeth_part, $follow_up_date);

$execute_result = $stmt->execute();
if (!$execute_result) {
die('Error executing query: ' . $stmt->error);
} else {
echo "Treatment record(s) added successfully.";
}
            }
          }
        }
      }

?>
