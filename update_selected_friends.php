<?php
session_start();

if (!isset($_SESSION['username'])) {
    echo "Unauthorized";
    exit();
}

include 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['post_id']) && isset($_POST['friends'])) {
    $post_id = $_POST['post_id'];
    $friends = $_POST['friends'];
    $current_user = $_SESSION['username'];

    // Ensure the post belongs to the current user
    $check_sql = "SELECT * FROM posts WHERE id = ? AND username = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("is", $post_id, $current_user);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows == 1) {
        // Delete existing visibility settings for the post
        $delete_sql = "DELETE FROM post_visibility WHERE post_id = ?";
        $delete_stmt = $conn->prepare($delete_sql);
        $delete_stmt->bind_param("i", $post_id);
        $delete_stmt->execute();

        // Insert new visibility settings for the post
        $insert_sql = "INSERT INTO post_visibility (post_id, friend_username) VALUES (?, ?)";
        $insert_stmt = $conn->prepare($insert_sql);
        foreach ($friends as $friend) {
            $insert_stmt->bind_param("is", $post_id, $friend);
            $insert_stmt->execute();
        }

        echo "success";
    } else {
        echo "Unauthorized";
    }

    $check_stmt->close();
}

$conn->close();
?>
