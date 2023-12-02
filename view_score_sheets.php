<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

if (!isset($_SESSION['username'])) {
    header("Location: login_form.php");
    exit();
}

include "db_connection.php";

if (isset($_GET['id'])) {
    $event_id = $_GET['id'];

    $getJudgesQuery = "SELECT DISTINCT j.judge_id, j.judge_name
                      FROM judges j
                      JOIN scores s ON j.judge_id = s.judge_id
                      WHERE s.events_id = ?";

    $stmtJudges = $conn->prepare($getJudgesQuery);
    $stmtJudges->bind_param("i", $event_id);
    $stmtJudges->execute();
    $resultJudges = $stmtJudges->get_result();

    $getContestantCountQuery = "SELECT COUNT(DISTINCT contestant_number) as contestant_count
                                FROM contestants
                                WHERE events_id = ?";

    $stmtContestantCount = $conn->prepare($getContestantCountQuery);
    $stmtContestantCount->bind_param("i", $event_id);
    $stmtContestantCount->execute();
    $resultContestantCount = $stmtContestantCount->get_result();
    $contestantCount = $resultContestantCount->fetch_assoc()['contestant_count'];

    // Fetch the event title
    $getEventTitleQuery = "SELECT title FROM events WHERE events_id = ?";
    $stmtEventTitle = $conn->prepare($getEventTitleQuery);
    $stmtEventTitle->bind_param("i", $event_id);
    $stmtEventTitle->execute();
    $resultEventTitle = $stmtEventTitle->get_result();
    $eventTitle = $resultEventTitle->fetch_assoc()['title'];

    echo "<h2>Score Sheet for $eventTitle</h2>";

    // Initialize arrays to store data
    $criteriaNames = [];
    $contestantData = [];
    $judgeTotalScores = [];

    while ($judge = $resultJudges->fetch_assoc()) {
        echo "<h3>Judge: {$judge['judge_name']}</h3>";

        $getScoresQuery = "SELECT c.contestant_number, c.contestant_name, cri.criteria_name, s.score
                          FROM contestants c
                          JOIN scores s ON c.contestant_number = s.contestant_id
                          JOIN criteria cri ON s.criteria_id = cri.criteria_id
                          WHERE s.events_id = ? AND s.judge_id = ? AND c.events_id = ?";

        $stmtScores = $conn->prepare($getScoresQuery);
        $stmtScores->bind_param("iii", $event_id, $judge['judge_id'], $event_id);
        $stmtScores->execute();
        $resultScores = $stmtScores->get_result();

        if ($resultScores->num_rows > 0) {
            while ($row = $resultScores->fetch_assoc()) {
                $criteriaNames[$row['criteria_name']] = true;
                $contestantKey = $row['contestant_number'];
                $contestantData[$contestantKey]['contestant_number'] = $row['contestant_number'];
                $contestantData[$contestantKey]['name'] = $row['contestant_name'];
                $contestantData[$contestantKey][$judge['judge_name']][$row['criteria_name']] = $row['score'];

                $contestantData[$contestantKey][$judge['judge_name']]['total_score'] =
                    isset($contestantData[$contestantKey][$judge['judge_name']]['total_score'])
                    ? $contestantData[$contestantKey][$judge['judge_name']]['total_score'] + $row['score']
                    : $row['score'];

                $contestantData[$contestantKey]['total_criterion_score'] =
                    isset($contestantData[$contestantKey]['total_criterion_score'])
                    ? $contestantData[$contestantKey]['total_criterion_score'] + $row['score']
                    : $row['score'];
            }

            echo "<table border='1'>";
            echo "<tr><th>Contestant Number</th><th>Contestant Name</th>";

            // Display criteria names as table headers
            foreach ($criteriaNames as $criterion => $_) {
                echo "<th>{$criterion}</th>";
            }

            echo "<th>Total Judge Score</th>";

            echo "</tr>";

            // Display scores for each contestant
            foreach ($contestantData as $contestantKey => $data) {
                echo "<tr>";
                echo "<td>{$contestantKey}</td>";
                echo "<td>{$data['name']}</td>";

                // Display scores for each criterion
                foreach ($criteriaNames as $criterion => $_) {
                    $totalScore = isset($data[$judge['judge_name']][$criterion])
                        ? $data[$judge['judge_name']][$criterion]
                        : '';
                    echo "<td>{$totalScore}</td>";
                }

                // Display total score for each contestant
                echo "<td>";
                echo isset($data[$judge['judge_name']]['total_score']) ? $data[$judge['judge_name']]['total_score'] : '';
                echo "</td>";

                echo "</tr>";
            }

            echo "</table>";
        } else {
            echo "No scores found for Event $event_id and Judge {$judge['judge_name']}<br>";
        }

        $stmtScores->close();
    }

    $stmtJudges->close();
    $stmtContestantCount->close();

    // Rank contestants
    $rankedContestants = rankContestants($contestantData);

    // Display summary table
    echo "<h2>Summary</h2>";
    echo "<table border='1'>";
    echo "<tr><th>Contestant Number</th><th>Contestant Name</th>";

    // Display total scores for each judge
    foreach ($resultJudges as $judge) {
        echo "<th>{$judge['judge_name']} Total</th>";
        $judgeTotalScores[$judge['judge_name']] = 0;
    }

    echo "<th>Grand Total</th>";
    echo "<th>Rank</th></tr>";

    // Display scores for each contestant in the summary table
    $rank = 1;
    foreach ($rankedContestants as $contestant) {
        echo "<tr>";
        echo "<td>";
        echo isset($contestant['contestant_number']) ? $contestant['contestant_number'] : '';
        echo "</td>";
        echo "<td>{$contestant['name']}</td>";

        // Display total scores for each judge
        foreach ($resultJudges as $judge) {
            // Ensure the value is numeric before adding
            $judgeTotalScore = isset($contestant[$judge['judge_name']]['total_score'])
                ? $contestant[$judge['judge_name']]['total_score']
                : 0;
            echo "<td>{$judgeTotalScore}</td>";
            $judgeTotalScores[$judge['judge_name']] = $judgeTotalScore;
        }

        // Display grand total for each contestant
        echo "<td>";
        echo is_array($judgeTotalScores) ? array_sum($judgeTotalScores) : '';
        echo "</td>";

        // Display rank for each contestant
        echo "<td>{$rank}</td>";

        echo "</tr>";
        $rank++;
    }

    echo "</table>";

    // Display table for each criterion
    foreach ($criteriaNames as $criterion => $_) {
        echo "<h2>$criterion Scores</h2>";
        echo "<table border='1'>";
        echo "<tr><th>Contestant Number</th><th>Contestant Name</th>";

        // Display scores for each judge
        foreach ($resultJudges as $judge) {
            echo "<th>{$judge['judge_name']}</th>";
        }

        // Add the new column for Total Criterion Score
        echo "<th>Total Criterion Score</th>";
        echo "<th>Rank</th>"; // Added Rank column

        echo "</tr>";

        // Display scores for each contestant for the criterion
        foreach ($contestantData as $contestantKey => $data) {
            echo "<tr>";
            echo "<td>{$contestantKey}</td>";
            echo "<td>{$data['name']}</td>";

            // Display scores for each judge
            foreach ($resultJudges as $judge) {
                $totalScore = isset($data[$judge['judge_name']][$criterion])
                    ? $data[$judge['judge_name']][$criterion]
                    : '';
                echo "<td>{$totalScore}</td>";
            }

            // Display total criterion score for each contestant
            echo "<td>";
            $totalCriterionScore = 0;
            foreach ($resultJudges as $judge) {
                $totalCriterionScore += isset($data[$judge['judge_name']][$criterion]) ? $data[$judge['judge_name']][$criterion] : 0;
            }
            echo $totalCriterionScore;
            echo "</td>";

            // Display rank for each contestant for the criterion
            echo "<td>";
            $rank = 1;
            foreach ($contestantData as $otherContestantKey => $otherData) {
                $otherTotalCriterionScore = 0;
                foreach ($resultJudges as $otherJudge) {
                    $otherTotalCriterionScore += isset($otherData[$otherJudge['judge_name']][$criterion]) ? $otherData[$otherJudge['judge_name']][$criterion] : 0;
                }
                if ($otherTotalCriterionScore > $totalCriterionScore) {
                    $rank++;
                }
            }
            echo $rank;
            echo "</td>";

            echo "</tr>";
        }

        echo "</table>";
    }


    // Display WINNERS
    echo "<h2>WINNERS</h2>";
    // Display rank 1, rank 2, and rank 3
    for ($i = 0; $i < 3 && $i < count($rankedContestants); $i++) {
        echo "Rank " . ($i + 1) . " is Contestant Number " . $rankedContestants[$i]['contestant_number'] . " " . $rankedContestants[$i]['name'] . "<br>";
    }

    echo "<h2>AWARDS</h2>";

    foreach ($criteriaNames as $criterion => $_) {
        $bestInCriterion = findBestInCriterion($rankedContestants, $criterion);
        if ($bestInCriterion) {
            $awardName = "Best in " . $criterion;
            echo "$awardName is Contestant Number {$bestInCriterion['contestant_number']} {$bestInCriterion['name']}<br>";
        }
    }

    

    $conn->close();
} else {
    echo "Event ID not provided in the URL.";
}

function rankContestants($contestantData)
{
    foreach ($contestantData as $contestantKey => $data) {
        $totalScore = 0;
        foreach ($data as $judge => $scores) {
            if ($judge !== 'name' && is_array($scores) && array_key_exists('total_score', $scores)) {
                $totalScore += $scores['total_score'];
            }
        }   
        $contestantData[$contestantKey]['grand_total'] = $totalScore;
    }

    usort($contestantData, function ($a, $b) {
        return $b['grand_total'] - $a['grand_total'];
    });

    $rankedContestants = [];
    $rank = 1;
    foreach ($contestantData as $contestant) {
        $contestant['rank'] = $rank;
        $rank++;
        $rankedContestants[] = $contestant;
    }

    return $rankedContestants;
}

function findBestInCriterion($rankedContestants, $criterion)
{
    foreach ($rankedContestants as $contestant) {
        if ($contestant['rank'] === 1) {
            return $contestant;
        }
    }

    return null;
}

?>
