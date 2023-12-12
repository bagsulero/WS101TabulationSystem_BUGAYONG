<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: login_form.php");
    exit();
}

include "db_connection.php";

// Retrieve judge details and assigned events based on the logged-in username
$username = $_SESSION['username'];
$judgeQuery = "SELECT judges.judge_id, judges.judge_name, events.events_id, events.title 
               FROM judges 
               JOIN events ON judges.events_id = events.events_id 
               WHERE judges.judge_name = ?";
$stmt = $conn->prepare($judgeQuery);
$stmt->bind_param("s", $username);
$stmt->execute();

$result = $stmt->get_result();

// Check if the judge exists
if ($result->num_rows > 0) {
    $judge = $result->fetch_assoc();
    $_SESSION['judge_id'] = $judge['judge_id']; // Set judge_id in the session

    // Display the Judge Homepage
    ?>

    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Judge Homepage</title>
    </head>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
            background-color: rgba(255, 255, 255, 0.8); /* Opacity ay 0.8 (80%) */
            box-shadow: 0 0 20px 20px rgba(0, 0, 0, 0.1);
            background-image: url('images/bgfooter.jpg');
            background-size: 100%;
            background-position: cover;
            background-repeat: no-repeat;

        }

        header {
            background-image: url('images/PSU-LABEL-2.png');
            background-size:30%;
            background-position: 15% center; /* Adjust the percentage value as needed */
            background-repeat: no-repeat;
            background-color: #0A2647;
            color: #fff;
            padding: 20px;
            text-align: center;

        }
        header, footer {
            background-color: #0A2647;
            color: white;
            text-align: center;
            padding: 30px;
            font-family: 'Comic Sans MS', sans-serif;
        }

        header {
            position: fixed;
            top: 0;
            width: 100%;
        }

        footer {
            position: fixed;
            bottom: 0;
            width: 100%;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background-color: #fff;
            box-shadow: 0 0 20px 20px rgba(0, 0, 0, 0.1);
            margin-top: 120px;
        }


        h1, h2 {
            color: #0A2647;
            text-align: center;
        }

        a {
            text-decoration: none;
            color: black;
        }

        a:hover {
            text-decoration: underline;
        }

        p {
            margin-bottom: 10px;
        }

        a.logout-btn {
            display: inline-block;
            margin-top: 20px;
            padding: 12px 24px;
            background-color: #e74c3c;
            color: #fff;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-align: center;
            text-decoration: none;
            transition: background-color 0.3s ease;
        }

        a.logout-btn:hover {
            background-color: #c0392b;
        }
    </style>
    <body>

    <br><br><br><br><br><br>
    <header><h1>fsas</h1></header>


    <section>
        <div class="container">
            <?php
            if ($result->num_rows > 0) {
                echo "<h1>Welcome, {$judge['judge_name']}!</h1>";
                echo "<h2>Your Assigned Events:</h2>";
                do {
                    // Make the event clickable with a link to score_sheet.php
                    echo "<p><a href='score_sheet.php?event_id={$judge['events_id']}'>{$judge['title']}</a></p>";
                } while ($judge = $result->fetch_assoc());
            } else {
                echo "<h1>Welcome, {$judge['judge_name']}!</h1>";
                echo "<p>You don't have any assigned events right now!</p>";
            }
            ?>
            <br>
            <a href="login_form.php" class="logout-btn">Logout</a>
        </div>
    </section>
    <!-- Add your scripts or include external scripts here -->
    <footer>
        &copy; 2023 Events Tabulation System
    </footer>
    </body>
    </html>

    <?php
} else {
    echo '<h1>HELLO AND WELCOME</h1>';
    echo '<p>You don\'t have any assigned events right now!</p></div>';
}

$stmt->close();
$conn->close();
?>
