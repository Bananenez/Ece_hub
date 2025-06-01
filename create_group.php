<?php
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: login.php");
    exit();
}

include 'db.php';

$current_user = $_SESSION['username'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $group_name = $conn->real_escape_string($_POST['group_name']);
    $members = $_POST['members'];

    // Start transaction
    $conn->begin_transaction();

    try {
        // Insert new group
        $sql = "INSERT INTO group_chats (group_name, created_by) VALUES ('$group_name','$current_user')";
        echo "cree";
        if ($conn->query($sql) === TRUE) {
            $group_id = $conn->insert_id;

            // Insert current user as a member
            $sql = "INSERT INTO group_members (group_id, username) VALUES ('$group_id', '$current_user')";
            $conn->query($sql);

            // Insert other members
            foreach ($members as $member) {
                $member = $conn->real_escape_string($member);
                $sql = "INSERT INTO group_members (group_id, username) VALUES ('$group_id', '$member')";
                $conn->query($sql);
            }

            // Commit transaction
            $conn->commit();
            header("Location: messagerie.php");
            exit();
        } else {
            throw new Exception("Erreur lors de la création du groupe: " . $conn->error);
        }
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        echo $e->getMessage();
    }
}

$conn->close();
?>
