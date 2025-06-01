<?php
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

include 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['comment'])) {
    $post_id = $_POST['post_id'];
    $username = $_SESSION['username'];
    $comment = $_POST['comment'];

    //Recuperer le nom de la personne qui fait le post
    $nom_post_sql = "SELECT username FROM posts WHERE id = $post_id;";
    $nom_result = $conn->query($nom_post_sql);
    $nom_r = $nom_result->fetch_assoc();
    $nom_auteur = $nom_r['username'];

    //Envoyer la notification
    $notif_sql = "INSERT INTO notifications (receiver, sender, types, statut) VALUES ('$nom_auteur', '$username', 'comment', 'pending')";
    $conn->query($notif_sql);

    $sql = "INSERT INTO comments (post_id, username, comment) VALUES ('$post_id', '$username', '$comment')";

    if ($conn->query($sql) === TRUE) {
        $user_sql = "SELECT profile_picture FROM users WHERE username='$username'";
        $user_result = $conn->query($user_sql);
        $user = $user_result->fetch_assoc();

        $response = [
            'username' => $username,
            'comment' => $comment,
            'profile_picture' => $user['profile_picture'] ?? 'default.jpg'
        ];

        echo json_encode($response);
    } else {
        echo json_encode(['error' => $conn->error]);
    }
}

$conn->close();
?>
