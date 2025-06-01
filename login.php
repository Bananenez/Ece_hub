<?php
session_start();

include 'db.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $sql = "SELECT * FROM users WHERE username=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if (password_verify($password, $row['password'])) {
            $_SESSION['username'] = $username;
            if ($row['is_admin']) {
                header("Location: admin_dashboard.php"); // Rediriger vers une page spécifique pour l'admin
            } else {
                header("Location: index.php");
            }
            exit();
        } else {
            echo "Mot de passe incorrect.";
        }
    } else {
        echo "Nom d'utilisateur incorrect.";
    }

    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <link rel="stylesheet" href="styles.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - ECE In</title>
</head>
<body>
    <div id="loginForm"class="container">
        <div class="bloc1Login">
            <img src="logoPiscine.png" alt="logo" class="logoPiscineDebut">
            <img src="12.png" alt="ECE HUB Logo" class="logo-login">
            <p>
                Le réseau social 100% ECE. Créez du lien, postulez à des offres, échangez avec votre communauté.
            </p>
            <p>
                Connectez-vous dès maintenant pour publier, discuter, et faire évoluer votre carrière.
            </p>
        </div>
        <div class="bloc2Login">
            <form class="form" action="login.php" method="POST">
                <h2>Connexion</h2>
                <input type="text" name="username" placeholder="Nom d'utilisateur" required>
                <input type="password" name="password" placeholder="Mot de passe" required>
                <button type="submit">Se connecter</button>
                <p>Pas encore inscrit ? <a href="register.php">Inscrivez-vous ici</a></p>
            </form>
        </div>
    </div>
</body>
</html>
