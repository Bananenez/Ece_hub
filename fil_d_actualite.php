<?php
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

include 'db.php';

$current_user = $_SESSION['username'];

// Requête pour obtenir les posts de l'utilisateur connecté et de ses amis
$sql = "
    SELECT posts.*, users.profile_picture 
    FROM posts 
    INNER JOIN users ON posts.username = users.username 
    LEFT JOIN post_visibility ON posts.id = post_visibility.post_id
    WHERE 
    (posts.visibility = 'public') OR 
    (posts.visibility = 'private' AND posts.username = '$current_user') OR 
    (posts.visibility = 'all_friends' AND posts.username IN (
        SELECT CASE
            WHEN user1 = '$current_user' THEN user2
            ELSE user1
        END AS friend
        FROM friends
        WHERE user1 = '$current_user' OR user2 = '$current_user'
    )) OR 
    (posts.visibility = 'selected_friends' AND post_visibility.friend_username = '$current_user') OR 
    (posts.username = '$current_user')
    GROUP BY posts.id
    ORDER BY posts.created_at DESC";
$result = $conn->query($sql);
$username = $_SESSION['username'];
$user_sql = "SELECT * FROM users WHERE username='$username'";
$user_result = $conn->query($user_sql);
$user = $user_result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <link rel="stylesheet" href="styles.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fil d'actualité - ECE In</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            function attachLikeAndCommentHandlers() {
                $('.like-button').off('click').on('click', function(event) {
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
                            $button.closest('.post').find('.likes-count').text(response.likes);
                        }
                    });
                });

                $('.comment-form').off('submit').on('submit', function(event) {
                    event.preventDefault();
                    var $form = $(this);
                    $.ajax({
                        url: 'comment.php',
                        type: 'POST',
                        data: $form.serialize(),
                        success: function(response) {
                            var commentData = JSON.parse(response);
                            var newComment = '<div class="commentaires"><a href="user_profile.php?username=' + commentData.username + '"><img src="' + commentData.profile_picture + '" alt="Profile Picture" style="width: 20px; height: 20px; border-radius: 50%; margin-right: 5px;"></a><p class="usercom">' + commentData.username + ':</p><p> ' + commentData.comment + '</p>';
                            $form.closest('.post').find('.comments').append(newComment);
                            $form.find('input[name="comment"]').val('');
                        }
                    });
                });

                $('#visibility').on('change', function() {
                    if ($(this).val() == 'selected_friends') {
                        $('#friends-selection').show();
                    } else {
                        $('#friends-selection').hide();
                    }
                });
            }

            $('#post-form').on('submit', function(event) {
                event.preventDefault();
                var formData = new FormData(this);
                $.ajax({
                    url: 'publish_post.php',
                    type: 'POST',
                    data: formData,
                    contentType: false,
                    processData: false,
                    success: function(response) {
                        var postData = JSON.parse(response);
                        if (postData.status === 'success') {
                            var post = postData.post;
                            var newPost = `
                                <div class='post'>
                                    <div class='contenairpubli'><p>${post.username}</p><a href='user_profile.php?username=${post.username}'><img src='${post.profile_picture ?? 'default.jpg'}' alt='Profile Picture' style='width: 60px; height: 60px; border-radius: 50%; margin-left: 10px;'></a></div>
                                    <p class='titre-post'><strong>${post.feeling}</strong></p>
                                    <p>${post.content}</p>
                                    ${post.media_path ? (post.media_path.match(/\.(mp4|avi|mov)$/i) ? `<p><video width='320' height='240' controls><source src='${post.media_path}' type='video/mp4'>Your browser does not support the video tag.</video></p>` : `<p><img src='${post.media_path}' alt='media' style='max-width:100%'></p>`) : ''}
                                    <div class='post-info'><p>Lieu: ${post.location}</p>
                                    ${post.datetime != '0000-00-00 00:00:00' ? `<p>Le: ${post.datetime}</p>` : ''}
                                    <p>Créé le: ${post.created_at}</p></div>
                                    
                                    <form action='comment.php' method='POST' class='comment-form'>
                                        <input type='hidden' name='post_id' value='${post.id}'>
                                        <input type='text' name='comment' placeholder='Commenter' required>
                                        <button type='submit' class='comment-button'><i class='far fa-comment'></i></button>
                                        <button class='like-button' data-post-id='${post.id}'><i class='far fa-heart'></i></button>
                                        <p class='likes-count'>0</p>
                                    </form>
                                    
                                    <div class='comments'></div>
                                    <button type='button' class='offer-button' data-post-id='${post.id}' data-receiver-username='${post.username}'>Envoyer une offre</button>
                                </div>
                            `;
                            $('#posts-section').prepend(newPost);
                            $('#post-form')[0].reset();
                            $('#friends-selection').hide();
                            attachLikeAndCommentHandlers();
                        } else {
                            alert('Erreur lors de la publication du post: ' + postData.message);
                        }
                    }
                });
            });

            attachLikeAndCommentHandlers();
        });
    </script>
</head>
<body>
    <div class="wrapper">
        <div class="header">
            <img src="logoPiscine.png" alt="ECE Paris Logo">
            <h1>Fil d'Actualité de </h1>
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
            <a href="fil_d_actualite.php" class="navitem navcurrent">Fil d'actualité</a>
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
                <h2>Bienvenue, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h2>
                <div class="choix">
                    <button class="tablinks" onclick="openTab(event, 'accueil')">Publier</button>
                    <button class="tablinks" onclick="openTab(event, 'fermer')">Fermer</button>
                </div>
                <div id="accueil" class="tabcontent">
                    <h3>Publier un événement</h3>
                    <form id="post-form" enctype="multipart/form-data">
                        <input type="text" name="feeling" placeholder="Titre">
                        <textarea name="content" placeholder="Quoi de neuf?" required></textarea>
                        <input type="text" name="location" placeholder="Lieu">
                        <input type="file" name="media">
                        <label for="datetime">Date et heure (optionnel):</label>
                        <input type="datetime-local" name="datetime">
                        <label for="visibility">Visibilité:</label>
                        <select name="visibility" id="visibility">
                            <option value="public">Public</option>
                            <option value="all_friends">Tous les amis</option>
                            <option value="selected_friends">Certains amis</option>
                            <option value="private">Privé</option>
                        </select>
                        <div id="friends-selection" style="display: none;">
                            <label for="friends">Choisissez des amis:</label>
                            <select name="friends[]" id="friends" multiple>
                                <?php
                                $friends_sql = "
                                    SELECT CASE 
                                        WHEN user1 = '$current_user' THEN user2
                                        ELSE user1
                                    END AS friend
                                    FROM friends
                                    WHERE user1 = '$current_user' OR user2 = '$current_user'
                                ";
                                $friends_result = $conn->query($friends_sql);
                                while ($friend_row = $friends_result->fetch_assoc()) {
                                    echo "<option value='" . htmlspecialchars($friend_row['friend']) . "'>" . htmlspecialchars($friend_row['friend']) . "</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <button type="submit">Publier</button>
                    </form>
                </div>
                <div id="fermer" class="tabcontent">
                </div>

                <script>
                    function openTab(evt, tabName) {
                        var i, tabcontent, tablinks;

                        // Cacher tous les onglets
                        tabcontent = document.getElementsByClassName("tabcontent");
                        for (i = 0; i < tabcontent.length; i++) {
                            tabcontent[i].style.display = "none";
                        }

                        // Désélectionner tous les boutons d'onglet
                        tablinks = document.getElementsByClassName("tablinks");
                        for (i = 0; i < tablinks.length; i++) {
                            tablinks[i].className = tablinks[i].className.replace(" active", "");
                        }

                        // Afficher l'onglet actuel et ajouter la classe "active" au bouton
                        document.getElementById(tabName).style.display = "block";
                        evt.currentTarget.className += " active";
                    }
                </script>
                <h3>Mur des publications</h3>
                <div id="posts-section">
                <?php
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $post_id = $row['id'];
                        echo "<div class='post'>";
                        echo "<div class='contenairpubli'><p>" . htmlspecialchars($row['username']) . "</p><a href='user_profile.php?username=" . htmlspecialchars($row['username']) . "'><img src='" . htmlspecialchars($row['profile_picture'] ?? 'default.jpg') . "' alt='Profile Picture' style='width: 60px; height: 60px; border-radius: 50%; margin-left: 10px;'></a></div>";
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
                        
                        if (!is_null($row['datetime'])){
                            echo "<p>Le: " . htmlspecialchars($row['datetime']) . "</p>";
                        }
                        echo "<p>Créé le: " . htmlspecialchars($row['created_at']) . "</p></div>";

                        echo "<form action='comment.php' method='POST' class='comment-form'>";
                        echo "<input type='hidden' name='post_id' value='" . $post_id . "'>";
                        echo "<input type='text' name='comment' placeholder='Commenter' required>";
                        echo "<button type='submit' class='comment-button'><i class='far fa-comment'></i></button>";

                        $post_likes_sql = "SELECT * FROM post_likes JOIN users ON users.id = post_likes.user_id  WHERE users.username = '$username' AND post_likes.post_id = $post_id;";
                        $post_likes = $conn->query($post_likes_sql);
                        
                        if($post_likes->num_rows == 0){
                            echo "<button class='like-button' data-post-id='" . $post_id . "'><i class='far fa-heart'></i></button>";
                        }
                        else{
                            echo "<button class='like-button' data-post-id='" . $post_id . "'><i class='fas fa-heart'></i></button>";
                        }

                        echo "<p class='likes-count'>" . htmlspecialchars($row['likes']) . "</p>";
                        echo "</form>";

                        $comment_sql = "SELECT comments.*, users.profile_picture FROM comments 
                                        INNER JOIN users ON comments.username = users.username 
                                        WHERE comments.post_id='$post_id' ORDER BY comments.created_at ASC";
                        $comment_result = $conn->query($comment_sql);

                        echo "<div class='comments'>";
                        if ($comment_result->num_rows > 0) {
                            while ($comment_row = $comment_result->fetch_assoc()) {
                                echo "<div class='commentaires'><a href='user_profile.php?username=" . htmlspecialchars($comment_row['username']) . "'><img src='" . htmlspecialchars($comment_row['profile_picture'] ?? 'default.jpg') . "' alt='Profile Picture' style='width: 20px; height: 20px; border-radius: 50%; margin-right: 5px;'></a><p class='usercom'>" . htmlspecialchars($comment_row['username']) . ":</p><p>" . htmlspecialchars($comment_row['comment']) . "</p></div>";
                            }
                        }
                        echo "</div>";

                        echo "<button type='button' class='offer-button' data-post-id='" . $post_id . "' data-receiver-username='" . htmlspecialchars($row['username']) . "'>Envoyer une offre</button>";
                        echo "</div>";
                    }
                } else {
                    echo "<p>Aucune publication pour le moment.</p>";
                }
                ?>
                </div>
            </div>
        </div>
        <div class="footer">
            <p>&copy; 2024 ECE In. Tous droits réservés.</p>
        </div>
    </div>

    <!-- Modal pour envoyer une offre -->
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

    <!-- Style pour le modal -->
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
</body>
</html>
