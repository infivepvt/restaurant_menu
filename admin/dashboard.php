<?php
session_start();
require '../config.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="icon" type="image/x-icon" href="../assets/image/Logo1.ico">
    <title>Dashboard - Restaurant Admin</title>
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

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            background-color: var(--bg-light);
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
            position: relative;
            z-index: 100;
        }

        .container {
            display: flex;
            flex-direction: column;
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
            background: #fff;
            border-radius: 10px;
            box-shadow: var(--card-shadow);
            padding: 2rem;
            margin-bottom: 2rem;
            transition: transform 0.3s ease;
        }

        .section:hover {
            transform: translateY(-3px);
        }

        h2 {
            margin-top: 0;
            margin-bottom: 1.5rem;
            color: var(--gradient-mid);
            font-weight: 600;
            font-size: 1.5rem;
            position: relative;
        }

        h1 {
            font-size: 1.8rem;
            color: white;
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
            padding: 0.7rem 1.5rem;
            background: linear-gradient(135deg, var(--gradient-dark), var(--gradient-mid), var(--gradient-light));
            color: white;
            text-decoration: none;
            border-radius: 30px;
            font-weight: 500;
            transition: all 0.3s ease;
            box-shadow: 0 2px 5px rgba(199, 16, 46, 0.3);
            border: none;
            cursor: pointer;
        }

        .btn i {
            margin-right: 8px;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(199, 16, 46, 0.4);
        }

        .btn-outline {
            background: transparent;
            border: 2px solid var(--primary-color);
            color: var(--primary-color);
        }

        .btn-outline:hover {
            background: var(--primary-color);
            color: white;
        }

        .action-links a {
            color: var(--primary-color);
            margin-right: 15px;
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .action-links a:hover {
            color: var(--primary-dark);
            text-decoration: underline;
        }

        .quick-actions {
            display: flex;
            gap: 15px;
            margin-top: 1.5rem;
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

        .nav-container {
            display: flex;
            flex-direction: row;
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
        }

        .logout-btn:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: translateY(-2px);
        }

        .logout-btn i {
            margin-right: 8px;
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
                <a href="services.php"><i class="fas fa-concierge-bell"></i>Services</a>
                <a href="add_category.php"><i class="fas fa-plus-circle"></i>Add Category</a>
                <a href="add_service.php"><i class="fas fa-plus-square"></i>Add Service</a>
                <a href="offer.php"><i class="fas fa-images"></i>Offer Images</a>
                <a href="admin_restaurant.php"><i class="fas fa-images"></i>Restaurant Logo & Name</a>
            </nav>

            <main>
                <div class="section welcome-section">
                    <h2>Welcome to Admin Dashboard</h2>
                    <p>Manage your restaurant's menu, categories, and promotional offers with ease. Use the navigation
                        menu to access different sections.</p>

                    <div class="quick-actions">
                        <a href="add_category.php" class="btn">
                            <i class="fas fa-plus-circle"></i> Add New Category
                        </a>
                        <a href="add_service.php" class="btn">
                            <i class="fas fa-plus-square"></i> Add New Service
                        </a>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
        // Enhanced JavaScript
        document.addEventListener('DOMContentLoaded', function () {
            const menuToggle = document.getElementById('menuToggle');
            const nav = document.getElementById('mainNav');
            const navContainer = document.querySelector('.nav-container');
            const body = document.body;

            // Toggle mobile menu
            menuToggle.addEventListener('click', function (e) {
                e.stopPropagation(); // Prevent event bubbling
                nav.classList.toggle('open');
                navContainer.classList.toggle('nav-open');

                // Change icon
                const icon = this.querySelector('i');
                icon.classList.toggle('fa-bars');
                icon.classList.toggle('fa-times');
            });

            // Close menu when clicking on a link (for mobile)
            document.querySelectorAll('#mainNav a').forEach(link => {
                link.addEventListener('click', function () {
                    if (window.innerWidth <= 768) {
                        nav.classList.remove('open');
                        navContainer.classList.remove('nav-open');
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
                    navContainer.classList.remove('nav-open');
                    menuToggle.querySelector('i').classList.remove('fa-times');
                    menuToggle.querySelector('i').classList.add('fa-bars');
                }
            });

            // Handle window resize
            window.addEventListener('resize', function () {
                if (window.innerWidth > 768) {
                    nav.classList.remove('open');
                    navContainer.classList.remove('nav-open');
                    menuToggle.querySelector('i').classList.remove('fa-times');
                    menuToggle.querySelector('i').classList.add('fa-bars');
                }
            });
        });
    </script>


</body>

</html>