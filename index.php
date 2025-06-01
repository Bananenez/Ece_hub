<?php
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

include 'db.php';
$username = $_SESSION['username'];
$user_sql = "SELECT * FROM users WHERE username='$username'";
$user_result = $conn->query($user_sql);
$user = $user_result->fetch_assoc();

$time_actu = date("Y-m-d H:i:s");

$carousel_sql = "
    SELECT posts.*, users.profile_picture 
    FROM posts 
    INNER JOIN users ON posts.username = users.username 
    WHERE (posts.username = '$username'
    OR posts.username IN (
        SELECT CASE
            WHEN user1 = '$username' THEN user2
            ELSE user1
        END AS friend
        FROM friends
        WHERE user1 = '$username' OR user2 = '$username'
    ))
    AND posts.datetime > '$time_actu'
    ORDER BY posts.datetime ASC 
    LIMIT 4
";
$result_carousel = $conn->query($carousel_sql);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <link rel="stylesheet" href="styles.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ECE HUB - Accueil</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        function fetchNotifications() {
            $.ajax({
                url: 'fetch_notifications.php',
                method: 'GET',
                success: function(response) {
                    const data = JSON.parse(response);
                    const notificationCount = data.length;
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
<script>
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

    $(document).ready(function () {
        fetchNotifications(); // initial
        setInterval(fetchNotifications, 1000); // auto refresh
    });
</script>

<body>
<div class="wrapper">
    <div class="header">
        <img src="logoPiscine.png" alt="ECE Paris Logo">
        <h1>Bienvenue sur </h1>
        <img src="12.png" alt="ECE HUB Logo" class="ece-logo-header">
    </div>


    <div class="menu">
        <img src="<?php echo htmlspecialchars($user['profile_picture']); ?>" alt="Menu Icon" class="menu-icon" onclick="toggleDropdown()">
        <p class="nom-profil"><?php echo htmlspecialchars($_SESSION['username']); ?></p>
        <div id="myDropdown" class="dropdown-content">
            <a href="profile.php">Vous</a>
            <a href="logout.php">Déconnexion</a>
        </div>
    </div>

    <div class="top-navigation">
        <a href="index.php" class="navitem navcurrent">Accueil</a>
        <a href="mon_reseau.php" class="navitem">Mon Réseau</a>
        <a href="notifications.php" class="navitem">
            Notifications <span id="notif-count" class="notificationb">0</span>
        </a>

        <a href="messagerie.php" class="navitem">Messagerie</a>
        <a href="fil_d_actualite.php" class="navitem">Fil d'actualité</a>
        <a href="emplois.php" class="navitem">Emplois</a>
    </div>

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

    <div class="rightcolonne">
        <div class="section">
            <h2>ECE HUB</h2>
            <div class="bloc-droit">
                <div class="leftcolonne2">
                    <p>Bienvenue sur ECE HUB, LA plateforme sociale 100% ECE : pour les amis, les projets et l'avenir.</p>

                    <div class="l">
                        <?php
                        $mon_post_sql = "SELECT posts.*, users.profile_picture FROM posts JOIN users ON posts.username = users.username WHERE posts.username = '$username' ORDER BY created_at DESC LIMIT 1;";
                        $mon_post_result = $conn->query($mon_post_sql);

                        if ($mon_post_result && $mon_post_result->num_rows > 0) {
                            $mon_post = $mon_post_result->fetch_assoc();
                            echo "<div class='post'>";
                            echo "<div class='contenairpubli'><p>" . htmlspecialchars($mon_post['username']) . "</p><a href='user_profile.php?username=" . htmlspecialchars($mon_post['username']) . "'><img src='" . htmlspecialchars($mon_post['profile_picture']) . "' alt='Profile Picture' style='width: 60px; height: 60px; border-radius: 50%; margin-left: 10px;'></a></div>";
                            echo "<p class='titre-post'><strong>" . htmlspecialchars($mon_post['feeling']) . "</strong></p>";
                            echo "<p>" . htmlspecialchars($mon_post['content']) . "</p>";
                            if ($mon_post['media_path']) {
                                if (preg_match('/\\.(mp4|avi|mov)$/i', $mon_post['media_path'])) {
                                    echo "<p><video width='320' height='240' controls><source src='" . htmlspecialchars($mon_post['media_path']) . "' type='video/mp4'></video></p>";
                                } else {
                                    echo "<p><img src='" . htmlspecialchars($mon_post['media_path']) . "' alt='media' style='max-width:100%'></p>";
                                }
                            }
                            echo "<div class='post-info'><p>Lieu: " . htmlspecialchars($mon_post['location']) . "</p>";
                            echo "<p>Créé le: " . htmlspecialchars($mon_post['created_at']) . "</p>";
                            if (!empty($mon_post['datetime']) && $mon_post['datetime'] !== '0000-00-00 00:00:00') {
                                echo "<p>Le: " . htmlspecialchars($mon_post['datetime']) . "</p>";
                            }
                            echo "</div></div>";
                        }
                        ?>
                    </div>

                    <div class="r">
                        <?php
                        $friend_post_sql = "SELECT posts.*, users.profile_picture FROM posts JOIN users ON posts.username = users.username WHERE posts.username != '$username' AND posts.username IN (SELECT CASE WHEN user1 = '$username' THEN user2 ELSE user1 END AS friend FROM friends WHERE user1 = '$username' OR user2 = '$username') ORDER BY posts.created_at DESC LIMIT 1;";
                        $friend_post_result = $conn->query($friend_post_sql);

                        if ($friend_post_result && $friend_post_result->num_rows > 0) {
                            $post = $friend_post_result->fetch_assoc();
                            echo "<div class='post'>";
                            echo "<div class='contenairpubli'><p>" . htmlspecialchars($post['username']) . "</p><a href='user_profile.php?username=" . htmlspecialchars($post['username']) . "'><img src='" . htmlspecialchars($post['profile_picture']) . "' alt='Profile Picture' style='width: 60px; height: 60px; border-radius: 50%; margin-left: 10px;'></a></div>";
                            echo "<p class='titre-post'><strong>" . htmlspecialchars($post['feeling']) . "</strong></p>";
                            echo "<p>" . htmlspecialchars($post['content']) . "</p>";
                            if ($post['media_path']) {
                                if (preg_match('/\\.(mp4|avi|mov)$/i', $post['media_path'])) {
                                    echo "<p><video width='320' height='240' controls><source src='" . htmlspecialchars($post['media_path']) . "' type='video/mp4'></video></p>";
                                } else {
                                    echo "<p><img src='" . htmlspecialchars($post['media_path']) . "' alt='media' style='max-width:100%'></p>";
                                }
                            }
                            echo "<div class='post-info'><p>Lieu: " . htmlspecialchars($post['location']) . "</p>";
                            echo "<p>Créé le: " . htmlspecialchars($post['created_at']) . "</p>";
                            if (!empty($post['datetime']) && $post['datetime'] !== '0000-00-00 00:00:00') {
                                echo "<p>Le: " . htmlspecialchars($post['datetime']) . "</p>";
                            }
                            echo "</div></div>";
                        }
                        ?>
                    </div>
                </div>

                <div class="rightcolonne2">
                    <h3>Événement à venir</h3>
                    <div class="carousel">
                        <div class="carousel-inner">
                            <?php
                            if ($result_carousel->num_rows > 0) {
                                $active = true;
                                while ($row = $result_carousel->fetch_assoc()) {
                                    $username = htmlspecialchars($row['username']);
                                    $media = htmlspecialchars($row['media_path']);
                                    $content = htmlspecialchars($row['content']);
                                    $datetime = htmlspecialchars($row['datetime']);
                                    $location = htmlspecialchars($row['location']);
                                    $desc = htmlspecialchars($row['feeling']);
                                    $profile_picture = htmlspecialchars($row['profile_picture']);

                                    $date = new DateTime($datetime);
                                    $formatted_date = $date->format('d/m/Y H:i');

                                    echo '<div class="carousel-item' . ($active ? ' active' : '') . '">';
                                    echo '<div class="post">';
                                    echo '<div class="post-header">';
                                    echo '<div class="post-content"><strong>' . $desc . '</strong></div>';
                                    echo '<p>' . $content . '</p>';
                                    if (!empty($media)) {
                                        if (preg_match('/\.(mp4|avi|mov)$/i', $media)) {
                                            echo "<p><video width='320' height='240' controls><source src='$media' type='video/mp4'></video></p>";
                                        } else {
                                            echo '<p><img src="' . $media . '" alt="Media" class="event-media" style="max-width: 100%; height: auto;"></p>';
                                        }
                                    }
                                    echo '<p><strong>Publié par :</strong> <a href="user_profile.php?username=' . $username . '"><img src="' . $profile_picture . '" alt="Photo de profil" style="width: 20px; height: 20px; border-radius: 50%; margin-right: 5px;">' . $username . '</a></p>';
                                    echo '<p><strong>A lieu le :</strong> ' . $formatted_date . '</p>';
                                    echo '<p><strong>Lieu :</strong> ' . $location . '</p>';
                                    echo '</div>'; // post-header
                                    echo '</div>'; // post
                                    echo '</div>'; // carousel-item

                                    $active = false;
                                }
                            } else {
                                echo '<div class="carousel-item active">Aucun événement à venir</div>';
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="contact">
                <h3>Contact</h3>
                <p>Vous pouvez nous contacter à l'adresse suivante pour toute question ou information :</p>
                <p>Email : contact@ecehub.com</p>
                <p>Téléphone : +33 6 95 95 75 11</p>
                <p>Adresse : 10 Rue de Sextius Michel, 75015 Paris, France</p>
                <iframe src="https://www.google.com/maps/embed?pb=..." width="600" height="450" style="border:0;" allowfullscreen="" loading="lazy"></iframe>
            </div>
        </div>
    </div>

    <div class="footer">
        <p>&copy; 2025 ECE HUB. Tous droits réservés.</p>
    </div>
</div>
</body>
</html>
