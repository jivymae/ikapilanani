<?php
include 'login_check.php'; // Ensure user is logged in
include 'db_config.php';

// Check if the user is logged in and has the correct role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'patient') {
    header('Location: pat_unauthorized.php');
    exit();
}

if (isset($_GET['appointment_id'])) {
    $appointment_id = $_GET['appointment_id'];

    // Fetch appointment details to show on the review page
    $stmt = $conn->prepare("SELECT a.appointment_date, a.appointment_time
                            FROM appointments a
                            WHERE a.appointment_id = ? AND a.patient_id = ?");
    $stmt->bind_param('ii', $appointment_id, $_SESSION['user_id']);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($appointment_date, $appointment_time);
    $stmt->fetch();
    $stmt->close();

    // Check if the user has already submitted a review for this appointment
    $stmt = $conn->prepare("SELECT review_id FROM reviews WHERE appointment_id = ? AND patient_id = ?");
    $stmt->bind_param('ii', $appointment_id, $_SESSION['user_id']);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows > 0) {
        // If a review exists, prevent further submission
        $error_message = "You have already submitted a review for this appointment.";
        $stmt->close();
    } else {
        // Handle the form submission for review (including stars)
        if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['review']) && isset($_POST['rating'])) {
            $review = $_POST['review'];
            $rating = $_POST['rating'];  // Capture the star rating

            // Insert the review into the database (with rating)
            $stmt = $conn->prepare("INSERT INTO reviews (appointment_id, patient_id, review_text, rating) 
                                    VALUES (?, ?, ?, ?)");
            $stmt->bind_param('iisi', $appointment_id, $_SESSION['user_id'], $review, $rating);
            $stmt->execute();
            $stmt->close();

            echo "Thank you for your review!";
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
    <title>Leave a Review</title>
    <link rel="stylesheet" href="css/styles.css">
    <style>
        /* General Reset */
        body, h1, p, textarea {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f9;
            color: #333;
            padding: 20px;
        }

        h1 {
            color: #007bff;
            margin-bottom: 20px;
        }

        .error {
            color: red;
            font-weight: bold;
        }

        /* Review Form */
        .review-form {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            max-width: 600px;
            margin: 0 auto;
        }

        .review-form h2 {
            margin-bottom: 20px;
            font-size: 1.5rem;
        }

        .review-form p {
            font-size: 1rem;
            margin-bottom: 20px;
        }

        /* Star Rating */
        .rating {
            display: flex;
            gap: 10px;
            font-size: 2rem;
            margin-bottom: 20px;
            cursor: pointer;
        }

        .star {
            color: #dcdcdc;
            transition: color 0.2s ease-in-out;
        }

        .star:hover,
        .star.selected {
            color: #ffcc00;
        }

        textarea {
            width: 100%;
            padding: 10px;
            font-size: 1rem;
            border: 1px solid #ddd;
            border-radius: 8px;
            margin-bottom: 20px;
            resize: vertical;
        }

        button {
            background-color: #007bff;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            font-size: 1rem;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        button:hover {
            background-color: #0056b3;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .review-form {
                padding: 15px;
            }

            .rating {
                font-size: 1.5rem;
                gap: 5px;
            }
        }
    </style>
</head>
<body>
    <h1>Leave a Review for Your Experience</h1>
    <div class="review-form">
        <p>Appointment Date: <?php echo htmlspecialchars($appointment_date); ?>, <?php echo htmlspecialchars($appointment_time); ?></p>

        <?php if (isset($error_message)): ?>
            <p class="error"><?php echo htmlspecialchars($error_message); ?></p>
        <?php else: ?>
            <form method="POST">
                <div class="rating" id="rating">
                    <span class="star" data-value="1">★</span>
                    <span class="star" data-value="2">★</span>
                    <span class="star" data-value="3">★</span>
                    <span class="star" data-value="4">★</span>
                    <span class="star" data-value="5">★</span>
                </div>

                <input type="hidden" name="rating" id="rating-input" value="">

                <textarea name="review" rows="5" placeholder="Write your overall review here..."></textarea><br>

                <button type="submit">Submit Review</button>
            </form>
        <?php endif; ?>
    </div>

    <script>
        // Get all star elements
        const stars = document.querySelectorAll('.star');
        const ratingInput = document.getElementById('rating-input');

        // Add event listener for hover effect
        stars.forEach(star => {
            star.addEventListener('mouseover', () => {
                const value = parseInt(star.getAttribute('data-value'));
                updateStars(value);
            });

            // Reset stars on mouseout
            star.addEventListener('mouseout', () => {
                const selectedRating = parseInt(ratingInput.value);
                updateStars(selectedRating);
            });

            // Add click event to select rating
            star.addEventListener('click', () => {
                const value = parseInt(star.getAttribute('data-value'));
                ratingInput.value = value;
                updateStars(value);
            });
        });

        // Function to update star colors
        function updateStars(rating) {
            stars.forEach(star => {
                const value = parseInt(star.getAttribute('data-value'));
                if (value <= rating) {
                    star.classList.add('selected');
                } else {
                    star.classList.remove('selected');
                }
            });
        }
    </script>
</body>
</html>
