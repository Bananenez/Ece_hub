<?php
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

include 'db.php';


if (isset($_GET['id'])) {
    $post_id = $_GET['id'];
    $sql = "DELETE FROM posts WHERE id='$post_id' AND username='" . $_SESSION['username'] . "'";

    if ($conn->query($sql) === TRUE) {
        echo "Publication supprimée avec succès!";
    } else {
        echo "Erreur: " . $sql . "<br>" . $conn->error;
    }
}

$conn->close();
header("Location: index.php");
exit();
?>
