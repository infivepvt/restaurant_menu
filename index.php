<?php
require 'config.php';
$offer_stmt = $pdo->query("SELECT * FROM offer_banners WHERE is_active = 1 ORDER BY display_order ASC, created_at DESC LIMIT 1");

$offer_banner = $offer_stmt->fetch();

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
    <title>Our Services</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
            max-width: 1200px;
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

        .category-filter {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 30px;
            padding: 15px;
            background-color: var(--darker);
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .filter-option {
            display: flex;
            align-items: center;
            padding: 10px 20px;
            border-radius: 30px;
            text-decoration: none;
            color: var(--light);
            background-color: var(--gray);
            transition: all 0.3s ease;
            font-size: 0.95rem;
            border: none;
            cursor: pointer;
        }

        .filter-option i {
            margin-right: 8px;
            font-size: 0.9em;
        }

        .filter-option:hover {
            background-color: var(--primary);
            transform: translateY(-2px);
        }

        .filter-option.active {
            background-color: var(--primary);
            font-weight: bold;
        }

        .filter-option img {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            margin-right: 8px;
            object-fit: cover;
            vertical-align: middle;
        }

        .category-section {
            margin-bottom: 40px;
        }

        .category-header {
            display: flex;
            align-items: center;
            margin-bottom: 25px;
            padding: 15px;
            background-color: rgba(255, 255, 255, 0.05);
            border-radius: 10px;
            transition: background-color 0.3s ease;
        }

        .category-header:hover {
            background-color: rgba(255, 255, 255, 0.08);
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
            color: var(--primary);
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }

        .services-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); /* Increased min width for larger images */
            gap: 25px;
        }

        .service-card {
            background-color: var(--darker);
            border-radius: 10px;
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.05);
            cursor: pointer;
            display: flex;
            flex-direction: row;
            align-items: center;
            padding: 15px;
        }

        .service-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
        }

        .service-image-container {
            width: 120px; /* Increased image size */
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
            min-height: 120px; /* Adjusted for larger image */
        }

        .service-title {
            font-size: 1.2rem;
            margin-bottom: 8px;
            color: white;
        }

        .service-price {
            font-size: 1.1rem;
            font-weight: bold;
            color: var(--success);
        }

        .admin-link {
            display: inline-block;
            margin-top: 40px;
            padding: 10px 20px;
            background-color: var(--primary);
            color: white;
            text-decoration: none;
            border-radius: 5px;
            transition: all 0.3s ease;
            float: right;
        }

        .admin-link:hover {
            background-color: #ff5252;
            transform: translateY(-2px);
        }

        .no-services {
            text-align: center;
            padding: 40px;
            color: var(--gray);
            font-size: 1.2rem;
            grid-column: 1 / -1;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.85);
            z-index: 1000;
            overflow-y: auto;
            padding: 20px;
            box-sizing: border-box;
        }

        .modal-content {
            background: linear-gradient(135deg, var(--darker) 0%, #1e2a44 100%); /* Gradient background */
            margin: 50px auto;
            max-width: 800px;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.4);
            border: 1px solid var(--primary);
            animation: modalFadeIn 0.3s ease-out;
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
            filter: brightness(0.9);
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
            background-color: #ff5252;
            transform: scale(1.1);
        }

        .modal-body {
            padding: 35px;
        }

        .modal-title {
            font-size: 2rem;
            margin-bottom: 15px;
            color: var(--primary);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .modal-price {
            font-size: 1.6rem;
            font-weight: bold;
            color: var(--success);
            margin-bottom: 20px;
            background-color: rgba(6, 214, 160, 0.1);
            padding: 8px 15px;
            border-radius: 5px;
            display: inline-block;
        }

        .modal-description {
            margin-bottom: 25px;
            line-height: 1.8;
            font-size: 1rem;
            color: var(--light);
        }

        .modal-category {
            display: inline-block;
            padding: 8px 20px;
            background-color: rgba(255, 107, 107, 0.3);
            color: var(--primary);
            border-radius: 25px;
            font-size: 0.95rem;
            margin-bottom: 20px;
            font-weight: 500;
        }

        @media (max-width: 768px) {
            h1 {
                font-size: 2rem;
            }

            .category-filter {
                justify-content: center;
            }

            .services-grid {
                grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); /* Adjusted for larger images */
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
                width: 100px; /* Scaled down for tablets */
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
        }

        @media (max-width: 480px) {
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

            .filter-option {
                padding: 8px 16px;
                font-size: 0.85rem;
            }

            .modal-header {
                height: 150px;
            }

            .service-image-container {
                width: 80px; /* Scaled down for mobile */
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

        @media (max-width: 768px) {
            .offer-banner {
                height: 250px;
                margin: 0 15px;
            }
        }

        @media (max-width: 480px) {
            .offer-banner {
                height: 200px;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>Our Services</h1>
    </div>
    <?php
    // Get active banners in display order
    $banners = $pdo->query("SELECT * FROM offer_banners WHERE is_active = 1 ORDER BY display_order")->fetchAll();

    if (!empty($banners)): ?>
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
        <div class="category-filter">
            <a href="?" class="filter-option <?= !$filtered_category ? 'active' : '' ?>">
                <i class="fas fa-list"></i> All Categories
            </a>
            <?php foreach ($categories as $category): ?>
                <a href="?category=<?= $category['id'] ?>"
                    class="filter-option <?= $filtered_category == $category['id'] ? 'active' : '' ?>">
                    <?php if ($category['image']): ?>
                        <img src="<?= htmlspecialchars($category['image']) ?>" alt="<?= htmlspecialchars($category['name']) ?>">
                    <?php else: ?>
                        <i class="fas fa-folder" style="margin-right:8px;"></i>
                    <?php endif; ?>
                    <?= htmlspecialchars($category['name']) ?>
                </a>
            <?php endforeach; ?>
        </div>

        <?php if (empty($filtered_categories)): ?>
            <div class="no-services">
                <i class="fas fa-box-open" style="font-size:2rem;margin-bottom:15px;"></i>
                <p>No services found in this category</p>
            </div>
        <?php else: ?>
            <?php foreach ($filtered_categories as $cat_id => $category): ?>
                <?php if (!empty($category['services'])): ?>
                    <div class="category-section">
                        <div class="category-header">
                            <?php if ($category['image']): ?>
                                <img src="<?= htmlspecialchars($category['image']) ?>" alt="<?= htmlspecialchars($category['name']) ?>"
                                    class="category-image">
                            <?php else: ?>
                                <div class="category-image"
                                    style="background-color:var(--gray);display:flex;align-items:center;justify-content:center;">
                                    <i class="fas fa-folder" style="font-size:2rem;color:var(--dark);"></i>
                                </div>
                            <?php endif; ?>
                            <h2 class="category-title"><?= htmlspecialchars($category['name']) ?></h2>
                        </div>

                        <div class="services-grid">
                            <?php foreach ($category['services'] as $service): ?>
                                <div class="service-card" onclick="openServiceModal(
                                    '<?= htmlspecialchars($service['name'], ENT_QUOTES) ?>',
                                    '<?= htmlspecialchars($service['image'] ? $service['image'] : '', ENT_QUOTES) ?>',
                                    '<?= htmlspecialchars($service['price']) ?>',
                                    '<?= htmlspecialchars($service['description'], ENT_QUOTES) ?>',
                                    '<?= htmlspecialchars($service['description'], ENT_QUOTES) ?>', // Using description twice since detailed_description doesn't exist
                                    '<?= htmlspecialchars($category['name'], ENT_QUOTES) ?>'
                                )">
                                    <div class="service-image-container">
                                        <?php if ($service['image']): ?>
                                            <img src="<?= htmlspecialchars($service['image']) ?>"
                                                 alt="<?= htmlspecialchars($service['name']) ?>" class="service-image">
                                        <?php else: ?>
                                            <div style="background-color:var(--gray);height:100%;display:flex;align-items:center;justify-content:center;">
                                                <i class="fas fa-image" style="font-size:2.5rem;color:var(--dark);"></i>
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
        <?php endif; ?>
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
                <p id="modalDescription" class="modal-description"></p>
                <p id="modalDetailedDescription" class="modal-description"></p>
            </div>
        </div>
    </div>

    <script>
        // Service Modal Functions
        function openServiceModal(name, image, price, description, detailedDescription, category) {
            const modal = document.getElementById('serviceModal');
            const modalImage = document.getElementById('modalServiceImage');
            const modalTitle = document.getElementById('modalTitle');
            const modalPrice = document.getElementById('modalPrice');
            const modalDescription = document.getElementById('modalDescription');
            const modalDetailedDescription = document.getElementById('modalDetailedDescription');
            const modalCategory = document.getElementById('modalCategory');

            // Set modal content
            modalTitle.textContent = name;
            modalPrice.textContent = 'Rs. ' + parseFloat(price).toFixed(2);
            modalDescription.textContent = description;
            modalDetailedDescription.textContent = detailedDescription || description;
            modalCategory.textContent = category;

            // Set image or placeholder
            if (image) {
                modalImage.src = image;
                modalImage.style.display = 'block';
            } else {
                modalImage.style.display = 'none';
                document.querySelector('.modal-header').style.backgroundColor = 'var(--gray)';
                document.querySelector('.modal-header').style.display = 'flex';
                document.querySelector('.modal-header').style.alignItems = 'center';
                document.querySelector('.modal-header').style.justifyContent = 'center';
                document.querySelector('.modal-header').innerHTML = `
                    <i class="fas fa-image" style="font-size:3rem;color:var(--dark);"></i>
                    ${document.querySelector('.modal-header').innerHTML}
                `;
            }

            // Show modal
            modal.style.display = 'block';
            document.body.style.overflow = 'hidden';
        }

        function closeModal() {
            const modal = document.getElementById('serviceModal');
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        // Close modal when clicking outside
        window.onclick = function (event) {
            const modal = document.getElementById('serviceModal');
            if (event.target == modal) {
                closeModal();
            }
        }

        // Close modal with ESC key
        document.addEventListener('keydown', function (event) {
            const modal = document.getElementById('serviceModal');
            if (event.key === 'Escape' && modal.style.display === 'block') {
                closeModal();
            }
        });

        // Banner Slider
        document.addEventListener('DOMContentLoaded', function () {
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
                }, 5000); // Change slide every 5 seconds
            }

            if (banners.length > 0) {
                // Show first banner (already has 'active' class from PHP)
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

                // Pause on hover
                const bannerContainer = document.querySelector('.offer-banner');
                bannerContainer.addEventListener('mouseenter', () => {
                    clearInterval(interval);
                });

                bannerContainer.addEventListener('mouseleave', () => {
                    startAutoSlide();
                });
            }
        });
    </script>
</body>

</html>
