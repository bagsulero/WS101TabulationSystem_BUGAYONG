<?php
session_start();
include "db_connection.php";

if (isset($_POST['uname']) && isset($_POST['password'])) {
    function validate($data) {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data);
        return $data;
    }

    $uname = validate($_POST['uname']);
    $pass = validate($_POST['password']);

    if (empty($uname)) {
        header("Location: login_form.php?error=User Name is required");
        exit();
    } else if (empty($pass)) {
        header("Location: login_form.php?error=Password is required");
        exit();
    } else {
        $sql = "SELECT * FROM user WHERE username='$uname' AND password='$pass'";

        $result = mysqli_query($conn, $sql);

        if (mysqli_num_rows($result) === 1) {
            $row = mysqli_fetch_assoc($result);

            // Store user details in session
            $_SESSION['username'] = $row['username'];
            $_SESSION['id'] = $row['id'];

            // Check if the user is a judge and get judge_id
            if ($row['is_judge']) {
                $_SESSION['judge_id'] = $row['judge_id'];
            }

            header("Location: judge_homepage.php");
            exit();
        } else {
            header("Location: login_form.php?error=Incorrect User name or password");
            exit();
        }
    }
} else {
    header("Location: login_form.php");
    exit();
}
?>
