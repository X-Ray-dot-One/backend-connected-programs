<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?? 'X-RAY - Twitter Posts' ?></title>
    <link rel="stylesheet" href="public/css/xray-style.css">
    <!-- Solana Web3.js -->
    <script src="https://unpkg.com/@solana/web3.js@latest/lib/index.iife.min.js"></script>
    <!-- Phantom Wallet Integration -->
    <script src="public/js/phantom-wallet.js?v=<?= time() ?>" defer></script>
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="<?= isset($_SESSION['shadow_mode']) && $_SESSION['shadow_mode'] ? 'shadow-mode' : '' ?>">
    <div class="xray-container">
