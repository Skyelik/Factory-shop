<?php
session_start();
require_once '../config/db.php';
require_once '../src/Database.php';

// Проверка авторизации и роли администратора
if (!isset($_SESSION['user']['role']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}

$db = new Database($dbConfig);
$connection = $db->getConnection();

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price = trim($_POST['price']);
    $stock = trim($_POST['stock']);
    $category = trim($_POST['category']);
    $fullDescription = trim($_POST['full_description']);
    $countryOfOrigin = trim($_POST['country_of_origin']);
    $dimensions = trim($_POST['dimensions']);
    $weight = trim($_POST['weight']);
    $imageFile = $_FILES['image'];

    // Проверка обязательных полей
    if (empty($name) || empty($description) || empty($price) || empty($stock) || empty($category)) {
        $errors[] = "Пожалуйста, заполните все обязательные поля.";
    }

    // Проверка валидности цены и количества
    if (!is_numeric($price) || $price <= 0) {
        $errors[] = "Цена должна быть положительным числом.";
    }
    if (!is_numeric($stock) || $stock < 0) {
        $errors[] = "Количество должно быть неотрицательным числом.";
    }

    // Загрузка изображения
    if (!empty($imageFile['name'])) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        if (in_array($imageFile['type'], $allowedTypes) && $imageFile['size'] <= 2 * 1024 * 1024) {
            $uploadDir = __DIR__ . '/uploads/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $imagePath = $uploadDir . uniqid() . '_' . basename($imageFile['name']);
            if (move_uploaded_file($imageFile['tmp_name'], $imagePath)) {
                // Сохраняем относительный путь для использования на сайте
                $imagePath = 'uploads/' . basename($imagePath);
            }
             else {
                $errors[] = "Ошибка загрузки изображения.";
            }
        } else {
            $errors[] = "Допустимые форматы изображения: JPEG, PNG, GIF. Максимальный размер — 2 МБ.";
        }
    } else {
        $errors[] = "Пожалуйста, загрузите изображение товара.";
    }

    // Добавление товара в базу данных
    if (empty($errors)) {
        $query = "INSERT INTO products (name, description, price, stock, image_path, created_at, category, full_description, country_of_origin, dimensions, weight) 
                  VALUES (:name, :description, :price, :stock, :image_path, NOW(), :category, :full_description, :country_of_origin, :dimensions, :weight)";
        $stmt = $connection->prepare($query);
        $params = [
            ':name' => $name,
            ':description' => $description,
            ':price' => $price,
            ':stock' => $stock,
            ':image_path' => $imagePath,
            ':category' => $category,
            ':full_description' => $fullDescription,
            ':country_of_origin' => $countryOfOrigin,
            ':dimensions' => $dimensions,
            ':weight' => $weight
        ];

        if ($stmt->execute($params)) {
            $success = "Товар успешно добавлен.";
        } else {
            $errors[] = "Ошибка добавления товара. Попробуйте снова.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Добавить товар</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<?php include 'includes/header.php'; ?>
<div class="container py-5">
    <h1 class="mb-4">Добавить товар</h1>

    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <?php foreach ($errors as $error): ?>
                <p><?php echo htmlspecialchars($error); ?></p>
            <?php endforeach; ?>
        </div>
    <?php elseif (!empty($success)): ?>
        <div class="alert alert-success"> <?php echo htmlspecialchars($success); ?> </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">
        <div class="mb-3">
            <label for="name" class="form-label">Название</label>
            <input type="text" class="form-control" id="name" name="name" required>
        </div>
        <div class="mb-3">
            <label for="description" class="form-label">Краткое описание</label>
            <textarea class="form-control" id="description" name="description" required></textarea>
        </div>
        <div class="mb-3">
            <label for="price" class="form-label">Цена</label>
            <input type="number" step="0.01" class="form-control" id="price" name="price" required>
        </div>
        <div class="mb-3">
            <label for="stock" class="form-label">Количество на складе</label>
            <input type="number" class="form-control" id="stock" name="stock" required>
        </div>
        <div class="mb-3">
            <label for="category" class="form-label">Категория</label>
            <input type="text" class="form-control" id="category" name="category" required>
        </div>
        <div class="mb-3">
            <label for="full_description" class="form-label">Полное описание</label>
            <textarea class="form-control" id="full_description" name="full_description"></textarea>
        </div>
        <div class="mb-3">
            <label for="country_of_origin" class="form-label">Страна производства</label>
            <input type="text" class="form-control" id="country_of_origin" name="country_of_origin">
        </div>
        <div class="mb-3">
            <label for="dimensions" class="form-label">Размеры (см)</label>
            <input type="text" class="form-control" id="dimensions" name="dimensions">
        </div>
        <div class="mb-3">
            <label for="weight" class="form-label">Вес (кг)</label>
            <input type="text" class="form-control" id="weight" name="weight">
        </div>
        <div class="mb-3">
            <label for="image" class="form-label">Изображение</label>
            <input type="file" class="form-control" id="image" name="image" required>
        </div>
        <button type="submit" class="btn btn-primary">Добавить товар</button>
    </form>
</div>
<?php include 'includes/footer.php'; ?>
</body>
</html>
