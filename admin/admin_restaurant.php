<?php
require '../config.php';
session_start();

// CSRF token generation
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: ../admin_login.php');
    exit;
}

// Configuration
$upload_base_dir = __DIR__ . '/../assets/images/';
$web_base_path = 'assets/images/';
$max_file_size = 2 * 1024 * 1024; // 2MB
$allowed_extensions = ['png', 'jpg', 'jpeg', 'gif'];
$allowed_types = ['image/png', 'image/jpeg', 'image/gif'];

// Fetch current restaurant info
try {
    $restaurant_stmt = $pdo->query("SELECT id, name, logo_path FROM restaurant_info LIMIT 1");
    $restaurant = $restaurant_stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $error = '';
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $error = "Invalid CSRF token.";
    } else {
        $name = trim($_POST['restaurant_name']);
        $logo_path = $restaurant['logo_path'] ?? null;

        // Validate restaurant name
        if (empty($name)) {
            $error = "Restaurant name is required.";
        } else {
            // Handle logo upload
            if (isset($_FILES['restaurant_logo']) && $_FILES['restaurant_logo']['error'] === UPLOAD_ERR_OK) {
                // Ensure upload directory exists
                if (!is_dir($upload_base_dir)) {
                    mkdir($upload_base_dir, 0755, true);
                }

                $logo_name = time() . '_' . basename($_FILES['restaurant_logo']['name']);
                $logo_path = $web_base_path . $logo_name;
                $file_ext = strtolower(pathinfo($_FILES['restaurant_logo']['name'], PATHINFO_EXTENSION));

                // Validate file
                if (!in_array($_FILES['restaurant_logo']['type'], $allowed_types) || !in_array($file_ext, $allowed_extensions)) {
                    $error = "Only PNG, JPG, and GIF files are allowed.";
                } elseif ($_FILES['restaurant_logo']['size'] > $max_file_size) {
                    $error = "Logo file size must be less than 2MB.";
                } elseif (!getimagesize($_FILES['restaurant_logo']['tmp_name'])) {
                    $error = "Uploaded file is not a valid image.";
                } elseif (!move_uploaded_file($_FILES['restaurant_logo']['tmp_name'], $upload_base_dir . $logo_name)) {
                    $error = "Failed to upload logo.";
                    $logo_path = $restaurant['logo_path'] ?? null;
                } else {
                    // Delete old logo if it exists
                    if ($restaurant && $restaurant['logo_path'] && file_exists(__DIR__ . '/../' . $restaurant['logo_path'])) {
                        unlink(__DIR__ . '/../' . $restaurant['logo_path']);
                    }
                }
            }

            // Update or insert restaurant info if no error
            if (!$error) {
                try {
                    if ($restaurant) {
                        $stmt = $pdo->prepare("UPDATE restaurant_info SET name = ?, logo_path = ? WHERE id = ?");
                        $stmt->execute([$name, $logo_path, $restaurant['id']]);
                    } else {
                        $stmt = $pdo->prepare("INSERT INTO restaurant_info (name, logo_path) VALUES (?, ?)");
                        $stmt->execute([$name, $logo_path]);
                    }
                    // Regenerate CSRF token after successful submission
                    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                    header('Location: admin_restaurant.php?success=1');
                    exit;
                } catch (PDOException $e) {
                    $error = "Database error: " . $e->getMessage();
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Restaurant Info</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="icon" type="image/x-icon" href="../assets/image/Logo1.ico">
    <style>
        :root {
            --primary: #ff6b6b;
            --dark: #1a1a2e;
            --darker: #16213e;
            --light: #e6e6e6;
            --gray: #4a4a4a;
            --success: #06d6a0;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--dark);
            color: var(--light);
            line-height: 1.6;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }

        header {
            text-align: center;
            padding: 40px 0;
            background: linear-gradient(135deg, var(--darker) 0%, var(--dark) 100%);
            margin-bottom: 30px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
            color: var(--primary);
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .form-group {
            margin-bottom: 20px;
            padding: 15px;
            background-color: var(--darker);
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: var(--light);
        }

        input[type="text"],
        input[type="file"] {
            width: 100%;
            padding: 10px;
            border: 1px solid var(--gray);
            border-radius: 5px;
            background-color: #1e2a44;
            color: var(--light);
            transition: border-color 0.3s ease;
            box-sizing: border-box;
        }

        input[type="text"]:focus,
        input[type="file"]:focus {
            border-color: var(--primary);
            outline: none;
        }

        input[type="file"] {
            padding: 5px;
        }

        button {
            background-color: var(--primary);
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            width: 100%;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        button:hover {
            background-color: #ff5252;
            transform: translateY(-2px);
        }

        .current-logo {
            margin-top: 20px;
            text-align: center;
            padding: 15px;
            background-color: var(--darker);
            border-radius: 10px;
        }

        .current-logo img {
            max-width: 200px;
            max-height: 200px;
            border-radius: 5px;
            border: 2px solid var(--primary);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .success-message {
            color: var(--success);
            text-align: center;
            margin-bottom: 20px;
            padding: 10px;
            background-color: rgba(6, 214, 160, 0.1);
            border-radius: 5px;
        }

        .error-message {
            color: #ff5252;
            text-align: center;
            margin-bottom: 20px;
            padding: 10px;
            background-color: rgba(255, 107, 107, 0.1);
            border-radius: 5px;
        }

        .admin-link {
            display: inline-block;
            margin-top: 20px;
            padding: 10px 20px;
            background-color: var(--primary);
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: all 0.3s ease;
            text-align: center;
            width: 100%;
        }

        .admin-link:hover {
            background-color: #ff5252;
            transform: translateY(-2px);
        }

        @media (max-width: 768px) {
            h1 {
                font-size: 2rem;
            }

            .container {
                padding: 15px;
            }

            .form-group {
                padding: 10px;
            }
        }

        @media (max-width: 480px) {
            h1 {
                font-size: 1.8rem;
            }

            .current-logo img {
                max-width: 150px;
            }

            .admin-link {
                padding: 8px 16px;
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <h1>Manage Restaurant Info</h1>
        </div>
    </header>
    <div class="container">
        <?php if (isset($_GET['success']) && $_GET['success'] == 1): ?>
            <div class="success-message">Restaurant info updated successfully!</div>
        <?php elseif (isset($error)): ?>
            <div class="error-message"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
            <div class="form-group">
                <label for="restaurant_name">Restaurant Name</label>
                <input type="text" id="restaurant_name" name="restaurant_name" value="<?= htmlspecialchars($restaurant['name'] ?? '') ?>" required>
            </div>
            <div class="form-group">
                <label for="restaurant_logo">Restaurant Logo</label>
                <input type="file" id="restaurant_logo" name="restaurant_logo" accept="image/png,image/jpeg,image/gif">
            </div>
            <?php if ($restaurant && $restaurant['logo_path']): ?>
                <div class="current-logo">
                    <p>Current Logo:</p>
                    <img src="../<?= htmlspecialchars($restaurant['logo_path']) ?>" alt="Current Restaurant Logo">
                </div>
            <?php endif; ?>
            <button type="submit">Update Restaurant Info</button>
        </form>
        <a href="dashboard.php" class="admin-link">Back to Services</a>
    </div>
    <script>
        document.getElementById('restaurant_logo').addEventListener('change', function(e) {
            const file = e.target.files[0];
            const allowedTypes = ['image/png', 'image/jpeg', 'image/gif'];
            const maxSize = 2 * 1024 * 1024; // 2MB
            if (file && !allowedTypes.includes(file.type)) {
                alert('Only PNG, JPG, and GIF files are allowed.');
                e.target.value = '';
            } else if (file && file.size > maxSize) {
                alert('File size must be less than 2MB.');
                e.target.value = '';
            }
        });
    </script>
</body>
</html>