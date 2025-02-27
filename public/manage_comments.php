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

// Получение всех комментариев из базы данных
$query = "
    SELECT c.id, c.text, c.created_at, p.name AS product_name, u.name AS user_name
    FROM comments c
    JOIN products p ON c.product_id = p.id
    JOIN users u ON c.user_id = u.id
    ORDER BY c.created_at DESC
";
$stmt = $connection->query($query);
$comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Обработка удаления комментария
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $deleteId = $_POST['delete_id'];

    $deleteQuery = "DELETE FROM comments WHERE id = :id";
    $stmtDelete = $connection->prepare($deleteQuery);
    $stmtDelete->bindValue(':id', $deleteId, PDO::PARAM_INT);
    $stmtDelete->execute();

    // Перезагрузка страницы для обновления списка
    header('Location: manage_comments.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление отзывами</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <div class="container mt-5">
        <h1>Управление отзывами</h1>
        <table class="table table-striped table-hover mt-4">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Товар</th>
                    <th>Пользователь</th>
                    <th>Текст</th>
                    <th>Дата</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($comments as $comment): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($comment['id']); ?></td>
                        <td><?php echo htmlspecialchars($comment['product_name']); ?></td>
                        <td><?php echo htmlspecialchars($comment['user_name']); ?></td>
                        <td><?php echo htmlspecialchars($comment['text']); ?></td>
                        <td><?php echo htmlspecialchars($comment['created_at']); ?></td>
                        <td>
                            <form method="POST" style="display: inline-block;">
                                <input type="hidden" name="delete_id" value="<?php echo $comment['id']; ?>">
                                <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Вы уверены, что хотите удалить этот отзыв?');">
                                    Удалить
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
