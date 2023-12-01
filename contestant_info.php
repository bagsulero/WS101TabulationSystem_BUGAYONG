<?php
include 'db_connection.php';

$editCandidateId = '';
$editedNumber = '';
$editedName = '';
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

    // Process form submission for adding/editing/deleting contestants
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Process form submission for adding contestant
        if (isset($_POST['submit'])) {
            $contestantNumber = isset($_POST['edited_number']) ? $_POST['edited_number'] : '';
            $contestantName = isset($_POST['edited_name']) ? $_POST['edited_name'] : '';

            // Insert contestant data into the contestants table and connect it to the events table
            $insertContestantQuery = "INSERT INTO contestants (contestant_number, contestant_name, events_id) VALUES ('$contestantNumber', '$contestantName', '$eventId')";

            if ($conn->query($insertContestantQuery) !== TRUE) {
                die("Contestant insertion failed: " . $conn->error);
            }
        }

        // Process form submission for editing contestant
        if (isset($_POST['edit'])) {
            $editCandidateId = isset($_POST['edit_contestant_id']) ? $_POST['edit_contestant_id'] : '';
            $editedNumber = isset($_POST['edited_number']) ? $_POST['edited_number'] : '';
            $editedName = isset($_POST['edited_name']) ? $_POST['edited_name'] : '';

            // Update candidate data in the event-specific table
            $updateCandidateQuery = "UPDATE contestants SET contestant_number='$editedNumber', contestant_name='$editedName' WHERE contestant_id='$editCandidateId'";
            if ($conn->query($updateCandidateQuery) !== TRUE) {
                die("Contestant update failed: " . $conn->error);
            }
        }

        // Process form submission for deleting contestant
        if (isset($_POST['delete'])) {
            $deleteCandidateId = isset($_POST['delete_contestant_id']) ? $_POST['delete_contestant_id'] : '';

            // Delete contestant data from the event-specific table
            $deleteCandidateQuery = "DELETE FROM contestants WHERE contestant_id='$deleteCandidateId'";
            if ($conn->query($deleteCandidateQuery) !== TRUE) {
                die("Contestant deletion failed: " . $conn->error);
            }
        }
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
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contestant Information</title>
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
    <header>
        <button onclick="window.location.href='admin_homepage.php'">LIST OF EVENTS</button>
        <button onclick="window.location.href='contestant_info.php?id=<?php echo $eventId; ?>'">CONTESTANT OF THE EVENT</button>
        <button onclick="window.location.href='criteria_info.php?id=<?php echo $eventId; ?>'">CRITERIAS OF THE EVENT</button>
        <button onclick="window.location.href='judge_info.php?id=<?php echo $eventId; ?>'">JUDGES OF THE EVENT</button>
        <button onclick="window.location.href='view_score_sheets.php?id=<?php echo $eventId; ?>'">VIEW SCORE SHEETS</button>
    </header>
<div class="container">

    <h2><?php echo $eventTitle; ?></h2>

    <!-- Existing Form for Contestant -->
    <form method="post" action="contestant_info.php?id=<?php echo $eventId; ?>">
        <label>Contestant Number:</label>
        <input type="text" name="edited_number" value="<?php echo isset($editedNumber) ? htmlspecialchars($editedNumber) : ''; ?>" required>

        <label>Contestant Name:</label>
        <input type="text" name="edited_name" value="<?php echo isset($editedName) ? htmlspecialchars($editedName) : ''; ?>" required>

        <?php
        if ($editCandidateId) {
            echo "<input type='hidden' name='edit_contestant_id' value='{$editCandidateId}'>";
            echo "<button type='submit' name='edit' class='edit-button'>Edit Contestant</button>";
            echo "<button type='submit' name='cancel' class='delete-button'>Cancel</button>";
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
            echo "<form method='post' action='contestant_info.php?id={$eventId}'>";
            echo "<input type='hidden' name='edit_contestant_id' value='{$rowData['contestant_id']}'>";
            echo "<input type='hidden' name='edited_number' value='{$rowData['contestant_number']}'>";
            echo "<input type='hidden' name='edited_name' value='{$rowData['contestant_name']}'>";
            echo "<button type='submit' name='edit' class='edit-button'>Edit</button>";
            echo "</form>";
            echo "<form method='post' action='contestant_info.php?id={$eventId}'>";
            echo "<input type='hidden' name='delete_contestant_id' value='{$rowData['contestant_id']}' >";
            echo "<button type='submit' name='delete' class='delete-button' onclick=\"return confirm('Are you sure you want to delete this candidate?');\">Delete</button>";
            echo "</form>";
            echo "</td>";
            echo "</tr>";
        }
        ?>
    </table>
</div>

<!-- Footer -->
<footer>
    &copy; 2023 Events Tabulation System
</footer>

</body>
</html>
