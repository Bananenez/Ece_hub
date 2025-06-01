<?php
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['username'])) {
    header("Location: index.php");
    exit();
}

include 'db.php';

$username = $_GET['username'];
$current_user = $_SESSION['username'];

$user_sql = "SELECT * FROM users WHERE username='$username'";
$user_result = $conn->query($user_sql);
$user = $user_result->fetch_assoc();

$friend_request_sql = "SELECT * FROM notifications WHERE (sender='$current_user' AND receiver='$username' AND types ='friend_request') OR (sender='$username' AND receiver='$current_user' AND types ='friend_request')";
$friend_request_result = $conn->query($friend_request_sql);
$friend_request = $friend_request_result->fetch_assoc();

$friend_sql = "SELECT * FROM friends WHERE (user1='$current_user' AND user2='$username') OR (user1='$username' AND user2='$current_user')";
$friend_result = $conn->query($friend_sql);
$is_friend = $friend_result->num_rows > 0;

$username_ = $_SESSION['username'];
$user_sql_ = "SELECT * FROM users WHERE username='$username_'";
$user_result_ = $conn->query($user_sql_);
$user_ = $user_result_->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil de <?php echo htmlspecialchars($username); ?> - ECE In</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            $('.like-button').on('click', function(event) {
                event.preventDefault();
                var postId = $(this).data('post-id');
                var $button = $(this);
                $.ajax({
                    url: 'like_post.php',
                    type: 'POST',
                    data: { post_id: postId },
                    dataType: 'json',
                    success: function(response) {
                        if (response.liked) {
                            $button.find('i').removeClass('far').addClass('fas');
                        } else {
                            $button.find('i').removeClass('fas').addClass('far');
                        }
                        $button.closest('.post').find('.likes-count').text('Likes: ' + response.likes);
                    }
                });
            });

            $('.comment-form').on('submit', function(event) {
                event.preventDefault();
                var $form = $(this);
                $.ajax({
                    url: 'comment.php',
                    type: 'POST',
                    data: $form.serialize(),
                    success: function(response) {
                        var commentData = JSON.parse(response);
                        var newComment = '<p><strong><a href="user_profile.php?username=' + commentData.username + '"><img src="' + commentData.profile_picture + '" alt="Profile Picture" style="width: 20px; height: 20px; border-radius: 50%; margin-right: 5px;"></a>' + commentData.username + ':</strong> ' + commentData.comment + '</p>';
                        $form.closest('.post').find('.comments').append(newComment);
                        $form.find('input[name="comment"]').val('');
                    }
                });
            });

            $('.add-friend-form').on('submit', function(event) {
                event.preventDefault();
                var $form = $(this);
                $.ajax({
                    url: 'add_friend.php',
                    type: 'POST',
                    data: $form.serialize(),
                    success: function(response) {
                        if (response === 'success') {
                            $form.find('button').text('Demande envoyée').prop('disabled', true);
                        } else {
                            alert('Erreur lors de l\'envoi de la demande d\'ami');
                        }
                    }
                });
            });

            $('.accept-friend-form').on('submit', function(event) {
                event.preventDefault();
                var $form = $(this);
                $.ajax({
                    url: 'accept_friend.php',
                    type: 'POST',
                    data: $form.serialize(),
                    success: function(response) {
                        if (response === 'success') {
                            $form.find('button').text('Vous êtes déjà amis').prop('disabled', true);
                        } else {
                            alert('Erreur lors de l\'envoi de la demande d\'ami');
                        }
                    }
                });
            });
        });
    </script>
</head>
<body>
<div class="wrapper">
    <!-- Header sticky en haut -->
    <div class="header">
        <img src="logoPiscine.png" alt="ECE Paris Logo">
        <h1 class="header-title">
            <span>Profil de <?php echo htmlspecialchars($username); ?></span>
            <img src="12.png" alt="ECE HUB Logo" class="ece-logo-header">
        </h1>
    </div>

    <div class="menu">
        <img src="<?php echo htmlspecialchars($user_['profile_picture']); ?>" alt="Menu Icon" class="menu-icon" onclick="toggleDropdown()">
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
            Notifications
            <span id="notif-count" class="notificationb">
                <?php
                $nbr_notif_sql = "SELECT COUNT(*) FROM notifications WHERE receiver = '$current_user' and statut = 'pending';";
                $reponse = $conn->query($nbr_notif_sql);
                $resultat = $reponse->fetch_assoc();
                echo $resultat['COUNT(*)'];
                ?>
            </span>
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
                <div class="profile-overview">
                    <div class="profilcomp">
                        <img src="<?php echo htmlspecialchars($user['profile_picture']); ?>" alt="Photo de profil" style="width: 100px; height: 100px; border-radius: 50%;">
                        <h3 class="nom-profilvue"><?php echo htmlspecialchars($user['username']); ?></h3>
                        <div class="boutoncheckami">
                            <?php if ($is_friend): ?>
                                <button disabled>Vous êtes déjà amis</button>
                            <?php elseif ($friend_request && $friend_request['statut'] == 'pending'): ?>
                                <?php if ($friend_request['sender'] == $current_user): ?>
                                    <button disabled>Demande envoyée</button>
                                <?php else: ?>
                                    <form action="accept_friend.php" method="POST" class="accept-friend-form">
                                        <input type="hidden" name="request_id" value="<?php echo $friend_request['id']; ?>">
                                        <button type="submit">Accepter la demande d'ami</button>
                                    </form>
                                <?php endif; ?>
                            <?php elseif ($current_user !== $username): ?>
                                <form class="add-friend-form">
                                    <input type="hidden" name="receiver" value="<?php echo htmlspecialchars($username); ?>">
                                    <button type="submit">Ajouter comme ami</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <style>
                    .profilcomp{
                        margin-bottom:50px;
                        padding:20px;
                        display:flex;
                        align-items:center;
                        width: 95%; /* Largeur du conteneur */
                        height: 100px; /* Hauteur du conteneur */
                        background-image: url('<?php echo htmlspecialchars($user['overlay']); ?>'); /* Chemin de l'image */
                        background-size: cover; /* Redimensionner l'image pour remplir complètement le conteneur */
                        background-position: center; /* Positionner l'image au centre */
                        background-repeat: no-repeat; /* Ne pas répéter l'image */
                        border-radius:5px;
                    }
                </style>

                <a href="cv_view.php?username=<?php echo htmlspecialchars($user['username']); ?>" target="_blank">
                    <button class="bouton-cv">Voir le CV</button>
                </a>

                <h3>Publications de <?php echo htmlspecialchars($username); ?></h3>
                <?php
                $sql = "
                    SELECT posts.*, users.profile_picture 
                    FROM posts 
                    INNER JOIN users ON posts.username = users.username 
                    WHERE posts.username = '$username'
                    AND (
                        posts.visibility = 'public'
                        OR (posts.visibility = 'all_friends' AND '$current_user' IN (SELECT CASE WHEN user1 = '$username' THEN user2 ELSE user1 END FROM friends WHERE user1 = '$username' OR user2 = '$username'))
                        OR (posts.visibility = 'selected_friends' AND posts.id IN (SELECT post_id FROM post_visibility WHERE friend_username = '$current_user'))
                        OR posts.visibility = 'private' AND posts.username = '$current_user'
                    )
                    ORDER BY posts.created_at DESC
                ";
                $result = $conn->query($sql);

                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        // Display comments
                        $post_id = $row['id'];
                        $comment_sql = "SELECT comments.*, users.profile_picture FROM comments INNER JOIN users ON comments.username = users.username WHERE comments.post_id='$post_id' ORDER BY comments.created_at ASC";
                        $comment_result = $conn->query($comment_sql);
                        echo "<div class='post'>";
                        echo "<div class='contenairpubli'><p>" . htmlspecialchars($row['username']) . "</p><img src='" . htmlspecialchars($row['profile_picture']) . "' alt='Profile Picture' style='width: 60px; height: 60px; border-radius: 50%; margin-left: 10px;'></div>";
                        echo "<p class='titre-post'><strong>" . htmlspecialchars($row['feeling']) . "</strong></p>";
                        echo "<p>" . htmlspecialchars($row['content']) . "</p>";
                        if ($row['media_path']) {
                            if (strpos($row['media_path'], '.mp4') !== false || strpos($row['media_path'], '.avi') !== false || strpos($row['media_path'], '.mov') !== false) {
                                echo "<p><video width='320' height='240' controls><source src='" . htmlspecialchars($row['media_path']) . "' type='video/mp4'>Your browser does not support the video tag.</video></p>";
                            } else {
                                echo "<p><img src='" . htmlspecialchars($row['media_path']) . "' alt='media' style='max-width:100%'></p>";
                            }
                        }
                        echo "<div class='post-info'><p>Lieu: " . htmlspecialchars($row['location']) . "</p>";
                        if (!empty($row['datetime']) && $row['datetime'] !== '0000-00-00 00:00:00') {
                            echo "<p>Le: " . htmlspecialchars($row['datetime']) . "</p>";
                        }
                        echo "<p>Créé le: " . htmlspecialchars($row['created_at']) . "</p></div>";
                        echo "<form action='comment.php' method='POST' class='comment-form'>";
                        echo "<input type='hidden' name='post_id' value='" . $post_id . "'>";
                        echo "<input type='text' name='comment' placeholder='Commenter' required>";
                        echo "<button type='submit' class='comment-button'><i class='far fa-comment'></i></button>";
                        echo "<button class='like-button' data-post-id='" . $row['id'] . "'><i class='far fa-heart'></i></button>";
                        echo "<p class='likes-count'>Likes: " . htmlspecialchars($row['likes']) . "</p>";
                        echo "</form>";

                        echo "<div class='comments'>";
                        if ($comment_result->num_rows > 0) {
                            while ($comment_row = $comment_result->fetch_assoc()) {
                                echo "<p><strong><img src='" . htmlspecialchars($comment_row['profile_picture']) . "' alt='Profile Picture' style='width: 20px; height: 20px; border-radius: 50%; margin-right: 5px;'>" . htmlspecialchars($comment_row['username']) . ":</strong> " . htmlspecialchars($comment_row['comment']) . "</p>";
                            }
                        }
                        
                        echo "</div>";
                        echo "<form action='send_job_offer.php' method='POST' class='job-offer-form'>";
                        echo "<input type='hidden' name='post_id' value='" . $post_id . "'>";
                        echo "<button type='button' class='offer-button' data-post-id='" . $post_id . "' data-receiver-username='" . htmlspecialchars($row['username']) . "'>Envoyer une offre</button>";
                        echo "</form>";
                        echo "</div>";
                    }
                } else {
                    echo "<p>Aucune publication pour le moment.</p>";
                }

                $conn->close();
                ?>
            </div>

            
        </div>

        <style>
    .modal {
        display: none;
        position: fixed;
        z-index: 1;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        overflow: auto;
        background-color: rgb(0,0,0);
        background-color: rgba(0,0,0,0.4);
        padding-top: 60px;
    }

    .modal-content {
        background-color: #fefefe;
        margin: 5% auto;
        padding: 20px;
        border: 1px solid #888;
        width: 80%;
    }

    .close {
        color: #aaa;
        float: right;
        font-size: 28px;
        font-weight: bold;
    }

    .close:hover,
    .close:focus {
        color: black;
        text-decoration: none;
        cursor: pointer;
    }
    </style>


        <script>
        $(document).ready(function() {
        var modal = document.getElementById("offerModal");
        var span = document.getElementsByClassName("close")[0];

        $('.offer-button').on('click', function() {
            var postId = $(this).data('post-id');
            var receiverUsername = $(this).data('receiver-username');
            $('#post-id').val(postId);
            $('#receiver-username').val(receiverUsername);
            modal.style.display = "block";
        });

        span.onclick = function() {
            modal.style.display = "none";
        }

        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }
    });
    </script>
        <div class="footer">
            <p>&copy; 2025 ECE HUB. Tous droits réservés.</p>
        </div>

        
    </div>

    <div id="offerModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Envoyer une offre</h2>
            <form id="offer-form" action="send_job_offer.php" method="POST">
                <input type="hidden" name="post_id" id="post-id">
                <input type="hidden" name="receiver_username" id="receiver-username">
                <div class="offer-options">
                    <label>
                        <input type="radio" name="offer_type" value="stage" required>
                        Stage
                    </label>
                    <label>
                        <input type="radio" name="offer_type" value="apprentissage" required>
                        Apprentissage
                    </label>
                    <label>
                        <input type="radio" name="offer_type" value="temporaire" required>
                        Emploi Temporaire
                    </label>
                    <label>
                        <input type="radio" name="offer_type" value="permanent" required>
                        Emploi Permanent
                    </label>
                </div>
                <button type="submit">Envoyer</button>
            </form>
        </div>
    </div>
</body>
</html>
