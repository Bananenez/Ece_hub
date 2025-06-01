<?php
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

include 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['post_id'])) {
    $username = $_SESSION['username'];
    $post_id = $_POST['post_id'];

    // Obtenir l'ID de l'utilisateur à partir du nom d'utilisateur
    $user_sql = "SELECT id FROM users WHERE username=?";
    $stmt = $conn->prepare($user_sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $user_result = $stmt->get_result();
    $user = $user_result->fetch_assoc();
    $user_id = $user['id'];
    $stmt->close();

    // Vérifier si l'utilisateur a déjà liké ce post
    $check_like_sql = "SELECT * FROM post_likes WHERE user_id=? AND post_id=?";
    $stmt = $conn->prepare($check_like_sql);
    $stmt->bind_param("ii", $user_id, $post_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $liked = false;

    if ($result->num_rows == 0) {
        // Recuperer le nom de la personne qui fait le post
        $nom_post_sql = "SELECT username FROM posts WHERE id=?";
        $stmt = $conn->prepare($nom_post_sql);
        $stmt->bind_param("i", $post_id);
        $stmt->execute();
        $nom_result = $stmt->get_result();
        $nom_r = $nom_result->fetch_assoc();
        $nom_auteur = $nom_r['username'];
        $stmt->close();
        
        // Envoyer la notification
        $notif_sql = "INSERT INTO notifications (receiver, sender, types, statut) VALUES (?, ?, 'like', 'pending')";
        $stmt = $conn->prepare($notif_sql);
        $stmt->bind_param("ss", $nom_auteur, $username);
        $stmt->execute();
        $stmt->close();

        // Ajouter un like
        $like_sql = "INSERT INTO post_likes (user_id, post_id) VALUES (?, ?)";
        $stmt = $conn->prepare($like_sql);
        $stmt->bind_param("ii", $user_id, $post_id);
        $stmt->execute();
        $stmt->close();

        // Mettre à jour le nombre de likes dans la table posts
        $update_likes_sql = "UPDATE posts SET likes = likes + 1 WHERE id = ?";
        $stmt = $conn->prepare($update_likes_sql);
        $stmt->bind_param("i", $post_id);
        $stmt->execute();
        $stmt->close();

        $liked = true;
    } else {
        // Retirer le like
        $unlike_sql = "DELETE FROM post_likes WHERE user_id=? AND post_id=?";
        $stmt = $conn->prepare($unlike_sql);
        $stmt->bind_param("ii", $user_id, $post_id);
        $stmt->execute();
        $stmt->close();

        // Mettre à jour le nombre de likes dans la table posts
        $update_likes_sql = "UPDATE posts SET likes = likes - 1 WHERE id = ?";
        $stmt = $conn->prepare($update_likes_sql);
        $stmt->bind_param("i", $post_id);
        $stmt->execute();
        $stmt->close();
    }

    // Obtenir le nouveau nombre de likes
    $get_likes_sql = "SELECT likes FROM posts WHERE id=?";
    $stmt = $conn->prepare($get_likes_sql);
    $stmt->bind_param("i", $post_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $post = $result->fetch_assoc();
    $likes_count = $post['likes'];
    $stmt->close();

    // Retourner la réponse en JSON
    header('Content-Type: application/json');
    echo json_encode([
        'liked' => $liked,
        'likes' => $likes_count
    ]);
    exit();
}

$conn->close();
?>
