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

// Проверка наличия параметра заказа
if (!isset($_GET['order_id'])) {
    header('Location: order_history.php');
    exit;
}

$orderId = (int)$_GET['order_id'];
$userId = $_SESSION['user']['id'];

// Получение информации о заказе
$orderQuery = "
    SELECT id, total_price, status, created_at
    FROM orders
    WHERE id = :order_id AND user_id = :user_id
";
$orderStmt = $connection->prepare($orderQuery);
$orderStmt->execute([':order_id' => $orderId, ':user_id' => $userId]);
$order = $orderStmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    header('Location: order_history.php');
    exit;
}

// Получение деталей товаров заказа
$orderItemsQuery = "
    SELECT oi.product_id, oi.quantity, oi.price, p.name
    FROM order_items oi
    INNER JOIN products p ON oi.product_id = p.id
    WHERE oi.order_id = :order_id
";
$orderItemsStmt = $connection->prepare($orderItemsQuery);
$orderItemsStmt->execute([':order_id' => $orderId]);
$orderItems = $orderItemsStmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Детали заказа</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<?php include 'includes/header.php'; ?>
<div class="container py-5">
    <h1 class="text-center mb-4">Детали заказа #<?php echo htmlspecialchars($order['id']); ?></h1>
    <p><strong>Дата:</strong> <?php echo htmlspecialchars($order['created_at']); ?></p>
    <p><strong>Статус:</strong> <?php echo htmlspecialchars($order['status']); ?></p>
    <p><strong>Общая сумма:</strong> <?php echo number_format($order['total_price'], 2, ',', ' '); ?> руб.</p>

    <h4>Товары в заказе:</h4>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Товар</th>
                <th>Количество</th>
                <th>Цена за единицу</th>
                <th>Итого</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($orderItems as $item): ?>
                <tr>
                    <td><?php echo htmlspecialchars($item['name']); ?></td>
                    <td><?php echo $item['quantity']; ?></td>
                    <td><?php echo number_format($item['price'], 2, ',', ' '); ?> руб.</td>
                    <td><?php echo number_format($item['price'] * $item['quantity'], 2, ',', ' '); ?> руб.</td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <a href="order_history.php" class="btn btn-secondary mt-3">Вернуться к истории заказов</a>
</div>
<?php include 'includes/footer.php'; ?>
</body>
</html>
