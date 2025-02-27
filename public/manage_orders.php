<?php
session_start();
$message = $_GET['message'] ?? null;
require_once '../config/db.php';
require_once '../src/Database.php';

// Проверка роли администратора
if (empty($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}

$db = new Database($dbConfig);
$connection = $db->getConnection();

// Получение всех заказов из базы данных
$query = "
    SELECT o.id, u.name AS customer_name, o.total_price, o.status, o.created_at 
    FROM orders o
    JOIN users u ON o.user_id = u.id
    ORDER BY o.created_at DESC
";
$stmt = $connection->query($query);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление заказами</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <div class="container mt-5">
        <h1>Управление заказами</h1>
        
        <?php if ($message === 'success'): ?>
            <div class="alert alert-success">Заказ успешно удален.</div>
        <?php elseif ($message === 'error'): ?>
            <div class="alert alert-danger">Произошла ошибка при удалении заказа.</div>
        <?php endif; ?>

        <table class="table table-striped table-hover mt-4">
            <thead>
                <tr>
                    <th>№ заказа</th>
                    <th>Покупатель</th>
                    <th>Дата</th>
                    <th>Сумма</th>
                    <th>Статус</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $order): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($order['id']); ?></td>
                        <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                        <td><?php echo htmlspecialchars($order['created_at']); ?></td>
                        <td><?php echo htmlspecialchars($order['total_price']); ?> руб.</td>
                        <td><?php echo htmlspecialchars($order['status']); ?></td>
                        <td>
                            <a href="view_order.php?id=<?php echo $order['id']; ?>" class="btn btn-info btn-sm">Подробнее</a>
                            <!-- <a href="edit_order.php?id=<?php echo $order['id']; ?>" class="btn btn-warning btn-sm">Редактировать</a> -->
                            <a href="delete_order.php?id=<?php echo $order['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Вы уверены, что хотите удалить этот заказ?');">Удалить</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
