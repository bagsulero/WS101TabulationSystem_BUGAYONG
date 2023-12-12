<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Total Scores</title>
    <style>
  body
   {
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
    position: fixed;
    width: 100%;
    background-image: url('images/PSU-LABEL-2.png');
    background-size:30%;
    background-position: 15% center; 
    background-repeat: no-repeat;
    background-color: #0A2647;
    color: #fff;
    padding: 20px;
    text-align: center;
        }

        footer {
            background-color: #0A2647;
            color: white;
            padding: 2px;
            text-align: center;
            font-family: 'Comic Sans MS', sans-serif;
            position: fixed;
            bottom: 0;
            width: 100%;
        }

        h1{
            margin-bottom: 20px;
            color: #0A2647;
            text-align: center;
            font-size: 28px;
            text-transform: uppercase;
            font-family: 'Comic Sans MS', sans-serif;
            padding-bottom: 10px;
        }
        h2 {
            margin-bottom: 20px;
            color: #0A2647;
            text-align: center;
            font-size: 28px;
            text-transform: uppercase;
            font-family: 'Comic Sans MS', sans-serif;
            padding-bottom: 10px;
        }

        section {
            width: 50%;
            margin: 20px auto;
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
            background-color:#064789;
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
            background-color: #3498db;
            border: none;
            color: white;
            cursor: pointer;
            border-radius: 4px;
            transition: background-color 0.3s ease;
        }

        input[type="submit"]:hover {
            background-color: #2980b9;
        }

        a {
            color: #3498db;
            text-decoration: none;
        }

        a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<header>
    <!-- Header content goes here -->
    <h1>Your</h1>
</header>
<body> 
<section>
        <div class="container">
            <?php
            session_start();

            if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['scores']) && isset($_POST['event_id'])) {
                include "db_connection.php";

                // Retrieve the event ID, scores array, and judge ID from the form submission
                $event_id = $_POST['event_id'];
                $scores = $_POST['scores'];
                $judge_id = $_SESSION['judge_id']; // Assuming the judge_id is stored in the session

                // Prepare the statement for updating scores
                $updateQuery = "INSERT INTO scores (events_id, contestant_id, criteria_id, score, judge_id) VALUES (?, ?, ?, ?, ?)";
                $stmtUpdate = $conn->prepare($updateQuery);

                if (!$stmtUpdate) {
                    // Handle the error as needed
                    echo "Error preparing update statement: " . $conn->error;
                    exit();
                }

                // Iterate through the scores and update the database
                foreach ($scores as $contestantNumber => $criteriaScores) {
                    foreach ($criteriaScores as $criterionName => $criterionScore) {
                        // Retrieve the criteria_id for the current criterionName
                        $criteriaQuery = "SELECT criteria_id FROM criteria WHERE events_id = ? AND criteria_name = ?";
                        $stmtCriteria = $conn->prepare($criteriaQuery);
                        $stmtCriteria->bind_param("is", $event_id, $criterionName);
                        $stmtCriteria->execute();
                        $resultCriteria = $stmtCriteria->get_result();

                        // Check if the criteria exists
                        if ($resultCriteria->num_rows > 0) {
                            $criteria = $resultCriteria->fetch_assoc();
                            $criteria_id = $criteria['criteria_id'];

                            // Update the scores in the database
                            $stmtUpdate->bind_param("iiidi", $event_id, $contestantNumber, $criteria_id, $criterionScore, $judge_id);
                            $stmtUpdate->execute();

                            if ($stmtUpdate->error) {
                                // Handle the error as needed
                                echo "Error updating scores: " . $stmtUpdate->error;
                            }
                        }

                        // Close the statement for retrieving criteria
                        $stmtCriteria->close();
                    }
                }

                // Close the prepared statement for updating scores
                $stmtUpdate->close();

                // Calculate and display total scores for each contestant from the same judge
                $getContestantsQuery = "SELECT c.contestant_number, c.contestant_name, COALESCE(SUM(s.score), 0) AS total_score
                                        FROM contestants c
                                        LEFT JOIN scores s ON c.contestant_number = s.contestant_id AND s.events_id = ? AND s.judge_id = ?
                                        WHERE c.events_id = ?
                                        GROUP BY c.contestant_number
                                        ORDER BY total_score DESC, c.contestant_number ASC";
                $stmtContestants = $conn->prepare($getContestantsQuery);
                $stmtContestants->bind_param("iii", $event_id, $judge_id, $event_id);
                $stmtContestants->execute();
                $resultContestants = $stmtContestants->get_result();

                // Retrieve the event title based on the event ID
                $getEventTitleQuery = "SELECT title FROM events WHERE events_id = ?";
                $stmtEventTitle = $conn->prepare($getEventTitleQuery);
                $stmtEventTitle->bind_param("i", $event_id);
                $stmtEventTitle->execute();
                $resultEventTitle = $stmtEventTitle->get_result();

                // Check if the event title exists
                if ($resultEventTitle->num_rows > 0) {
                    $eventTitleRow = $resultEventTitle->fetch_assoc();
                    $eventTitle = $eventTitleRow['title'];
                } else {
                    // Handle the case where the event title is not found
                    $eventTitle = "Unknown Event";
                }

                // Display the total scores in a table along with the rank
                echo "<h2>Total Scores for $eventTitle</h2>";
                echo "<table border='1'>";
                echo "<tr><th>Contestant Number</th><th>Contestant Name</th>";

                // Retrieve and display the criteria names as headers
                $getCriteriasQuery = "SELECT criteria_name FROM criteria WHERE events_id = ?";
                $stmtCriterias = $conn->prepare($getCriteriasQuery);
                $stmtCriterias->bind_param("i", $event_id);
                $stmtCriterias->execute();
                $resultCriterias = $stmtCriterias->get_result();

                while ($row = $resultCriterias->fetch_assoc()) {
                    echo "<th>{$row['criteria_name']}</th>";
                }

                echo "<th>Total Score</th><th>Rank</th></tr>";

                $rank = 0;
                $prevTotalScore = null; // Variable to track the previous total score

                while ($row = $resultContestants->fetch_assoc()) {
                    echo "<tr>";
                    echo "<td>{$row['contestant_number']}</td>";
                    echo "<td>{$row['contestant_name']}</td>";

                    // Retrieve and display the scores for each criterion
                    $getScoresQuery = "SELECT cri.criteria_name, s.score
                                       FROM criteria cri
                                       LEFT JOIN scores s ON cri.criteria_id = s.criteria_id AND s.contestant_id = ? AND s.events_id = ? AND s.judge_id = ?
                                       WHERE cri.events_id = ?";
                    $stmtScores = $conn->prepare($getScoresQuery);
                    $stmtScores->bind_param("iiii", $row['contestant_number'], $event_id, $judge_id, $event_id);
                    $stmtScores->execute();
                    $resultScores = $stmtScores->get_result();

                    while ($scoreRow = $resultScores->fetch_assoc()) {
                        echo "<td>{$scoreRow['score']}</td>";
                    }

                    echo "<td>{$row['total_score']}</td>";

                    // Check if the current total score is different from the previous one
                    if ($row['total_score'] != $prevTotalScore) {
                        // If different, update the rank
                        $rank++;
                    }

                    echo "<td>{$rank}</td>";
                    echo "</tr>";

                    // Update the previous total score
                    $prevTotalScore = $row['total_score'];
                }

                echo "</table>";

                // Close the prepared statement for retrieving contestants
                $stmtContestants->close();
                $stmtCriterias->close();
                $stmtScores->close();

                // Close the database connection
                $conn->close();
            } else {
                // Invalid request, redirect to the homepage or display an error message
                header("Location: judge_homepage.php");
                exit();
            }
            ?>
        </div>
    </section>
  </div>
  <footer>
    <!-- Footer content goes here -->
    &copy; 2023 Events Tabulation System
</footer>
</body>
</html>
