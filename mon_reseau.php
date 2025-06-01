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

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_friend'])) {
    $receiver = $conn->real_escape_string($_POST['receiver']);
    $sender = $username;

    $friend_request_sql = "SELECT * FROM notifications WHERE (sender='$sender' AND receiver='$receiver') OR (sender='$receiver' AND receiver='$sender')";
    $friend_request_result = $conn->query($friend_request_sql);

    if ($friend_request_result->num_rows == 0) {
        $sql = "INSERT INTO notifications (receiver, sender, types, statut) VALUES ('$receiver', '$sender', 'friend_request', 'pending')";
        $conn->query($sql);
    }
    header("Location: mon_reseau.php");
    exit();
}

$sql = "SELECT friends.*, u1.profile_picture AS profile_picture1, u2.profile_picture AS profile_picture2 
        FROM friends 
        LEFT JOIN users u1 ON friends.user1 = u1.username 
        LEFT JOIN users u2 ON friends.user2 = u2.username 
        WHERE user1='$username' OR user2='$username'";
$friends_result = $conn->query($sql);

$search_result = null;
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['search'])) {
    $search = $conn->real_escape_string($_POST['search']);
    $sql = "SELECT * FROM users WHERE username LIKE '%$search%' AND username != '$username'";
    $search_result = $conn->query($sql);
}

$user_sql = "SELECT * FROM users WHERE username='$username'";
$user_result = $conn->query($user_sql);
$user = $user_result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Réseau - ECE HUB</title>
    <link rel="stylesheet" href="styles.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        function sendFriendRequest(event) {
            event.preventDefault();
            const formData = new FormData(event.target);

            fetch('accept_friend.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.text())
                .then(data => {
                    window.location.reload();
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    alert('Erreur lors de l\'envoi de la demande.');
                });
        }

        function fetchNotifications() {
            $.ajax({
                url: 'fetch_notifications.php',
                method: 'GET',
                dataType: 'json',
                success: function(response) {
                    const notificationCount = response.filter(notification => notification.statut === 'pending').length;
                    $('#notif-count').text(notificationCount);
                }
            });
        }

        $(document).ready(function() {
            fetchNotifications();
            setInterval(fetchNotifications, 1000);
        });
    </script>
</head>
<body>
<div class="wrapper">
    <!-- Header -->
    <div class="header">
        <img src="logoPiscine.png" alt="ECE Paris Logo">
        <h1>Media Professionel </h1>
        <img src="12.png" alt="ECE HUB Logo" class="ece-logo-header">
    </div>

    <!-- Menu utilisateur -->
    <div class="menu">
        <img src="<?php echo htmlspecialchars($user['profile_picture']); ?>" alt="Menu Icon" class="menu-icon" onclick="toggleDropdown()">
        <p class="nom-profil"><?php echo htmlspecialchars($_SESSION['username']); ?></p>
        <div id="myDropdown" class="dropdown-content">
            <a href="profile.php">Vous</a>

            <a href="logout.php">Déconnexion</a>
        </div>
    </div>

    <!-- Barre de navigation sticky -->
    <div class="top-navigation">
        <a href="index.php" class="navitem">Accueil</a>
        <a href="mon_reseau.php" class="navitem navcurrent">Mon Réseau</a>
        <a href="notifications.php" class="navitem">
            Notifications
            <span id="notif-count" class="notificationb">
            <?php
            $notif_sql = "SELECT COUNT(*) FROM notifications WHERE receiver = '$current_user' AND statut = 'pending'";
            $notif_result = $conn->query($notif_sql);
            $notif_count = $notif_result->fetch_row()[0];
            echo $notif_count;
            ?>
        </span>
        </a>
        <a href="messagerie.php" class="navitem">Messagerie</a>
        <a href="fil_d_actualite.php" class="navitem">Fil d'actualité</a>
        <a href="emplois.php" class="navitem">Emplois</a>
    </div>

    <!-- Script menu dropdown -->
    <script>
        function toggleDropdown() {
            document.getElementById("myDropdown").classList.toggle("show");
        }

        window.onclick = function(event) {
            if (!event.target.matches('.menu-icon')) {
                var dropdowns = document.getElementsByClassName("dropdown-content");
                for (var i = 0; i < dropdowns.length; i++) {
                    var openDropdown = dropdowns[i];
                    if (openDropdown.classList.contains('show')) {
                        openDropdown.classList.remove('show');
                    }
                }
            }
        }
    </script>

    <!-- Contenu principal -->
    <div class="rightcolonne">
        <div class="section3">
            <div class="blocprincipalami">

                <div class="bloc1ami">
                    <h2>Mes Contacts</h2>
                    <?php
                    if ($friends_result->num_rows > 0) {
                        while ($row = $friends_result->fetch_assoc()) {
                            $friend = ($row['user1'] == $username) ? $row['user2'] : $row['user1'];
                            $friend_profile_picture = ($row['user1'] == $username) ? $row['profile_picture2'] : $row['profile_picture1'];
                            echo "<div class='profile-container'><a href='user_profile.php?username=" . htmlspecialchars($friend ?? '') . "'><img src='" . htmlspecialchars($friend_profile_picture ?? 'default.jpg') . "' alt='Profile Picture' class='profile-picture1'></a><p>" . htmlspecialchars($friend ?? '') . "</p></div>";
                        }
                    } else {
                        echo "<p>Vous n'avez pas encore de contact.</p>";
                    }
                    ?>
                </div>

                <!-- Bloc recherche -->
                <div class="bloc2ami">
                    <h2>Rechercher et Ajouter des contacts</h2>
                    <form method="POST" action="mon_reseau.php">
                        <input type="text" name="search" placeholder="Rechercher un utilisateur" required>
                        <button type="submit" class="boutonrech">Rechercher</button>
                    </form>

                    <?php
                    if ($search_result && $search_result->num_rows > 0) {
                        echo "<h3>Résultats de la recherche :</h3>";
                        while ($row = $search_result->fetch_assoc()) {
                            $searched_user = $row['username'];
                            $profile_picture = $row['profile_picture'];

                            $friend_request_sql = "SELECT * FROM notifications WHERE (sender='$username' AND receiver='$searched_user' AND types='friend_request') OR (sender='$searched_user' AND receiver='$username' AND types='friend_request')";
                            $friend_request_result = $conn->query($friend_request_sql);
                            $friend_request = $friend_request_result->fetch_assoc();

                            $friend_sql = "SELECT * FROM friends WHERE (user1='$username' AND user2='$searched_user') OR (user1='$searched_user' AND user2='$username')";
                            $friend_result = $conn->query($friend_sql);
                            $is_friend = $friend_result->num_rows > 0;

                            echo "<div class='profile-container'><a href='user_profile.php?username=" . htmlspecialchars($searched_user) . "'><img src='" . htmlspecialchars($profile_picture) . "' class='profile-picture1'></a><p>" . htmlspecialchars($searched_user);

                            if ($is_friend) {
                                echo " <button disabled>Vous êtes déjà en contact</button>";
                            } elseif ($friend_request && $friend_request['statut'] == 'pending') {
                                if ($friend_request['sender'] == $username) {
                                    echo " <button disabled>Demande envoyée</button>";
                                } else {
                                    echo " <form onsubmit='sendFriendRequest(event)'><input type='hidden' name='request_id' value='" . htmlspecialchars($friend_request['id']) . "'><button type='submit' class='boutonrech1'>Accepter la demande</button></form>";
                                }
                            } else {
                                echo " <form method='POST' style='display:inline;'><input type='hidden' name='receiver' value='" . htmlspecialchars($searched_user) . "'><button type='submit' name='add_friend' class='boutonaddami'><img src='ami.png' alt='ami-add'></button></form>";
                            }

                            echo "</p></div>";
                        }
                    }
                    ?>

                        <h2>Vous connaissez peut-être</h2>
                        <?php
                        $friend_of_friends_sql = "
                            SELECT DISTINCT users.username, users.profile_picture 
                            FROM users 
                            JOIN friends f1 ON users.username = f1.user1 OR users.username = f1.user2 
                            WHERE (f1.user1 IN (SELECT CASE WHEN user1='$username' THEN user2 ELSE user1 END AS friend FROM friends WHERE user1='$username' OR user2='$username') 
                            OR f1.user2 IN (SELECT CASE WHEN user1='$username' THEN user2 ELSE user1 END AS friend FROM friends WHERE user1='$username' OR user2='$username'))
                            AND users.username != '$username'
                            AND users.username NOT IN (SELECT CASE WHEN user1='$username' THEN user2 ELSE user1 END AS friend FROM friends WHERE user1='$username' OR user2='$username')";
                        $friend_of_friends_result = $conn->query($friend_of_friends_sql);

                        if ($friend_of_friends_result && $friend_of_friends_result->num_rows > 0) {
                            while ($row = $friend_of_friends_result->fetch_assoc()) {
                                $friend_of_friend = $row['username'];
                                $profile_picture = $row['profile_picture'];

                                echo "<div class='profile-container'><a href='user_profile.php?username=" . htmlspecialchars($friend_of_friend ?? '') . "'><img src='" . htmlspecialchars($profile_picture ?? 'default.jpg') . "' alt='Profile Picture' class='profile-picture1'></a><p>" . htmlspecialchars($friend_of_friend ?? '');

                                // Vérifier l'état de la demande d'ami
                                $friend_request_sql = "SELECT * FROM notifications WHERE (sender='$username' AND receiver='$friend_of_friend' AND types='friend_request') OR (sender='$friend_of_friend' AND receiver='$username' AND types='friend_request')";
                                $friend_request_result = $conn->query($friend_request_sql);
                                $friend_request = $friend_request_result->fetch_assoc();

                                // Vérifier si l'utilisateur est déjà ami
                                $friend_sql = "SELECT * FROM friends WHERE (user1='$username' AND user2='$friend_of_friend') OR (user1='$friend_of_friend' AND user2='$username')";
                                $friend_result = $conn->query($friend_sql);
                                $is_friend = $friend_result->num_rows > 0;

                                if ($is_friend) {
                                    echo " <button disabled>Vous êtes déjà en contact</button>";
                                } elseif ($friend_request && $friend_request['statut'] == 'pending') {
                                    if ($friend_request['sender'] == $username) {
                                        echo " <button disabled>Demande envoyée</button>";
                                    } else {
                                        echo " <form id='friendRequestForm' onsubmit='sendFriendRequest(event)'>
                                                <input type='hidden' name='request_id' value='" . htmlspecialchars($friend_request['id']) . "'>
                                                <button type='submit' class='boutonrech1'>Accepter la demande</button>
                                            </form>";
                                    }
                                } else {
                                    echo " <form method='POST' style='display:inline;'>
                                            <input type='hidden' name='receiver' value='" . htmlspecialchars($friend_of_friend) . "'>
                                            <button type='submit' name='add_friend' class='boutonaddami'><img src='ami.png' alt='ami-add'></button>
                                        </form>";
                                }
                                echo "</p></div>";
                            }
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="footer">
            <p>&copy; 2025 ECE HUB. Tous droits réservés.</p>
        </div>
    </div>
</body>
</html>
