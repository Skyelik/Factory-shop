<?php
require_once '../config/db.php';
require_once '../src/Database.php';

$db = new Database($dbConfig);
$connection = $db->getConnection();

// Обработка формы
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $avatar = 'uploads/noname.png'; // Стандартный аватар
    $role = 'user';
    $created_at = date('Y-m-d H:i:s');

    // Проверка уникальности email
    $checkQuery = "SELECT id FROM users WHERE email = :email";
    $stmt = $connection->prepare($checkQuery);
    $stmt->bindValue(':email', $email, PDO::PARAM_STR);
    $stmt->execute();

    if ($stmt->fetch()) {
        $error = 'Пользователь с таким email уже зарегистрирован.';
    } else {
        // Хэширование пароля
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

        // Вставка данных в базу
        $insertQuery = "
            INSERT INTO users (username, name, email, avatar, password, role, created_at)
            VALUES (:username, :name, :email, :avatar, :password, :role, :created_at)
        ";
        $stmt = $connection->prepare($insertQuery);
        $stmt->bindValue(':username', $username, PDO::PARAM_STR);
        $stmt->bindValue(':name', $name, PDO::PARAM_STR);
        $stmt->bindValue(':email', $email, PDO::PARAM_STR);
        $stmt->bindValue(':avatar', $avatar, PDO::PARAM_STR);
        $stmt->bindValue(':password', $hashedPassword, PDO::PARAM_STR);
        $stmt->bindValue(':role', $role, PDO::PARAM_STR);
        $stmt->bindValue(':created_at', $created_at, PDO::PARAM_STR);

        if ($stmt->execute()) {
            $success = 'Регистрация прошла успешно. Вы можете войти в систему.';
        } else {
            $error = 'Произошла ошибка при регистрации. Попробуйте еще раз.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Регистрация</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php 
    include 'includes/header.php'
    ?>
    <div class="container py-5">
        <h1 class="text-center">Регистрация</h1>
        <?php if (!empty($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php elseif (!empty($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>
        <form method="POST">
            <div class="mb-3">
                <label for="username" class="form-label">Имя пользователя</label>
                <input type="text" id="username" name="username" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="name" class="form-label">Имя</label>
                <input type="text" id="name" name="name" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">Email</label>
                <input type="email" id="email" name="email" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Пароль</label>
                <input type="password" id="password" name="password" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary">Зарегистрироваться</button>
        </form>
    </div>
</body>
</html>
