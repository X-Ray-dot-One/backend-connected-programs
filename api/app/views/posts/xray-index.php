<?php
// Start the page with the new X-RAY header
require_once __DIR__ . '/../layouts/xray-header.php';
?>

<!-- Include sidebar -->
<?php require_once __DIR__ . '/../layouts/xray-sidebar.php'; ?>

<!-- Main Content Wrapper with margins -->
<div class="main-content-wrapper">
    <!-- Include main feed -->
    <?php require_once __DIR__ . '/xray-feed.php'; ?>
</div>

<!-- Include right panel -->
<?php require_once __DIR__ . '/../layouts/xray-right-panel.php'; ?>

<!-- Include footer -->
<?php require_once __DIR__ . '/../layouts/xray-footer.php'; ?>
