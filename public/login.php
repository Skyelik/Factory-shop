<?php
session_start(); // Стартуем сессию для хранения данных пользователя
require_once '../config/db.php';
require_once '../src/Database.php';

$db = new Database($dbConfig);
$connection = $db->getConnection();

$error = ''; // Переменная для хранения сообщений об ошибках

// Обработка формы
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    // Проверка email в базе данных
    $query = "SELECT * FROM users WHERE email = :email";
    $stmt = $connection->prepare($query);
    $stmt->bindValue(':email', $email, PDO::PARAM_STR);
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        // Сохраняем данные пользователя в сессии
        $_SESSION['user'] = [
            'id' => $user['id'],
            'username' => $user['username'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role'],
            'avatar' => $user['avatar'],
        ];

        // Перенаправление в зависимости от роли
        if ($user['role'] === 'admin') {
            header('Location: admin_dashboard.php'); // Перенаправление для администратора
        } else {
            header('Location: index.php'); // Перенаправление для обычного пользователя
        }
        exit;
    } else {
        $error = 'Неверный email или пароль.';
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Авторизация</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php 
    include 'includes/header.php'; // Подключаем заголовок
    ?>
    <div class="container py-5">
        <h1 class="text-center">Авторизация</h1>
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <form method="POST">
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" id="email" name="email" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Пароль</label>
                <input type="password" id="password" name="password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary">Войти</button>
        </form>
    </div>
</body>
</html>
