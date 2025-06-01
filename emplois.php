<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}
include 'db.php';
$receiver_username = $_SESSION['username'];
$sql = "SELECT job_offers.*, posts.content AS post_content, posts.username AS post_author 
        FROM job_offers 
        INNER JOIN posts ON job_offers.post_id = posts.id 
        WHERE job_offers.receiver_username = '$receiver_username' 
        ORDER BY job_offers.created_at DESC";
$result = $conn->query($sql);
$username = $_SESSION['username'];

$user_sql = "SELECT * FROM users WHERE username='$username'";
$user_result = $conn->query($user_sql);
$user = $user_result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Emplois Reçus</title>
    <link rel="stylesheet" href="styles.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        function fetchNotifications() {
            $.ajax({
                url: 'fetch_notifications.php',
                method: 'GET',
                success: function(response) {
                    const notifications = JSON.parse(response);
                    const notificationCount = notifications.filter(n => n.statut === 'pending').length;
                    $('.notificationb').text(notificationCount);
                }
            });
        }
        $(document).ready(function() {
            setInterval(fetchNotifications, 1000);
            fetchNotifications();
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
        <h1>Propositions d'Emplois </h1>
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
        <a href="index.php" class="navitem">Accueil</a>
        <a href="mon_reseau.php" class="navitem">Mon Réseau</a>
        <a href="notifications.php" class="navitem">
            Notifications <span id="notif-count" class="notificationb">0</span>
        </a>

        <a href="messagerie.php" class="navitem">Messagerie</a>
        <a href="fil_d_actualite.php" class="navitem">Fil d'actualité</a>
        <a href="emplois.php" class="navitem navcurrent">Emplois</a>
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
            <h2>Offres d'emploi reçues</h2>
            <?php
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    echo "<div class='job-offer'>";
                    echo "<p><strong>Expéditeur :</strong> " . htmlspecialchars($row['sender_username']) . "</p>";
                    echo "<p><strong>Type d'emploi :</strong> " . htmlspecialchars($row['offer_type']) . "</p>";
                    echo "<p><strong>Contenu de la publication :</strong> " . htmlspecialchars($row['post_content']) . "</p>";
                    echo "<p><strong>Date :</strong> " . htmlspecialchars($row['created_at']) . "</p>";
                    echo "</div>";
                }
            } else {
                echo "<p>Aucune offre d'emploi reçue pour le moment.</p>";
            }
            ?>
        </div>
    </div>

    <div class="footer">
        <p>&copy; 2025 ECE HUB. Tous droits réservés.</p>
    </div>
</div>
</body>
</html>
