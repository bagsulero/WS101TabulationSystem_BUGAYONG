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

    // Sort contestants based on grand total and handle ties
    usort($rankedContestants, function ($a, $b) {
        return $b['grand_total'] - $a['grand_total'];
    });

    // Display scores for each contestant in the summary table
    $rank = 1;
    $prevTotal = null;
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
        echo "<td>";
        if ($prevTotal === null || $prevTotal != $contestant['grand_total']) {
            echo $rank;
        } else {
            // Assign the same rank for the same grand total
            $rank--;
            echo $rank;
        }
        echo "</td>";

        echo "</tr>";
        $prevTotal = $contestant['grand_total'];
        $rank++;
    }

    echo "</table>";

   // Display table for each criterion
foreach ($criteriaNames as $criterion => $_) {
    echo "<h2>$criterion Scores</h2>";

    // Create a temporary array to store total criterion scores
    $criterionScores = [];

    foreach ($contestantData as $contestantKey => $data) {
        // Calculate total criterion score for each contestant
        $totalCriterionScore = 0;
        foreach ($resultJudges as $judge) {
            $totalCriterionScore += isset($data[$judge['judge_name']][$criterion]) ? $data[$judge['judge_name']][$criterion] : 0;
        }

        // Store the total criterion score for each contestant
        $criterionScores[$contestantKey] = $totalCriterionScore;
    }

    // Sort contestants based on total criterion score
    arsort($criterionScores);

    // Display sorted scores in the table
    echo "<table border='1'>";
    echo "<tr><th>Contestant Number</th><th>Contestant Name</th>";

    foreach ($resultJudges as $judge) {
        echo "<th>{$judge['judge_name']}</th>";
    }

    echo "<th>Total Criterion Score</th>";
    echo "<th>Rank</th></tr>";

    $rank = 1;
    $prevTotalCriterion = null;
    foreach ($criterionScores as $contestantKey => $totalCriterionScore) {
        $data = $contestantData[$contestantKey];

        echo "<tr>";
        echo "<td>{$contestantKey}</td>";
        echo "<td>{$data['name']}</td>";

        foreach ($resultJudges as $judge) {
            $score = isset($data[$judge['judge_name']][$criterion]) ? $data[$judge['judge_name']][$criterion] : '';
            echo "<td>{$score}</td>";
        }

        echo "<td>{$totalCriterionScore}</td>";

        // Display rank for each contestant for the criterion
        echo "<td>";
        if ($prevTotalCriterion === null || $prevTotalCriterion != $totalCriterionScore) {
            echo $rank;
        } else {
            // Assign the same rank for the same total criterion score
            $rank--;
            echo $rank;
        }
        echo "</td>";

        echo "</tr>";
        $prevTotalCriterion = $totalCriterionScore;
        $rank++;
    }

    echo "</table>";
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
        $rankedContestants[] = $contestant;
        $rank++;
    }

    return $rankedContestants;
}

// Handle tied contestants
$tiedContestants = [];
$rank = 1;
$prevTotal = null;

foreach ($rankedContestants as $contestant) {
    // Check if 'rank' key exists before accessing it
    if (isset($contestant['rank'])) {
        if ($prevTotal === null || $prevTotal != $contestant['grand_total']) {
            $contestant['rank'] = $rank;
        } else {
            // Assign the same rank for the same grand total
            $rank--;
            $contestant['rank'] = $rank;
        }

        $tiedContestants[$contestant['grand_total']][] = $contestant;
        $prevTotal = $contestant['grand_total'];
        $rank++;

        // Break the loop once we reach the third rank
        if ($rank > 3) {
            break;
        }
    }
}

// Display winners
echo "<h2>Winners</h2>";
foreach ($tiedContestants as $total => $winners) {
    if (!empty($winners)) {
        // Check if 'rank' key exists before accessing it
        if (isset($winners[0]['rank'])) {
            echo "<p>Rank " . $winners[0]['rank'];
            if (count($winners) > 1) {
                echo " (Tied)";
            }
            echo ": ";
            foreach ($winners as $index => $winner) {
                // Check if 'name' key exists before accessing it
                if (isset($winner['name'])) {
                    echo $winner['name'];
                    if ($index < count($winners) - 1) {
                        echo ", ";
                    }
                }
            }
            echo " with a total score of $total</p>";
        }
    }
}

// Display awards for contestants with the highest score
echo "<h2>Awards</h2>";

foreach ($criteriaNames as $criterion => $_) {
    echo "<h3>$criterion Award</h3>";

    // Find the highest score for the criterion
    $highestScore = 0;
    foreach ($contestantData as $data) {
        $totalScoreForCriterion = 0;
        foreach ($resultJudges as $judge) {
            if (isset($data[$judge['judge_name']][$criterion])) {
                $totalScoreForCriterion += $data[$judge['judge_name']][$criterion];
            }
        }

        if ($totalScoreForCriterion > $highestScore) {
            $highestScore = $totalScoreForCriterion;
        }
    }

    // Display contestants with the highest score for the criterion
    foreach ($contestantData as $data) {
        $totalScoreForCriterion = 0;
        foreach ($resultJudges as $judge) {
            if (isset($data[$judge['judge_name']][$criterion])) {
                $totalScoreForCriterion += $data[$judge['judge_name']][$criterion];
            }
        }

        if ($totalScoreForCriterion == $highestScore) {
            echo "<p> Best in $criterion is  {$data['name']} with a total  score of $highestScore</p>";
        }
    }
}


?>
