<?php
session_start();

$email = $_POST['email'];
$password = $_POST['password'];

$con = new mysqli("localhost", "root", "", "register");
if ($con->connect_error) {
    die("Connection failed: " . $con->connect_error);
} else {

    $stmt = $con->prepare("select * from register where email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt_result = $stmt->get_result();

    if ($stmt_result->num_rows > 0) {


        $user = $stmt_result->fetch_all()[0];

        if (password_verify($password, $user[2])) {

            $_SESSION["email"] = $user[1];
            header("Location: client.php");
            return;
        } else {
            header("Location: Login.php");
            return;

        }
    } else {

        echo "Invalid email...";

    }
}
?>
