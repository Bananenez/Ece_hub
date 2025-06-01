<?php
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

include 'db.php';


if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['search'])) {
    $search = $conn->real_escape_string($_POST['search']);
    $sql = "SELECT * FROM users WHERE username LIKE '%$search%' AND username != '{$_SESSION['username']}'";
    $result = $conn->query($sql);
} elseif ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_friend'])) {
    $receiver = $conn->real_escape_string($_POST['receiver']);
    $sender = $_SESSION['username'];
    $sql = "INSERT INTO notifications (sender, receiver, statut, types) VALUES ('$sender', '$receiver', 'pending','friend_request')";
    $conn->query($sql);
    header("Location: search.php");
    exit();
}

?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rechercher des Amis - ECE In</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="wrapper">
        <div class="header">
            <h1>ECE In: Social Media Professionnel de l’ECE Paris</h1>
            <img src="https://via.placeholder.com/150" alt="ECE Paris Logo">
        </div>

        <div class="navigation">
            <a href="index.php">Accueil</a>
            <a href="mon_reseau.php">Mon Réseau</a>
            <a href="fil_d_actualite.php">Fil d'actualité</a>
            <a href="profile.php">Vous</a>
            <a href="notifications.php">Notifications</a>
            <a href="messagerie.php">Messagerie</a>
            <a href="emplois.php">Emplois</a>
            <a href="logout.php">Déconnexion</a>
        </div>

        <div class="section">
            <h2>Rechercher des Amis</h2>
            <form method="POST" action="search.php">
                <input type="text" name="search" placeholder="Rechercher un utilisateur" required>
                <button type="submit">Rechercher</button>
            </form>

            <?php
            if (isset($result) && $result->num_rows > 0) {
                echo "<h3>Résultats de la recherche :</h3>";
                while ($row = $result->fetch_assoc()) {
                    echo "<p>" . htmlspecialchars($row['username']) . 
                         " <form method='POST' style='display:inline;'>
                            <input type='hidden' name='receiver' value='" . htmlspecialchars($row['username']) . "'>
                            <button type='submit' name='add_friend'>Ajouter</button>
                          </form></p>";
                }
            }
            ?>

        </div>

        <div class="footer">
            <p>&copy; 2024 ECE In. Tous droits réservés.</p>
        </div>
    </div>
</body>
</html>
