<?php
session_start();
require_once '../config/db.php';
require_once '../src/Database.php';

// Проверка роли администратора
if (empty($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}

// Проверка наличия параметра id
$userId = $_GET['id'] ?? null;
if (!$userId) {
    header('Location: manage_users.php');
    exit;
}

$db = new Database($dbConfig);
$connection = $db->getConnection();

try {
    // Проверяем роль пользователя перед удалением
    $checkRoleQuery = "SELECT role FROM users WHERE id = :id";
    $stmtRole = $connection->prepare($checkRoleQuery);
    $stmtRole->bindValue(':id', $userId, PDO::PARAM_INT);
    $stmtRole->execute();
    $user = $stmtRole->fetch(PDO::FETCH_ASSOC);

    // Если пользователь не найден или его роль - admin, отменяем удаление
    if (!$user || $user['role'] === 'admin') {
        $_SESSION['error_message'] = 'Нельзя удалить администратора.';
        header('Location: manage_users.php');
        exit;
    }

    // Удаление связанных данных и пользователя
    $connection->beginTransaction();

    $deleteCommentsQuery = "DELETE FROM comments WHERE user_id = :user_id";
    $stmtComments = $connection->prepare($deleteCommentsQuery);
    $stmtComments->bindValue(':user_id', $userId, PDO::PARAM_INT);
    $stmtComments->execute();

    $deleteCartQuery = "DELETE FROM cart WHERE user_id = :user_id";
    $stmtCart = $connection->prepare($deleteCartQuery);
    $stmtCart->bindValue(':user_id', $userId, PDO::PARAM_INT);
    $stmtCart->execute();

    $deleteOrdersQuery = "DELETE FROM orders WHERE user_id = :user_id";
    $stmtOrders = $connection->prepare($deleteOrdersQuery);
    $stmtOrders->bindValue(':user_id', $userId, PDO::PARAM_INT);
    $stmtOrders->execute();

    $deleteUserQuery = "DELETE FROM users WHERE id = :id";
    $stmtUser = $connection->prepare($deleteUserQuery);
    $stmtUser->bindValue(':id', $userId, PDO::PARAM_INT);
    $stmtUser->execute();

    $connection->commit();

    $_SESSION['success_message'] = 'Пользователь успешно удалён.';
    header('Location: manage_users.php');
    exit;
} catch (Exception $e) {
    $connection->rollBack();
    $_SESSION['error_message'] = 'Ошибка при удалении пользователя: ' . $e->getMessage();
    header('Location: manage_users.php');
    exit;
}
?>
