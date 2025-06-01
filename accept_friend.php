<?php
session_start();

if (!isset($_SESSION['username'])) {
    echo "error";
    exit();
}

include 'db.php';

$request_id = $_POST['request_id'];
$current_user = $_SESSION['username'];

// Accepter la demande d'ami
$sql = "UPDATE notifications SET statut='accepted' WHERE id='$request_id' AND receiver='$current_user' AND types = 'friend_request'";

if ($conn->query($sql) === TRUE) {
    // Ajouter les utilisateurs à la table des amis
    $friend_request_sql = "SELECT * FROM notifications WHERE id='$request_id'";
    $friend_request_result = $conn->query($friend_request_sql);
    $friend_request = $friend_request_result->fetch_assoc();

    $user1 = $friend_request['sender'];
    $user2 = $friend_request['receiver'];

    $add_friend_sql = "INSERT INTO friends (user1, user2) VALUES ('$user1', '$user2')";
    $conn->query($add_friend_sql);

    echo "success";
} else {
    echo "error";
}

$conn->close();
?>
