<?php
session_start();
require '../config.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = 'Invalid category ID';
    header('Location: categories.php');
    exit;
}

$id = (int) $_GET['id'];

// Fetch category from database
$stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
$stmt->execute([$id]);
$category = $stmt->fetch();

if (!$category) {
    $_SESSION['error'] = 'Category not found';
    header('Location: categories.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);

    // Require a new image
    if (empty($_FILES['image']['name'])) {
        $_SESSION['error'] = 'Image is required when editing a category.';
        header("Location: edit_category.php?id=$id");
        exit;
    }

    $targetDir = "uploads/";
    $newFileName = uniqid() . '_' . basename($_FILES["image"]["name"]);
    $targetFile = $targetDir . $newFileName;

    // Move uploaded file
    if (move_uploaded_file($_FILES["image"]["tmp_name"], "../" . $targetFile)) {
        // Delete old image
        if (!empty($category['image']) && file_exists("../" . $category['image'])) {
            unlink("../" . $category['image']);
        }

        // Update database
        try {
            $stmt = $pdo->prepare("UPDATE categories SET name = ?, image = ? WHERE id = ?");
            $stmt->execute([$name, $targetFile, $id]);

            $_SESSION['message'] = 'Category updated successfully';
            header("Location: categories.php");
            exit;
        } catch (PDOException $e) {
            $_SESSION['error'] = 'Error updating category: ' . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = 'Failed to upload image.';
        header("Location: edit_category.php?id=$id");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/x-icon" href="../assets/image/Logo1.ico">
    <title>Edit Category</title>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', sans-serif;
            background-color: #f9f9f9;
            margin: 0;
            padding: 0;
            color: #333;
            line-height: 1.6;
        }

        .container {
            max-width: 600px;
            width: 90%;
            margin: 20px auto;             background: linear-gradient(135deg, #0f2027 0%, #203a43 50%, #2c5364 100%);
            border-radius: 8px;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
            padding: 20px;
        }

        h2 {
            color:rgb(255, 255, 255);
            margin-top: 0;
            text-align: center;
        }

        label {
            display: block;
            margin-top: 15px;
            font-weight: bold;
            color: white;
        }

        input[type="text"],
        input[type="file"] {
            width: 100%;
            padding: 10px;
            margin-top: 6px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 16px;
        }

        .btn {
            background-color: #c8102e;
            color: white;
            border: none;
            padding: 12px 20px;
            margin-top: 20px;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            font-size: 16px;
            width: 100%;
            display: block;
            text-align: center;
        }

        .btn:hover {
            background-color: #a70f26;
        }

        .btn1 {
            background-color: rgb(124, 123, 123);
            color: white;
            border: none;
            padding: 12px 20px;
            margin-top: 10px;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            font-size: 16px;
            width: 100%;
            display: block;
            text-align: center;
        }

        .btn1:hover {
            background-color: rgb(145, 143, 143);
        }

        img.preview {
            max-width: 100%;
            height: auto;
            margin-top: 10px;
            border-radius: 4px;
            display: block;
        }

        .alert {
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 15px;
        }

        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
        }

        .action-buttons {
            display: flex;
            flex-direction: column;
        }

        @media (min-width: 768px) {
            .container {
                margin: 40px auto;
                padding: 30px;
            }

            .action-buttons {
                flex-direction: row;
                justify-content: space-between;
            }

            .btn {
                width: 48%;
                margin-top: 20px;
            }

            .btn1 {
                width: 48%;
                margin-top: 20px;
            }

            img.preview {
                max-width: 200px;
            }
        }
    </style>
</head>

<body>

    <div class="container">
        <h2>Edit Category</h2>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-error"><?= $_SESSION['error'];
            unset($_SESSION['error']); ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <label for="name">Category Name:</label>
            <input type="text" name="name" id="name" value="<?= htmlspecialchars($category['name']) ?>" required>
            
            <?php if (!empty($category['image'])): ?>
                <p style="color:white;">Current Image:</p>
                <img src="../<?= htmlspecialchars($category['image']) ?>" class="preview" alt="Current Image">
            <?php endif; ?>
            
            <label for="image">Upload New Image:</label>
            <input type="file" name="image" id="image" accept="image/*" required>

            <div class="action-buttons">
                <button type="submit" class="btn">Update Category</button>
                <a href="dashboard.php" class="btn1">Cancel</a>
            </div>
        </form>
    </div>

</body>

</html>