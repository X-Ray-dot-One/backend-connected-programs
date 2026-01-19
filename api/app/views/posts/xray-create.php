<?php
// Start the page with the new X-RAY header
require_once __DIR__ . '/../layouts/xray-header.php';
$isShadowMode = isset($_SESSION['shadow_mode']) && $_SESSION['shadow_mode'];
?>

<!-- Include sidebar -->
<?php require_once __DIR__ . '/../layouts/xray-sidebar.php'; ?>

<!-- Main Content Wrapper -->
<div class="main-content-wrapper">
    <!-- Main Feed - Create Post Form -->
    <main class="main-feed">
    <!-- Header -->
    <div class="feed-header">
        <div class="feed-header-content">
            <h1 class="feed-title">
                <?= $isShadowMode ? '// new_shadow_post' : '// new_post' ?>
            </h1>
            <p class="feed-subtitle">
                <?= $isShadowMode
                    ? 'Your post will be anonymous (0.001 SOL)'
                    : 'Your post will be public (FREE)' ?>
            </p>
        </div>
    </div>

    <!-- Form -->
    <div style="padding: 1.5rem;">
        <?php if (isset($_SESSION['errors'])): ?>
            <div style="background: #fee2e2; border: 1px solid #ef4444; color: #991b1b; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1rem;">
                <?php foreach ($_SESSION['errors'] as $error): ?>
                    <p style="margin: 0;"><?= htmlspecialchars($error) ?></p>
                <?php endforeach; ?>
                <?php unset($_SESSION['errors']); ?>
            </div>
        <?php endif; ?>

        <form action="index.php?action=store" method="POST" style="display: flex; flex-direction: column; gap: 1.5rem;">
            <!-- Twitter Username -->
            <div>
                <label for="twitter_username" style="display: block; font-weight: 500; color: var(--foreground); margin-bottom: 0.5rem;">
                    Twitter Username
                </label>
                <input
                    type="text"
                    id="twitter_username"
                    name="twitter_username"
                    placeholder="@username"
                    value="<?= htmlspecialchars($_SESSION['old']['twitter_username'] ?? '') ?>"
                    required
                    style="width: 100%; padding: 0.75rem; background: var(--input); border: 1px solid var(--border); border-radius: 0.5rem; color: var(--foreground); font-family: inherit;">
                <p style="font-size: 0.75rem; color: var(--muted-foreground); margin-top: 0.25rem;">
                    The Twitter account you want to mention in your post
                </p>
            </div>

            <!-- Post Content -->
            <div>
                <label for="content" style="display: block; font-weight: 500; color: var(--foreground); margin-bottom: 0.5rem;">
                    Post Content
                </label>
                <textarea
                    id="content"
                    name="content"
                    rows="6"
                    placeholder="<?= $isShadowMode ? 'Share your thoughts anonymously...' : 'What\'s happening?' ?>"
                    required
                    style="width: 100%; padding: 0.75rem; background: var(--input); border: 1px solid var(--border); border-radius: 0.5rem; color: var(--foreground); font-family: sans-serif; resize: vertical;"><?= htmlspecialchars($_SESSION['old']['content'] ?? '') ?></textarea>
                <p style="font-size: 0.75rem; color: var(--muted-foreground); margin-top: 0.25rem;">
                    <?= $isShadowMode ? 'Your identity will be encrypted' : 'This will be publicly associated with your wallet' ?>
                </p>
            </div>

            <?php if ($isShadowMode): ?>
                <!-- Payment Info (Shadow Mode Only) -->
                <div style="background: var(--muted); padding: 1rem; border-radius: 0.5rem; border: 1px solid var(--border);">
                    <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                        <i data-lucide="info" style="width: 16px; height: 16px; color: var(--primary);"></i>
                        <span style="font-weight: 500; color: var(--foreground);">Payment Required</span>
                    </div>
                    <p style="font-size: 0.875rem; color: var(--muted-foreground); margin: 0;">
                        Anonymous posting costs <strong style="color: var(--primary);">0.001 SOL</strong> which helps prevent spam and keeps the platform quality high. Your post will be encrypted and cannot be deleted.
                    </p>
                </div>
            <?php else: ?>
                <!-- Free Info (Public Mode) -->
                <div style="background: var(--muted); padding: 1rem; border-radius: 0.5rem; border: 1px solid var(--border);">
                    <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem;">
                        <i data-lucide="sparkles" style="width: 16px; height: 16px; color: var(--primary);"></i>
                        <span style="font-weight: 500; color: var(--foreground);">Free Posting</span>
                    </div>
                    <p style="font-size: 0.875rem; color: var(--muted-foreground); margin: 0;">
                        Public posts are <strong style="color: var(--primary);">FREE</strong>. Your wallet identity will be visible to others. Switch to Shadow Mode for anonymous posting.
                    </p>
                </div>
            <?php endif; ?>

            <!-- Submit Button -->
            <div style="display: flex; gap: 0.75rem;">
                <button
                    type="submit"
                    style="flex: 1; padding: 0.75rem; background: var(--primary); color: var(--primary-foreground); border: none; border-radius: 0.75rem; font-weight: 500; cursor: pointer; transition: opacity 0.2s; display: flex; align-items: center; justify-content: center; gap: 0.5rem;">
                    <i data-lucide="send" style="width: 16px; height: 16px;"></i>
                    <span><?= $isShadowMode ? 'Post (0.001 SOL)' : 'Post (FREE)' ?></span>
                </button>
                <button
                    type="button"
                    onclick="window.location='index.php'"
                    style="padding: 0.75rem 1.5rem; background: transparent; color: var(--foreground); border: 1px solid var(--border); border-radius: 0.75rem; font-weight: 500; cursor: pointer; transition: all 0.2s;">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</main>
</div>

<!-- Include right panel -->
<?php require_once __DIR__ . '/../layouts/xray-right-panel.php'; ?>

<!-- Include footer -->
<?php
unset($_SESSION['old']);
require_once __DIR__ . '/../layouts/xray-footer.php';
?>
