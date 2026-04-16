<?php
require_once('includes/dbconn.php');
if (!empty($_POST['emailid'])) {
    $email = trim($_POST['emailid']);
    if (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
        echo "error : You did not enter a valid email.";
        exit;
    }
    $result = 'SELECT count(*) FROM userregistration WHERE email=?';
    $stmt = $mysqli->prepare($result);
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();
    if ($count > 0) {
        echo "<span style='color:red'> Email already exist! Try using new one.</span>";
    } else {
        echo "<span style='color:green'> Email available for registration!!</span>";
    }
}
