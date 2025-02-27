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

// Получение текущих данных пользователя
$query = "SELECT name, email, avatar FROM users WHERE id = :id";
$stmt = $connection->prepare($query);
$stmt->execute([':id' => $userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Инициализация переменных
$name = $user['name'];
$email = $user['email'];
$avatar = $user['avatar'];
$errors = [];
$success = '';

// Обработка формы
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $avatarFile = $_FILES['avatar'];

    // Проверка данных
    if (empty($name)) {
        $errors[] = "Имя обязательно.";
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Введите корректный email.";
    }
    if (!empty($password) && strlen($password) < 6) {
        $errors[] = "Пароль должен содержать минимум 6 символов.";
    }

    // Загрузка аватара
    if (!empty($avatarFile['name'])) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        if (in_array($avatarFile['type'], $allowedTypes) && $avatarFile['size'] <= 2 * 1024 * 1024) {
            $uploadDir = __DIR__ . '/uploads/avatars/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true); // Создаем директорию, если её нет
            }

            $fileName = uniqid() . '_' . basename($avatarFile['name']);
            $avatarPath = $uploadDir . $fileName;

            if (move_uploaded_file($avatarFile['tmp_name'], $avatarPath)) {
                // Относительный путь для хранения в базе данных
                $avatar = 'uploads/avatars/' . $fileName;
            } else {
                $errors[] = "Ошибка загрузки аватара.";
            }
        } else {
            $errors[] = "Допустимые форматы: JPEG, PNG, GIF. Максимальный размер — 2 МБ.";
        }
    }   

    


    // Если нет ошибок, обновляем данные
    if (empty($errors)) {
        $updateQuery = "UPDATE users SET name = :name, email = :email, avatar = :avatar WHERE id = :id";
        $params = [
            ':name' => $name,
            ':email' => $email,
            ':avatar' => $avatar,
            ':id' => $userId,
        ];

        // Обновляем пароль, если он введен
        if (!empty($password)) {
            $updateQuery = "UPDATE users SET name = :name, email = :email, password = :password, avatar = :avatar WHERE id = :id";
            $params[':password'] = password_hash($password, PASSWORD_DEFAULT);
        }

        $stmt = $connection->prepare($updateQuery);
        $stmt->execute($params);

        $_SESSION['user']['name'] = $name;
        $_SESSION['user']['email'] = $email;
        $_SESSION['user']['avatar'] = $avatar;

        $success = "Данные успешно обновлены.";
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Настройки профиля</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<?php include 'includes/header.php'; ?>
<div class="container py-5">
    <h1 class="mb-4">Настройки профиля</h1>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <?php foreach ($errors as $error): ?>
                <p><?php echo htmlspecialchars($error); ?></p>
            <?php endforeach; ?>
        </div>
    <?php elseif (!empty($success)): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <div class="mb-3">
            <label for="name" class="form-label">Имя</label>
            <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($name); ?>" required>
        </div>
        <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
        </div>
        <div class="mb-3">
            <label for="password" class="form-label">Новый пароль</label>
            <input type="password" class="form-control" id="password" name="password">
            <small class="form-text text-muted">Оставьте пустым, если не хотите менять пароль.</small>
        </div>
        <div class="mb-3">
            <label for="avatar" class="form-label">Аватар</label>
            <input type="file" class="form-control" id="avatar" name="avatar">
            <small class="form-text text-muted">Текущий аватар:</small>
            <?php if (!empty($avatar)): ?>
                <img src="<?php echo htmlspecialchars($avatar); ?>" alt="Аватар" class="img-thumbnail mt-2" style="width: 100px; height: 100px;">
            <?php endif; ?>
        </div>
        <button type="submit" class="btn btn-primary">Сохранить изменения</button>
    </form>
</div>
<?php include 'includes/footer.php'; ?>
</body>
</html>
