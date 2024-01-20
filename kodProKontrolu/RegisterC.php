<?php
session_start();
$email = $_POST['email'];
$password = $_POST['password'];
$password1 = $_POST['password1'];
$conn = new mysqli("localhost", "root", "", "register");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
} else {
    $check_email_stmt = $conn->prepare("SELECT * FROM register WHERE email = ?");
    $check_email_stmt->bind_param("s", $email);
    $check_email_stmt->execute();
    $result = $check_email_stmt->get_result();

    if ($result->num_rows > 0) {
        echo "E-mail již existuje. Zvolte prosím jiný e-mail.";
    } else {
        if ($password === $password1) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $insert_stmt = $conn->prepare("INSERT INTO register (email, password) VALUES (?, ?)");
            $insert_stmt->bind_param("ss", $email, $hashed_password);
            $insert_stmt->execute();
            $myfile = fopen("Login.php", "r");
            header("Location: Login.php");
            return;
        } else {
            echo "Invalid password...";
        }
    }
}
?>