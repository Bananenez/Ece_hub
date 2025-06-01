<?php
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

include 'db.php';

$current_user = $_SESSION['username'];

if (isset($_GET['group'])) {
    $group_id = $_GET['group'];
    $sql = "SELECT group_messages.*, users.profile_picture 
            FROM group_messages 
            JOIN users ON group_messages.sender = users.username 
            WHERE group_messages.group_id='$group_id' 
            ORDER BY timestamp ASC";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            echo "<div class='message'>";
            echo "<p>";
            echo "<strong>";
            echo "<a href='user_profile.php?username=" . htmlspecialchars($row['sender']) . "'>";
            echo "<img src='" . htmlspecialchars($row['profile_picture'] ?? 'default.jpg') . "' alt='Profile Picture' style='width: 20px; height: 20px; border-radius: 50%; margin-right: 5px;'>";
            echo "</a>";
            echo htmlspecialchars($row['sender']);
            echo "</strong> (" . $row['timestamp'] . "): ";
            echo htmlspecialchars($row['message']);
            echo "</p>";
            echo "</div>";
        }
    } else {
        echo "<p>Aucun message dans ce groupe.</p>";
    }
}
?>
