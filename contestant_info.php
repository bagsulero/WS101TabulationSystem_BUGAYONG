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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .container {
            max-width: 600px;
            width: 100%;
            margin:  auto;
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
    background-position: left;
    background-repeat: no-repeat;
            
        }

        header {
    position: fixed;
    top: 0;
    width: 100%;
    background-image: url('images/PSU-LABEL-2.png');
    background-size:25%;
    background-position: left; 
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
            /* ... (existing styles) ... */
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

        @keyframes buttonAnimation {
            from {
                transform: translateY(0);
            }
            to {
                transform: translateY(10px);
            }
        }

        @keyframes buttonHoverAnimation {
            to {
                transform: scale(1.2);
            }
        }
        .container {
            display: flex;
            margin-top: 80px; /* Adjust as needed to avoid overlapping with the header */
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

        th, td {
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
            <i class="fas fa-user"></i> <!-- FontAwesome user icon -->
            &nbsp&nbsp CONTESTANT OF THE EVENT
        </button>

        <button onclick="window.location.href='criteria_info.php?id=<?php echo $eventId; ?>'">
            <i class="fas fa-trophy"></i> <!-- FontAwesome trophy icon -->
            &nbsp&nbsp&nbspCRITERIAS OF THE EVENT
        </button>

        <button onclick="window.location.href='judge_info.php?id=<?php echo $eventId; ?>'">
            <i class="fas fa-gavel"></i> <!-- FontAwesome gavel icon -->
            &nbsp&nbsp&nbspJUDGES OF THE EVENT
        </button>

        <button onclick="window.location.href='view_score_sheets.php?id=<?php echo $eventId; ?>'">
    <i class="fas fa-chart-bar"></i>
    &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;VIEW SCORE
</button>
    </sidebar>



<div class="container">
<br><br>  <br><br>  
<main>


    <!-- Existing Form for Contestant -->
    <section><form method="post" action="contestant_info.php?id=<?php echo $eventId; ?>">
    <h2><?php echo $eventTitle; ?></h2>
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

        // Edit button
        echo "<button type='submit' name='edit' class='edit-button'>Edit</button>";

        // Delete button
        echo "<button type='submit' name='delete' class='delete-button' onclick=\"return confirm('Are you sure you want to delete this candidate?');\">Delete</button>";
        echo "<input type='hidden' name='delete_contestant_id' value='{$rowData['contestant_id']}' >";
        echo "</form>";
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
