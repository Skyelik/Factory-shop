<?php
session_start();
require_once '../config/db.php';
require_once '../src/Database.php';

// Проверка роли администратора
if (empty($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}

$db = new Database($dbConfig);
$connection = $db->getConnection();

// Получение всех пользователей, кроме администраторов
$query = "
    SELECT id, username, name, email, role, created_at 
    FROM users 
    WHERE role != 'admin'
    ORDER BY created_at DESC
";
$stmt = $connection->query($query);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление пользователями</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <div class="container mt-5">
        <h1>Управление пользователями</h1>
        <table class="table table-striped table-hover mt-4">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Логин</th>
                    <th>Имя</th>
                    <th>Email</th>
                    <th>Роль</th>
                    <th>Дата создания</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($user['id']); ?></td>
                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                        <td><?php echo htmlspecialchars($user['name']); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td><?php echo htmlspecialchars($user['role']); ?></td>
                        <td><?php echo htmlspecialchars($user['created_at']); ?></td>
                        <td>
                            <a href="delete_user.php?id=<?php echo $user['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Вы уверены, что хотите удалить этого пользователя?');">Удалить</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
