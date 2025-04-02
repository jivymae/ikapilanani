<?php
session_start();
include 'db_config.php';

// Redirect non-dentists to login page
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'dentist') {
    header('Location: login.php');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['signature'])) {
    $signature = $_POST['signature'];
    $dentist_id = $_SESSION['user_id'];

    // Update the signature in the database
    $stmt = $conn->prepare("UPDATE users SET signature = ? WHERE user_id = ?");
    $stmt->bind_param("si", $signature, $dentist_id);

    if ($stmt->execute()) {
        $message = "Signature saved successfully!";
    } else {
        $message = "Failed to save signature.";
    }
    
    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create Your Signature</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.css" />
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            text-align: center;
            background-color: #f7f7f7;
        }
        #signature-pad {
            border: 2px solid #ccc;
            width: 100%;
            height: 300px;
            position: relative;
            margin: 0 auto;
            background-color: #f9f9f9;
        }
        canvas {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
        }
        button {
            padding: 10px 20px;
            margin: 5px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        button:hover {
            background-color: #45a049;
        }
        .error, .success {
            color: red;
        }
        .controls {
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <h1>Create Your Signature</h1>
    
    <?php if (isset($message)): ?>
        <p class="success"><?php echo htmlspecialchars($message); ?></p>
    <?php endif; ?>
    
    <form method="POST" id="signature-form">
        <div id="signature-pad">
            <canvas id="signature-canvas"></canvas>
        </div>
        <input type="hidden" name="signature" id="signature" />
        <div class="controls">
            <button type="button" id="clear-signature">Clear</button>
            <button type="submit" id="save-signature">Save Signature</button>
        </div>
    </form>
    
    <p id="message"></p>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script>
    $(document).ready(function() {
        const canvas = document.getElementById('signature-canvas');
        const signaturePad = canvas.getContext('2d');
        let isDrawing = false;
        let lastX = 0;
        let lastY = 0;

        // Set canvas dimensions with high resolution
        const scale = window.devicePixelRatio;
        canvas.width = document.getElementById('signature-pad').clientWidth * scale;
        canvas.height = document.getElementById('signature-pad').clientHeight * scale;
        canvas.style.width = document.getElementById('signature-pad').clientWidth + 'px';
        canvas.style.height = document.getElementById('signature-pad').clientHeight + 'px';
        signaturePad.scale(scale, scale);

        // Set stroke style for the signature
        signaturePad.strokeStyle = "#000"; 
        signaturePad.lineWidth = 4; 
        signaturePad.lineJoin = 'round'; 
        signaturePad.lineCap = 'round'; 

        // Event listeners for drawing
        canvas.addEventListener('mousedown', (e) => {
            isDrawing = true;
            lastX = e.offsetX * scale;
            lastY = e.offsetY * scale;
        });

        canvas.addEventListener('mousemove', (e) => {
            if (isDrawing) {
                signaturePad.beginPath();
                signaturePad.moveTo(lastX, lastY);
                lastX = e.offsetX * scale;
                lastY = e.offsetY * scale;
                signaturePad.lineTo(lastX, lastY);
                signaturePad.stroke();
            }
        });

        canvas.addEventListener('mouseup', () => {
            isDrawing = false;
            document.getElementById('signature').value = canvas.toDataURL(); // Save signature as a base64 string
        });

        canvas.addEventListener('mouseout', () => {
            isDrawing = false;
        });

        // Clear button functionality
        $('#clear-signature').click(() => {
            signaturePad.clearRect(0, 0, canvas.width, canvas.height);
            document.getElementById('signature').value = ''; // Clear the hidden input
        });

        // Prevent default form submission
        $('#signature-form').on('submit', function(e) {
            e.preventDefault();
            if (document.getElementById('signature').value) {
                this.submit(); // Only submit the form if there's a signature
            } else {
                $('#message').text('Please create a signature before saving.').css('color', 'red');
            }
        });
    });
    </script>
</body>
</html>
