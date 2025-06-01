<?php
session_start();
include 'db.php';

if (!isset($_SESSION['username'])) {
    echo json_encode(['status' => 'error', 'message' => 'Non connecté']);
    exit();
}

$current_user = $_SESSION['username'];
$group_id = $_POST['group_id'];
$message = $_POST['message'];

// Enregistrement du message dans la base de
$message = $conn->real_escape_string($message);
$sql = "INSERT INTO group_messages (group_id, sender, message, timestamp) VALUES ('$group_id', '$current_user', '$message', NOW())";
if ($conn->query($sql) === TRUE) {
    // Récupérer l'image de profil de l'utilisateur
    $profile_sql = "SELECT profile_picture FROM users WHERE username='$current_user'";
    $profile_result = $conn->query($profile_sql);
    $profile_picture = 'default.jpg'; // Valeur par défaut
    if ($profile_result->num_rows > 0) {
        $row = $profile_result->fetch_assoc();
        $profile_picture = $row['profile_picture'] ?? 'default.jpg';
    }
    echo json_encode([
        'status' => 'success',
        'sender' => $current_user,
        'message' => htmlspecialchars($message),
        'timestamp' => date('Y-m-d H:i:s'),
        'profile_picture' => htmlspecialchars($profile_picture)
    ]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Erreur lors de l\'enregistrement du message']);
}

$conn->close();
?>
