<?php
include 'db_connection.php';

$editCandidateId = '';
$editCriteriaId = '';
$editedNumber = '';
$editedName = '';
$editedCriteria = '';
$editedPercentage = '';
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

    // Process form submission (Add Contestant)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
        $contestantNumber = isset($_POST['edited_number']) ? $_POST['edited_number'] : '';
        $contestantName = isset($_POST['edited_name']) ? $_POST['edited_name'] : '';

        // Insert contestant data into the contestants table and connect it to the events table
        $insertContestantQuery = "INSERT INTO contestants (contestant_number, contestant_name, events_id) VALUES ('$contestantNumber', '$contestantName', '$eventId')";
    
        if ($conn->query($insertContestantQuery) !== TRUE) {
            die("Contestant insertion failed: " . $conn->error);
        }
    }

    // Process form submission (Edit Contestant)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit'])) {
        $editCandidateId = isset($_POST['edit_contestant_id']) ? $_POST['edit_contestant_id'] : '';
        $editedNumber = isset($_POST['edited_number']) ? $_POST['edited_number'] : '';
        $editedName = isset($_POST['edited_name']) ? $_POST['edited_name'] : '';

        // Update candidate data in the event-specific table
        $updateCandidateQuery = "UPDATE contestants SET contestant_number='$editedNumber', contestant_name='$editedName' WHERE contestant_id='$editCandidateId'";
        if ($conn->query($updateCandidateQuery) !== TRUE) {
            die("Contestant update failed: " . $conn->error);
        }
    }

    // Process form submission (Delete Contestant)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete'])) {
        $deleteCandidateId = isset($_POST['delete_contestant_id']) ? $_POST['delete_contestant_id'] : '';

        // Delete contestant data from the event-specific table
        $deleteCandidateQuery = "DELETE FROM contestants WHERE contestant_id='$deleteCandidateId'";
        if ($conn->query($deleteCandidateQuery) !== TRUE) {
            die("Contestant deletion failed: " . $conn->error);
        }
    }

    // Process form submission (Add Criteria)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_additional'])) {
        $editedCriteria = isset($_POST['edited_criteria']) ? $_POST['edited_criteria'] : '';
        $editedPercentage = isset($_POST['edited_percentage']) ? $_POST['edited_percentage'] : '';
    
        // Add additional information to the title_criteria table
        $insertCriteriaQuery = "INSERT INTO criteria (criteria_name, percentage, events_id) VALUES (?, ?, ?)";
        
        // Use prepared statement to avoid SQL injection
        $stmt = $conn->prepare($insertCriteriaQuery);
        $stmt->bind_param('sii', $editedCriteria, $editedPercentage, $eventId);
    
        if ($stmt->execute() !== TRUE) {
            die("Criteria insertion failed: " . $stmt->error);
        }
    
        // Close the prepared statement
        $stmt->close();
    
        // Redirect to refresh the page after adding criteria
        header("Location: event_info.php?id=" . $eventId);
        exit();
    }

    // Process form submission (Edit Criteria)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_criteria'])) {
        $editCriteriaId = isset($_POST['edit_criteria_id']) ? $_POST['edit_criteria_id'] : '';
        $editedCriteria = isset($_POST['edited_criteria']) ? $_POST['edited_criteria'] : '';
        $editedPercentage = isset($_POST['edited_percentage']) ? $_POST['edited_percentage'] : '';

        // Update criteria data in the criteria table
        $updateCriteriaQuery = "UPDATE criteria SET criteria_name='$editedCriteria', percentage='$editedPercentage' WHERE criteria_id='$editCriteriaId'";

        if ($conn->query($updateCriteriaQuery) !== TRUE) {
            die("Criteria update failed: " . $conn->error);
        }
    }

    // Process form submission (Delete Criteria)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_criteria'])) {
        $deleteCriteriaId = isset($_POST['delete_criteria_id']) ? $_POST['delete_criteria_id'] : '';

        // Delete criteria data from the criteria table
        $deleteCriteriaQuery = "DELETE FROM criteria WHERE criteria_id=?";
        $stmt = $conn->prepare($deleteCriteriaQuery);
        $stmt->bind_param('i', $deleteCriteriaId);

        if ($stmt->execute() !== TRUE) {
            die("Criteria deletion failed: " . $stmt->error);
        }

        // Close the prepared statement
        $stmt->close();

        // Redirect to refresh the page after deleting criteria
        header("Location: event_info.php?id=" . $eventId);
        exit();
    }

    // Retrieve updated criteria data
    $selectCriteriaQuery = "SELECT criteria_id, criteria_name, percentage FROM criteria WHERE events_id = $eventId";
    $criteriaResult = $conn->query($selectCriteriaQuery);
    if ($criteriaResult === false) {
        die("Criteria retrieval failed: " . $conn->error);
    }

    // Retrieve data from the event-specific table including the 'id' column
    $selectDataQuery = "SELECT contestant_id, contestant_number, contestant_name FROM contestants WHERE events_id = $eventId";
    $dataResult = $conn->query($selectDataQuery);
    if ($dataResult === false) {
        die("Data retrieval failed: " . $conn->error);
    }

    // Retrieve columns from the event-specific table
    $selectColumnsQuery = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'contestants'";
    $columnsResult = $conn->query($selectColumnsQuery);
    if ($columnsResult === false) {
        die("Columns retrieval failed: " . $conn->error);
    }

    // Populate $columns array with column names
    while ($columnRow = $columnsResult->fetch_assoc()) {
        $columns[] = $columnRow['COLUMN_NAME'];
    }
} else {
    // Redirect back to admin_homepage.php if no event ID is provided
    header("Location: admin_homepage.php");
    exit();
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
    header("Location: event_info.php?id=" . $eventId);
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
    header("Location: event_info.php?id=" . $eventId);
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
    <title>Event Information</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        header {
    background-image: url('images/PSU-LABEL_b.png'); /* Palitan mo ang 'lo.png' ng tamang pangalan ng iyong larawan */
    background-size: contain; /* I-adjust ang size depende sa laki ng larawan nang hindi naapekto ang aspect ratio */
    background-position: left center; /* I-adjust ang position para maging 'left' */
    background-repeat: no-repeat;
    background-color: #333;
    color: #fff;
    padding: 20px;
    text-align: center;
}
        header, footer {
            background-color: #333;
            color: #fff;
            text-align: center;
            padding: 10px;
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

        .column {
            max-width:734px; /* Adjusted max-width */
            margin: 80px auto; /* Center the columns */
            padding: 40px;
            background-color: #fff;
            box-shadow: 0 0 20px 20px rgba(0, 0, 0, 0.1);
            display: inline-block;
            vertical-align: top;
            width: 100%; /* Make sure the column takes full width on smaller screens */
            box-sizing: border-box; /* Include padding and border in the div's total width and height */
            
        }

        .column h2, .column h3 {
            color: #333;
            text-align: center;
        }

        .column h2 strong {
            color: #3498db; /* Blue */
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
            width: 100%; /* Make the input fields take full width */
            padding: 10px;
            margin-bottom: 15px;
            box-sizing: border-box;
        }

        input[type="submit"],
        a.edit-button,
        a.delete-button {
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
            background-color: #3498db; /* Blue */
        }

        input[type="submit"]:hover {
            background-color: #2980b9;
        }

        a.edit-button {
            background-color: #3498db; /* Blue */
        }

        a.edit-button:hover {
            background-color: #2980b9;
        }

        a.delete-button {
            background-color: red; /* Red */
        }

        a.delete-button:hover {
            background-color: #c0392b;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th, td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
        }

        th {
            background-color: #333;
            color: red;
        }

        a {
            text-decoration: none;
            color: black;
        }

        a:hover {
            text-decoration: underline;
        }

        footer {
            background-color: #333;
            color: #fff;
            padding: 10px;
            text-align: center;
            margin-top: 100px;
        }
         h3 {
            font-family: 'Arial', sans-serif;
            font-size: 24px;
            font-weight: bold;
            color: #333;
        }
    </style>
</head>
<body>
<header>
        <h1>Events Tabulation System</h1>
    </header>
</body>
</html>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Information</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }

        header, footer {
            background-color: #333;
            color: #fff;
            text-align: center;
            padding: 10px;
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
            margin-top: 120px; /* Adjusted margin-top */
        }

        h2, h3 {
            color: #333;
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
        a.edit-button,
        a.delete-button {
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

        a.edit-button {
            background-color: #3498db;
        }

        a.edit-button:hover {
            background-color: #2980b9;
        }

        a.delete-button {
            background-color: red;
        }

        a.delete-button:hover {
            background-color: #c0392b;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th, td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: left;
        }

        th {
            background-color: #333;
            color: red;
        }

        a {
            text-decoration: none;
            color: black;
        }

        a:hover {
            text-decoration: underline;
        }

        footer {
            background-color: #333;
            color: #fff;
            padding: 10px;
            text-align: center;
        }
    </style>
</head>
<body>
<header>
        <h1>Events Tabulation System</h1>
    </header>
    <div class="container">
    <h2><?php echo $eventTitle; ?></h2>

    <!-- Existing Form for Contestant -->
    <form method="post" action="event_info.php?id=<?php echo $eventId; ?>">
        <label>Contestant Number:</label>
        <input type="text" name="edited_number" value="<?php echo isset($editedNumber) ? htmlspecialchars($editedNumber) : ''; ?>" required>
        
        <label>Contestant Name:</label>
        <input type="text" name="edited_name" value="<?php echo isset($editedName) ? htmlspecialchars($editedName) : ''; ?>" required>

        <?php
        if ($editCandidateId) {
            echo "<input type='hidden' name='edit_contestant_id' value='{$editCandidateId}'>";
            echo "<button type='submit' name='edit'>Edit Contestant</button>";
            echo "<button type='submit' name='cancel'>Cancel</button>";
        } else {
            echo "<input type='submit' name='submit' value='Add Contestant'>";
        }
        ?>
    </form>

    <!-- Table Display for Candidate Information -->
    <h3>EVENT CONTESTANTS:</h3>
    <table>
        <tr>
            <th>Contestant Number</th>
            <th>Contestant Name</th>
            <th>Actions</th> <!-- Add Actions column -->
        </tr>
        <?php
        // Display table data dynamically
        while ($rowData = $dataResult->fetch_assoc()) {
            echo "<tr>";
            echo "<td>{$rowData['contestant_number']}</td>";
            echo "<td>{$rowData['contestant_name']}</td>";
            echo "<td>";
            echo "<form method='post' action='event_info.php?id={$eventId}'>";
            echo "<input type='hidden' name='edit_contestant_id' value='{$rowData['contestant_id']}'>";
            echo "<input type='hidden' name='edited_number' value='{$rowData['contestant_number']}'>";
            echo "<input type='hidden' name='edited_name' value='{$rowData['contestant_name']}'>";
            echo "<button type='submit' name='edit'>Edit</button>";
            echo "</form>";
            echo "<form method='post' action='event_info.php?id={$eventId}'>";
            echo "<input type='hidden' name='delete_contestant_id' value='{$rowData['contestant_id']}' >";
            echo "<button type='submit' name='delete' onclick=\"return confirm('Are you sure you want to delete this candidate?');\">Delete</button>";
            echo "</form>";
            echo "</td>";
            echo "</tr>";
        }
        ?>
    </table>

    <!-- Additional Input Boxes for Criteria and Percentage -->
    <h3>Criteria:</h3>
    <form method="post" action="event_info.php?id=<?php echo $eventId; ?>">
        <label>Criteria:</label>
        <!-- Update input name to 'edited_criteria' -->
        <input type="text" name="edited_criteria" value="<?php echo isset($editedCriteria) ? htmlspecialchars($editedCriteria) : ''; ?>" required>

        <label>Percentage:</label>
        <!-- Update input name to 'edited_percentage' -->
        <input type="number" name="edited_percentage" min="1" max="100" value="<?php echo isset($editedPercentage) ? htmlspecialchars($editedPercentage) : ''; ?>" required>

        <?php
        if ($editCriteriaId) {
            echo "<input type='hidden' name='edit_criteria_id' value='{$editCriteriaId}'>";
            echo "<button type='submit' name='edit_criteria'>Save Changes</button>";
            echo "<button type='submit' name='cancel_criteria'>Cancel</button>";
        } else {
            echo "<input type='submit' name='submit_additional' value='Add Criteria'>";
        }
        ?>
    </form>

    <!-- Additional Information Table -->
    <h3>EVENT CRITERIA:</h3>
    <table>
        <tr>
            <th>Criteria</th>
            <th>Percentage</th>
            <th>Actions</th>
        </tr>
        <?php
        // Display additional information dynamically
        $selectCriteriaQuery = "SELECT criteria_id, criteria_name, percentage FROM criteria WHERE events_id = $eventId";
        $criteriaResult = $conn->query($selectCriteriaQuery);

        if ($criteriaResult === false) {
            die("Criteria retrieval failed: " . $conn->error);
        }

        while ($criteriaRow = $criteriaResult->fetch_assoc()) {
            echo "<tr>";
            echo "<td>{$criteriaRow['criteria_name']}</td>";
            echo "<td>{$criteriaRow['percentage']}%</td>";
            echo "<td>";
            ?>
            <!-- Edit Criteria Form -->
            <form method="post" action="event_info.php?id=<?php echo $eventId; ?>">
                <input type="hidden" name="edit_criteria_id" value="<?php echo $criteriaRow['criteria_id']; ?>">
                <input type="hidden" name="edited_criteria" value="<?php echo $criteriaRow['criteria_name']; ?>">
                <input type="hidden" name="edited_percentage" value="<?php echo $criteriaRow['percentage']; ?>">
                <button type="submit" name="edit_criteria">Edit</button>
            </form>

            <!-- Delete Criteria Form -->
            <form method="post" action="event_info.php?id=<?php echo $eventId; ?>">
                <input type="hidden" name="delete_criteria_id" value="<?php echo $criteriaRow['criteria_id']; ?>">
                <button type="submit" name="delete_criteria" onclick="return confirm('Are you sure you want to delete this criteria?');">Delete</button>
            </form>
            <?php
            echo "</td>";
            echo "</tr>";
        }
        ?>
    </table>


   <!-- Additional Information Table for Judges -->
<h3>JUDGES:</h3>
<form method="post" action="event_info.php?id=<?php echo $eventId; ?>">
    <label>Judge Name:</label>
    <input type="text" name="edited_judge_name" value="<?php echo isset($editedjudgeName) ? htmlspecialchars($editedjudgeName) : ''; ?>" required>

    <?php
        if ($editJudgeId) {
            echo "<input type='hidden' name='edit_judge_id' value='{$editJudgeId}'>";
            echo "<button type='submit' name='edit_judge'>Edit Judge</button>";
            echo "<button type='submit' name='cancel'>Cancel</button>";
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
        <form method="post" action="event_info.php?id=<?php echo $eventId; ?>">
            <input type="hidden" name="edit_judge_id" value="<?php echo $judgeRow['judge_id']; ?>">
            <input type="hidden" name="edited_judge_name" value="<?php echo $judgeRow['judge_name']; ?>">
            <button type="submit" name="edit_judge">Edit</button>
        </form>


        <!-- Delete Judge Form -->
        <form method="post" action="event_info.php?id=<?php echo $eventId; ?>">
            <input type="hidden" name="delete_judge_id" value="<?php echo $judgeRow['judge_id']; ?>">
            <button type="submit" name="delete_judge" onclick="return confirm('Are you sure you want to delete this judge?');">Delete</button>
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