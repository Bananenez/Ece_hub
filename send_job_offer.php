<?php
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

include 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $sender_username = $_SESSION['username'];
    $receiver_username = $_POST['receiver_username'];
    $post_id = $_POST['post_id'];
    $offer_type = $_POST['offer_type'];

    // Insertion de l'offre d'emploi dans la base de données
    $sql = "INSERT INTO job_offers (sender_username, receiver_username, post_id, offer_type, created_at) 
            VALUES (?, ?, ?, ?, NOW())";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssis", $sender_username, $receiver_username, $post_id, $offer_type);

    $notif_sql = "INSERT INTO notifications (receiver, sender, types, statut) VALUES (?, ?, 'offre_emplois', 'pending')";
    $notif = $conn->prepare($notif_sql);
    $notif->bind_param("ss", $receiver_username, $sender_username);
    $notif->execute();

    if ($stmt->execute()) {
        echo "Offre d'emploi envoyée avec succès.";
    } else {
        echo "Erreur: " . $stmt->error;
    }

    $stmt->close();
}

$conn->close();
header("Location: fil_d_actualite.php");
exit();
?>
