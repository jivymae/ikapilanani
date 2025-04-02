<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> <!-- Make the page responsive -->
    <title>Welcome to Our Dental Clinic</title>
    <style>
        /* Base font size set to 12px */
        html {
            font-size: 12px;
        }

        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            font-size: 1rem; /* 1rem = 12px, inherited from the html tag */
            display: flex;
            flex-direction: column;
            justify-content: flex-start; /* Align items at the top */
            min-height: 100vh; /* Full height of the viewport */
            padding-top: 70px; /* Space for the fixed header */
        }

        .container {
            width: 100%;
            max-width: 1200px; /* Limit the width to 1200px on large screens */
            margin: 0 auto;
            overflow: hidden;
        }

        header {
            background: #00baff;
            color: #fff;
            padding-top: 30px;
            height: 70px; /* Fixed height of the header */
            position: fixed; /* Fix the header at the top */
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000; /* Ensure header is above other content */
            text-align: center;
            border-bottom: #ccc 1px solid;
        }

        header h1 {
            margin: 0;
            font-size: 2rem; /* 24px */
        }

        .main-content {
            display: flex;
            justify-content: center;
            align-items: flex-start;
            margin-top: 90px; /* Allow space for fixed header */
            padding: 1rem;
            flex-wrap: wrap; /* Ensure content wraps on smaller screens */
            width: 100%;
        }

        .info {
            flex: 1;
            margin-right: 2rem;
            max-width: 800px; /* Limit width on larger screens */
        }

        .info h2 {
            color: black;
            font-size: 1.5rem; /* 18px */
        }

        .info p {
            line-height: 1.6;
            font-size: 1rem; /* 12px */
            text-align: justify; /* Justify the text */
            margin-bottom: 1rem; /* Ensure some space below paragraphs */
        }

        .login-box {
            flex: 1;
            background: #00baff;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            text-align: center;
            max-width: 400px; /* Limit width for login box */
            width: 100%;
        }

        .login-box h3 {
            margin-top: 0;
            font-size: 1.25rem; /* 15px */
        }

        .login-box a {
            display: inline-block;
            padding: 1rem 2rem;
            font-size: 1.2rem; /* 14px */
            color: black;
            background: #fff;
            text-decoration: none;
            border-radius: 5px;
            transition: background 0.3s;
        }

        .login-box a:hover {
            background: #13b200;
            color: white;
        }

        footer {
            background: #00baff;
            color: #fff;
            text-align: center;
            padding: 1rem 0;
            position: fixed;
            width: 100%;
            bottom: 0;
        }

        /* Responsive Styles for Phones and Tablets */
        @media screen and (max-width: 768px) {
            body {
                padding-top: 70px; /* Maintain space for fixed header */
            }

            .main-content {
                display: flex;
                flex-direction: column;
                justify-content: flex-start;
                align-items: center;
                margin-top: 90px; /* Space for fixed header */
                padding: 1rem;
                width: 100%;
            }

            .info, .login-box {
                width: 100%;
                margin-right: 0;
                margin-bottom: 1rem;
            }

            .login-box {
                max-width: 100%;
                padding: 1rem;
            }

            .login-box a {
                font-size: 1rem; /* Slightly smaller font on mobile */
                padding: 0.8rem 1.6rem;
            }
        }

        @media screen and (max-width: 480px) {
            /* Ensure the body is centered vertically and horizontally on small screens */
            body {
                display: flex;
                justify-content: center; /* Center content horizontally */
                align-items: center; /* Center content vertically */
                flex-direction: column;
                height: 100vh; /* Full height of the viewport */
                padding: 0;
            }

            .container {
                width: 90%; /* Increase width for mobile screens */
                display: flex;
                flex-direction: column;
                justify-content: space-between; /* Ensure elements fill available space */
                align-items: stretch; /* Make sections take full width */
            }

            header h1 {
                font-size: 1.75rem; /* 21px on smaller screens */
            }

            .info h2 {
                font-size: 1.25rem; /* 15px */
            }

            .info p {
                font-size: 0.875rem; /* 10.5px */
            }

            .login-box h3 {
                font-size: 1.1rem; /* 13px */
            }

            footer {
                padding: 0.8rem 0;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <h1>Welcome to Our Dental Clinic</h1>
        </div>
    </header>

    <div class="container main-content">
        <div class="info">
            <h2>About Us</h2>
            <p>Welcome to our dental clinic, where we prioritize your oral health and provide exceptional care. Our experienced team of dental professionals is committed to offering a range of services designed to meet all your dental needs.</p>
            <p>We offer routine check-ups, cleanings, orthodontics, cosmetic dentistry, and emergency care. Our state-of-the-art facility ensures that you receive the highest quality of care in a comfortable and relaxing environment.</p>
            <p>Whether you're here for a routine appointment or a specialized procedure, our goal is to make your visit as pleasant and effective as possible.</p>
        </div>

        <div class="login-box">
            <h3>Already a Member?</h3>
            <p>Please login to access your appointments and more.</p>
            <a href="login.php">Login Now</a>
        </div>
    </div>

    <footer>
        <p>&copy; 2024 Our Dental Clinic. All Rights Reserved.</p>
    </footer>
</body>
</html>
