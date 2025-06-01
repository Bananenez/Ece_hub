<?php
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

include 'db.php';
// Requête pour compter les notifications en attente
$notif_sql = "SELECT COUNT(*) AS notif_count FROM notifications WHERE receiver = '$username' AND statut = 'pending'";
$notif_result = $conn->query($notif_sql);
$notif_data = $notif_result->fetch_assoc();
$notif_count = $notif_data['notif_count'];

$username = $_SESSION['username'];
$user_sql = "SELECT * FROM users WHERE username='$username'";
$user_result = $conn->query($user_sql);
$user = $user_result->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] == 'accept' || $_POST['action'] == 'reject' || $_POST['action'] == 'read') {
        $request_id = $conn->real_escape_string($_POST['request_id']);
        $new_status = $_POST['action'] == 'accept' ? 'accepted' : ($_POST['action'] == 'reject' ? 'rejected' : 'read');

        $sql = "UPDATE notifications SET statut='$new_status' WHERE id='$request_id'";
        if (!$conn->query($sql)) {
            die("Erreur de mise à jour: " . $conn->error);
        }

        if ($_POST['action'] == 'accept') {
            $sql = "SELECT sender, receiver FROM notifications WHERE id='$request_id'";
            $result = $conn->query($sql);
            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $sender = $row['sender'];
                $receiver = $row['receiver'];

                $sql = "INSERT INTO friends (user1, user2) VALUES ('$sender', '$receiver')";
                if (!$conn->query($sql)) {
                    die("Erreur d'insertion: " . $conn->error);
                }
            } else {
                die("Notification non trouvée.");
            }
        }
    } elseif ($_POST['action'] == 'read_all') {
        $sql = "UPDATE notifications SET statut='read' WHERE receiver='$username' AND statut='pending'";
        if (!$conn->query($sql)) {
            die("Erreur de mise à jour: " . $conn->error);
        }
    }
    header("Location: notifications.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Notifications - ECE In</title>
    <link rel="stylesheet" href="styles.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        function fetchNotifications() {
            fetch('fetch_notifications.php')
                .then(response => response.json())
                .then(data => {
                    const notificationsDiv = document.getElementById('notif');
                    notificationsDiv.innerHTML = '<h2>Notifications</h2>' +
                        '<form method="POST">' +
                        '<input type="hidden" name="action" value="read_all">' +
                        '<button type="submit" class="read-all-button">Marquer toutes comme lues</button>' +
                        '</form>';

                    if (data.length > 0) {
                        data.forEach(row => {
                            let notifHtml = '';
                            if (row.types === 'friend_request') {
                                notifHtml = `<div class='profile-container1'><a href='user_profile.php?username=${row.sender}'><img src='${row.profile_picture}' alt='Profile Picture' class='profile-picture1'></a><p>${row.sender}
                                <form method='POST' style='display:inline;'>
                                    <input type='hidden' name='request_id' value='${row.id}'>
                                    <button type='submit' name='action' value='accept' class='acceptrefus'>Accepter</button>
                                    <button type='submit' name='action' value='reject' class='acceptrefus'>Rejeter</button>
                                </form></p></div><br>`;
                            } else {
                                let message = '';
                                if (row.types === 'comment') message = 'a commenté votre publication.';
                                if (row.types === 'like') message = 'a liké votre publication.';
                                if (row.types === 'new_post') message = 'a publié un nouvel événement.';
                                if (row.types === 'offre_emplois') message = 'vous a envoyé une offre d\'emploi.';

                                notifHtml = `<div class='profile-container1'><p>${row.sender} ${message}
                                <form method='POST' style='display:inline;'>
                                    <input type='hidden' name='request_id' value='${row.id}'>
                                    <button type='submit' name='action' value='read' class='LU'>Lu</button>
                                </form></p></div><br>`;
                            }
                            notificationsDiv.innerHTML += notifHtml;
                        });
                    } else {
                        notificationsDiv.innerHTML += "<p>Aucune notification en attente.</p>";
                    }

                    const notificationCount = data.filter(n => n.statut === 'pending').length;
                    $('.notificationb p').text(notificationCount);
                })
                .catch(error => console.error('Erreur:', error));
        }

        $(document).ready(function () {
            fetchNotifications();
            setInterval(fetchNotifications, 1000);
        });
    </script>
</head>
<body>
<div class="wrapper">
    <div class="header">
        <img src="logoPiscine.png" alt="ECE Paris Logo">
        <h1>Notifications </h1>
        <img src="12.png" alt="ECE HUB Logo" class="ece-logo-header">
    </div>

    <div class="menu">
        <img src="<?php echo htmlspecialchars($user['profile_picture']); ?>" alt="Menu Icon" class="menu-icon" onclick="toggleDropdown()">
        <p class="nom-profil"><?php echo htmlspecialchars($username); ?></p>
        <div id="myDropdown" class="dropdown-content">
            <a href="profile.php">Vous</a>

            <a href="logout.php">Déconnexion</a>
        </div>
    </div>

    <!-- MENU HORIZONTAL COLLÉ EN HAUT -->
    <div class="top-navigation">
        <a href="index.php" class="navitem">Accueil</a>
        <a href="mon_reseau.php" class="navitem">Mon Réseau</a>
        <a href="notifications.php" class="navitem navcurrent">Notifications
        </a>
        <a href="messagerie.php" class="navitem">Messagerie</a>
        <a href="fil_d_actualite.php" class="navitem">Fil d'actualité</a>
        <a href="emplois.php" class="navitem">Emplois</a>
    </div>


    <script>
        function toggleDropdown() {
            document.getElementById("myDropdown").classList.toggle("show");
        }

        window.onclick = function (event) {
            if (!event.target.matches('.menu-icon')) {
                let dropdowns = document.getElementsByClassName("dropdown-content");
                for (let i = 0; i < dropdowns.length; i++) {
                    let openDropdown = dropdowns[i];
                    if (openDropdown.classList.contains('show')) {
                        openDropdown.classList.remove('show');
                    }
                }
            }
        };
    </script>

    <div class="rightcolonne">
        <div class="section" id="notif">
            <!-- Notifications seront chargées ici -->
        </div>
    </div>

    <div class="footer">
        <p>&copy; 2025 ECE HUB. Tous droits réservés.</p>
    </div>
</div>
</body>
</html>
