<?php
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

include 'db.php';

$username = $_SESSION['username'];

// Récupération de l'utilisateur complet
$user_sql = "SELECT * FROM users WHERE username='$username'";
$user_result = $conn->query($user_sql);
$user = $user_result->fetch_assoc();

$is_admin = $user['is_admin'];

// Ajouter un utilisateur
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_user'])) {
    $new_username = $_POST['new_username'];
    $new_mail = $_POST['new_mail'];
    $new_password = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
    $new_is_admin = isset($_POST['new_is_admin']) ? 1 : 0;
    $new_is_partner = isset($_POST['new_is_partner']) ? 1 : 0;

    $add_user_sql = "INSERT INTO users (username, email, password, is_admin, partenaire_ece, profile_picture) 
                     VALUES ('$new_username', '$new_mail', '$new_password', '$new_is_admin', '$new_is_partner', '')";
    $conn->query($add_user_sql);
}

// Supprimer un utilisateur
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_user'])) {
    $delete_username = $_POST['delete_username'];
    $delete_user_sql = "DELETE FROM users WHERE username='$delete_username'";
    $conn->query($delete_user_sql);
}

// Récupérer tous les utilisateurs
$users_sql = "SELECT * FROM users";
$users_result = $conn->query($users_sql);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Admin - ECE HUB</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>

<div class="wrapper">
    <!-- En-tête -->
    <div class="header">
        <img src="logoPiscine.png" alt="ECE Paris Logo">
        <h1>Tableau de bord Admin</h1>
        <img src="12.png" alt="ECE HUB Logo" class="ece-logo-header">
    </div>

    <!-- Menu horizontal sticky -->
    <div class="menu">
        <img src="<?php echo htmlspecialchars($user['profile_picture']); ?>" alt="Menu Icon" class="menu-icon" onclick="toggleDropdown()">
        <p class="nom-profil"><?php echo htmlspecialchars($username); ?></p>
        <div id="myDropdown" class="dropdown-content">
            <a href="profile.php">Vous</a>
            <?php if ($is_admin): ?>
                <a href="admin_dashboard.php">Tableau Admin</a>
            <?php endif; ?>
            <a href="logout.php">Déconnexion</a>
        </div>

    </div>

    <div class="top-navigation">
        <a href="index.php" class="navitem">Accueil</a>
        <a href="mon_reseau.php" class="navitem">Mon Réseau</a>
        <a href="notifications.php" class="navitem">Notifications</a>
        <a href="messagerie.php" class="navitem">Messagerie</a>
        <a href="fil_d_actualite.php" class="navitem">Fil d'actualité</a>
        <a href="emplois.php" class="navitem">Emplois</a>
        <?php if ($is_admin): ?>
            <a href="admin_dashboard.php" class="navitem navcurrent">Tableau Admin</a>
        <?php endif; ?>
    </div>

    <script>
        function toggleDropdown() {
            document.getElementById("myDropdown").classList.toggle("show");
        }

        window.onclick = function(event) {
            if (!event.target.matches('.menu-icon')) {
                var dropdowns = document.getElementsByClassName("dropdown-content");
                for (var i = 0; i < dropdowns.length; i++) {
                    if (dropdowns[i].classList.contains('show')) {
                        dropdowns[i].classList.remove('show');
                    }
                }
            }
        };
    </script>

    <!-- Contenu de la page -->
    <div class="section1">
        <div class="blocprincipal">
            <div class="blocadmin1">
                <h2>Ajouter un utilisateur</h2>
                <form method="POST" action="admin_dashboard.php">
                    <label for="new_username">Nom d'utilisateur</label>
                    <input type="text" id="new_username" name="new_username" required>

                    <label for="new_mail">Adresse mail</label>
                    <input type="text" id="new_mail" name="new_mail" required>

                    <label for="new_password">Mot de passe</label>
                    <input type="password" id="new_password" name="new_password" required>

                    <label><input type="checkbox" name="new_is_admin"> Administrateur</label>
                    <label><input type="checkbox" name="new_is_partner"> Partenaire ECE</label>

                    <button type="submit" name="add_user">Ajouter</button>
                </form>
            </div>
            <div class="blocadmin2">
                <h2>Liste des utilisateurs</h2>
                <table>
                    <tr>
                        <th>Nom d'utilisateur</th>
                        <th>Administrateur</th>
                        <th>Action</th>
                    </tr>
                    <?php while ($row = $users_result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($row['username']); ?></td>
                            <td><?php echo $row['is_admin'] ? 'Oui' : 'Non'; ?></td>
                            <td>
                                <form method="POST" action="admin_dashboard.php" style="display:inline-block;">
                                    <input type="hidden" name="delete_username" value="<?php echo htmlspecialchars($row['username']); ?>">
                                    <button type="submit" name="delete_user">Supprimer</button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </table>
            </div>
        </div>
    </div>

    <div class="footer">
        <p>&copy; 2025 ECE HUB. Tous droits réservés.</p>
    </div>
</div>

</body>
</html>
