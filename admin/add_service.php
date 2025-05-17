<?php
session_start();
require '../config.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category_id = (int) $_POST['category_id'];
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price = (float) $_POST['price'];

    // Handle image upload
    $image = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
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
        INSERT INTO services (category_id, name, description, price, image)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([$category_id, $name, $description, $price, $image]);

    header('Location: services.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Service - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #4e73df;
            --secondary-color: #f8f9fc;
            --accent-color: #2e59d9;
            --text-color: #5a5c69;
        }

        body {
            --secondary-color: rgba(0, 0, 0, 0.5);
            /* Example: semi-transparent black */
            --text-color: #fff;
            /* White text for contrast */

            background-color: var(--secondary-color);
            color: var(--text-color);
            font-family: 'Nunito', sans-serif;

            background-image: url('../assets/image/admin-b.png');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            background-blend-mode: overlay;

            padding-top: 20px;
            padding-bottom: 20px;
        }


        .card {
            border-radius: 0.35rem;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            border: none;
             background: linear-gradient(135deg, #0f2027 0%, #203a43 50%, #2c5364 100%);
            backdrop-filter: blur(5px);
            margin: 10px;
        }

        .card-header {
            background: linear-gradient(135deg, #0f2027 0%, #203a43 50%, #2c5364 100%);
            color: white;
            font-weight: 600;
            border-radius: 0.35rem 0.35rem 0 0 !important;
            padding: 1rem;
        }

        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-primary:hover {
            background-color: var(--accent-color);
            border-color: var(--accent-color);
        }

        .form-control,
        .form-select {
            border-radius: 0.35rem;
            padding: 0.75rem 1rem;
            background-color: rgba(255, 255, 255);
        }

        .form-label {
            color: white;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }

        .form-control:focus,
        .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(78, 115, 223, 0.25);
            background-color: white;
        }

        .form-text {
            color: white;
            font-size: 0.875rem;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .container {
                padding-left: 15px;
                padding-right: 15px;
            }

            .card-header h4 {
                font-size: 1.25rem;
            }

            .btn-sm {
                padding: 0.25rem 0.5rem;
                font-size: 0.875rem;
            }

            .form-control,
            .form-select {
                padding: 0.5rem 0.75rem;
            }

            .card-body {
                padding: 1rem;
            }

            .d-md-flex {
                flex-direction: column;
            }

            .btn {
                width: 100%;
                margin-bottom: 10px;
            }

            .me-md-2 {
                margin-right: 0 !important;
            }
        }

        @media (min-width: 992px) {
            .col-lg-8 {
                max-width: 800px;
            }
        }
    </style>
</head>

<body>
    <div class="container py-3">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card">
                    <div
                        class="card-header py-3 d-flex flex-column flex-md-row justify-content-between align-items-center">
                        <h4 class="m-0 font-weight-bold text-center text-md-start">Add New Service</h4>
                        <a href="dashboard.php" class="btn btn-sm btn-light mt-2 mt-md-0">
                            <i class="fas fa-arrow-left me-1"></i> Back to Dashboard
                        </a>
                    </div>
                    <div class="card-body">
                        <form method="POST" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="category_id" class="form-label">Category</label>
                                <select class="form-select" id="category_id" name="category_id" required>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?= $category['id'] ?>"><?= htmlspecialchars($category['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="name" class="form-label">Service Name</label>
                                <input type="text" class="form-control" id="name" name="name" required>
                            </div>

                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                            </div>

                            <div class="mb-3">
                                <label for="price" class="form-label">Price (Rs.)</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rs.</span>
                                    <input type="number" class="form-control" id="price" name="price" step="0.01"
                                        min="0" required>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="image" class="form-label">Service Image</label>
                                <input class="form-control" type="file" id="image" name="image" accept="image/*">
                                <div class="form-text">Upload a high-quality image for the service (JPEG, PNG)</div>
                            </div>

                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-plus me-1"></i> Add Service
                                </button>
                                <a href="dashboard.php" class="btn btn-secondary me-md-2">
                                    <i class="fas fa-times me-1"></i> Cancel
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>