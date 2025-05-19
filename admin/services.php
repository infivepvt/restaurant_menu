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

// Handle service deletion
if (isset($_GET['delete_service'])) {
    if (!isset($_GET['csrf_token']) || $_GET['csrf_token'] !== $_SESSION['csrf_token']) {
        die('Invalid CSRF token');
    }

    $id = (int) $_GET['delete_service'];
    try {
        // First get image path to delete file
        $stmt = $pdo->prepare("SELECT image FROM services WHERE id = ?");
        $stmt->execute([$id]);
        $service = $stmt->fetch();

        // Delete from database
        $pdo->prepare("DELETE FROM services WHERE id = ?")->execute([$id]);

        // Delete image file if exists
        if ($service && !empty($service['image']) && file_exists('../' . $service['image'])) {
            unlink('../' . $service['image']);
        }

        $_SESSION['message'] = 'Service deleted successfully';
    } catch (PDOException $e) {
        $_SESSION['error'] = 'Error deleting service: ' . $e->getMessage();
    }
    header('Location: services.php');
    exit;
}

// Get all services with category names
$services = $pdo->query("
    SELECT s.*, c.name AS category_name 
    FROM services s 
    JOIN categories c ON s.category_id = c.id
    ORDER BY c.name, s.name
")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Services - Restaurant Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="icon" type="image/x-icon" href="../assets/image/Logo1.ico">
    <style>
        :root {
            --primary-color: #c8102e;
            --primary-dark: #a70f26;
            --border-radius: 8px;
            --gradient-dark: #0f2027;
            --gradient-mid: #203a43;
            --gradient-light: #2c5364;
            --text-light: #f8f9fa;
            --bg-light: #f9f9f9;
            --card-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            background-color: var(--bg-light);
            color: #333;
            line-height: 1.6;
        }

        header {
            background: linear-gradient(135deg, #0f2027 0%, #203a43 50%, #2c5364 100%);
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

        .container {
            display: flex;
            flex-direction: column;
            min-height: calc(100vh - 70px);
        }

        .nav-container {
            display: flex;
            flex-direction: row;
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
            overflow-x: auto;
        }

        .section {
            background: #fff;
            border-radius: var(--border-radius);
            box-shadow: var(--card-shadow);
            padding: 1.5rem;
            margin-bottom: 2rem;
            transition: transform 0.3s ease;
            min-width: 300px;
        }

        .section:hover {
            transform: translateY(-3px);
        }

        h1 {
            color: white;
            font-size: 1.8rem;

        }

        h2 {
            margin-top: 0;
            color: var(--gradient-mid);
            font-weight: 600;
            font-size: 1.5rem;
            margin-bottom: 1.5rem;
            position: relative;
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

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1.5rem;
            box-shadow: 0 0 0 1px #e0e0e0;
            border-radius: var(--border-radius);
            overflow: hidden;
            min-width: 600px;
        }

        th,
        td {
            padding: 12px 15px;
            border-bottom: 1px solid #e0e0e0;
            text-align: left;
        }

        th {
            background-color: #f5f5f5;
            font-weight: 600;
            color: #555;
            position: sticky;
            top: 0;
        }

        tr:hover {
            background-color: #f9f9f9;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            padding: 0.7rem 1.2rem;
            background: linear-gradient(135deg, var(--gradient-dark), var(--gradient-mid), var(--gradient-light));
            color: white;
            text-decoration: none;
            border-radius: 30px;
            font-weight: 500;
            transition: all 0.3s ease;
            box-shadow: 0 2px 5px rgba(199, 16, 46, 0.3);
            border: none;
            cursor: pointer;
            font-size: 0.9rem;
            white-space: nowrap;
        }

        .btn i {
            margin-right: 8px;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(199, 16, 46, 0.4);
        }

        .service-image {
            max-width: 100px;
            max-height: 60px;
            border-radius: 4px;
            object-fit: cover;
            border: 1px solid #e0e0e0;
        }

        .alert {
            padding: 12px 15px;
            margin-bottom: 20px;
            border-radius: var(--border-radius);
            font-weight: 500;
        }

        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border-left: 4px solid #28a745;
        }

        .alert-error {
            background-color: #f8d7da;
            color: #721c24;
            border-left: 4px solid #dc3545;
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }

        .action-btn {
            padding: 6px 10px;
            border-radius: 4px;
            text-decoration: none;
            font-size: 0.8rem;
            transition: all 0.3s;
            white-space: nowrap;
            display: inline-flex;
            align-items: center;
        }

        .action-btn i {
            margin-right: 5px;
            font-size: 0.8rem;
        }

        .edit-btn {
            background-color: #4CAF50;
            color: white;
            border: 1px solid #4CAF50;
        }

        .edit-btn:hover {
            background-color: #3e8e41;
            border-color: #3e8e41;
        }

        .delete-btn {
            background-color: #f44336;
            color: white;
            border: 1px solid #f44336;
        }

        .delete-btn:hover {
            background-color: #d32f2f;
            border-color: #d32f2f;
        }

        .search-filter {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .search-box {
            padding: 10px 15px;
            border-radius: 30px;
            border: 1px solid #ddd;
            min-width: 200px;
            flex-grow: 1;
            max-width: 400px;
            transition: all 0.3s;
            font-size: 14px;
        }

        .search-box:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(200, 16, 46, 0.2);
        }

        /* Mobile menu toggle */
        .menu-toggle {
            display: none;
            background: none;
            border: none;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
            transition: transform 0.3s ease;
            padding: 0.5rem;
            margin-right: 0.5rem;
        }

        .menu-toggle:hover {
            transform: scale(1.1);
        }

        .logout-btn {
            display: flex;
            align-items: center;
            color: white;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            padding: 0.5rem 1rem;
            border-radius: 30px;
            background: rgba(255, 255, 255, 0.1);
            font-size: 0.9rem;
            white-space: nowrap;
        }

        .logout-btn:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
        }

        .logout-btn i {
            margin-right: 8px;
        }

        /* Price styling */
        .price {
            font-weight: 600;
            color: var(--primary-dark);
            white-space: nowrap;
        }

        /* Responsive styles */
        @media (max-width: 1024px) {
            nav {
                width: 220px;
                padding: 1rem;
            }

            main {
                padding: 1.5rem;
            }

            .section {
                padding: 1.2rem;
            }
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
                background-color: white;
                transition: transform 0.3s ease;
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

            .search-filter {
                flex-direction: column;
                align-items: flex-start;
            }

            .search-box {
                width: 100%;
                max-width: 100%;
            }
        }

        @media (max-width: 576px) {
            header {
                padding: 0.8rem 1rem;
            }

            h1 {
                font-size: 1.2rem;
            }

            h2 {
                font-size: 1.3rem;
            }

            .btn {
                padding: 0.6rem 1rem;
                font-size: 0.8rem;
            }

            th,
            td {
                padding: 0.8rem 0.6rem;
                font-size: 0.9rem;
            }

            .action-buttons {
                flex-direction: column;
                gap: 0.3rem;
            }

            .action-btn {
                padding: 5px 8px;
                font-size: 0.75rem;
            }

            .service-image {
                max-width: 80px;
                max-height: 50px;
            }
        }

        /* Table wrapper for horizontal scrolling */
        .table-wrapper {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            margin: 0 -1rem;
            padding: 0 1rem;
        }

        /* Animation for alerts */
        @keyframes fadeIn {
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
            animation: fadeIn 0.3s ease-out;
        }

        /* Animation for table rows */
        @keyframes fadeInRow {
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
            animation: fadeInRow 0.4s ease-out forwards;
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
        <div class="nav-container">
            <nav id="mainNav">
                <h3><i class="fas fa-compass"></i> Navigation</h3>
                <a href="dashboard.php"><i class="fas fa-tachometer-alt"></i>Dashboard</a>
                <a href="categories.php"><i class="fas fa-list"></i>Categories</a>
                <a href="services.php" class="active"><i class="fas fa-concierge-bell"></i>Services</a>
                <a href="add_category.php"><i class="fas fa-plus-circle"></i>Add Category</a>
                <a href="add_service.php"><i class="fas fa-plus-square"></i>Add Service</a>
                <a href="offer.php"><i class="fas fa-images"></i>Offer Images</a>
                <a href="admin_restaurant.php"><i class="fas fa-images"></i>Restaurant Logo & Name</a>
            </nav>

            <main>
                <?php if (isset($_SESSION['message'])): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle" style="margin-right: 8px;"></i>
                        <?= $_SESSION['message'];
                        unset($_SESSION['message']); ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle" style="margin-right: 8px;"></i>
                        <?= $_SESSION['error'];
                        unset($_SESSION['error']); ?>
                    </div>
                <?php endif; ?>

                <div class="section">
                    <h2><i class="fas fa-concierge-bell" style="margin-right: 10px;"></i>Services Management</h2>
                    <div class="search-filter">
                        <a class="btn" href="add_service.php">
                            <i class="fas fa-plus"></i> Add New Service
                        </a>
                        <input type="text" id="searchInput" class="search-box"
                            placeholder="Search services by name, category..." onkeyup="searchServices()">
                    </div>
                    <div class="table-wrapper">
                        <table id="servicesTable">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Category</th>
                                    <th>Price</th>
                                    <th>Image</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($services as $index => $service): ?>
                                    <tr style="--row-index: <?= $index ?>">
                                        <td><?= $service['id'] ?></td>
                                        <td><?= htmlspecialchars($service['name']) ?></td>
                                        <td><?= htmlspecialchars($service['category_name']) ?></td>
                                        <td class="price">Rs. <?= number_format($service['price'], 2) ?></td>
                                        <td>
                                            <?php if ($service['image']): ?>
                                                <img src="../<?= htmlspecialchars($service['image']) ?>" class="service-image"
                                                    alt="<?= htmlspecialchars($service['name']) ?>">
                                            <?php else: ?>
                                                <span style="color: #777;">No image</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <a href="edit_service.php?id=<?= $service['id'] ?>"
                                                    class="action-btn edit-btn">
                                                    <i class="fas fa-edit"></i> Edit
                                                </a>
                                                <a href="services.php?delete_service=<?= $service['id'] ?>&csrf_token=<?= $_SESSION['csrf_token'] ?>"
                                                    class="action-btn delete-btn"
                                                    onclick="return confirm('Are you sure you want to delete this service? This action cannot be undone.')">
                                                    <i class="fas fa-trash-alt"></i> Delete
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const menuToggle = document.getElementById('menuToggle');
            const nav = document.getElementById('mainNav');
            const navContainer = document.querySelector('.nav-container');
            const body = document.body;

            // Toggle mobile menu
            menuToggle.addEventListener('click', function (e) {
                e.stopPropagation();
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
            document.querySelectorAll('#mainNav a').forEach(link => {
                link.addEventListener('click', function () {
                    if (window.innerWidth <= 768) {
                        nav.classList.remove('open');
                        menuToggle.querySelector('i').classList.remove('fa-times');
                        menuToggle.querySelector('i').classList.add('fa-bars');
                    }
                });
            });

            // Close menu when clicking outside (for mobile)
            document.addEventListener('click', function (e) {
                if (window.innerWidth <= 768 &&
                    nav.classList.contains('open') &&
                    !nav.contains(e.target) &&
                    e.target !== menuToggle &&
                    !menuToggle.contains(e.target)) {
                    nav.classList.remove('open');
                    menuToggle.querySelector('i').classList.remove('fa-times');
                    menuToggle.querySelector('i').classList.add('fa-bars');
                }
            });

            // Handle window resize
            window.addEventListener('resize', function () {
                if (window.innerWidth > 768 && nav.classList.contains('open')) {
                    nav.classList.remove('open');
                    menuToggle.querySelector('i').classList.remove('fa-times');
                    menuToggle.querySelector('i').classList.add('fa-bars');
                }
            });
        });

        function searchServices() {
            const input = document.getElementById('searchInput');
            const filter = input.value.toUpperCase();
            const table = document.getElementById('servicesTable');
            const tr = table.getElementsByTagName('tr');

            for (let i = 1; i < tr.length; i++) {
                const tdName = tr[i].getElementsByTagName('td')[1];
                const tdCategory = tr[i].getElementsByTagName('td')[2];
                if (tdName || tdCategory) {
                    const txtValue = (tdName.textContent || tdName.innerText) + ' ' + (tdCategory.textContent || tdCategory.innerText);
                    if (txtValue.toUpperCase().indexOf(filter) > -1) {
                        tr[i].style.display = '';
                    } else {
                        tr[i].style.display = 'none';
                    }
                }
            }
        }
    </script>

</body>

</html>