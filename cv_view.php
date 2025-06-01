<?php
session_start();
include 'db.php';

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

$username = isset($_GET['username']) ? $_GET['username'] : $_SESSION['username'];

$user_sql = "SELECT * FROM users WHERE username='$username'";
$user_result = $conn->query($user_sql);

if ($user_result->num_rows == 0) {
    echo "Utilisateur non trouvé.";
    exit();
}

$user = $user_result->fetch_assoc();
$cv_file = $user['cv_xml'];
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>CV de <?php echo htmlspecialchars($username); ?> - ECE HUB</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .CV-layout {
            display: flex;
            justify-content: space-between;
            gap: 30px;
            margin-top: 20px;
            flex-wrap: wrap;
        }

        .CV-info {
            flex: 3;
            min-width: 300px;
            max-width: 700px;
            background-color: #f9c5a6;
            padding: 25px;
            border-radius: 10px;
            word-wrap: break-word;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }

        .CV-photo {
            flex: 1;
            text-align: center;
            min-width: 200px;
        }

        .photo-cv {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid orange;
        }

        .titre-CV {
            font-size: 1.5em;
            margin-top: 10px;
            color: #333;
            margin-bottom: 15px;
        }

        .CV-container h4 {
            margin-top: 20px;
            color: #444;
            border-top: 1px solid #999;
            padding-top: 10px;
        }

        .CV-container p {
            margin: 5px 0;
            font-size: 14px;
            line-height: 1.5;
        }

        .info-perso {
            margin-bottom: 20px;
        }

        .info-perso strong {
            display: inline-block;
            width: 90px;
        }

        .footer {
            margin-top: 40px;
            text-align: center;
        }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="header">
        <img src="logoPiscine.png" alt="ECE Paris Logo">
        <h1>CV de <?php echo htmlspecialchars($username); ?></h1>
        <img src="12.png" alt="ECE HUB Logo" class="ece-logo-header">
    </div>

    <div class="CV-layout">
        <div class="CV-info">
            <?php
            if ($cv_file && file_exists($cv_file)) {
                $xml = simplexml_load_file($cv_file);
                echo "<div class='CV-container'>";

                // HEADER: Titre à gauche, Infos à droite
                echo "<div class='cv-header-row'>";
                echo "<h3 class='titre-CV'>CV de " . htmlspecialchars($username) . "</h3>";
                echo "<div class='info-perso-top'>";
                echo "<p><strong>Nom:</strong> " . $xml->InformationsPersonnelles->Nom . "</p>";
                echo "<p><strong>Adresse:</strong> " . $xml->InformationsPersonnelles->Adresse . "</p>";
                echo "<p><strong>Email:</strong> " . $xml->InformationsPersonnelles->Email . "</p>";
                echo "<p><strong>Téléphone:</strong> " . $xml->InformationsPersonnelles->Téléphone . "</p>";
                echo "</div>";
                echo "</div>";

                // Corps du CV
                echo "<h4>Expérience</h4>";
                foreach ($xml->Expérience->Emploi as $emploi) {
                    echo "<p><strong> - Titre: </strong> " . $emploi->Titre . "</p>";
                    echo "<p><strong>Entreprise:</strong> " . $emploi->Entreprise . "</p>";
                    echo "<p><strong>Années:</strong> " . $emploi->Années . "</p>";
                    echo "<p><strong>Description:</strong> " . $emploi->Description . "</p>";
                }

                echo "<h4>Éducation</h4>";
                echo "<p><strong> - Diplôme: </strong> " . $xml->Éducation->Diplôme . "</p>";
                echo "<p><strong>Établissement:</strong> " . $xml->Éducation->Établissement . "</p>";
                echo "<p><strong>Années:</strong> " . $xml->Éducation->Années . "</p>";

                echo "<h4>Compétences</h4>";
                foreach ($xml->Compétences->Compétence as $comp) {
                    echo "<p> - " . $comp . "</p>";
                }

                echo "</div>";
            } else {
                echo "<p>Aucun CV disponible pour cet utilisateur.</p>";
            }
            ?>
        </div>


        <div class="CV-photo">
            <img src="<?php echo htmlspecialchars($user['profile_picture']); ?>" alt="Photo de profil" class="photo-cv">
            <p><strong><?php echo htmlspecialchars($user['username']); ?></strong></p>
        </div>
    </div>

    <div class="footer">
        <p>&copy; 2025 ECE HUB. Tous droits réservés.</p>
    </div>
</div>
</body>
</html>
