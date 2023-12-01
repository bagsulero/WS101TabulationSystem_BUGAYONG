<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: login_form.php");
    exit();
}

include "db_connection.php";

// Assuming you have the event ID in the URL
if (isset($_GET['event_id'])) {
    $event_id = $_GET['event_id'];

    // Fetch the event title
    $eventTitleQuery = "SELECT title FROM events WHERE events_id = ?";
    $stmtEventTitle = $conn->prepare($eventTitleQuery);
    $stmtEventTitle->bind_param("i", $event_id);
    $stmtEventTitle->execute();
    $resultEventTitle = $stmtEventTitle->get_result();

    if ($resultEventTitle->num_rows > 0) {
        $eventTitle = $resultEventTitle->fetch_assoc()['title'];
    } else {
        $eventTitle = "Unknown Event";
    }

    $stmtEventTitle->close();

    // Retrieve contestant and criteria information for the specified event
    $query = "SELECT contestants.contestant_number, contestants.contestant_name, criteria.criteria_id, criteria.criteria_name, criteria.percentage, scores.score
              FROM contestants
              JOIN criteria ON contestants.events_id = criteria.events_id
              LEFT JOIN scores ON contestants.contestant_number = scores.contestant_id AND criteria.criteria_id = scores.criteria_id
              WHERE contestants.events_id = ?";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $event_id);
    $stmt->execute();
    $result = $stmt->get_result();

    // Check if there are contestants and criteria for the specified event
    if ($result->num_rows > 0) {
        $contestants = array();

        while ($row = $result->fetch_assoc()) {
            $contestantNumber = $row['contestant_number'];
            $contestantName = $row['contestant_name'];
            $criterionId = $row['criteria_id'];
            $criterionName = $row['criteria_name'];
            $percentage = $row['percentage'];
            $score = $row['score'];

            // Build the array structure
            $contestants[$contestantNumber]['name'] = $contestantName;
            $contestants[$contestantNumber]['criteria'][$criterionId]['name'] = $criterionName;
            $contestants[$contestantNumber]['criteria'][$criterionId]['percentage'] = $percentage;
            $contestants[$contestantNumber]['criteria'][$criterionId]['score'] = $score;
        }

        // Remove duplicate criteria
        foreach ($contestants as &$contestant) {
            $contestant['criteria'] = array_values($contestant['criteria']);
        }
        unset($contestant);

        // Display the Score Sheet
        ?>

        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Score Sheet</title>
            <!-- Add your styles or include external stylesheets here -->
        </head>
        <body>
            <h1>Score Sheet for <?php echo $eventTitle; ?></h1>

            <form action="save_scores.php" method="post">
                <table border="1">
                    <tr>
                        <th>Contestant Number</th>
                        <th>Contestant Name</th>

                        <?php
                        // Display criteria columns
                        foreach ($contestants[key($contestants)]['criteria'] as $criterion) {
                            echo "<th>{$criterion['name']}</th>";
                        }
                        ?>
                    </tr>

                    <?php
                    // Display contestant rows
                    foreach ($contestants as $contestantNumber => $contestant) {
                        echo "<tr>";
                        echo "<td>$contestantNumber</td>";
                        echo "<td>{$contestant['name']}</td>";

                        // Display input fields for criteria scores
                        foreach ($contestant['criteria'] as $criterion) {
                            $maxValue = $criterion['percentage'];
                            echo "<td><input type='number' name='scores[$contestantNumber][{$criterion['name']}]' max='$maxValue' min='0' step='any'></td>";
                        }
                        // Calculate and display total score
                        $totalScore = 0;
                        foreach ($contestant['criteria'] as $criterion) {
                            $criterionScore = isset($criterion['score']) ? (float)$criterion['score'] : 0;
                            $totalScore += $criterionScore;
                        }

                        echo "</tr>";
                    }
                    ?>
                </table>

                <!-- Add a hidden input field to pass the event ID to the processing script -->
                <input type="hidden" name="event_id" value="<?php echo $event_id; ?>">

                <br>
                <input type="submit" value="Submit Scores">
            </form>

            <!-- Add more content as needed -->

            <br>
            <a href="judge_homepage.php">Back to Judge Homepage</a>

            <!-- Add your scripts or include external scripts here -->
        </body>
        </html>

        <?php
    } else {
        echo "No contestants or criteria found for the specified event.";
    }

    $stmt->close();
} else {
    echo "Event ID not provided.";
}

$conn->close();
?>
