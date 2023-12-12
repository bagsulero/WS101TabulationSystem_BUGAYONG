<?php
include 'db_connection.php';

function saveEvent($title, $id = null) {
    global $conn;

    if ($id === null) {
        $sql = "INSERT INTO events (title) VALUES ('$title')";
    } else {
        // Fetch the current title before updating
        $result = $conn->query("SELECT title FROM events WHERE events_id = $id");
        if ($result === false) {
            die("Query failed: " . $conn->error);
        }

        $row = $result->fetch_assoc();
        $currentTitle = $row['title'];

        // Update the event title
        $sql = "UPDATE events SET title='$title' WHERE events_id=$id";
    }

    if ($conn->query($sql) === TRUE) {
        return true;
    } else {
        die("Event creation/update failed: " . $conn->error);
    }
}

function deleteEvent($id) {
    global $conn;

    $id = (int)$id; // Ensure it's an integer

    // Delete the event from the events table
    $deleteEventQuery = "DELETE FROM events WHERE events_id = $id";
    if ($conn->query($deleteEventQuery) === TRUE) {
        return true;
    } else {
        die("Event deletion failed: " . $conn->error);
    }
}

$isEdit = isset($_GET['action']) && $_GET['action'] === 'edit';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create'])) {
        $title = $_POST['title'];

        saveEvent($title);
    } elseif (isset($_POST['edit'])) {
        $id = isset($_POST['edit_id']) ? $_POST['edit_id'] : null;
        $title = $_POST['title'];

        saveEvent($title, $id);
    } elseif (isset($_POST['delete'])) {
        $id = isset($_POST['delete_id']) ? $_POST['delete_id'] : null;

        deleteEvent($id);
        header("Location: admin_homepage.php");
        exit();
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Events Tabulation System</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
            background-color: rgba(255, 255, 255, 0.8); 
            box-shadow: 0 0 20px 20px rgba(0, 0, 0, 0.1);
            background-image: url('images/bgfooter.jpg');
            background-size: 100%;
            background-position: center;
            background-repeat: no-repeat;          
        }

        header {
    background-image: url('images/PSU-LABEL-2.png');
    background-size:30%;
    background-position: 15% center;
    background-repeat: no-repeat;
    background-color: #0A2647;
    color: #fff;
    padding: 20px;
    text-align: center;
        }
        

        section {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            background-color: #fff;
            box-shadow: 0 0 20px 20px rgba(0, 0, 0, 0.1);
        }

        footer {
            background-color: #0A2647;
            color: #fff;
            padding: 30px;
            text-align: center;
            margin-top: 50px;
        }

        header h1 {
            font-family: 'Arial', sans-serif;
            color:#0A2647;
            text-align: right;
        }

        h2 {
            color: black;
            text-align: center;
            font-family: 'Arial', sans-serif;
        }

        form {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #333;
        }

        input[type="text"] {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            box-sizing: border-box;
        }

        input[type="submit"],
        .edit-button,
        .delete-button {
            display: inline-block;
            margin: 5px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-align: center;
            text-decoration: none;
            color: white;
            width: 90px; 
            padding: 10px; 
        }

        input[type="submit"] {
            background-color: blue;
        }

        input[type="submit"]:hover {
            background-color: #45a049;
        }

        .edit-button {
            background-color: #0D3B66;
        }

        .edit-button:hover {
            background-color: red;
        }

        .delete-button {
            background-color: red;
        }

        .delete-button:hover {
            background-color: #400000;
        }

        

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th, td {
            border: 1px solid #ddd;
            padding: 12px;
            text-align: center;
            
        }

        th {
            background-color: #064789;
            color: white;
        }

        a {
            text-decoration: none;
            color: black;
        }

        a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
<header>
        <h1>Events Tabulation System</h1>
    </header>
   
    <section><form method="post" action="admin_homepage.php?action=<?php echo $isEdit ? 'edit' : 'create'; ?>&id=<?php echo isset($_GET['id']) ? $_GET['id'] : ''; ?>">

    <h2>Events Tabulation System</h2>
        <?php
        if ($isEdit) {
            $editId = $_GET['id'];
            $result = $conn->query("SELECT * FROM events WHERE events_id = $editId");
            if ($result === false) {
                die("Query failed: " . $conn->error);
            }

            $row = $result->fetch_assoc();
            $editTitle = $row['title'];

            echo "<input type='hidden' name='edit_id' value='$editId'>";
            echo "<label>Edit Title:</label>";
            echo "<input type='text' name='title' value='$editTitle' required><br>";
        } else {
            echo "<label>Add Title:</label>";
            echo "<input type='text' name='title' required><br>";
        }

        echo "<button type='submit' name='edit' class='edit-button' >" . ($isEdit ? 'Edit' : 'Add') . " Event</button>";
        ?>
        <?php if ($isEdit): ?>
            <a href="admin_homepage.php"><button type="button" class='delete-button'>Cancel</button></a>
        <?php endif; ?>
    </form>

    <h2>Events</h2>
    <table border="1">
    <tr>
        <th>Title</th>
        <th>Action</th>
    </tr>

    <?php
    $result = $conn->query("SELECT events_id, title FROM events");
    if ($result === false) {
        die("Query failed: " . $conn->error);
    }

    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td><a href='contestant_info.php?id={$row['events_id']}'>{$row['title']}</a></td>";
        echo "<td>";

        // Edit button
        echo "<form method='get' action='admin_homepage.php' style='display: inline; margin-right: 5px;'>";
        echo "<input type='hidden' name='id' value='{$row['events_id']}'>";
        echo "<button type='submit' name='action' value='edit' class='edit-button'>Edit</button>";
        echo "</form>";

        // Delete button
        echo "<form method='post' action='admin_homepage.php'  style='display: inline;' onsubmit=\"return confirm('Are you sure you want to delete this event?');\">";
        echo "<input type='hidden' name='delete_id' value='{$row['events_id']}'>";
        echo "<button type='submit' name='delete' value='delete' class='delete-button'>Delete</button>";
        echo "</form>";

        echo "</td>";
        echo "</tr>";
    }
    ?>
</table>
    </section>

<br>  
 <footer>
    © 2023 Events Tabulation System. All rights reserved.
</footer>
</body>
</html>
