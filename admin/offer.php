<?php
require '../config.php';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete'])) {
        // Delete banner
        $stmt = $pdo->prepare("SELECT image_path FROM offer_banners WHERE id = ?");
        $stmt->execute([$_POST['id']]);
        $banner = $stmt->fetch();
        
        if ($banner && file_exists("../".$banner['image_path'])) {
            unlink("../".$banner['image_path']);
        }
        
        $pdo->prepare("DELETE FROM offer_banners WHERE id = ?")->execute([$_POST['id']]);
        header("Location: offer.php?success=deleted");
        exit();
    }
    else {
        // Add new banner
        if ($_FILES['offer_image']['tmp_name']) {
            $target_dir = "../uploads/offers/";
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            
            $ext = pathinfo($_FILES["offer_image"]["name"], PATHINFO_EXTENSION);
            $filename = 'offer_'.time().'.'.$ext;
            $target_file = $target_dir . $filename;

            if (move_uploaded_file($_FILES["offer_image"]["tmp_name"], $target_file)) {
                $db_path = "uploads/offers/" . $filename;
                $pdo->prepare("INSERT INTO offer_banners (image_path) VALUES (?)")->execute([$db_path]);
                header("Location: offer.php?success=added");
                exit();
            }
        }
    }
}

// Get all banners
$banners = $pdo->query("SELECT * FROM offer_banners ORDER BY display_order, created_at DESC")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/x-icon" href="../assets/image/Logo1.ico">
    <title>Offer Banner Management</title>
    <style>
        * {
            box-sizing: border-box;
            font-family: 'Poppins', Arial, sans-serif;
        }

        body {
            margin: 0;
            padding: 0;
            background-image: url('../assets/image/admin-b.png');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
            color: #333;
        }

        .container {
            max-width: 1000px;
            width: 100%;
            margin: 0 auto;
            background: linear-gradient(135deg, #0f2027 0%, #203a43 50%, #2c5364 100%);
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
            backdrop-filter: blur(5px);
        }

        h1 {
            text-align: center;
            color:rgb(255, 255, 255);
            margin-top: 0;
            margin-bottom: 25px;
            font-weight: 600;
        }

        .form-group {
            margin-bottom: 25px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color:rgb(255, 255, 255);
        }

        input[type="file"] {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 6px;
            background: #f9f9f9;
            transition: border 0.3s;
        }

        input[type="file"]:focus {
            border-color: #3498db;
            outline: none;
        }

        button {
            background-color: #3498db;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 16px;
            width: 100%;
            transition: all 0.3s;
            font-weight: 500;
        }

        button:hover {
            background-color: #2980b9;
            transform: translateY(-2px);
        }

        .success {
            background-color: #d4edda;
            color: #155724;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 25px;
            text-align: center;
            border: 1px solid #c3e6cb;
        }

        .error {
            background-color: #f8d7da;
            color: #721c24;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 25px;
            text-align: center;
            border: 1px solid #f5c6cb;
        }

        .preview {
            margin-top: 20px;
            text-align: center;
        }

        .preview img {
            max-width: 100%;
            height: auto;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .cancel-button {
            display: inline-block;
            background-color: #95a5a6;
            color: white;
            text-align: center;
            padding: 12px 20px;
            border-radius: 6px;
            font-size: 16px;
            text-decoration: none;
            width: 100%;
            margin-top: 20px;
            transition: all 0.3s;
            font-weight: 500;
            border: none;
            cursor: pointer;
        }

        .cancel-button:hover {
            background-color: #7f8c8d;
            transform: translateY(-2px);
        }

        .banner-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 25px;
            margin-top: 30px;
        }

        .banner-item {
            background: white;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 3px 10px rgba(0,0,0,0.1);
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .banner-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.15);
        }

        .banner-image-container {
            height: 180px;
            overflow: hidden;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f5f5f5;
            margin-bottom: 15px;
        }

        .banner-image-container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s;
        }

        .banner-item:hover .banner-image-container img {
            transform: scale(1.05);
        }

        .banner-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            margin-top: 15px;
        }

        .delete-btn {
            background-color: #e74c3c;
            padding: 8px 15px;
            flex: 1;
        }

        .delete-btn:hover {
            background-color: #c0392b;
        }

        select {
            padding: 8px 12px;
            border-radius: 6px;
            border: 2px solid #ddd;
            font-size: 14px;
            flex: 1;
            min-width: 120px;
        }

        select:focus {
            border-color: #3498db;
            outline: none;
        }

        @media (max-width: 768px) {
            .container {
                padding: 20px;
            }
            
            .banner-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>Manage Offer Banners</h1>

        <?php if (isset($_GET['success'])): ?>
            <div class="success">Banner <?= htmlspecialchars($_GET['success']) ?> successfully!</div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label for="offer_image">Upload New Offer Image:</label>
                <input type="file" id="offer_image" name="offer_image" accept="image/*" required>
                <small style="color: #7f8c8d; display: block; margin-top: 5px;">Recommended size: 1200x400px or similar ratio</small>
            </div>
            <button type="submit">Add Banner</button>
        </form>

        <div class="banner-grid">
            <?php foreach ($banners as $banner): ?>
                <div class="banner-item">
                    <div class="banner-image-container">
                        <img src="../<?= htmlspecialchars($banner['image_path']) ?>" alt="Offer Banner">
                    </div>
                    <div class="banner-actions">
                        <form method="POST">
                            <input type="hidden" name="id" value="<?= $banner['id'] ?>">
                            <button type="submit" name="delete" class="delete-btn">Delete</button>
                        </form>
                        <form method="POST" action="update_order.php">
                            <input type="hidden" name="id" value="<?= $banner['id'] ?>">
                            <select name="display_order" onchange="this.form.submit()">
                                <?php for ($i=1; $i<=count($banners); $i++): ?>
                                    <option value="<?= $i ?>" <?= $i==$banner['display_order']?'selected':'' ?>>
                                        Position <?= $i ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <a href="dashboard.php" class="cancel-button">Back to Dashboard</a>
    </div>
</body>

</html>