<?php
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

include 'db.php';

$response = [];

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['content'])) {
    $username = $_SESSION['username'];
    $user_id_query = "SELECT id FROM users WHERE username = '$username'";
    $user_id_result = $conn->query($user_id_query);
    $user_id_row = $user_id_result->fetch_assoc();
    $user_id = $user_id_row['id'];
    $content = $_POST['content'];
    $location = isset($_POST['location']) ? $_POST['location'] : '';
    $datetime = isset($_POST['datetime']) && !empty($_POST['datetime']) ? $_POST['datetime'] : NULL;
    $feeling = isset($_POST['feeling']) ? $_POST['feeling'] : '';
    $visibility = $_POST['visibility'];
    $friends = isset($_POST['friends']) ? $_POST['friends'] : [];
    $media_path = '';

    // Créer le dossier de l'utilisateur s'il n'existe pas déjà
    $user_dir = "uploads/$user_id/posts";
    if (!is_dir($user_dir)) {
        mkdir($user_dir, 0777, true);
    }

    if (isset($_FILES['media']) && $_FILES['media']['error'] == 0) {
        $target_file = $user_dir . '/' . basename($_FILES["media"]["name"]);
        move_uploaded_file($_FILES["media"]["tmp_name"], $target_file);
        $media_path = $target_file;
    }

    if (is_null($datetime)) {
        $sql = "INSERT INTO posts (username, content, location, feeling, media_path, visibility, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssss", $username, $content, $location, $feeling, $media_path, $visibility);
    } else {
        $sql = "INSERT INTO posts (username, content, location, datetime, feeling, media_path, visibility, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssss", $username, $content, $location, $datetime, $feeling, $media_path, $visibility);
    }


    $friends_to_notify = [];
    if ($visibility == 'friends') {
        $friends_query = "SELECT CASE WHEN user1 = ? THEN user2 ELSE user1 END AS friend 
                          FROM friends WHERE user1 = ? OR user2 = ?";
        $friends_stmt = $conn->prepare($friends_query);
        $friends_stmt->bind_param("sss", $username, $username, $username);
        $friends_stmt->execute();
        $friends_result = $friends_stmt->get_result();
        $friends_stmt->close();
        while ($friend_row = $friends_result->fetch_assoc()) {
            $friends_to_notify[] = $friend_row['friend'];
        }
    } elseif ($visibility == 'selected_friends' && !empty($friends)) {
        $friends_to_notify = $friends;
    }
    elseif ($visibility == 'public') {
        $friends_of_friends_query = "   SELECT DISTINCT users.username
                                        FROM users 
                                        JOIN friends f1 ON users.username = f1.user1 OR users.username = f1.user2 
                                        WHERE ((f1.user1 IN (SELECT CASE WHEN user1= '$username' THEN user2 ELSE user1 END AS friend FROM friends WHERE user1='$username' OR user2='$username') 
                                        OR f1.user2 IN (SELECT CASE WHEN user1='$username' THEN user2 ELSE user1 END AS friend FROM friends WHERE user1='$username' OR user2='$username'))
                                        AND users.username != '$username'
                                        AND users.username NOT IN (SELECT CASE WHEN user1='$username' THEN user2 ELSE user1 END AS friend FROM friends WHERE user1='$username' OR user2='$username') OR (user1 = '$username' OR user2 = '$username')) AND users.username != '$username';
                                    ";
        $friends_of_friends_result = $conn->query($friends_of_friends_query);
        
        while ($friend_of_friend_row = $friends_of_friends_result->fetch_assoc()) {
            $friends_to_notify[] = $friend_of_friend_row['username'];
        }
    }
    $verif_partenaire_sql = "SELECT partenaire_ece FROM `users` WHERE username = ?";
    $verif_partenaire_stmt = $conn->prepare($verif_partenaire_sql);
    $verif_partenaire_stmt->bind_param("s", $username);
    $verif_partenaire_stmt->execute();
    $verif_partenaire_result = $verif_partenaire_stmt->get_result();

    if ($verif_partenaire_result->num_rows > 0) {
        
        $row = $verif_partenaire_result->fetch_assoc();
        
        if ($row['partenaire_ece'] == 1) {
            $all_friends_sql = "SELECT username FROM users WHERE username != '$username';";
            $all_friends_result = $conn->query($all_friends_sql);
            while ($friend_of_friend_row = $all_friends_result->fetch_assoc()) {
                $friends_to_notify[] = $friend_of_friend_row["username"];
            }
        } 
    }


    $notif_sql = "INSERT INTO notifications (receiver, sender, types, statut) VALUES (?, ?, 'new_post', 'pending')";
    $notif_stmt = $conn->prepare($notif_sql);

    foreach ($friends_to_notify as $friend) {
        $notif_stmt->bind_param("ss", $friend, $username);
        if (!$notif_stmt->execute()) {
            $response['status'] = 'error';
            $response['message'] = 'Notification insert failed: ' . $notif_stmt->error;
            echo json_encode($response);
            exit();
        }
    }

    if ($stmt->execute()) {
        $post_id = $stmt->insert_id;

        // Insert visibility for selected friends
        if ($visibility == 'selected_friends' && !empty($friends)) {
            foreach ($friends as $friend) {
                $visibility_sql = "INSERT INTO post_visibility (post_id, friend_username) VALUES (?, ?)";
                $visibility_stmt = $conn->prepare($visibility_sql);
                $visibility_stmt->bind_param("is", $post_id, $friend);
                $visibility_stmt->execute();
            }
        }

        $response['status'] = 'success';
        $response['post'] = [
            'id' => $post_id,
            'username' => $username,
            'content' => $content,
            'location' => $location,
            'datetime' => $datetime,
            'feeling' => $feeling,
            'media_path' => $media_path,
            'created_at' => date('Y-m-d H:i:s'),
            'visibility' => $visibility,
            'friends' => $friends
        ];

        // Obtenir l'image de profil de l'utilisateur
        $profile_sql = "SELECT profile_picture FROM users WHERE username = ?";
        $stmt = $conn->prepare($profile_sql);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $profile_result = $stmt->get_result();
        $profile_row = $profile_result->fetch_assoc();
        $response['post']['profile_picture'] = $profile_row['profile_picture'];
    } else {
        $response['status'] = 'error';
        $response['message'] = $stmt->error;
    }

    $stmt->close();
}

$conn->close();
echo json_encode($response);
?>
