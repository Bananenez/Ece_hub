<?php
session_start();

if (!isset($_SESSION['username'])) {
    echo "Unauthorized";
    exit();
}

include 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['post_id']) && isset($_POST['visibility'])) {
    $post_id = $_POST['post_id'];
    $visibility = $_POST['visibility'];
    $current_user = $_SESSION['username'];

    // Ensure the post belongs to the current user
    $check_sql = "SELECT * FROM posts WHERE id = ? AND username = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("is", $post_id, $current_user);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows == 1) {
        $update_sql = "UPDATE posts SET visibility = ? WHERE id = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("si", $visibility, $post_id);

        if ($update_stmt->execute()) {
            echo "success";
        } else {
            echo "error";
        }
    } else {
        echo "Unauthorized";
    }

    $check_stmt->close();
}

$conn->close();
?>
