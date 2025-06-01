<?php
session_start();
include 'db.php';

$current_user = $_SESSION['username'];

// Correction 1 : pas d'espace en trop dans 'pending'
$sql = "SELECT n.*, u.profile_picture 
        FROM notifications n
        JOIN users u ON n.sender = u.username
        WHERE n.receiver = '$current_user' AND n.statut = 'pending'
        ORDER BY n.id DESC";

$result = $conn->query($sql);

$notifications = [];
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $notifications[] = $row;
    }
}

header('Content-Type: application/json');
echo json_encode($notifications);
?>
