<?php
session_start();
require '../config.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    
    // Handle file upload
    $imagePath = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../assets/images/categories/';
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '.' . $extension;
        $destination = $uploadDir . $filename;
        
        if (move_uploaded_file($_FILES['image']['tmp_name'], $destination)) {
            $imagePath = 'assets/images/categories/' . $filename;
        }
    }

    if (!empty($name)) {
        $stmt = $pdo->prepare("INSERT INTO categories (name, image) VALUES (?, ?)");
        $stmt->execute([$name, $imagePath]);
        header('Location: categories.php');
        exit;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Category</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="../assets/image/Logo1.ico">
    <style>
        * {
            box-sizing: border-box;
        }
        
        body {
            margin: 0;
            padding: 0;
            font-family: 'Poppins', sans-serif;
            background-image: url('../assets/image/admin-b.png');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }

        .form-container {
            background: linear-gradient(135deg, #0f2027 0%, #203a43 50%, #2c5364 100%);
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.3);
            width: 100%;
            max-width: 500px;
        }

        h1 {
            text-align: center;
            margin-bottom: 25px;
            color: white;
            font-size: 1.8rem;
        }

        form div {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: white;
        }

        input[type="text"],
        input[type="file"] {
            width: 100%;
            padding: 12px;
            font-size: 16px;
            border: 1px solid #ccc;
            border-radius: 8px;
            background-color: white;
        }

        button {
            width: 100%;
            background-color: #28a745;
            color: white;
            padding: 12px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s ease;
            font-weight: 600;
        }

        button:hover {
            background-color: #218838;
        }

        .action-links {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
        }
        
        .action-link {
            text-align: center;
            text-decoration: none;
            color: white;
            padding: 10px;
            border-radius: 8px;
            transition: all 0.3s ease;
            flex: 1;
            margin: 0 5px;
            font-weight: 600;
        }
        
        .cancel-link {
            background-color: #6c757d;
        }
        
        .cancel-link:hover {
            background-color: #5a6268;
            text-decoration: none;
        }
        
        .view-link {
            background-color: #17a2b8;
        }
        
        .view-link:hover {
            background-color: #138496;
            text-decoration: none;
        }
        
        .image-preview {
            width: 100%;
            max-width: 200px;
            height: auto;
            max-height: 200px;
            object-fit: cover;
            border-radius: 8px;
            margin-top: 10px;
            display: none;
            border: 2px solid white;
        }
        
        @media (max-width: 576px) {
            .form-container {
                padding: 20px;
            }
            
            h1 {
                font-size: 1.5rem;
                margin-bottom: 20px;
            }
            
            .action-links {
                flex-direction: column;
            }
            
            .action-link {
                margin: 5px 0;
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="form-container">
        <h1>Add New Category</h1>
        <form method="POST" enctype="multipart/form-data">
            <div>
                <label>Category Name:</label>
                <input type="text" name="name" required>
            </div>
            <div>
                <label>Category Image:</label>
                <input type="file" name="image" id="imageInput" accept="image/*">
                <img id="imagePreview" class="image-preview" src="#" alt="Preview">
            </div>
            <button type="submit">Add Category</button>
            
            <div class="action-links">
                <a href="categories.php" class="action-link view-link">View Categories</a>
                <a href="dashboard.php" class="action-link cancel-link">Back to Dashboard</a>
            </div>
        </form>
    </div>

    <script>
        // Image preview functionality
        document.getElementById('imageInput').addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    const preview = document.getElementById('imagePreview');
                    preview.src = event.target.result;
                    preview.style.display = 'block';
                }
                reader.readAsDataURL(file);
            }
        });
    </script>
</body>
</html>