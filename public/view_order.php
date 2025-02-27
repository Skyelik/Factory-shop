<?php
session_start();
require_once '../config/db.php';
require_once '../src/Database.php';

// Проверка роли администратора
if (empty($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}

// Проверяем, передан ли ID заказа
$orderId = $_GET['id'] ?? null;
if (!$orderId) {
    header('Location: manage_orders.php');
    exit;
}

$db = new Database($dbConfig);
$connection = $db->getConnection();

// Получаем информацию о заказе
$queryOrder = "
    SELECT o.id, u.name AS customer_name, u.email, o.total_price, o.status, o.created_at 
    FROM orders o
    JOIN users u ON o.user_id = u.id
    WHERE o.id = :id
";
$stmtOrder = $connection->prepare($queryOrder);
$stmtOrder->bindValue(':id', $orderId, PDO::PARAM_INT);
$stmtOrder->execute();
$order = $stmtOrder->fetch(PDO::FETCH_ASSOC);

// Проверяем, существует ли заказ
if (!$order) {
    header('Location: manage_orders.php');
    exit;
}

// Обработка изменения статуса заказа
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['status'])) {
    $newStatus = $_POST['status'];

    // Обновляем статус в базе данных
    $updateQuery = "UPDATE orders SET status = :status WHERE id = :id";
    $stmtUpdate = $connection->prepare($updateQuery);
    $stmtUpdate->bindValue(':status', $newStatus, PDO::PARAM_STR);
    $stmtUpdate->bindValue(':id', $orderId, PDO::PARAM_INT);
    $stmtUpdate->execute();

    // Перезагрузка страницы, чтобы отобразить обновленные данные
    header("Location: view_order.php?id=$orderId");
    exit;
}

// Получаем список товаров для данного заказа
$queryItems = "
    SELECT p.name, oi.quantity, oi.price 
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    WHERE oi.order_id = :order_id
";
$stmtItems = $connection->prepare($queryItems);
$stmtItems->bindValue(':order_id', $orderId, PDO::PARAM_INT);
$stmtItems->execute();
$items = $stmtItems->fetchAll(PDO::FETCH_ASSOC);
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
    <div class="container mt-5">
        <h1>Детали заказа №<?php echo htmlspecialchars($order['id']); ?></h1>
        <p><strong>Покупатель:</strong> <?php echo htmlspecialchars($order['customer_name']); ?></p>
        <p><strong>Email:</strong> <?php echo htmlspecialchars($order['email']); ?></p>
        <p><strong>Дата заказа:</strong> <?php echo htmlspecialchars($order['created_at']); ?></p>
        <p><strong>Общая сумма:</strong> <?php echo htmlspecialchars($order['total_price']); ?> руб.</p>
        
        <!-- <form method="POST" class="mt-3">
            <div class="mb-3">
                <label for="status" class="form-label"><strong>Статус:</strong></label>
                <select name="status" id="status" class="form-select">
                    <option value="Новый" <?php echo $order['status'] === 'Новый' ? 'selected' : ''; ?>>Новый</option>
                    <option value="В обработке" <?php echo $order['status'] === 'В обработке' ? 'selected' : ''; ?>>В обработке</option>
                    <option value="Доставлен" <?php echo $order['status'] === 'Доставлен' ? 'selected' : ''; ?>>Доставлен</option>
                    <option value="Отменен" <?php echo $order['status'] === 'Отменен' ? 'selected' : ''; ?>>Отменен</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Сохранить изменения</button>
        </form> -->

        <form method="POST" class="mt-3">
            <div class="mb-3">
                <label for="status" class="form-label"><strong>Статус:</strong></label>
                <select name="status" id="status" class="form-select">
                    <option value="processing" <?php echo $order['status'] === 'pending' ? 'selected' : ''; ?>>В обработке</option>
                    <option value="completed" <?php echo $order['status'] === 'completed' ? 'selected' : ''; ?>>Доставлен</option>
                    <option value="cancelled" <?php echo $order['status'] === 'cancelled' ? 'selected' : ''; ?>>Отменен</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Сохранить изменения</button>
        </form>

        <h3>Товары:</h3>
        <table class="table table-bordered mt-3">
            <thead>
                <tr>
                    <th>Название</th>
                    <th>Количество</th>
                    <th>Цена за единицу</th>
                    <th>Сумма</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['name']); ?></td>
                        <td><?php echo htmlspecialchars($item['quantity']); ?></td>
                        <td><?php echo htmlspecialchars($item['price']); ?> руб.</td>
                        <td><?php echo htmlspecialchars($item['quantity'] * $item['price']); ?> руб.</td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <a href="manage_orders.php" class="btn btn-secondary mt-3">Назад</a>
    </div>
</body>
</html>
