<?php
require '../config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'], $_POST['display_order'])) {
    $id = $_POST['id'];
    $display_order = $_POST['display_order'];
    
    // Update the display order
    $stmt = $pdo->prepare("UPDATE offer_banners SET display_order = ? WHERE id = ?");
    $stmt->execute([$display_order, $id]);
    
    // Reorder all banners to ensure no duplicate positions
    $banners = $pdo->query("SELECT id FROM offer_banners ORDER BY display_order, created_at")->fetchAll();
    
    foreach ($banners as $index => $banner) {
        $newPosition = $index + 1;
        $pdo->prepare("UPDATE offer_banners SET display_order = ? WHERE id = ?")->execute([$newPosition, $banner['id']]);
    }
    
    header("Location: offer.php?success=updated");
    exit();
}

header("Location: offer.php");
exit();
?>