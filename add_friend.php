<?php
session_start();

if (!isset($_SESSION['username'])) {
    echo "error";
    exit();
}

include 'db.php';

$sender = $_SESSION['username'];
$receiver = $_POST['receiver'];

// Vérifier si une demande d'ami existe déjà
$friend_request_sql = "SELECT * FROM notifications WHERE (sender='$sender' AND receiver='$receiver') OR (sender='$receiver' AND receiver='$sender')";
$friend_request_result = $conn->query($friend_request_sql);

if ($friend_request_result->num_rows == 0) {
    // Ajouter une demande d'ami
    $sql = "INSERT INTO notifications (sender, receiver, statut, types) VALUES ('$sender', '$receiver', 'pending', 'friend_request')";

    if ($conn->query($sql) === TRUE) {
        echo "success";
    } else {
        echo "error";
    }
} else {
    echo "exists";
}

$conn->close();
?>
