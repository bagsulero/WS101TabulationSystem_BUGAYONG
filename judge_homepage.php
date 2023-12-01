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
    <body>
        <h1>Welcome, <?php echo $judge['judge_name']; ?>!</h1>
        <h2>Your Assigned Events:</h2>

        <?php
        // Echo the judge_id
        echo "Judge ID: " . $_SESSION['judge_id'];
        ?>

        <?php
        do {
            // Make the event clickable with a link to score_sheet.php
            echo "<p><a href='score_sheet.php?event_id={$judge['events_id']}'>{$judge['title']}</a></p>";
        } while ($judge = $result->fetch_assoc());

        ?>


        <br>
        <a href="login_form.php">Logout</a>

    </body>
    </html>

    <?php
} else {
    echo "Judge not found!";
}

$stmt->close();
$conn->close();
?>
