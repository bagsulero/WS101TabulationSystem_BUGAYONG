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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <title>Criteria Information</title>
    <style>
           .container {
            max-width: 600px;
            width: 100%;
            margin:  auto; /* Center horizontally */
            padding-bottom: 50px;
            padding-top: 50px;
        }
       
        body {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            margin: 0;
            padding: 0;
            font-family: 'Arial', sans-serif;
 
    box-shadow: 0 0 20px 20px rgba(0, 0, 0, 0.1);
    background-image: url('images/bgfooter.jpg');
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
            
        }

        header {
    position: fixed;
    top: 0;
    width: 100%;
    background-image: url('images/PSU-LABEL-2.png');
    background-size:25%; /* Adjust the percentage value to resize the image */
    background-position: left; /* Adjust as needed */
    background-repeat: no-repeat;
    background-color: #0A2647;
    color: white;
    padding: 20px;
    display: flex;
    justify-content: center;
    align-items: center;
}
        header h1 {
            font-family: 'Arial', sans-serif;
            color: white;
            text-align: center;
        }

        sidebar {
            width: 290px;
            background-color: #0A2647;
            color: white;
            padding: 60px;
            box-sizing: border-box;
            position: fixed;
            top: 118px;
            bottom: 0;
        }

        sidebar button {
            display: flex;
            align-items: center;
            margin-bottom: 30px;
            justify-content: space-around;
            background-color: #686868;
            font-family: 'Comic Sans MS', sans-serif;
            border: none;
            border-radius: 15px;
            cursor: pointer;
            text-align: center;
            text-decoration: none;
            color: white;
            padding: 10px;
        }

        sidebar button i {
            font-size: 30px;
        }

        sidebar button:hover {
            background-color: red;
            animation: buttonHoverAnimation 0.5s forwards;
        }

        @keyframes buttonHoverAnimation {
            to {
                transform: scale(1.2);
            }
        }

        .container {
            display: flex;
            margin-top: 80px;
        }

        main {
            flex: 1;
            padding: 20px;
        }

        footer {
            background-color: #333;
            color: white;
            text-align: center;
            padding: 10px;
            font-family: 'Comic Sans MS', sans-serif;
            position: fixed;
            bottom: 0;
            width: 100%;
        }

        form {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #333;
        }

        input[type="text"],
        input[type="number"] {
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
            border-radius: 10px;
            cursor: pointer;
            text-align: center;
            text-decoration: none;
            color: white;
        }

        input[type="submit"] {
            background-color: #0D3B66;
        }

        input[type="submit"]:hover {
            background-color: #2980b9;
        }

        .edit-button {
            background-color: #0D3B66;
        }

        .edit-button:hover {
            background-color: #2980b9;
        }

        .delete-button {
            background-color: #0D3B66;
        }

        .delete-button:hover {
            background-color: #c0392b;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th,
        td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background-color: #064789;
            color: white;
        }

        td {
            background-color: #d9d9d9;
        }

     
        section {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            background-color: #fff;
            box-shadow: 0 0 20px 20px rgba(0, 0, 0, 0.1);
            width: 200%; /* Change the width as per your requirement */
        }
      
    </style>
</head>
<body>
<header>
        <h1>Events Tabulation System</h1>
    </header>

    <sidebar>
        <button onclick="window.location.href='admin_homepage.php'">
            <i class="fas fa-tasks"></i>
            &nbsp;&nbsp;&nbsp;LIST OF EVENTS
        </button>

        <button onclick="window.location.href='contestant_info.php?id=<?php echo $eventId; ?>'">
            <i class="fas fa-user"></i>
            &nbsp&nbsp CONTESTANT OF THE EVENT
        </button>

        <button onclick="window.location.href='criteria_info.php?id=<?php echo $eventId; ?>'">
            <i class="fas fa-trophy"></i>
            &nbsp&nbsp&nbspCRITERIAS OF THE EVENT
        </button>

        <button onclick="window.location.href='judge_info.php?id=<?php echo $eventId; ?>'">
            <i class="fas fa-gavel"></i>
            &nbsp&nbsp&nbspJUDGES OF THE EVENT
        </button>

        <button onclick="window.location.href='view_score_sheets.php?id=<?php echo $eventId; ?>'">
            <i class="fas fa-chart-bar"></i>
            &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;VIEW SCORE
        </button>
    </sidebar>

    <div class="container">
        <br><br> <br><br> 
        <main>

    <!-- Existing Form for Criteria -->
    <section><form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>?id=<?php echo $eventId; ?>">
    <h2><?php echo $eventTitle; ?></h2>
    <h2>Criteria Information</h2>
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
</main>
    </section>


</body>
</html>
