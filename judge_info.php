<?php
include 'db_connection.php';

$editJudgeId = '';
$editedjudgeName = '';
$columns = array();

if (isset($_GET['id'])) {
    $eventId = $_GET['id'];

    // Retrieve event title using the provided event ID
    $result = $conn->query("SELECT * FROM events WHERE events_id = $eventId");
    if ($result === false) {
        die("Query failed: " . $conn->error);
    }

    $row = $result->fetch_assoc();
    $eventTitle = $row['title'];

}

// Process form submission (Add Judge)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_judge'])) {
    $judgeName = isset($_POST['edited_judge_name']) ? $_POST['edited_judge_name'] : '';

    // Insert judge data into the judges table and connect it to the events table
    $insertJudgeQuery = "INSERT INTO judges (judge_name, events_id) VALUES (?, ?)";
    $stmt = $conn->prepare($insertJudgeQuery);
    $stmt->bind_param('si', $judgeName, $eventId);

    if ($stmt->execute() !== TRUE) {
        die("Judge insertion failed: " . $stmt->error);
    }

    // Close the prepared statement
    $stmt->close();

    // Redirect to refresh the page after adding judge
    header("Location: judge_info.php?id=" . $eventId);
    exit();
}


// Process form submission (Edit Judge)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_judge'])) {
    $editJudgeId = isset($_POST['edit_judge_id']) ? $_POST['edit_judge_id'] : '';
    $editedjudgeName = isset($_POST['edited_judge_name']) ? $_POST['edited_judge_name'] : '';

    // Update judge data in the judges table
    $updateJudgeQuery = "UPDATE judges SET judge_name='$editedjudgeName' WHERE judge_id='$editJudgeId'";

    if ($conn->query($updateJudgeQuery) !== TRUE) {
        die("Judges update failed: " . $conn->error);
    }
}

// Process form submission (Delete Judge)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_judge'])) {
    $deleteJudgeId = isset($_POST['delete_judge_id']) ? $_POST['delete_judge_id'] : '';

    // Delete judge data from the judges table
    $deleteJudgeQuery = "DELETE FROM judges WHERE judge_id=?";
    $stmt = $conn->prepare($deleteJudgeQuery);
    $stmt->bind_param('i', $deleteJudgeId);

    if ($stmt->execute() !== TRUE) {
        die("Judge deletion failed: " . $stmt->error);
    }

    // Close the prepared statement
    $stmt->close();

    // Redirect to refresh the page after deleting judge
    header("Location: judge_info.php?id=" . $eventId);
    exit();
}

    // Retrieve judge data for editing
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_judge'])) {
        $editJudgeId = isset($_POST['edit_judge_id']) ? $_POST['edit_judge_id'] : '';
        $result = $conn->query("SELECT judge_name FROM judges WHERE judge_id = $editJudgeId");

        if ($result === false) {
            die("Judge retrieval failed: " . $conn->error);
        }

        $judgeRow = $result->fetch_assoc();
        $editedjudgeName = $judgeRow['judge_name'];
    }

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        .container {
            max-width: 600px;
            width: 100%;
            margin: 0 auto; /* Center horizontally */
            padding-bottom: 50px;
            padding-top: 50px;
        }

        body {
            font-family: 'Arial', sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
            display: flex;
            align-items: center;
            justify content: center;
            min-heigh: 100vh ;
        }

        footer {
            background-color: #333;
            color: #fff;
            text-align: center;
            padding: 10px;
            font-family: 'Comic Sans MS', sans-serif;
            position: fixed;
            bottom: 0;
            width: 100%;
        }

        header {
            position: fixed;
            top: 0;
            width: 100%;
            background-image: url('images/PSU-LABEL_b.png'); /* Palitan mo ang 'lo.png' ng tamang pangalan ng iyong larawan */
            background-size: contain; /* I-adjust ang size depende sa laki ng larawan nang hindi naapekto ang aspect ratio */
            background-position: left center; /* I-adjust ang position para maging 'left' */
            background-repeat: no-repeat;
            background-color: #333;
            color: #fff;
            padding: 20px;
            text-align: center;
        }

        form {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #333;
        }

        input[type="text"], input[type="number"] {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            box-sizing: border-box;
        }

        input[type="submit"],
        .edit-button,
        .delete-button {
            display: inline-block;
            padding: 12px 20px;
            margin: 5px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-align: center;
            text-decoration: none;
            color: white;
        }

        input[type="submit"] {
            background-color: #3498db;
        }

        input[type="submit"]:hover {
            background-color: #2980b9;
        }

        .edit-button {
            background-color: #3498db;
        }

        .edit-button:hover {
            background-color: #2980b9;
        }

        .delete-button {
            background-color: #e74c3c;
        }

        .delete-button:hover {
            background-color: #c0392b;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background-color: #333;
            color: white;
        }

        td {
            background-color: #f9f9f9;
        }
    </style>
</head>
<body>
</body>
</html>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
</head>
<body>
    <header>
        <button onclick="window.location.href='admin_homepage.php'">LIST OF EVENTS</button>
        <button onclick="window.location.href='contestant_info.php?id=<?php echo $eventId; ?>'">CONTESTANT OF THE EVENT</button>
        <button onclick="window.location.href='criteria_info.php?id=<?php echo $eventId; ?>'">CRITERIAS OF THE EVENT</button>
        <button onclick="window.location.href='judge_info.php?id=<?php echo $eventId; ?>'">JUDGES OF THE EVENT</button>
        <button onclick="window.location.href='view_score_sheets.php?id=<?php echo $eventId; ?>'">VIEW SCORE SHEETS</button>
    </header>

<div class="container">
   <!-- Additional Information Table for Judges -->
<h3>JUDGES:</h3>
<form method="post" action="judge_info.php?id=<?php echo $eventId; ?>">
    <label>Judge Name:</label>
    <input type="text" name="edited_judge_name" value="<?php echo isset($editedjudgeName) ? htmlspecialchars($editedjudgeName) : ''; ?>" required>

    <?php
        if ($editJudgeId) {
            echo "<input type='hidden' name='edit_judge_id' value='{$editJudgeId}'>";
            echo "<button type='submit' name='edit_judge' class='edit-button'>Edit Judge</button>";
            echo "<button type='submit' name='cancel' class='delete-button'>Cancel</button>";
        } else {
            echo "<input type='submit' name='submit_judge' value='Add Judge'>";
        }
        ?>
</form>

<!-- Table Display for Judges -->
<table>

<h3>EVENT JUDGES:</h3>
    <tr>
        <th>Judge Name</th>
        <th>Actions</th>
    </tr>
    <?php
    // Display judge information dynamically
    $selectJudgesQuery = "SELECT judge_id, judge_name FROM judges WHERE events_id = $eventId";
    $judgesResult = $conn->query($selectJudgesQuery);

    if ($judgesResult === false) {
        die("Judges retrieval failed: " . $conn->error);
    }

    while ($judgeRow = $judgesResult->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$judgeRow['judge_name']}</td>";
        echo "<td>";
        ?>

        
          <!-- Edit Judge Form -->
        <form method="post" action="judge_info.php?id=<?php echo $eventId; ?>">
            <input type="hidden" name="edit_judge_id" value="<?php echo $judgeRow['judge_id']; ?>">
            <input type="hidden" name="edited_judge_name" value="<?php echo $judgeRow['judge_name']; ?>">
            <button type="submit" name="edit_judge" class='edit-button'>Edit</button>
        </form>


        <!-- Delete Judge Form -->
        <form method="post" action="judge_info.php?id=<?php echo $eventId; ?>">
            <input type="hidden" name="delete_judge_id" value="<?php echo $judgeRow['judge_id']; ?>">
            <button type="submit" name="delete_judge" class='delete-button' onclick="return confirm('Are you sure you want to delete this judge?');">Delete</button>
        </form>
        
        <?php
        echo "</td>";
        echo "</tr>";
    }
    ?>
</table>
</div>

<footer>
    &copy; 2023 Events Tabulation System
</footer>


</body>

</html>