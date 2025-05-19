<?php
require 'config.php';

// Fetch restaurant info
$restaurant_stmt = $pdo->query("SELECT name, logo_path FROM restaurant_info LIMIT 1");
$restaurant = $restaurant_stmt->fetch(PDO::FETCH_ASSOC);

// Fetch active offer banners
$banners = $pdo->query("SELECT * FROM offer_banners WHERE is_active = 1 ORDER BY display_order ASC, created_at DESC")->fetchAll();

// Get all categories with their services
$stmt = $pdo->query("
    SELECT c.id AS cat_id, c.name AS cat_name, c.image AS cat_image,
           s.id AS service_id, s.name, s.description, s.image, s.price
    FROM categories c
    LEFT JOIN services s ON c.id = s.category_id
    ORDER BY c.name, s.name
");

$categories = [];
$all_services = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $cat_id = $row['cat_id'];
    if (!isset($categories[$cat_id])) {
        $categories[$cat_id] = [
            'id' => $row['cat_id'],
            'name' => $row['cat_name'],
            'image' => $row['cat_image'],
            'services' => []
        ];
    }
    if ($row['service_id']) {
        $service = [
            'id' => $row['service_id'],
            'name' => $row['name'],
            'description' => $row['description'],
            'image' => $row['image'],
            'price' => $row['price'],
            'category_id' => $row['cat_id']
        ];
        $categories[$cat_id]['services'][] = $service;
        $all_services[] = $service;
    }
}

// Check if a category filter is applied
$filtered_category = isset($_GET['category']) ? intval($_GET['category']) : null;
$filtered_services = $all_services;

if ($filtered_category) {
    $filtered_services = array_filter($all_services, function ($service) use ($filtered_category) {
        return $service['category_id'] == $filtered_category;
    });

    $filtered_categories = [];
    foreach ($filtered_services as $service) {
        $cat_id = $service['category_id'];
        if (!isset($filtered_categories[$cat_id])) {
            $filtered_categories[$cat_id] = $categories[$cat_id];
            $filtered_categories[$cat_id]['services'] = [];
        }
        $filtered_categories[$cat_id]['services'][] = $service;
    }
} else {
    $filtered_categories = $categories;
}
?>

<!DOCTYPE html>
<html lang="si">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($restaurant['name'] ?? 'Our Services') ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="icon" type="image/x-icon"
        href="<?= htmlspecialchars($restaurant['logo_path'] ?? 'assets/image/Logo1.ico') ?>">
    <style>
        :root {
            --primary: #ff6b6b;
            --primary-light: #ff8a8a;
            --dark: #1a1a2e;
            --darker: #16213e;
            --light: #e6e6e6;
            --lighter: #f8f9fa;
            --gray: #4a4a4a;
            --gray-light: #6c757d;
            --success: #06d6a0;
            --success-light: #07f7b6;
            --text-dark: #212529;
            --text-light: #f8f9fa;
            --bg-dark: #1a1a2e;
            --bg-light: #f8f9fa;
            --card-dark: #16213e;
            --card-light: #ffffff;
            --border-dark: rgba(255, 255, 255, 0.1);
            --border-light: rgba(0, 0, 0, 0.1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
        }

        /* Dark Mode Default */
        body.dark-mode {
            background-color: var(--bg-dark);
            color: var(--light);
        }

        /* Light Mode */
        body.light-mode {
            background-color: var(--bg-light);
            color: var(--text-dark);
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2px;
        }

        header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 1px solid;
        }

        .dark-mode header {
            background: linear-gradient(135deg, var(--darker) 0%, var(--dark) 100%);
            border-bottom-color: var(--border-dark);
        }

        .light-mode header {
            background: linear-gradient(135deg, #e9ecef 0%, #dee2e6 100%);
            border-bottom-color: var(--border-light);
        }

        .restaurant-info {
            display: flex;
            flex-direction: row;
            align-items: center;
            justify-content: space-between;
            padding: 20px 0;
            width: 100%;
        }

        .logo-container {
            flex-shrink: 0;
            order: 2;
        }

        .restaurant-logo {
            width: 100px;
            height: 100px;
            object-fit: contain;
            border-radius: 50%;
            border: 2px solid var(--primary);
        }

        .restaurant-logo-placeholder {
            width: 100px;
            height: 100px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            border: 2px solid var(--primary);
        }

        .dark-mode .restaurant-logo-placeholder {
            background-color: var(--gray);
        }

        .light-mode .restaurant-logo-placeholder {
            background-color: var(--gray-light);
        }

        .restaurant-logo-placeholder i {
            font-size: 2.5rem;
        }

        .dark-mode .restaurant-logo-placeholder i {
            color: var(--dark);
        }

        .light-mode .restaurant-logo-placeholder i {
            color: var(--lighter);
        }

        h1 {
            font-size: 1.8rem;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            text-align: left;
            margin: 0;
            order: 1;
        }

        .dark-mode h1 {
            color: var(--primary);
        }

        .light-mode h1 {
            color: var(--primary);
        }

        /* Updated Search Bar Styles */
        .search-container {
            position: relative;
            margin-bottom: 10px;
            max-width: 500px;
            width: 100%;
        }

        .search-input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
            background-color: var(--card-light);
            border-radius: 25px;
            padding: 5px 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .dark-mode .search-input-wrapper {
            background-color: var(--card-dark);
            box-shadow: 0 4px 10px rgba(255, 255, 255, 0.05);
        }

        .search-input-wrapper:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
        }

        .search-input {
            width: 100%;
            padding: 10px 40px 10px 40px; /* Adjusted for icons */
            border: none;
            border-radius: 25px;
            font-size: 1rem;
            outline: none;
            background: transparent;
        }

        .dark-mode .search-input {
            color: var(--light);
        }

        .light-mode .search-input {
            color: var(--text-dark);
        }

        .search-input::placeholder {
            color: var(--gray-light);
            opacity: 0.7;
        }

        .search-icon {
            position: absolute;
            left: 15px;
            color: var(--gray-light);
            font-size: 1.1rem;
        }

        .search-loading {
            position: absolute;
            right: 15px;
            color: var(--primary);
            font-size: 1.1rem;
            display: none;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            100% {
                transform: rotate(360deg);
            }
        }

        .search-suggestions {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background-color: var(--card-light);
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
            max-height: 300px;
            overflow-y: auto;
            z-index: 1000;
            display: none;
            margin-top: 5px;
        }

        .dark-mode .search-suggestions {
            background-color: var(--card-dark);
            box-shadow: 0 4px 10px rgba(255, 255, 255, 0.05);
        }

        .search-suggestions ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .search-suggestions li {
            padding: 10px 15px;
            cursor: pointer;
            display: flex;
            align-items: center;
            transition: background-color 0.2s ease;
        }

        .search-suggestions li:hover {
            background-color: var(--primary-light);
            color: white;
        }

        .search-suggestions li i {
            margin-right: 10px;
            color: var(--primary);
        }

        .dark-mode .search-suggestions li {
            color: var(--light);
        }

        .light-mode .search-suggestions li {
            color: var(--text-dark);
        }

        .tab {
            display: flex;
            gap: 10px;
            margin-bottom: 30px;
            padding: 15px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            overflow-x: auto;
            white-space: nowrap;
            scrollbar-width: thin;
        }

        .dark-mode .tab {
            background-color: var(--darker);
            scrollbar-color: var(--primary) var(--darker);
        }

        .light-mode .tab {
            background-color: var(--card-light);
            scrollbar-color: var(--primary) var(--card-light);
        }

        .tab::-webkit-scrollbar {
            height: 8px;
        }

        .dark-mode .tab::-webkit-scrollbar-track {
            background: var(--darker);
        }

        .light-mode .tab::-webkit-scrollbar-track {
            background: var(--card-light);
        }

        .tab::-webkit-scrollbar-thumb {
            background: var(--primary);
            border-radius: 10px;
        }

        .tab::-webkit-scrollbar-thumb:hover {
            background: var(--primary-light);
        }

        .tablinks {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 10px 15px;
            border-radius: 30px;
            text-decoration: none;
            transition: all 0.3s ease;
            font-size: 0.95rem;
            border: none;
            cursor: pointer;
            flex-shrink: 0;
        }

        .dark-mode .tablinks {
            color: var(--light);
            background-color: var(--gray);
        }

        .light-mode .tablinks {
            color: var(--text-dark);
            background-color: #e9ecef;
        }

        .tablinks img {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            margin-right: 8px;
            object-fit: cover;
            vertical-align: middle;
            flex-shrink: 0;
        }

        .tablinks i {
            margin-right: 8px;
            font-size: 0.9em;
            flex-shrink: 0;
        }

        .tablinks:hover {
            background-color: var(--primary);
            transform: translateY(-2px);
            color: white;
        }

        .tablinks.active {
            background-color: var(--primary);
            font-weight: bold;
            color: white;
        }

        .category-section {
            margin-bottom: 40px;
        }

        .category-header {
            display: flex;
            align-items: center;
            margin-bottom: 25px;
            padding: 15px;
            border-radius: 10px;
            transition: background-color 0.3s ease;
        }

        .dark-mode .category-header {
            background-color: rgba(255, 255, 255, 0.05);
        }

        .light-mode .category-header {
            background-color: rgba(0, 0, 0, 0.03);
        }

        .dark-mode .category-header:hover {
            background-color: rgba(255, 255, 255, 0.08);
        }

        .light-mode .category-header:hover {
            background-color: rgba(0, 0, 0, 0.05);
        }

        .category-image {
            width: 80px;
            height: 80px;
            border-radius: 10px;
            object-fit: cover;
            margin-right: 20px;
            border: 3px solid var(--primary);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .category-title {
            font-size: 1.8rem;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }

        .dark-mode .category-title {
            color: var(--primary);
        }

        .light-mode .category-title {
            color: var(--primary);
        }

        .services-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 25px;
        }

        .service-card {
            border-radius: 10px;
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            cursor: pointer;
            display: flex;
            flex-direction: row;
            align-items: center;
            padding: 15px;
        }

        .dark-mode .service-card {
            background-color: var(--darker);
            border: 1px solid var(--border-dark);
        }

        .light-mode .service-card {
            background-color: var(--card-light);
            border: 1px solid var(--border-light);
        }

        .service-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
        }

        .service-image-container {
            width: 120px;
            height: 120px;
            overflow: hidden;
            margin-right: 15px;
            flex-shrink: 0;
            border-radius: 8px;
            border: 2px solid var(--primary);
        }

        .service-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .service-card:hover .service-image {
            transform: scale(1.05);
        }

        .service-content {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            min-height: 120px;
        }

        .service-title {
            font-size: 1.2rem;
            margin-bottom: 8px;
        }

        .dark-mode .service-title {
            color: white;
        }

        .light-mode .service-title {
            color: var(--text-dark);
        }

        .service-price {
            font-size: 1.1rem;
            font-weight: bold;
        }

        .dark-mode .service-price {
            color: var(--success);
        }

        .light-mode .service-price {
            color: var(--success);
        }

        .no-services {
            text-align: center;
            padding: 40px;
            font-size: 1.2rem;
            grid-column: 1 / -1;
        }

        .dark-mode .no-services {
            color: var(--gray);
        }

        .light-mode .no-services {
            color: var(--gray-light);
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 1000;
            overflow-y: auto;
            padding: 20px;
            box-sizing: border-box;
        }

        .dark-mode .modal {
            background-color: rgba(0, 0, 0, 0.85);
        }

        .light-mode .modal {
            background-color: rgba(0, 0, 0, 0.7);
        }

        .modal-content {
            margin: 50px auto;
            max-width: 800px;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.4);
            border: 1px solid var(--primary);
            animation: modalFadeIn 0.3s ease-out;
        }

        .dark-mode .modal-content {
            background: linear-gradient(135deg, var(--darker) 0%, #1e2a44 100%);
        }

        .light-mode .modal-content {
            background: linear-gradient(135deg, #ffffff 0%, #f1f3f5 100%);
        }

        @keyframes modalFadeIn {
            from {
                opacity: 0;
                transform: translateY(-50px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .modal-header {
            position: relative;
            height: 300px;
            overflow: hidden;
            border-bottom: 2px solid var(--primary);
        }

        .modal-header-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .dark-mode .modal-header-image {
            filter: brightness(0.9);
        }

        .light-mode .modal-header-image {
            filter: brightness(1);
        }

        .modal-close {
            position: absolute;
            top: 15px;
            right: 15px;
            background-color: var(--primary);
            color: white;
            border: none;
            border-radius: 50%;
            width: 45px;
            height: 45px;
            font-size: 1.4rem;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
        }

        .modal-close:hover {
            background-color: var(--primary-light);
            transform: scale(1.1);
        }

        .modal-body {
            padding: 35px;
        }

        .modal-title {
            font-size: 2rem;
            margin-bottom: 15px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .dark-mode .modal-title {
            color: var(--primary);
        }

        .light-mode .modal-title {
            color: var(--primary);
        }

        .modal-price {
            font-size: 1.6rem;
            font-weight: bold;
            margin-bottom: 20px;
            padding: 8px 15px;
            border-radius: 5px;
            display: inline-block;
        }

        .dark-mode .modal-price {
            color: var(--success);
            background-color: rgba(6, 214, 160, 0.1);
        }

        .light-mode .modal-price {
            color: var(--success);
            background-color: rgba(6, 214, 160, 0.1);
        }

        .modal-description {
            margin-bottom: 25px;
            line-height: 1.8;
            font-size: 1rem;
        }

        .dark-mode .modal-description {
            color: var(--light);
        }

        .light-mode .modal-description {
            color: var(--text-dark);
        }

        .modal-category {
            display: inline-block;
            padding: 8px 20px;
            border-radius: 25px;
            font-size: 0.95rem;
            margin-bottom: 20px;
            font-weight: 500;
        }

        .dark-mode .modal-category {
            background-color: rgba(255, 107, 107, 0.3);
            color: var(--primary);
        }

        .light-mode .modal-category {
            background-color: rgba(255, 107, 107, 0.2);
            color: var(--primary);
        }

        .offer-banner {
            position: relative;
            height: 300px;
            overflow: hidden;
            max-width: 1200px;
            margin: 20px auto;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
        }

        .offer-banner img {
            position: absolute;
            width: 100%;
            height: 100%;
            object-fit: cover;
            object-position: center;
            opacity: 0;
            transition: opacity 1s ease-in-out;
            left: 0;
            right: 0;
            margin: 0 auto;
        }

        .offer-banner img.active {
            opacity: 1;
        }

        .banner-nav {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background-color: rgba(26, 26, 46, 0.6);
            border: none;
            color: white;
            font-size: 0.8rem;
            padding: 10px 15px;
            cursor: pointer;
            z-index: 10;
            border-radius: 50%;
            transition: background-color 0.3s ease;
        }

        .banner-nav.left {
            left: 10px;
        }

        .banner-nav.right {
            right: 10px;
        }

        .banner-nav:hover {
            background-color: var(--primary);
        }

        /* Theme Toggle */
        .theme-toggle {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 999;
            background-color: var(--primary);
            color: white;
            border: none;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            font-size: 1.2rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease;
        }

        .theme-toggle:hover {
            transform: scale(1.1);
            background-color: var(--primary-light);
        }

        @media (max-width: 768px) {
            h1 {
                font-size: 1.6rem;
            }

            .restaurant-info {
                padding: 15px 0;
                flex-direction: row;
                justify-content: space-between;
            }

            .restaurant-logo,
            .restaurant-logo-placeholder {
                width: 80px;
                height: 80px;
                border-radius: 50%;
            }

            .restaurant-logo-placeholder i {
                font-size: 2rem;
            }

            .services-grid {
                grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
                gap: 15px;
            }

            .category-title {
                font-size: 1.5rem;
            }

            .category-image {
                width: 60px;
                height: 60px;
            }

            .modal-content {
                margin: 20px auto;
            }

            .modal-header {
                height: 200px;
            }

            .modal-body {
                padding: 25px;
            }

            .service-card {
                padding: 10px;
            }

            .service-image-container {
                width: 100px;
                height: 100px;
            }

            .service-content {
                min-height: 100px;
            }

            .modal-title {
                font-size: 1.8rem;
            }

            .modal-price {
                font-size: 1.4rem;
            }

            .offer-banner {
                height: 250px;
                margin: 0 15px;
            }

            .tab {
                flex-wrap: nowrap;
                justify-content: flex-start;
                padding: 10px;
            }

            .tablinks {
                padding: 8px 12px;
                font-size: 0.9rem;
            }

            .search-input {
                padding: 8px 35px 8px 35px;
                font-size: 0.95rem;
            }

            .search-icon,
            .search-loading {
                font-size: 1rem;
            }
        }

        @media (max-width: 480px) {
            .restaurant-info {
                flex-direction: row;
                justify-content: space-between;
                align-items: center;
                padding: 10px 0;
            }

            .restaurant-logo,
            .restaurant-logo-placeholder {
                width: 60px;
                height: 60px;
                border-radius: 50%;
            }

            .restaurant-logo-placeholder i {
                font-size: 1.6rem;
            }

            h1 {
                font-size: 1.4rem;
                text-align: left;
            }

            .services-grid {
                grid-template-columns: 1fr;
            }

            .category-header {
                flex-direction: column;
                text-align: center;
            }

            .category-image {
                margin-right: 0;
                margin-bottom: 15px;
                width: 80px;
                height: 80px;
            }

            .modal-header {
                height: 150px;
            }

            .service-image-container {
                width: 80px;
                height: 80px;
            }

            .service-content {
                min-height: 80px;
            }

            .modal-title {
                font-size: 1.6rem;
            }

            .modal-price {
                font-size: 1.3rem;
            }

            .offer-banner {
                height: 200px;
            }

            .tablinks {
                padding: 6px 10px;
                font-size: 0.85rem;
            }

            .tablinks img,
            .tablinks i {
                margin-right: 6px;
            }

            .theme-toggle {
                width: 40px;
                height: 40px;
                font-size: 1rem;
                bottom: 15px;
                right: 15px;
            }

            .search-container {
                max-width: 100%;
            }

            .search-input {
                padding: 8px 30px 8px 30px;
                font-size: 0.9rem;
            }

            .search-icon,
            .search-loading {
                font-size: 0.9rem;
            }
        }
    </style>
</head>

<body class="dark-mode">
    <button class="theme-toggle" id="themeToggle">
        <i class="fas fa-moon"></i>
    </button>

    <header>
        <div class="container">
            <div class="restaurant-info">
                <div class="logo-container">
                    <?php if ($restaurant && $restaurant['logo_path']): ?>
                        <img src="<?= htmlspecialchars(str_replace('../', '/', $restaurant['logo_path'])) ?>"
                            alt="Restaurant Logo" class="restaurant-logo">
                    <?php else: ?>
                        <div class="restaurant-logo-placeholder">
                            <i class="fas fa-utensils"></i>
                        </div>
                    <?php endif; ?>
                </div>
                <h1><?= htmlspecialchars($restaurant['name'] ?? 'Our Services') ?></h1>
            </div>
        </div>
        <?php if (!empty($banners)): ?>
            <div class="offer-banner">
                <?php foreach ($banners as $index => $banner): ?>
                    <img src="<?= htmlspecialchars($banner['image_path']) ?>" alt="Special Offer"
                        class="<?= $index === 0 ? 'active' : '' ?>">
                <?php endforeach; ?>
                <button class="banner-nav left"><i class="fas fa-chevron-left"></i></button>
                <button class="banner-nav right"><i class="fas fa-chevron-right"></i></button>
            </div>
        <?php endif; ?>
    </header>

    <div class="container">
        <!-- Updated Search Bar -->
        <div class="search-container">
            <div class="search-input-wrapper">
                <i class="fas fa-search search-icon"></i>
                <input type="text" id="searchInput" class="search-input"
                    placeholder="Search by category or service name...">
                <i class="fas fa-spinner search-loading"></i>
            </div>
            <div class="search-suggestions" id="searchSuggestions">
                <ul id="suggestionsList"></ul>
            </div>
        </div>

        <!-- Tabbed Interface -->
        <div class="tab">
            <button class="tablinks <?= $filtered_category === null ? 'active' : '' ?>"
                onclick="openCategory(event, 'all')">All Categories</button>
            <?php foreach ($categories as $category): ?>
                <button class="tablinks <?= $filtered_category == $category['id'] ? 'active' : '' ?>"
                    onclick="openCategory(event, 'category-<?= $category['id'] ?>')">
                    <?php if ($category['image']): ?>
                        <img src="<?= htmlspecialchars($category['image']) ?>" alt="<?= htmlspecialchars($category['name']) ?>">
                    <?php else: ?>
                        <i class="fas fa-folder" style="margin-right:8px;"></i>
                    <?php endif; ?>
                    <?= htmlspecialchars($category['name']) ?>
                </button>
            <?php endforeach; ?>
        </div>

        <!-- Tab Content -->
        <div id="all" class="tabcontent <?= $filtered_category === null ? 'active' : '' ?>">
            <?php foreach ($categories as $cat_id => $category): ?>
                <?php if (!empty($category['services'])): ?>
                    <div class="category-section">
                        <div class="category-header">
                            <?php if ($category['image']): ?>
                                <img src="<?= htmlspecialchars($category['image']) ?>"
                                    alt="<?= htmlspecialchars($category['name']) ?>" class="category-image">
                            <?php else: ?>
                                <div class="category-image" style="display:flex;align-items:center;justify-content:center;">
                                    <i class="fas fa-folder" style="font-size:2rem;"></i>
                                </div>
                            <?php endif; ?>
                            <h2 class="category-title"><?= htmlspecialchars($category['name']) ?></h2>
                        </div>
                        <div class="services-grid">
                            <?php foreach ($category['services'] as $service): ?>
                                <div class="service-card" data-service-name="<?= htmlspecialchars($service['name'], ENT_QUOTES) ?>"
                                    data-service-image="<?= htmlspecialchars($service['image'] ?? '', ENT_QUOTES) ?>"
                                    data-service-price="<?= htmlspecialchars($service['price']) ?>"
                                    data-service-description="<?= htmlspecialchars($service['description'], ENT_QUOTES) ?>"
                                    data-service-category="<?= htmlspecialchars($category['name'], ENT_QUOTES) ?>">
                                    <div class="service-image-container">
                                        <?php if ($service['image']): ?>
                                            <img src="<?= htmlspecialchars($service['image']) ?>"
                                                alt="<?= htmlspecialchars($service['name']) ?>" class="service-image">
                                        <?php else: ?>
                                            <div style="height:100%;display:flex;align-items:center;justify-content:center;">
                                                <i class="fas fa-image" style="font-size:2.5rem;"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="service-content">
                                        <h3 class="service-title"><?= htmlspecialchars($service['name']) ?></h3>
                                        <div class="service-price">Rs. <?= number_format($service['price'], 2) ?></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
            <?php if (empty($categories)): ?>
                <div class="no-services">
                    <i class="fas fa-box-open" style="font-size:2rem;margin-bottom:15px;"></i>
                    <p>No services found</p>
                </div>
            <?php endif; ?>
        </div>

        <?php foreach ($categories as $cat_id => $category): ?>
            <div id="category-<?= $cat_id ?>" class="tabcontent <?= $filtered_category == $cat_id ? 'active' : '' ?>">
                <?php if (!empty($category['services'])): ?>
                    <div class="category-section">
                        <div class="category-header">
                            <?php if ($category['image']): ?>
                                <img src="<?= htmlspecialchars($category['image']) ?>"
                                    alt="<?= htmlspecialchars($category['name']) ?>" class="category-image">
                            <?php else: ?>
                                <div class="category-image" style="display:flex;align-items:center;justify-content:center;">
                                    <i class="fas fa-folder" style="font-size:2rem;"></i>
                                </div>
                            <?php endif; ?>
                            <h2 class="category-title"><?= htmlspecialchars($category['name']) ?></h2>
                        </div>
                        <div class="services-grid">
                            <?php foreach ($category['services'] as $service): ?>
                                <div class="service-card" data-service-name="<?= htmlspecialchars($service['name'], ENT_QUOTES) ?>"
                                    data-service-image="<?= htmlspecialchars($service['image'] ?? '', ENT_QUOTES) ?>"
                                    data-service-price="<?= htmlspecialchars($service['price']) ?>"
                                    data-service-description="<?= htmlspecialchars($service['description'], ENT_QUOTES) ?>"
                                    data-service-category="<?= htmlspecialchars($category['name'], ENT_QUOTES) ?>">
                                    <div class="service-image-container">
                                        <?php if ($service['image']): ?>
                                            <img src="<?= htmlspecialchars($service['image']) ?>"
                                                alt="<?= htmlspecialchars($service['name']) ?>" class="service-image">
                                        <?php else: ?>
                                            <div style="height:100%;display:flex;align-items:center;justify-content:center;">
                                                <i class="fas fa-image" style="font-size:2.5rem;"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="service-content">
                                        <h3 class="service-title"><?= htmlspecialchars($service['name']) ?></h3>
                                        <div class="service-price">Rs. <?= number_format($service['price'], 2) ?></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="no-services">
                        <i class="fas fa-box-open" style="font-size:2rem;margin-bottom:15px;"></i>
                        <p>No services found in this category</p>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>

        <!-- Search Results -->
        <div id="searchResults" class="tabcontent" style="display: none;">
            <div id="searchResultsContent"></div>
        </div>
    </div>

    <!-- Service Modal -->
    <div id="serviceModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <img id="modalServiceImage" class="modal-header-image" src="" alt="Service Image">
                <button class="modal-close" onclick="closeModal()"><i class="fas fa-times"></i></button>
            </div>
            <div class="modal-body">
                <span id="modalCategory" class="modal-category"></span>
                <h2 id="modalTitle" class="modal-title"></h2>
                <div id="modalPrice" class="modal-price"></div>
                <p id="modalDetailedDescription" class="modal-description"></p>
            </div>
        </div>
    </div>

    <script>
        // Theme Toggle Functionality
        const themeToggle = document.getElementById('themeToggle');
        const body = document.body;
        const currentTheme = localStorage.getItem('theme') || 'dark';

        // Set initial theme
        if (currentTheme === 'light') {
            body.classList.remove('dark-mode');
            body.classList.add('light-mode');
            themeToggle.innerHTML = '<i class="fas fa-sun"></i>';
        } else {
            body.classList.remove('light-mode');
            body.classList.add('dark-mode');
            themeToggle.innerHTML = '<i class="fas fa-moon"></i>';
        }

        // Toggle theme
        themeToggle.addEventListener('click', () => {
            if (body.classList.contains('dark-mode')) {
                body.classList.remove('dark-mode');
                body.classList.add('light-mode');
                localStorage.setItem('theme', 'light');
                themeToggle.innerHTML = '<i class="fas fa-sun"></i>';
            } else {
                body.classList.remove('light-mode');
                body.classList.add('dark-mode');
                localStorage.setItem('theme', 'dark');
                themeToggle.innerHTML = '<i class="fas fa-moon"></i>';
            }
        });

        // Tab Switching Function
        function openCategory(evt, categoryId) {
            const tabcontent = document.getElementsByClassName("tabcontent");
            for (let i = 0; i < tabcontent.length; i++) {
                tabcontent[i].style.display = "none";
                tabcontent[i].classList.remove("active");
            }

            const tablinks = document.getElementsByClassName("tablinks");
            for (let i = 0; i < tablinks.length; i++) {
                tablinks[i].classList.remove("active");
            }

            const targetTab = document.getElementById(categoryId);
            targetTab.style.display = "block";
            targetTab.classList.add("active");
            evt.currentTarget.classList.add("active");

            // Hide search results and suggestions when a category is selected
            document.getElementById('searchResults').style.display = 'none';
            document.getElementById('searchInput').value = '';
            document.getElementById('searchSuggestions').style.display = 'none';
        }

        // Modal Functions
        function openServiceModal(name, image, price, detailedDescription, category) {
            const modal = document.getElementById('serviceModal');
            const modalImage = document.getElementById('modalServiceImage');
            const modalTitle = document.getElementById('modalTitle');
            const modalPrice = document.getElementById('modalPrice');
            const modalDetailedDescription = document.getElementById('modalDetailedDescription');
            const modalCategory = document.getElementById('modalCategory');

            try {
                modalTitle.textContent = name || 'Unnamed Service';
                modalPrice.textContent = price ? 'Rs. ' + parseFloat(price).toFixed(2) : 'Price not available';
                modalDetailedDescription.innerHTML = detailedDescription || 'No detailed description available';
                modalCategory.textContent = category || 'Uncategorized';

                if (image) {
                    modalImage.src = image;
                    modalImage.style.display = 'block';
                    document.querySelector('.modal-header').style.backgroundColor = '';
                    document.querySelector('.modal-header').style.display = '';
                    document.querySelector('.modal-header').style.alignItems = '';
                    document.querySelector('.modal-header').style.justifyContent = '';
                    document.querySelector('.modal-header').innerHTML = `
                        <img id="modalServiceImage" class="modal-header-image" src="${image}" alt="Service Image">
                        <button class="modal-close" onclick="closeModal()"><i class="fas fa-times"></i></button>
                    `;
                } else {
                    modalImage.style.display = 'none';
                    if (body.classList.contains('dark-mode')) {
                        document.querySelector('.modal-header').style.backgroundColor = 'var(--gray)';
                    } else {
                        document.querySelector('.modal-header').style.backgroundColor = 'var(--gray-light)';
                    }
                    document.querySelector('.modal-header').style.display = 'flex';
                    document.querySelector('.modal-header').style.alignItems = 'center';
                    document.querySelector('.modal-header').style.justifyContent = 'center';
                    document.querySelector('.modal-header').innerHTML = `
                        <i class="fas fa-image" style="font-size:3rem;"></i>
                        <button class="modal-close" onclick="closeModal()"><i class="fas fa-times"></i></button>
                    `;
                }

                modal.style.display = 'block';
                document.body.style.overflow = 'hidden';
            } catch (error) {
                console.error('Error opening modal:', error);
                alert('An error occurred while opening the service details. Please try again.');
            }
        }

        function closeModal() {
            const modal = document.getElementById('serviceModal');
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        // Search Functionality
        const categoriesData = <?php echo json_encode($categories); ?>;
        const searchInput = document.getElementById('searchInput');
        const searchSuggestions = document.getElementById('searchSuggestions');
        const suggestionsList = document.getElementById('suggestionsList');
        const searchLoading = document.querySelector('.search-loading');

        function searchServices() {
            const searchTerm = searchInput.value.trim().toLowerCase();
            const searchResultsContent = document.getElementById('searchResultsContent');
            const searchResultsTab = document.getElementById('searchResults');
            const tabcontent = document.getElementsByClassName("tabcontent");
            const tablinks = document.getElementsByClassName("tablinks");

            // Show loading spinner
            searchLoading.style.display = 'block';

            // Simulate search delay for better UX
            setTimeout(() => {
                // Hide loading spinner
                searchLoading.style.display = 'none';

                // Hide all tab content and remove active class from tablinks
                for (let i = 0; i < tabcontent.length; i++) {
                    tabcontent[i].style.display = "none";
                    tabcontent[i].classList.remove("active");
                }
                for (let i = 0; i < tablinks.length; i++) {
                    tablinks[i].classList.remove("active");
                }

                // If search input is empty, show the default "All" tab
                if (!searchTerm) {
                    document.getElementById('all').style.display = 'block';
                    document.getElementById('all').classList.add('active');
                    document.querySelector('.tablinks[onclick*="all"]').classList.add('active');
                    searchResultsTab.style.display = 'none';
                    searchSuggestions.style.display = 'none';
                    return;
                }

                // Show search results tab
                searchResultsTab.style.display = 'block';
                searchResultsTab.classList.add("active");

                // Filter categories and services
                let filteredCategories = {};
                for (const catId in categoriesData) {
                    const category = categoriesData[catId];
                    const matchesCategory = category.name.toLowerCase().includes(searchTerm);
                    const filteredServices = category.services.filter(service =>
                        service.name.toLowerCase().includes(searchTerm) ||
                        (service.description && service.description.toLowerCase().includes(searchTerm))
                    );

                    if (matchesCategory || filteredServices.length > 0) {
                        filteredCategories[catId] = {
                            ...category,
                            services: matchesCategory ? category.services : filteredServices
                        };
                    }
                }

                // Generate HTML for search results
                let html = '';
                if (Object.keys(filteredCategories).length === 0) {
                    html = `
                        <div class="no-services">
                            <i class="fas fa-box-open" style="font-size:2rem;margin-bottom:15px;"></i>
                            <p>No services or categories found matching "${searchTerm}"</p>
                        </div>
                    `;
                } else {
                    for (const catId in filteredCategories) {
                        const category = filteredCategories[catId];
                        if (category.services.length > 0) {
                            html += `
                                <div class="category-section">
                                    <div class="category-header">
                                        ${category.image ?
                                            `<img src="${category.image}" alt="${category.name}" class="category-image">` :
                                            `<div class="category-image" style="display:flex;align-items:center;justify-content:center;">
                                                <i class="fas fa-folder" style="font-size:2rem;"></i>
                                            </div>`
                                        }
                                        <h2 class="category-title">${category.name}</h2>
                                    </div>
                                    <div class="services-grid">
                            `;
                            category.services.forEach(service => {
                                html += `
                                    <div class="service-card" 
                                         data-service-name="${service.name.replace(/"/g, '&quot;')}" 
                                         data-service-image="${service.image ? service.image.replace(/"/g, '&quot;') : ''}" 
                                         data-service-price="${service.price}" 
                                         data-service-description="${service.description ? service.description.replace(/"/g, '&quot;') : ''}" 
                                         data-service-category="${category.name.replace(/"/g, '&quot;')}">
                                        <div class="service-image-container">
                                            ${service.image ?
                                                `<img src="${service.image}" alt="${service.name}" class="service-image">` :
                                                `<div style="height:100%;display:flex;align-items:center;justify-content:center;">
                                                    <i class="fas fa-image" style="font-size:2.5rem;"></i>
                                                </div>`
                                            }
                                        </div>
                                        <div class="service-content">
                                            <h3 class="service-title">${service.name}</h3>
                                            <div class="service-price">Rs. ${parseFloat(service.price).toFixed(2)}</div>
                                        </div>
                                    </div>
                                `;
                            });
                            html += `</div></div>`;
                        }
                    }
                }

                searchResultsContent.innerHTML = html;

                // Add event listeners to new service cards
                document.querySelectorAll('#searchResults .service-card').forEach(card => {
                    card.addEventListener('click', function () {
                        const name = this.dataset.serviceName;
                        const image = this.dataset.serviceImage;
                        const price = this.dataset.servicePrice;
                        const description = this.dataset.serviceDescription;
                        const category = this.dataset.serviceCategory;
                        openServiceModal(name, image, price, description, category);
                    });
                });
            }, 300); // Simulated delay
        }

        // Search Suggestions Functionality
        function showSuggestions() {
            const searchTerm = searchInput.value.trim().toLowerCase();
            suggestionsList.innerHTML = '';

            if (!searchTerm) {
                searchSuggestions.style.display = 'none';
                return;
            }

            let suggestions = [];
            for (const catId in categoriesData) {
                const category = categoriesData[catId];
                if (category.name.toLowerCase().includes(searchTerm)) {
                    suggestions.push({ type: 'category', name: category.name, id: catId });
                }
                category.services.forEach(service => {
                    if (service.name.toLowerCase().includes(searchTerm) ||
                        (service.description && service.description.toLowerCase().includes(searchTerm))) {
                        suggestions.push({ type: 'service', name: service.name, category: category.name, ...service });
                    }
                });
            }

            if (suggestions.length > 0) {
                suggestions.forEach(item => {
                    const li = document.createElement('li');
                    li.innerHTML = `
                        <i class="fas fa-${item.type === 'category' ? 'folder' : 'utensils'}"></i>
                        ${item.name} ${item.type === 'service' ? `<span style="color: var(--gray-light); font-size: 0.9rem;">(in ${item.category})</span>` : ''}
                    `;
                    li.addEventListener('click', () => {
                        searchInput.value = item.name;
                        searchSuggestions.style.display = 'none';
                        if (item.type === 'category') {
                            openCategory({ currentTarget: document.querySelector(`.tablinks[onclick*="category-${item.id}"]`) }, `category-${item.id}`);
                        } else {
                            searchServices();
                        }
                    });
                    suggestionsList.appendChild(li);
                });
                searchSuggestions.style.display = 'block';
            } else {
                searchSuggestions.style.display = 'none';
            }
        }

        // Event Listeners
        document.addEventListener('DOMContentLoaded', function () {
            // Service Card Clicks
            document.querySelectorAll('.service-card').forEach(card => {
                card.addEventListener('click', function () {
                    const name = this.dataset.serviceName;
                    const image = this.dataset.serviceImage;
                    const price = this.dataset.servicePrice;
                    const description = this.dataset.serviceDescription;
                    const category = this.dataset.serviceCategory;
                    openServiceModal(name, image, price, description, category);
                });
            });

            // Search Input Events
            searchInput.addEventListener('input', showSuggestions);
            searchInput.addEventListener('keypress', function (event) {
                if (event.key === 'Enter') {
                    searchSuggestions.style.display = 'none';
                    searchServices();
                }
            });

            // Close suggestions when clicking outside
            document.addEventListener('click', function (event) {
                if (!searchContainer.contains(event.target)) {
                    searchSuggestions.style.display = 'none';
                }
            });

            // Banner Slider
            const banners = document.querySelectorAll('.offer-banner img');
            const leftBtn = document.querySelector('.banner-nav.left');
            const rightBtn = document.querySelector('.banner-nav.right');
            let current = 0;
            let interval;

            function showBanner(index) {
                banners.forEach(banner => banner.classList.remove('active'));
                current = (index + banners.length) % banners.length;
                banners[current].classList.add('active');
            }

            function startAutoSlide() {
                interval = setInterval(() => {
                    showBanner(current + 1);
                }, 5000);
            }

            if (banners.length > 0) {
                startAutoSlide();

                leftBtn.addEventListener('click', () => {
                    clearInterval(interval);
                    showBanner(current - 1);
                    startAutoSlide();
                });

                rightBtn.addEventListener('click', () => {
                    clearInterval(interval);
                    showBanner(current + 1);
                    startAutoSlide();
                });

                const bannerContainer = document.querySelector('.offer-banner');
                bannerContainer.addEventListener('mouseenter', () => {
                    clearInterval(interval);
                });

                bannerContainer.addEventListener('mouseleave', () => {
                    startAutoSlide();
                });
            }

            // Close modal on outside click
            window.addEventListener('click', function (event) {
                const modal = document.getElementById('serviceModal');
                if (event.target === modal) {
                    closeModal();
                }
            });

            // Close modal on Escape key
            document.addEventListener('keydown', function (event) {
                const modal = document.getElementById('serviceModal');
                if (event.key === 'Escape' && modal.style.display === 'block') {
                    closeModal();
                }
            });
        });
    </script>
</body>

</html>