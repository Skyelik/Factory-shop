<?php
session_start();
require_once '../config/db.php';
require_once '../src/Database.php';

$db = new Database($dbConfig);
$connection = $db->getConnection();

// Проверка авторизации
if (!isset($_SESSION['user']['id'])) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user']['id'];

// Получение истории заказов
$query = "
    SELECT id, total_price, status, created_at 
    FROM orders 
    WHERE user_id = :user_id 
    ORDER BY created_at DESC
";
$stmt = $connection->prepare($query);
$stmt->execute([':user_id' => $userId]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>История заказов</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<?php include 'includes/header.php'; ?>
<div class="container py-5">
    <h1 class="text-center mb-4">История заказов</h1>
    <?php if (!empty($orders)): ?>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Дата</th>
                    <th>Статус</th>
                    <th>Общая сумма</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $order): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($order['id']); ?></td>
                        <td><?php echo htmlspecialchars($order['created_at']); ?></td>
                        <td><?php echo htmlspecialchars($order['status']); ?></td>
                        <td><?php echo number_format($order['total_price'], 2, ',', ' '); ?> руб.</td>
                        <td>
                            <a href="order_details.php?order_id=<?php echo $order['id']; ?>" class="btn btn-sm btn-primary">Подробнее</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p class="text-center">Вы еще не оформили ни одного заказа.</p>
    <?php endif; ?>
</div>
<?php include 'includes/footer.php'; ?>
</body>
