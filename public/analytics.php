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

$startDate = $_GET['start_date'] ?? '';
$endDate = $_GET['end_date'] ?? '';

$whereClause = "";
if (!empty($startDate) && !empty($endDate)) {
    $whereClause = " AND o.created_at BETWEEN :start_date AND :end_date";
}

// 1. Продажи по товарам
$query = "SELECT p.name, SUM(oi.quantity) as total_sold, SUM(oi.quantity * oi.price) as total_revenue 
          FROM order_items oi 
          JOIN products p ON oi.product_id = p.id 
          JOIN orders o ON oi.order_id = o.id 
          WHERE 1=1 $whereClause 
          GROUP BY p.name 
          ORDER BY total_sold DESC";
$statement = $connection->prepare($query);
if (!empty($whereClause)) {
    $statement->bindValue(':start_date', $startDate);
    $statement->bindValue(':end_date', $endDate);
}
$statement->execute();
$products = $statement->fetchAll(PDO::FETCH_ASSOC);

// 2. Оборот по категориям
$query = "SELECT p.category, SUM(oi.quantity) as total_sold, SUM(oi.quantity * oi.price) as total_revenue 
          FROM order_items oi 
          JOIN products p ON oi.product_id = p.id 
          JOIN orders o ON oi.order_id = o.id 
          WHERE 1=1 $whereClause 
          GROUP BY p.category 
          ORDER BY total_revenue DESC";
$statement = $connection->prepare($query);
if (!empty($whereClause)) {
    $statement->bindValue(':start_date', $startDate);
    $statement->bindValue(':end_date', $endDate);
}
$statement->execute();
$categories = $statement->fetchAll(PDO::FETCH_ASSOC);

// 3. Количество заказов по пользователям
$query = "SELECT u.username, COUNT(o.id) as total_orders, SUM(o.total_price) as total_spent 
          FROM orders o 
          JOIN users u ON o.user_id = u.id 
          WHERE 1=1 $whereClause 
          GROUP BY u.username 
          ORDER BY total_orders DESC";
$statement = $connection->prepare($query);
if (!empty($whereClause)) {
    $statement->bindValue(':start_date', $startDate);
    $statement->bindValue(':end_date', $endDate);
}
$statement->execute();
$users = $statement->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Аналитика</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
<?php include 'includes/header.php'; ?>
    <div class="container py-5">
        <h1 class="text-center text-primary">Аналитика продаж</h1>

        <form method="GET" class="mb-4 d-flex gap-3">
            <input type="date" name="start_date" class="form-control" value="<?= htmlspecialchars($startDate); ?>">
            <input type="date" name="end_date" class="form-control" value="<?= htmlspecialchars($endDate); ?>">
            <button type="submit" class="btn btn-primary">Применить</button>
            <a href="analytics.php" class="btn btn-danger">Сбросить</a>
        </form>

        <div class="row">
            <div class="col-md-6">
                <canvas id="salesChart"></canvas>
            </div>
            <div class="col-md-6">
                <table class="table table-bordered table-striped">
                    <thead class="table-dark">
                        <tr>
                            <th>Товар</th>
                            <th>Продано</th>
                            <th>Выручка</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $product): ?>
                            <tr>
                                <td><?= htmlspecialchars($product['name']); ?></td>
                                <td><?= $product['total_sold']; ?></td>
                                <td><?= number_format($product['total_revenue'], 2, ',', ' ') . ' руб.'; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <h3 class="mt-5">Оборот по категориям</h3>
        <div class="row">
            <div class="col-md-6">
                <canvas id="categoryChart"></canvas>
            </div>
            <div class="col-md-6">
                <table class="table table-bordered table-striped">
                    <thead class="table-dark">
                        <tr>
                            <th>Категория</th>
                            <th>Продано</th>
                            <th>Выручка</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $category): ?>
                            <tr>
                                <td><?= htmlspecialchars($category['category']); ?></td>
                                <td><?= $category['total_sold']; ?></td>
                                <td><?= number_format($category['total_revenue'], 2, ',', ' ') . ' руб.'; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <h3 class="mt-5">Количество заказов по пользователям</h3>
        <div class="row">
            <div class="col-md-6">
                <canvas id="userOrdersChart"></canvas>
            </div>
            <div class="col-md-6">
                <table class="table table-bordered table-striped">
                    <thead class="table-dark">
                        <tr>
                            <th>Пользователь</th>
                            <th>Заказов</th>
                            <th>Сумма покупок</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?= htmlspecialchars($user['username']); ?></td>
                                <td><?= $user['total_orders']; ?></td>
                                <td><?= number_format($user['total_spent'], 2, ',', ' ') . ' руб.'; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="mt-4">
            <a href="export_word.php?start_date=<?= urlencode($startDate) ?>&end_date=<?= urlencode($endDate) ?>" class="btn btn-success">Экспорт в Word</a>
            <a href="export_excel.php?start_date=<?= urlencode($startDate) ?>&end_date=<?= urlencode($endDate) ?>" class="btn btn-success">Экспорт в Excel</a>
        </div>

        <script>
            new Chart(document.getElementById('salesChart'), {
                type: 'bar',
                data: {
                    labels: <?= json_encode(array_column($products, 'name')); ?>,
                    datasets: [{ label: 'Продано', data: <?= json_encode(array_column($products, 'total_sold')); ?>, backgroundColor: 'rgba(54, 162, 235, 0.6)' }]
                }
            });

            new Chart(document.getElementById('categoryChart'), {
                type: 'bar',
                data: {
                    labels: <?= json_encode(array_column($categories, 'category')); ?>,
                    datasets: [{ label: 'Выручка', data: <?= json_encode(array_column($categories, 'total_revenue')); ?>, backgroundColor: 'rgba(255, 99, 132, 0.6)' }]
                }
            });

            new Chart(document.getElementById('userOrdersChart'), {
                type: 'bar',
                data: {
                    labels: <?= json_encode(array_column($users, 'username')); ?>,
                    datasets: [{ label: 'Заказов', data: <?= json_encode(array_column($users, 'total_orders')); ?>, backgroundColor: 'rgba(75, 192, 192, 0.6)' }]
                }
            });
        </script>
    </div>
</body>
</html>
