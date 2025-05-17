<?php
session_start();
require '../config.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

$id = (int) $_GET['id'];
$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();

// Get current service data
$stmt = $pdo->prepare("SELECT * FROM services WHERE id = ?");
$stmt->execute([$id]);
$service = $stmt->fetch();

if (!$service) {
    header('Location: dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category_id = (int) $_POST['category_id'];
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price = (float) $_POST['price'];

    // Handle image update
    $image = $service['image'];
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        // Delete old image if exists
        if ($image && file_exists("../$image")) {
            unlink("../$image");
        }

        $uploadDir = '../uploads/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '.' . $extension;
        $targetPath = $uploadDir . $filename;

        if (move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
            $image = 'uploads/' . $filename;
        }
    }

    $stmt = $pdo->prepare("
        UPDATE services 
        SET category_id = ?, name = ?, description = ?, price = ?, image = ?
        WHERE id = ?
    ");
    $stmt->execute([$category_id, $name, $description, $price, $image, $id]);

    $_SESSION['message'] = 'Service updated successfully';
    header('Location: services.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/x-icon" href="../assets/image/Logo1.ico">
    <title>Edit Service - Restaurant Admin</title>
    <style>
        :root {
            --primary-color: #c8102e;
            --primary-dark: #a70f26;
            --gradient-dark: #0f2027;
            --gradient-mid: #203a43;
            --gradient-light: #2c5364;
            --text-light: #f8f9fa;
            --bg-light: #f9f9f9;
            --card-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--light-gray);
            color: var(--dark-gray);
            line-height: 1.6;
        }

        header {
            background: linear-gradient(135deg, var(--gradient-dark), var(--gradient-mid), var(--gradient-light));
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .container {
            display: flex;
            flex-direction: column;
            min-height: calc(100vh - 60px);
        }

        main {
            flex-grow: 1;
            padding: 2rem;
        }

        .card {
            background: linear-gradient(135deg, var(--gradient-dark), var(--gradient-mid), var(--gradient-light));
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.05);
            padding: 2rem;
            max-width: 800px;
            margin: 0 auto;
        }

        .dea {
            color: white;
            font-weight: bold;
        }

        h1 {
            color: white;
            margin-bottom: 1.5rem;
            font-size: 1.8rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
        }

        select,
        input[type="text"],
        input[type="number"],
        textarea,
        input[type="file"] {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--medium-gray);
            border-radius: 4px;
            font-family: inherit;
            font-size: 1rem;
        }

        textarea {
            min-height: 100px;
            resize: vertical;
        }

        .current-image {
            margin: 1rem 0;
        }

        .current-image img {
            max-width: 200px;
            max-height: 150px;
            border-radius: 4px;
            border: 1px solid var(--medium-gray);
        }

        .btn {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            background: linear-gradient(135deg, var(--primary-color), var(--primary-dark));
            color: var(--text-light);
            text-decoration: none;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: var(--card-shadow);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn:hover {
            background: linear-gradient(135deg, var(--primary-dark), var(--primary-color));
            transform: translateY(-3px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
        }

        .btn:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(200, 16, 46, 0.3);
        }

        .btn-secondary {
            background: linear-gradient(135deg, var(--gradient-mid), var(--gradient-light));
            margin-left: 1rem;
        }

        .btn-secondary:hover {
            background: linear-gradient(135deg, var(--gradient-light), var(--gradient-mid));
            transform: translateY(-3px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
        }

        .btn-secondary:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(44, 83, 100, 0.3);
        }

        /* Ensure buttons in action-buttons container are aligned nicely */
        .action-buttons {
            margin-top: 2rem;
            display: flex;
            gap: 1rem;
            /* Adds space between buttons */
            justify-content: flex-start;
        }
    </style>
</head>

<body>
    <div class="container">
        <main>
            <div class="card">
                <h1>Edit Service</h1>

                <form method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label class="dea" for="category_id">Category</label>
                        <select id="category_id" name="category_id" required>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?= $category['id'] ?>" <?= $category['id'] == $service['category_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($category['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="dea" for="name">Service Name</label>
                        <input type="text" id="name" name="name" value="<?= htmlspecialchars($service['name']) ?>"
                            required>
                    </div>

                    <div class="form-group">
                        <label class="dea" class="dea" for="description">Description</label>
                        <textarea id="description"
                            name="description"><?= htmlspecialchars($service['description']) ?></textarea>
                    </div>

                    <div class="form-group">
                        <label class="dea" for="price">Price (Rs.)</label>
                        <input type="number" id="price" name="price" step="0.01" min="0"
                            value="<?= htmlspecialchars($service['price']) ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="dea">Current Image</label>
                        <div class="current-image">
                            <?php if ($service['image']): ?>
                                <img src="../<?= htmlspecialchars($service['image']) ?>"
                                    alt="<?= htmlspecialchars($service['name']) ?>">
                            <?php else: ?>
                                <p>No image currently set</p>
                            <?php endif; ?>
                        </div>

                        <label class="dea" for="image">Update Image (leave blank to keep current)</label>
                        <input type="file" id="image" name="image" accept="image/*">
                    </div>

                    <div class="action-buttons">
                        <button type="submit" class="btn btn-secondary">Update Service</button>
                        <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </main>
    </div>
</body>

</html>