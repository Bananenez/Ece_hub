<?php
include 'db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);

    // Insérez l'utilisateur d'abord sans images
    $sql = "INSERT INTO users (username, email, password) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $username, $email, $password);

    if ($stmt->execute()) {
        $user_id = $stmt->insert_id;
        $target_dir = "uploads/$user_id/";
        mkdir($target_dir, 0777, true);

        // Initialisation des chemins (valeurs nulles par défaut)
        $profile_picture_file = null;
        $overlay_file = null;

        // Upload photo de profil si fournie
        if (!empty($_FILES["profile_picture"]["tmp_name"])) {
            $profile_picture_type = strtolower(pathinfo($_FILES["profile_picture"]["name"], PATHINFO_EXTENSION));
            $profile_picture_file = $target_dir . "profile_picture." . $profile_picture_type;

            if (in_array($profile_picture_type, ['jpg', 'jpeg', 'png']) &&
                $_FILES["profile_picture"]["size"] < 500000 &&
                getimagesize($_FILES["profile_picture"]["tmp_name"])) {
                move_uploaded_file($_FILES["profile_picture"]["tmp_name"], $profile_picture_file);
            } else {
                $profile_picture_file = null; // invalide, on ignore
            }
        }

        // Upload overlay si fourni
        if (!empty($_FILES["overlay"]["tmp_name"])) {
            $overlay_type = strtolower(pathinfo($_FILES["overlay"]["name"], PATHINFO_EXTENSION));
            $overlay_file = $target_dir . "overlay." . $overlay_type;

            if (in_array($overlay_type, ['jpg', 'jpeg', 'png']) &&
                $_FILES["overlay"]["size"] < 500000 &&
                getimagesize($_FILES["overlay"]["tmp_name"])) {
                move_uploaded_file($_FILES["overlay"]["tmp_name"], $overlay_file);
            } else {
                $overlay_file = null;
            }
        }

        // Mise à jour des chemins des fichiers dans la base
        $sql_update = "UPDATE users SET profile_picture=?, overlay=? WHERE id=?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param("ssi", $profile_picture_file, $overlay_file, $user_id);
        $stmt_update->execute();

        header("Location: login.php");
        exit();
    } else {
        echo "Erreur: " . $stmt->error;
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
    <title>Inscription - ECE In</title>
</head>
<body>
    <div class="container" id="registerForm">
        <div class="bloc1Login">
            <img src="logoPiscine.png" alt="logo" class="logoPiscineDebut">
            <p>Bienvenue sur ECE In, le réseau social innovant créé par une équipe de quatre étudiants passionnés dans le cadre de leurs projet de web dynamique. ECE In est conçu pour être un espace où les membres de la communauté ECE peuvent se connecter, interagir et évoluer professionnellement.</p>

            </p>Sur ECE In, vous avez la possibilité d'explorer et de postuler à des offres d'emploi adaptées à votre profil, de partager vos idées et expériences à travers des publications, et de construire un réseau solide en établissant des connexions avec d'autres membres. De plus, grâce à notre messagerie intégrée, vous pouvez facilement discuter et échanger avec vos amis et contacts professionnels en temps réel.

            Rejoignez-nous sur ECE In et découvrez un environnement convivial et professionnel, propice à la croissance personnelle et à l'enrichissement de votre carrière.</p>
        </div>
        <div class="bloc2Login">
            <form class="form" action="register.php" method="POST" enctype="multipart/form-data">
                <h2>Inscription</h2>
                <input type="text" name="username" placeholder="Nom d'utilisateur" required>
                <input type="email" name="email" placeholder="Email" required>
                <input type="password" name="password" placeholder="Mot de passe" required>
                <label for="profile_picture">Photo de profil:</label>
                <input type="file" id="profile_picture" name="profile_picture" accept="image/*" required><br>
                <label for="overlay">Overlay:</label><br>
                <input type="file" id="overlay" name="overlay" accept="image/*" required>
                <button type="submit">S'inscrire</button>
                <p>Déjà inscrit ? <a href="login.php">Connectez-vous ici</a></p>
            </form>
        </div>
    </div>
</body>
</html>
