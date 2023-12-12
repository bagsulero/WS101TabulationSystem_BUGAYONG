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

    // Check if scores already submitted for this event
    $checkScoresQuery = "SELECT COUNT(*) AS score_count FROM scores WHERE events_id = ? AND judge_id = ?";
    $stmtCheckScores = $conn->prepare($checkScoresQuery);
    $stmtCheckScores->bind_param("ii", $event_id, $_SESSION['judge_id']);
    $stmtCheckScores->execute();
    $resultCheckScores = $stmtCheckScores->get_result();
    $scoreCount = $resultCheckScores->fetch_assoc()['score_count'];

    $stmtCheckScores->close();

    // If scores already submitted, display a message
    if ($scoreCount > 0) {
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Score Sheet</title>
            <style>
                body {
                    display: flex;
                    flex-direction: column;
                    min-height: 100vh;
                    margin: 0;
                    padding: 0;
                    align-items: center;
                    font-family: 'Arial', sans-serif;
                    box-shadow: 0 0 20px 20px rgba(0, 0, 0, 0.1);
                    background-image: url('images/bgfooter.jpg');
                    background-size: cover;
                    background-position: left;
                    background-repeat: no-repeat;
                }

                header {
                    background-image: url('images/PSU-LABEL-2.png');
                    background-size: 30%;
                    background-position: 15% center;
                    background-repeat: no-repeat;
                    background-color: #0A2647;
                    color: #fff;
                    padding: 20px;
                    text-align: center;
                    position: fixed;
                    top: 0;
                    width: 100%;
                }

                footer {
                    background-color: #0A2647;
                    color: white;
                    padding: 10px;
                    text-align: center;
                    width: 100%;
                    position: fixed;
                    bottom: 0;
                    box-shadow: 0 -2px 4px rgba(0, 0, 0, 0.1);
                }

                h2 {
                    margin-bottom: 20px;
                    color: #0A2647;
                    text-align: center;
                }

                section {
                    width: 50%;
                }

                .container {
                    background-color: #fff;
                    box-shadow: 0 0 20px 20px rgba(0, 0, 0, 0.1);
                    padding: 20px;
                    border-radius: 8px;
                    margin-top: 200px;
                }

                p {
                    margin-bottom: 10px;
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

                    /* Add this CSS to your existing styles */
                    a.back-btn {
                        display: inline-block;
                        padding: 10px 20px;
                        background-color: #333;
                        color: #fff;
                        text-decoration: none;
                        border-radius: 4px;
                        transition: background-color 0.3s ease;
                    }

                    a.back-btn:hover {
                        background-color: red;
                    }
            </style>
        </head>
        <body>
        <header>
            <h2>Score Sheet</h2>
        </header>
        <section>
            <div class="container">
                <p>YOU ALREADY SUBMITTED SCORES FOR THIS EVENT</p>
                <br>
                <a href="judge_homepage.php" class="back-btn">Back to Judge Homepage</a>
            </div>
        </section>
        <footer>
            &copy; 2023 Events Tabulation System
        </footer>
        </body>
        </html>
        <?php
    } else {
        // Continue with the rest of your code to display the score input form
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
                <style>
                    body {
                        display: flex;
                        flex-direction: column;
                        min-height: 100vh;
                        margin: 0;
                        padding: 0;
                        align-items: center;
                        font-family: 'Arial', sans-serif;
                        box-shadow: 0 0 20px 20px rgba(0, 0, 0, 0.1);
                        background-image: url('images/bgfooter.jpg');
                        background-size: cover;
                        background-position: left;
                        background-repeat: no-repeat;
                    }

                    header {
                        background-image: url('images/PSU-LABEL-2.png');
                        background-size: 30%;
                        background-position: 15% center;
                        background-repeat: no-repeat;
                        background-color: #0A2647;
                        color: #fff;
                        padding: 20px;
                        text-align: center;
                        position: fixed;
                        top: 0;
                        width: 100%;
                    }

                    footer {
                        background-color: #0A2647;
                        color: white;
                        padding: 10px;
                        text-align: center;
                        width: 100%;
                        position: fixed;
                        bottom: 0;
                        box-shadow: 0 -2px 4px rgba(0, 0, 0, 0.1);
                    }

                    h2 {
                        margin-bottom: 20px;
                        color: #0A2647;
                        text-align: center;
                    }

                    section {
                        width: 50%;
                    }

                    .container {
                        background-color: #fff;
                        box-shadow: 0 0 20px 20px rgba(0, 0, 0, 0.1);
                        padding: 20px;
                        border-radius: 8px;
                        margin-top: 200px;
                    }

                    table {
                        width: 100%;
                        margin: 20px 0;
                        border-collapse: collapse;
                    }

                    th, td {
                        border: 1px solid #ddd;
                        padding: 12px;
                        text-align: left;
                    }

                    th {
                        background-color: #064789;
                        color: white;
                    }

                    input[type="number"] {
                        width: 100%;
                        padding: 8px;
                        box-sizing: border-box;
                    }

                    input[type="submit"] {
                        display: block;
                        width: 100%;
                        padding: 12px;
                        background-color: #0A2647;
                        border: none;
                        color: white;
                        cursor: pointer;
                        border-radius: 4px;
                        transition: background-color 0.3s ease;
                    }

                    input[type="submit"]:hover {
                        background-color: red;
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

                    /* Add this CSS to your existing styles */
                    a.back-btn {
                        display: inline-block;
                        padding: 10px 20px;
                        background-color: #333;
                        color: #fff;
                        text-decoration: none;
                        border-radius: 4px;
                        transition: background-color 0.3s ease;
                    }

                    a.back-btn:hover {
                        background-color: red;
                    }
                </style>
            </head>
            <body>
            <header>
                <h2>Score Sheet</h2>
            </header>
            <section>
                <div class="container">
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
                    <a href="judge_homepage.php" class="back-btn">Back to Judge Homepage</a>
                </div>
            </section>
            <footer>
                &copy; 2023 Events Tabulation System
            </footer>
            </body>
            </html>
            <?php
        } else {
            echo "No contestants or criteria found for the specified event.";
        }

        $stmt->close();
    }
} else {
    echo "Event ID not provided.";
}

$conn->close();
?>
