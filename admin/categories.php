<?php
session_start();
require '../config.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

// Generate CSRF token if not exists
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Handle category deletion
if (isset($_GET['delete_category'])) {
    if (!isset($_GET['csrf_token']) || $_GET['csrf_token'] !== $_SESSION['csrf_token']) {
        die('Invalid CSRF token');
    }

    $id = (int) $_GET['delete_category'];
    try {
        // First get image path to delete file
        $stmt = $pdo->prepare("SELECT image FROM categories WHERE id = ?");
        $stmt->execute([$id]);
        $category = $stmt->fetch();

        // Delete from database
        $pdo->prepare("DELETE FROM categories WHERE id = ?")->execute([$id]);

        // Delete image file if exists
        if ($category && !empty($category['image']) && file_exists('../' . $category['image'])) {
            unlink('../' . $category['image']);
        }

        $_SESSION['message'] = 'Category deleted successfully';
    } catch (PDOException $e) {
        $_SESSION['error'] = 'Error deleting category: ' . $e->getMessage();
    }
    header('Location: categories.php');
    exit;
}

// Get all categories
$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="icon" type="image/x-icon" href="assets/image/Logo1.ico">
    <title>Categories - Restaurant Admin</title>
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
            --primary-color: #ff6b6b;
            --primary-dark: #ff4757;
            --secondary-color: #4ecdc4;
            --dark-color: #2f3542;
            --light-color: #f1f2f6;
            --success-color: #2ed573;
            --danger-color: #ff4757;
            --warning-color: #ffa502;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            background-color: #f8f9fa;
            color: #333;
            line-height: 1.6;
        }

        header {
            background: linear-gradient(135deg, var(--gradient-dark), var(--gradient-mid), var(--gradient-light));
            color: white;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .menu-toggle {
            display: none;
            background: none;
            border: none;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
            transition: transform 0.3s ease;
        }

        .menu-toggle:hover {
            transform: scale(1.1);
        }

        .container {
            display: flex;
            min-height: calc(100vh - 70px);
        }

        nav {
            width: 250px;
            background-color: white;
            border-right: 1px solid #e0e0e0;
            padding: 1.5rem;
            transition: all 0.3s ease;
            box-shadow: 2px 0 5px rgba(0, 0, 0, 0.05);
        }

        nav h3 {
            margin-top: 0;
            padding-bottom: 1rem;
            border-bottom: 1px solid #eee;
            color: var(--dark-color);
            font-size: 1.2rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        nav a {
            display: flex;
            align-items: center;
            gap: 0.8rem;
            padding: 0.8rem 0;
            text-decoration: none;
            color: var(--dark-color);
            transition: all 0.3s ease;
            font-weight: 500;
            border-radius: 4px;
            padding-left: 0.5rem;
        }

        nav a:hover {
            color: var(--primary-color);
            background-color: rgba(255, 107, 107, 0.1);
            transform: translateX(5px);
        }

        nav a.active {
            color: var(--primary-color);
            font-weight: 600;
            border-left: 3px solid var(--primary-color);
        }

        main {
            flex-grow: 1;
            padding: 2rem;
            background-color: #f5f7fa;
        }

        .section {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 1.5rem;
            margin-bottom: 2rem;
            transition: transform 0.3s ease;
        }

        .section:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
        }

        h1 {
            color: white;
            font-size: 1.8rem;
        }

        h2 {
            margin-top: 0;
            color: var(--dark-color);
            font-weight: 600;
        }

        h2 {
            font-size: 1.5rem;
            margin-bottom: 1.5rem;
            position: relative;
            display: inline-block;
        }

        h2::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 0;
            width: 50px;
            height: 3px;
            background: linear-gradient(to right, var(--primary-color), var(--primary-dark));
            border-radius: 3px;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.7rem 1.5rem;
            background: linear-gradient(135deg, var(--gradient-dark), var(--gradient-mid), var(--gradient-light));
            color: white;
            text-decoration: none;
            border-radius: 30px;
            font-weight: 500;
            transition: all 0.3s ease;
            box-shadow: 0 2px 5px rgba(255, 107, 107, 0.3);
            border: none;
            cursor: pointer;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(255, 107, 107, 0.4);
        }

        .btn i {
            font-size: 0.9rem;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1.5rem;
            background-color: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.05);
        }

        th,
        td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: var(--dark-color);
            text-transform: uppercase;
            font-size: 0.8rem;
            letter-spacing: 0.5px;
        }

        tr:hover {
            background-color: #f8f9fa;
        }

        .category-image {
            width: 80px;
            height: 60px;
            border-radius: 4px;
            object-fit: cover;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }

        .category-image:hover {
            transform: scale(1.05);
        }

        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }

        .edit-btn {
            padding: 0.5rem 1rem;
            text-decoration: none;
            border-radius: 4px;
            font-size: 0.8rem;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.3rem;
            background-color: #4CAF50;
            color: white;
            border: 1px solid #4CAF50;
        }

        .delete-btn {
            padding: 0.5rem 1rem;
            background-color: var(--danger-color);
            color: white;
            text-decoration: none;
            border-radius: 4px;
            font-size: 0.8rem;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.3rem;
        }

        .edit-btn:hover {
            background-color: #3e8e41;
            border-color: #3e8e41;
        }

        .delete-btn:hover {
            background-color: #e84118;
            transform: translateY(-2px);
        }

        .alert {
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-radius: 4px;
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }

        .alert-success {
            background-color: rgba(46, 213, 115, 0.2);
            color: #155724;
            border-left: 4px solid var(--success-color);
        }

        .alert-error {
            background-color: rgba(255, 71, 87, 0.2);
            color: #721c24;
            border-left: 4px solid var(--danger-color);
        }

        .alert i {
            font-size: 1.2rem;
        }

        .no-image {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 80px;
            height: 60px;
            background-color: #f1f2f6;
            border-radius: 4px;
            color: #7f8c8d;
            font-size: 0.8rem;
        }

        .logout-btn {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: white;
            text-decoration: none;
            font-weight: 500;
            padding: 0.5rem 1rem;
            border-radius: 30px;
            background: rgba(255, 255, 255, 0.1);
            transition: all 0.3s ease;
        }

        .logout-btn:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
        }

        @media (max-width: 768px) {
            .container {
                flex-direction: column;
            }

            nav {
                position: fixed;
                top: 70px;
                left: 0;
                width: 250px;
                height: calc(100vh - 70px);
                transform: translateX(-100%);
                z-index: 900;
                padding: 1rem;
            }

            nav.open {
                transform: translateX(0);
            }

            main {
                margin-left: 0;
                padding: 1rem;
            }

            .menu-toggle {
                display: block;
            }

            table {
                display: block;
                overflow-x: auto;
            }
        }

        @media (max-width: 480px) {
            header {
                padding: 0.8rem 1rem;
            }

            h1 {
                font-size: 1.2rem;
            }

            .btn {
                padding: 0.6rem 1rem;
                font-size: 0.8rem;
            }

            th,
            td {
                padding: 0.8rem;
            }

            .action-buttons {
                flex-direction: column;
                gap: 0.3rem;
            }
        }

        /* Animation for alerts */
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .alert {
            animation: slideIn 0.3s ease-out forwards;
        }

        /* Animation for table rows */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        tbody tr {
            animation: fadeIn 0.4s ease-out forwards;
            animation-delay: calc(var(--row-index) * 0.05s);
        }
    </style>
</head>

<body>

    <header>
        <button class="menu-toggle" id="menuToggle">
            <i class="fas fa-bars"></i>
        </button>
        <h1><i class="fas fa-utensils"></i> Admin Panel</h1>
        <a href="logout.php" class="logout-btn">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </header>

    <div class="container">
        <nav id="main-nav">
            <h3><i class="fas fa-compass"></i> Navigation</h3>
            <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="categories.php" class="active"><i class="fas fa-list"></i> Categories</a>
            <a href="services.php"><i class="fas fa-concierge-bell"></i> Services</a>
            <a href="add_category.php"><i class="fas fa-plus-circle"></i> Add Category</a>
            <a href="add_service.php"><i class="fas fa-plus-square"></i> Add Service</a>
            <a href="offer.php"><i class="fas fa-images"></i> Offer Images</a>
            <a href="admin_restaurant.php"><i class="fas fa-images"></i>Restaurant Logo & Name</a>
        </nav>

        <main>
            <?php if (isset($_SESSION['message'])): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?= $_SESSION['message'];
                    unset($_SESSION['message']); ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?= $_SESSION['error'];
                    unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>

            <div class="section">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
                    <h2><i class="fas fa-list"></i> Categories</h2>
                    <a class="btn" href="add_category.php">
                        <i class="fas fa-plus"></i> Add New Category
                    </a>
                </div>

                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Image</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($categories as $index => $category): ?>
                            <tr style="--row-index: <?= $index ?>;">
                                <td><?= $category['id'] ?></td>
                                <td><?= htmlspecialchars($category['name']) ?></td>
                                <td>
                                    <?php if (!empty($category['image'])): ?>
                                        <img src="../<?= htmlspecialchars($category['image']) ?>" class="category-image"
                                            alt="<?= htmlspecialchars($category['name']) ?>">
                                    <?php else: ?>
                                        <span class="no-image">No Image</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="edit_category.php?id=<?= $category['id'] ?>" class="edit-btn">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                        <a href="categories.php?delete_category=<?= $category['id'] ?>&csrf_token=<?= $_SESSION['csrf_token'] ?>"
                                            class="delete-btn"
                                            onclick="return confirm('Are you sure you want to delete this category? All services in this category will also be deleted.')">
                                            <i class="fas fa-trash-alt"></i> Delete
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <script>
        // Toggle mobile menu
        document.getElementById('menuToggle').addEventListener('click', function () {
            const nav = document.getElementById('main-nav');
            nav.classList.toggle('open');

            // Change icon
            const icon = this.querySelector('i');
            if (nav.classList.contains('open')) {
                icon.classList.remove('fa-bars');
                icon.classList.add('fa-times');
            } else {
                icon.classList.remove('fa-times');
                icon.classList.add('fa-bars');
            }
        });

        // Close menu when clicking on a link (for mobile)
        document.querySelectorAll('#main-nav a').forEach(link => {
            link.addEventListener('click', function () {
                if (window.innerWidth <= 768) {
                    document.getElementById('main-nav').classList.remove('open');
                    document.getElementById('menuToggle').querySelector('i').classList.remove('fa-times');
                    document.getElementById('menuToggle').querySelector('i').classList.add('fa-bars');
                }
            });
        });

        // Close menu when clicking outside (for mobile)
        document.addEventListener('click', function (e) {
            const nav = document.getElementById('main-nav');
            const menuToggle = document.getElementById('menuToggle');

            if (window.innerWidth <= 768 &&
                !nav.contains(e.target) &&
                e.target !== menuToggle &&
                !menuToggle.contains(e.target)) {
                nav.classList.remove('open');
                menuToggle.querySelector('i').classList.remove('fa-times');
                menuToggle.querySelector('i').classList.add('fa-bars');
            }
        });
    </script>

</body>

</html>