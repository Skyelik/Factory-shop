<?php
session_start();
require_once '../config/db.php';
require_once '../src/Database.php';

$db = new Database($dbConfig);
$connection = $db->getConnection();

// Проверка авторизации пользователя
if (!isset($_SESSION['user']['id'])) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user']['id'];

// Получение товаров из корзины пользователя
$query = "
    SELECT c.id AS cart_id, p.id AS product_id, p.name, p.price, c.quantity, p.image_path
    FROM carts c
    INNER JOIN products p ON c.product_id = p.id
    WHERE c.user_id = :user_id
";

$stmt = $connection->prepare($query);
$stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
$stmt->execute();
$cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);


// Обновление количества товара
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_quantity'])) {
    $cartId = $_POST['cart_id'];
    $quantity = max(1, (int)$_POST['quantity']);

    $updateQuery = "UPDATE cart SET quantity = :quantity WHERE id = :cart_id AND user_id = :user_id";
    $updateStmt = $connection->prepare($updateQuery);
    $updateStmt->bindValue(':quantity', $quantity, PDO::PARAM_INT);
    $updateStmt->bindValue(':cart_id', $cartId, PDO::PARAM_INT);
    $updateStmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
    $updateStmt->execute();

    header('Location: cart.php');
    exit;
}

// Удаление товара из корзины
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_item'])) {
    $cartId = $_POST['cart_id'];

    $deleteQuery = "DELETE FROM carts WHERE id = :cart_id AND user_id = :user_id";
    $deleteStmt = $connection->prepare($deleteQuery);
    $deleteStmt->bindValue(':cart_id', $cartId, PDO::PARAM_INT);
    $deleteStmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
    $deleteStmt->execute();

    header('Location: cart.php');
    exit;
}

// Общая стоимость
$totalCost = array_reduce($cartItems, function ($sum, $item) {
    return $sum + ($item['price'] * $item['quantity']);
}, 0);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Корзина</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<?php include 'includes/header.php'; ?>
<div class="container py-5">
    <h1 class="text-center mb-4">Моя корзина</h1>
    <?php if (!empty($cartItems)): ?>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Товар</th>
                    <th>Цена</th>
                    <th>Количество</th>
                    <th>Всего</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($cartItems as $item): ?>
                    <tr>
                        <td>
                            <img src="<?php echo htmlspecialchars($item['image_path']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" style="width: 50px; height: 50px;">
                            <?php echo htmlspecialchars($item['name']); ?>
                        </td>
                        <td><?php echo number_format($item['price'], 2, ',', ' '); ?> руб.</td>
                        <td>
                            <form method="POST" class="d-inline-block update-quantity-form">
                                <input type="hidden" name="cart_id" value="<?php echo $item['cart_id']; ?>">
                                <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" 
                                        class="form-control quantity-input" style="width: 70px;" min="1">
                            </form>
                        </td>
                        <td><?php echo number_format($item['price'] * $item['quantity'], 2, ',', ' '); ?> руб.</td>
                        <td>
                            <form method="POST">
                                <input type="hidden" name="cart_id" value="<?php echo $item['cart_id']; ?>">
                                <button type="submit" name="remove_item" class="btn btn-sm btn-danger">Удалить</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <div class="text-end">
            <h4>Итого: <?php echo number_format($totalCost, 2, ',', ' '); ?> руб.</h4>
            <a href="checkout.php" class="btn btn-success">Оформить заказ</a>
        </div>
    <?php else: ?>
        <p class="text-center">Ваша корзина пуста.</p>
    <?php endif; ?>
</div>
<?php include 'includes/footer.php'; ?>


<script>
    document.querySelectorAll('.quantity-input').forEach(input => {
        input.addEventListener('change', function () {
            const form = this.closest('.update-quantity-form');
            const formData = new FormData(form);

            fetch('update_cart_quantity.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Обновляем корзину на странице
                    location.reload(); // Перезагружаем страницу, чтобы отобразить новые данные
                } else {
                    alert(data.message || 'Ошибка при обновлении количества товара');
                }
            })
            .catch(error => {
                console.error('Ошибка:', error);
                alert('Произошла ошибка при обновлении количества');
            });
        });
    });
</script>

</body>
</html>
