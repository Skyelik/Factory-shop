<?php
session_start();
require_once '../config/db.php';
require_once '../src/Database.php';

// Проверка авторизации и роли администратора
if (!isset($_SESSION['user']['role']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$db = new Database($dbConfig);
$connection = $db->getConnection();

// Получение списка продуктов
$query = "SELECT id, name, price, stock, category FROM products ORDER BY created_at DESC";
$stmt = $connection->prepare($query);
$stmt->execute();
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Управление продуктами</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<?php include 'includes/header.php'; ?>
<div class="container py-5">
    <h1 class="mb-4">Управление продуктами</h1>
    <a href="add_product.php" class="btn btn-success mb-3">Добавить товар</a>

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>ID</th>
                <th>Название</th>
                <th>Цена</th>
                <th>В наличии</th>
                <th>Категория</th>
                <th>Действия</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($products as $product): ?>
                <tr>
                    <td><?php echo htmlspecialchars($product['id']); ?></td>
                    <td><?php echo htmlspecialchars($product['name']); ?></td>
                    <td><?php echo htmlspecialchars($product['price']); ?> ₽</td>
                    <td><?php echo htmlspecialchars($product['stock']); ?></td>
                    <td><?php echo htmlspecialchars($product['category']); ?></td>
                    <td>
                        <a href="edit_product.php?id=<?php echo $product['id']; ?>" class="btn btn-primary btn-sm">Редактировать</a>
                        <a href="delete_product.php?id=<?php echo $product['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Вы уверены, что хотите удалить этот товар?');">Удалить</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php include 'includes/footer.php'; ?>
</body>
</html>
