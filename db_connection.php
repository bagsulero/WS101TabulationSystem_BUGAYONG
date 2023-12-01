<?php
$host = "localhost";
$username = "root";
$password = "";
$database = "events";

$conn = new mysqli($host, $username, $password);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$createDatabaseQuery = "CREATE DATABASE IF NOT EXISTS $database";
if ($conn->query($createDatabaseQuery) !== TRUE) {
    die("Database creation failed: " . $conn->error);
}

$conn->select_db($database);

$createTableEventsQuery = "CREATE TABLE IF NOT EXISTS events (
    events_id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL
)";
if ($conn->query($createTableEventsQuery) !== TRUE) {
    die("Events table creation failed: " . $conn->error);
}

$createTableContestantsQuery = "CREATE TABLE IF NOT EXISTS contestants (
    contestant_id INT AUTO_INCREMENT PRIMARY KEY,
    contestant_number INT,
    contestant_name VARCHAR(255),
    events_id INT,
    FOREIGN KEY (events_id) REFERENCES events(events_id)
)";
if ($conn->query($createTableContestantsQuery) !== TRUE) {
    die("Contestants table creation failed: " . $conn->error);
}

$createTableCriteriaQuery = "CREATE TABLE IF NOT EXISTS criteria (
    criteria_id INT AUTO_INCREMENT PRIMARY KEY,
    criteria_name VARCHAR(255) NOT NULL,
    percentage INT,
    events_id INT,
    FOREIGN KEY (events_id) REFERENCES events(events_id)
)";
if ($conn->query($createTableCriteriaQuery) !== TRUE) {
    die("Criteria table creation failed: " . $conn->error);
}

// Add Judges table
$createTableJudgesQuery = "CREATE TABLE IF NOT EXISTS judges (
    judge_id INT AUTO_INCREMENT PRIMARY KEY,
    judge_name VARCHAR(255),
    events_id INT,
    FOREIGN KEY (events_id) REFERENCES events(events_id)
)";
if ($conn->query($createTableJudgesQuery) !== TRUE) {
    die("Judges table creation failed: " . $conn->error);
}

// Add user table
$createTableUserQuery = "CREATE TABLE IF NOT EXISTS user (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50),
    password VARCHAR(50)
)";
if ($conn->query($createTableUserQuery) !== TRUE) {
    die("User table creation failed: " . $conn->error);
}

// Add scores table
$createTableScoresQuery = "CREATE TABLE IF NOT EXISTS scores (
    score_id INT AUTO_INCREMENT PRIMARY KEY,
    score INT,
    events_id INT,
    contestant_id INT,
    criteria_id INT,
    judge_id INT,
    FOREIGN KEY (events_id) REFERENCES events(events_id),
    FOREIGN KEY (contestant_id) REFERENCES contestants(contestant_id),
    FOREIGN KEY (criteria_id) REFERENCES criteria(criteria_id),
    FOREIGN KEY (judge_id) REFERENCES judges(judge_id)

)";
if ($conn->query($createTableScoresQuery) !== TRUE) {
    die("Scores table creation failed: " . $conn->error);
}
?>
