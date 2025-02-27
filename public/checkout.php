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

// Получение данных из корзины
$query = "
    SELECT c.product_id, c.quantity, p.price, p.name
    FROM carts c
    INNER JOIN products p ON c.product_id = p.id
    WHERE c.user_id = :user_id
";
$stmt = $connection->prepare($query);
$stmt->execute([':user_id' => $userId]);
$cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Вычисление общей стоимости
$totalCost = 0;
foreach ($cartItems as $item) {
    $totalCost += $item['price'] * $item['quantity'];
}

// Инициализация переменных
$name = $phone = $email = $address = '';
$errors = [];

// Обработка формы
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);
    $address = trim($_POST['address']);

    // Проверка ввода
    if (empty($name)) {
        $errors[] = "Имя обязательно.";
    }
    if (!preg_match('/^\+?[0-9\s\-()]{7,20}$/', $phone)) {
        $errors[] = "Введите корректный номер телефона.";
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Введите корректный email.";
    }
    if (empty($address)) {
        $errors[] = "Адрес обязателен.";
    }

    
    

    if (empty($errors)) {
        try {
            $connection->beginTransaction();
    
            // Создание записи в таблице orders
            $orderQuery = "
                INSERT INTO orders (user_id, total_price, status, created_at)
                VALUES (:user_id, :total_price, 'pending', NOW())
            ";
            $orderStmt = $connection->prepare($orderQuery);
            $orderStmt->execute([
                ':user_id' => $userId,
                ':total_price' => $totalCost,
            ]);
    
            // Получение ID созданного заказа
            $orderId = $connection->lastInsertId();
    
            // Запись товаров из корзины в таблицу order_items
            $orderItemsQuery = "
                INSERT INTO order_items (order_id, product_id, quantity, price)
                VALUES (:order_id, :product_id, :quantity, :price)
            ";
            $orderItemsStmt = $connection->prepare($orderItemsQuery);
    
            foreach ($cartItems as $item) {
                $orderItemsStmt->execute([
                    ':order_id' => $orderId,
                    ':product_id' => $item['product_id'],
                    ':quantity' => $item['quantity'],
                    ':price' => $item['price'],
                ]);
            }
    
            // Очистка корзины
            $deleteCartQuery = "DELETE FROM carts WHERE user_id = :user_id";
            $deleteCartStmt = $connection->prepare($deleteCartQuery);
            $deleteCartStmt->execute([':user_id' => $userId]);
    
            $connection->commit();
    
            // Показ сообщения об успешном заказе
            $_SESSION['order_success'] = "Ваш заказ успешно принят!";
            header('Location: index.php');
            exit;
        } catch (Exception $e) {
            $connection->rollBack();
            die("Ошибка оформления заказа: " . $e->getMessage());
        }
    }
    
    
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Оформление заказа</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<?php include 'includes/header.php'; ?>
<div class="container py-5">
    <h1 class="text-center mb-4">Оформление заказа</h1>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <?php foreach ($errors as $error): ?>
                <p><?php echo htmlspecialchars($error); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <h4>Ваш заказ:</h4>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Товар</th>
                <th>Количество</th>
                <th>Цена</th>
                <th>Итого</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($cartItems as $item): ?>
                <tr>
                    <td><?php echo htmlspecialchars($item['name']); ?></td>
                    <td><?php echo $item['quantity']; ?></td>
                    <td><?php echo number_format($item['price'], 2, ',', ' '); ?> руб.</td>
                    <td><?php echo number_format($item['price'] * $item['quantity'], 2, ',', ' '); ?> руб.</td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <h5 class="text-end">Общая стоимость: <?php echo number_format($totalCost, 2, ',', ' '); ?> руб.</h5>

    <form method="POST" class="mt-4">
        <div class="mb-3">
            <label for="name" class="form-label">Имя</label>
            <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($name); ?>" required>
        </div>
        <div class="mb-3">
            <label for="phone" class="form-label">Телефон</label>
            <input type="text" class="form-control" id="phone" name="phone" value="<?php echo htmlspecialchars($phone); ?>" required>
        </div>
        <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
        </div>
        <div class="mb-3">
            <label for="address" class="form-label">Адрес</label>
            <textarea class="form-control" id="address" name="address" rows="3" required><?php echo htmlspecialchars($address); ?></textarea>
        </div>
        <button type="submit" class="btn btn-primary">Подтвердить заказ</button>
    </form>
</div>
<?php include 'includes/footer.php'; ?>
</body>
</html>
