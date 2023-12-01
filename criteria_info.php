<?php
// Include the database connection file
include 'db_connection.php';

// Initialize variables
$editCriteriaId = '';
$editedCriteriaName = '';
$editedCriteriaWeight = '';

if (isset($_GET['id'])) {
    $eventId = $_GET['id'];

    // Retrieve event title using the provided event ID
    $result = $conn->query("SELECT * FROM events WHERE events_id = $eventId");
    if ($result === false) {
        die("Query failed: " . $conn->error);
    }

    $row = $result->fetch_assoc();
    $eventTitle = $row['title'];

    // Process form submission (Add Criteria)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
        $editedCriteria = isset($_POST['edited_criteria']) ? $_POST['edited_criteria'] : '';
        $editedPercentage = isset($_POST['edited_percentage']) ? $_POST['edited_percentage'] : '';

        // Add additional information to the criteria table
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
        header("Location: criteria_info.php?id=" . $eventId);
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
        header("Location: criteria_info.php?id=" . $eventId);
        exit();
    }

    // Retrieve updated criteria data
    $selectCriteriaQuery = "SELECT criteria_id, criteria_name, percentage FROM criteria WHERE events_id = $eventId";
    $criteriaResult = $conn->query($selectCriteriaQuery);
    if ($criteriaResult === false) {
        die("Criteria retrieval failed: " . $conn->error);
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Criteria Information</title>
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
    <h2>Criteria Information</h2>

    <!-- Existing Form for Criteria -->
    <form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>?id=<?php echo $eventId; ?>">
        <label>Criteria:</label>
        <input type="text" name="edited_criteria" value="<?php echo isset($editedCriteria) ? htmlspecialchars($editedCriteria) : ''; ?>" required>

        <label>Percentage:</label>
        <input type="number" name="edited_percentage" min="1" max="100" value="<?php echo isset($editedPercentage) ? htmlspecialchars($editedPercentage) : ''; ?>" required>

        <?php
        if ($editCriteriaId) {
            echo "<input type='hidden' name='edit_criteria_id' value='{$editCriteriaId}'>";
            echo "<button type='submit' name='edit_criteria' class='edit-button'>Edit Criteria</button>";
            echo "<button type='submit' name='cancel' class='delete-button'>Cancel</button>";
        } else {
            echo "<input type='submit' name='submit' value='Add Criteria'>";
        }
        ?>
    </form>

    <!-- Table Display for Criteria Information -->
    <h3>EVENT CRITERIA:</h3>
    <table>
        <tr>
            <th>Criteria Name</th>
            <th>Percentage</th>
            <th>Actions</th> <!-- Add Actions column -->
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
            <!-- Edit and Delete Forms -->
            <form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>?id=<?php echo $eventId; ?>">
                <input type="hidden" name="edit_criteria_id" value="<?php echo $criteriaRow['criteria_id']; ?>">
                <input type="hidden" name="edited_criteria" value="<?php echo $criteriaRow['criteria_name']; ?>">
                <input type="hidden" name="edited_percentage" value="<?php echo $criteriaRow['percentage']; ?>">
                <button type="submit" name="edit_criteria" class='edit-button'>Edit</button>
            </form>

            <form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>?id=<?php echo $eventId; ?>">
                <input type="hidden" name="delete_criteria_id" value="<?php echo $criteriaRow['criteria_id']; ?>">
                <button type="submit" name="delete_criteria" class='delete-button' onclick="return confirm('Are you sure you want to delete this criteria?');">Delete</button>
            </form>
            <?php
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
