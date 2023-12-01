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
                            ORDER BY total_score DESC";
    $stmtContestants = $conn->prepare($getContestantsQuery);
    $stmtContestants->bind_param("iii", $event_id, $judge_id, $event_id);
    $stmtContestants->execute();
    $resultContestants = $stmtContestants->get_result();

    // Display the total scores in a table along with the rank
    echo "<h2>Total Scores for Event $event_id</h2>";
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

    $rank = 1;
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
        echo "<td>{$rank}</td>";
        echo "</tr>";

        $rank++;
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
