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

$current_user = $_SESSION['username'];

// Get user's friends
$sql = "SELECT u1.username AS user1, u1.profile_picture AS profile_picture1, 
        u2.username AS user2, u2.profile_picture AS profile_picture2 
        FROM friends 
        JOIN users u1 ON friends.user1 = u1.username 
        JOIN users u2 ON friends.user2 = u2.username 
        WHERE user1='$current_user' OR user2='$current_user'";
$result = $conn->query($sql);
$friends = array();

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        if ($row['user1'] == $current_user) {
            $friends[] = array('username' => $row['user2'], 'profile_picture' => $row['profile_picture2']);
        } else {
            $friends[] = array('username' => $row['user1'], 'profile_picture' => $row['profile_picture1']);
        }
    }
}

// Get user's groups
$sql = "SELECT group_chats.*, group_members.username AS member_username
        FROM group_chats
        JOIN group_members ON group_chats.id = group_members.group_id
        WHERE group_members.username = '$current_user'";
$group_result = $conn->query($sql);
$groups = array();

if ($group_result->num_rows > 0) {
    while ($row = $group_result->fetch_assoc()) {
        $groups[] = array('id' => $row['id'], 'group_name' => $row['group_name']);
    }
}
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
    <title>Messagerie ECE HUB</title>
    <link rel="stylesheet" href="styles.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        #createGroupForm {
            display: none;
        }
    </style>
    <script>
        function fetchNotifications() {
            $.ajax({
                url: 'fetch_notifications.php',
                method: 'GET',
                success: function(response) {
                    const notifications = JSON.parse(response);
                    const notificationCount = notifications.length;
                    $('.notificationb p').text(notificationCount);
                }
            });
        }

        $(document).ready(function() {
            setInterval(fetchNotifications, 1000);

            $('#messageForm').on('submit', function(event) {
                event.preventDefault();
                $.ajax({
                    url: 'send_message.php',
                    type: 'POST',
                    dataType: 'json',
                    data: $(this).serialize(),
                    success: function(response) {
                        if (response.error) {
                            console.error(response.error);
                        } else {
                            $('#conversation').append(
                                '<div class="message">' +
                                '<p>' +
                                '<strong>' +
                                '<a href="user_profile.php?username=' + response.sender + '">' +
                                '<img src="' + response.profile_picture + '" alt="Profile Picture" style="width: 20px; height: 20px; border-radius: 50%; margin-right: 5px;">' +
                                '</a>' +
                                response.sender +
                                '</strong> (' + response.timestamp + '): ' +
                                response.message +
                                '</p>' +
                                '</div>'
                            );
                            $('textarea[name="message"]').val('');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error(xhr.responseText);
                    }
                });
            });

            $('#groupMessageForm').on('submit', function(event) {
                event.preventDefault();
                $.ajax({
                    url: 'send_group_message.php',
                    type: 'POST',
                    dataType: 'json',
                    data: $(this).serialize(),
                    success: function(response) {
                        if (response.status === 'error') {
                            console.error(response.message);
                        } else {
                            $('#group-conversation').append(
                                '<div class="message">' +
                                '<p>' +
                                '<strong>' +
                                '<a href="user_profile.php?username=' + response.sender + '">' +
                                '<img src="' + response.profile_picture + '" alt="Profile Picture" style="width: 20px; height: 20px; border-radius: 50%; margin-right: 5px;">' +
                                '</a>' +
                                response.sender +
                                '</strong> (' + response.timestamp + '): ' +
                                response.message +
                                '</p>' +
                                '</div>'
                            );
                            $('textarea[name="message"]').val('');
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error(xhr.responseText);
                    }
                });
            });

            $('#closeConversation').on('click', function() {
                window.location.href = 'messagerie.php';
            });

            $('#closeGroupConversation').on('click', function() {
                window.location.href = 'messagerie.php';
            });

            $('#createGroupButton').on('click', function() {
                $('#createGroupForm').toggle();
            });
        });
    </script>
</head>
<body>
<div class="wrapper">
    <div class="header">
        <img src="logoPiscine.png" alt="ECE Paris Logo">
        <h1>Messagerie</h1>
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

    <!-- Navigation sticky -->
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
        <a href="messagerie.php" class="navitem navcurrent">Messagerie</a>
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
        <div class="sectionmess">
            <div class="blocprincipalconversation">
                <div class="bloconv">
                    <div class="MP">
                        <h2>Vos Conversations</h2>
                        <ul>
                            <?php foreach ($friends as $friend): ?>
                                <li>
                                    <a href="messagerie.php?user=<?php echo htmlspecialchars($friend['username']); ?>" class="user-link">
                                        <div class="profilconv"><img src="<?php echo htmlspecialchars($friend['profile_picture'] ?? 'default.jpg'); ?>" alt="Profile Picture" style="width: 30px; height: 30px; border-radius: 50%; margin-right: 10px;">
                                            <p class="nomprofilconv"><?php echo htmlspecialchars($friend['username']); ?></p></div>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <div class="groupes">
                        <h2>Vos Groupes</h2>
                        <button id="createGroupButton">Créer un groupe</button>
                        <form id="createGroupForm" action="create_group.php" method="POST">
                            <label for="group_name">Nom du groupe:</label>
                            <input type="text" id="group_name" name="group_name" required><br>
                            <label for="members">Ajouter des membres:</label>
                            <select id="members" name="members[]" multiple required>
                                <?php foreach ($friends as $friend): ?>
                                    <option value="<?php echo htmlspecialchars($friend['username']); ?>"><?php echo htmlspecialchars($friend['username']); ?></option>
                                <?php endforeach; ?>
                            </select><br>
                            <button type="submit">Créer</button>
                        </form>
                        <ul>
                            <?php foreach ($groups as $group): ?>
                                <li>
                                    <a href="messagerie.php?group=<?php echo htmlspecialchars($group['id']); ?>" class="group-link">
                                        <?php echo htmlspecialchars($group['group_name']); ?>
                                    </a>
                                </li><br>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
                <div id="conversation-section">
                    <?php if (isset($_GET['user'])): ?>
                        <?php
                        $otherUser = $_GET['user'];
                        if (!in_array($otherUser, array_column($friends, 'username'))) {
                            die("Vous ne pouvez afficher la conversation qu'avec vos amis.");
                        }
                        $sql = "SELECT messages.*, users.profile_picture 
                                    FROM messages 
                                    JOIN users ON messages.sender = users.username 
                                    WHERE (sender='$current_user' AND recipient='$otherUser') OR (sender='$otherUser' AND recipient='$current_user') 
                                    ORDER BY timestamp ASC";
                        $result = $conn->query($sql);
                        ?>

                        <h2>Conversation avec <?php echo htmlspecialchars($otherUser); ?></h2>
                        <div class="conversation" id="conversation">
                            <?php if ($result && $result->num_rows > 0): ?>
                                <?php while ($row = $result->fetch_assoc()): ?>
                                    <div class="message">
                                        <p>
                                            <strong>
                                                <a href="user_profile.php?username=<?php echo htmlspecialchars($row['sender']); ?>">
                                                    <img src="<?php echo htmlspecialchars($row['profile_picture'] ?? 'default.jpg'); ?>" alt="Profile Picture" style="width: 20px; height: 20px; border-radius: 50%; margin-right: 5px;">
                                                </a>
                                                <?php echo htmlspecialchars($row['sender']); ?>
                                            </strong> (<?php echo $row['timestamp']; ?>):
                                            <?php echo htmlspecialchars($row['message']); ?>
                                        </p>
                                    </div>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </div>
                        <div class="envoyermessages">
                            <form id="messageForm" class="message-form">
                                <input type="hidden" name="recipient" value="<?php echo htmlspecialchars($otherUser); ?>">
                                <textarea name="message" placeholder="Entrez votre message" required></textarea>
                                <button type="submit" class="boutonenvoi"><img src="envoi.png" alt="envoimess"></button>
                            </form>

                            <button id="closeConversation">Fermer la conversation</button>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($_GET['group'])): ?>
                        <?php
                        $group_id = $_GET['group'];
                        $sql = "SELECT * FROM group_chats WHERE id='$group_id'";
                        $group_info_result = $conn->query($sql);
                        $group_info = $group_info_result->fetch_assoc();

                        $sql = "SELECT group_members.username, users.profile_picture 
                                    FROM group_members 
                                    JOIN users ON group_members.username = users.username 
                                    WHERE group_id='$group_id'";
                        $members_result = $conn->query($sql);

                        $sql = "SELECT group_messages.*, users.profile_picture 
                                    FROM group_messages 
                                    JOIN users ON group_messages.sender = users.username 
                                    WHERE group_messages.group_id='$group_id' 
                                    ORDER BY timestamp ASC";
                        $result = $conn->query($sql);
                        ?>

                        <h2>Conversation du groupe: <?php echo htmlspecialchars($group_info['group_name']); ?></h2>
                        <div class="Membressss">
                            <button class="tablinks" onclick="openTab(event, 'membress')">Afficher les membres</button>
                            <button class="tablinks" onclick="openTab(event, 'fermerr')">Fermer</button>
                        </div>
                        <div id="membress" class="tabcontent">
                            <h3>Membres de ce groupe:</h3>
                            <ul>
                                <?php while ($member = $members_result->fetch_assoc()): ?>
                                    <li>
                                        <img src="<?php echo htmlspecialchars($member['profile_picture'] ?? 'default.jpg'); ?>" alt="Profile Picture" style="width: 20px; height: 20px; border-radius: 50%; margin-right: 5px;">
                                        <?php echo htmlspecialchars($member['username']); ?>
                                    </li>
                                <?php endwhile; ?>
                            </ul>
                        </div>
                        <div id="fermerr" class="tabcontent">
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
                        <div class="conversation" id="group-conversation">
                            <?php if ($result && $result->num_rows > 0): ?>
                                <?php while ($row = $result->fetch_assoc()): ?>
                                    <div class="message">
                                        <p>
                                            <strong>
                                                <a href="user_profile.php?username=<?php echo htmlspecialchars($row['sender']); ?>">
                                                    <img src="<?php echo htmlspecialchars($row['profile_picture'] ?? 'default.jpg'); ?>" alt="Profile Picture" style="width: 20px; height: 20px; border-radius: 50%; margin-right: 5px;">
                                                </a>
                                                <?php echo htmlspecialchars($row['sender']); ?>
                                            </strong> (<?php echo $row['timestamp']; ?>):
                                            <?php echo htmlspecialchars($row['message']); ?>
                                        </p>
                                    </div>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </div>
                        <div class="envoyermessages">
                            <form id="groupMessageForm" class="message-form">
                                <input type="hidden" name="group_id" value="<?php echo htmlspecialchars($group_id); ?>">
                                <textarea name="message" placeholder="Entrez votre message" required></textarea>
                                <button type="submit" class="boutonenvoi"><img src="envoi.png" alt="envoimess"></button>
                            </form>
                            <button id="closeGroupConversation">Fermer la conversation</button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <script>
                function loadMessages() {
                    if (window.location.search.includes('user=')) {
                        var user = new URLSearchParams(window.location.search).get('user');
                        $.ajax({
                            url: 'check_messages.php',
                            type: 'GET',
                            data: { user: user },
                            success: function(response) {
                                $('#conversation').html(response);
                            }
                        });
                    } else if (window.location.search.includes('group=')) {
                        var group = new URLSearchParams(window.location.search).get('group');
                        $.ajax({
                            url: 'check_group_messages.php',
                            type: 'GET',
                            data: { group: group },
                            success: function(response) {
                                $('#group-conversation').html(response);
                            }
                        });
                    }
                }

                function fetchNotifications() {
                    $.ajax({
                        url: 'fetch_notifications.php',
                        method: 'GET',
                        success: function(response) {
                            const notifications = JSON.parse(response);
                            const notificationCount = notifications.length;
                            $('#notif-count').text(notificationCount); // <- ligne correcte
                        }
                    });
                }

                $(document).ready(function() {
                    setInterval(loadMessages, 1000);
                    setInterval(fetchNotifications, 1000);
                });
            </script>
        </div>
    </div>
    <div class="footer">
        <p>&copy; 2025 ECE HUB. Tous droits réservés.</p>
    </div>
</div>
</body>
</html>
