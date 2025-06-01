<?php
session_start();

if (!isset($_SESSION['username'])) {
    die("Non autorisé");
}

include 'db.php';


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['recipient']) && isset($_POST['message'])) {
    $sender = $_SESSION['username'];
    $recipient = $_POST['recipient'];
    $message = $_POST['message'];
    $timestamp = date("Y-m-d H:i:s");

    $sql = "SELECT * FROM friends WHERE (user1='$sender' AND user2='$recipient') OR (user1='$recipient' AND user2='$sender')";
    $result = $conn->query($sql);
    if ($result->num_rows == 0) {
        die("Vous ne pouvez envoyer de messages qu'à vos amis.");
    }

    
    $message = $conn->real_escape_string($message);
    $sql = "INSERT INTO messages (sender, recipient, message, timestamp) 
            VALUES ('$sender', '$recipient', '$message', '$timestamp')";

    if ($conn->query($sql) === TRUE) {
        // Récupérer la photo de profil de l'expéditeur
        $sql = "SELECT profile_picture FROM users WHERE username='$sender'";
        $result = $conn->query($sql);
        $row = $result->fetch_assoc();
        $profile_picture = $row['profile_picture'] ?? 'default.jpg';

        echo json_encode([
            'message' => $message,
            'timestamp' => $timestamp,
            'sender' => $sender,
            'profile_picture' => $profile_picture
        ]);
    } else {
        echo json_encode(['error' => "Erreur: " . $sql . "<br>" . $conn->error]);
    }
}

$conn->close();
?>
