<?php
$isShadowMode = isset($_SESSION['shadow_mode']) && $_SESSION['shadow_mode'];
$activeTab = $_GET['tab'] ?? 'recently';
$currentUserId = $_SESSION['user_id'] ?? null;
?>

<!-- Main Feed -->
<main class="main-feed">
    <!-- Header -->
    <div class="feed-header">
        <div class="feed-header-content">
            <h1 class="feed-title">
                <?= $isShadowMode ? '// shadow_feed' : '// public_feed' ?>
            </h1>
            <p class="feed-subtitle">
                <?= $isShadowMode
                    ? 'You are browsing anonymously via Arcium encryption'
                    : 'Your wallet identity is visible to others' ?>
            </p>
        </div>

        <!-- Tabs -->
        <div class="feed-tabs">
            <button
                onclick="window.location='index.php?tab=recently'"
                class="feed-tab <?= $activeTab === 'recently' ? 'active' : '' ?>">
                Recently
            </button>
            <button
                onclick="window.location='index.php?tab=following'"
                class="feed-tab <?= $activeTab === 'following' ? 'active' : '' ?>">
                <?= $isShadowMode ? 'Premium_feed' : 'following' ?>
            </button>
        </div>
    </div>

    <!-- Posts -->
    <div>
        <?php
        // Initialize Post model for like counts
        require_once __DIR__ . '/../../models/Post.php';
        $postModel = new Post();
        ?>
        <?php if (!empty($posts)): ?>
            <?php foreach ($posts as $post):
                // Get like and comment info for this post
                $likeCount = $postModel->getLikeCount($post['id']);
                $hasLiked = $currentUserId ? $postModel->hasUserLiked($post['id'], $currentUserId) : false;
                $commentCount = $postModel->getCommentCount($post['id']);
            ?>
                <div class="post-card">
                    <div class="post-content-wrapper">
                        <!-- Avatar -->
                        <?php if ($isShadowMode): ?>
                            <!-- Anonymous avatar for shadow mode -->
                            <?php
                            $isPremiumNdd = isset($post['is_premium_ndd']) && $post['is_premium_ndd'];
                            if ($isPremiumNdd): ?>
                                <img
                                    src="https://api.dicebear.com/7.x/shapes/svg?seed=shadow_<?= substr(md5($post['user_wallet']), 0, 4) ?>&backgroundColor=ec4899,a855f7,8b5cf6"
                                    alt="Shadow"
                                    class="post-avatar premium">
                            <?php else: ?>
                                <div class="post-avatar anonymous"></div>
                            <?php endif; ?>
                        <?php else: ?>
                            <!-- User profile picture or first letter of username -->
                            <?php if (!empty($post['user_profile_picture'])): ?>
                                <img
                                    src="<?= htmlspecialchars($post['user_profile_picture']) ?>"
                                    alt="Avatar"
                                    class="post-avatar with-ring">
                            <?php elseif (!empty($post['user_username'])): ?>
                                <div class="post-avatar with-ring" style="background: linear-gradient(135deg, var(--primary), var(--accent)); display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 1.25rem;">
                                    <?= strtoupper(substr($post['user_username'], 0, 1)) ?>
                                </div>
                            <?php else: ?>
                                <!-- Fallback to dicebear if no username set -->
                                <img
                                    src="https://api.dicebear.com/7.x/avataaars/svg?seed=<?= htmlspecialchars($post['user_wallet']) ?>"
                                    alt="Avatar"
                                    class="post-avatar with-ring">
                            <?php endif; ?>
                        <?php endif; ?>

                        <div class="post-body">
                            <!-- Header -->
                            <div class="post-header">
                                <?php if ($isShadowMode): ?>
                                    <?php $isPremiumNdd = isset($post['is_premium_ndd']) && $post['is_premium_ndd']; ?>
                                    <span class="post-username <?= $isPremiumNdd ? 'premium' : 'anonymous' ?>">
                                        <?= $isPremiumNdd
                                            ? 'alpha.anon'
                                            : 'shadow_' . substr(md5($post['user_wallet']), 0, 4) ?>
                                    </span>
                                <?php else: ?>
                                    <?php if (!empty($post['user_username'])): ?>
                                        <?php
                                        // Si c'est notre propre post, rediriger vers notre profil
                                        $profileUrl = ($currentUserId && $post['user_id'] == $currentUserId)
                                            ? 'index.php?action=profile'
                                            : 'index.php?action=user&username=' . urlencode($post['user_username']);
                                        ?>
                                        <a href="<?= $profileUrl ?>" class="post-username-link">
                                            @<?= htmlspecialchars($post['user_username']) ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="post-username">
                                            <?= substr($post['user_wallet'], 0, 6) ?>
                                        </span>
                                    <?php endif; ?>
                                <?php endif; ?>
                                <span class="post-time">
                                    <?php
                                    $time = strtotime($post['created_at']);
                                    $diff = time() - $time;
                                    if ($diff < 3600) {
                                        echo floor($diff / 60) . 'm';
                                    } elseif ($diff < 86400) {
                                        echo floor($diff / 3600) . 'h';
                                    } else {
                                        echo floor($diff / 86400) . 'd';
                                    }
                                    ?>
                                </span>
                            </div>

                            <!-- Content -->
                            <p class="post-text">
                                <?php
                                // Render content with mentions highlighted and linked
                                $content = htmlspecialchars($post['content']);
                                $content = preg_replace_callback('/@(\w+)/', function($matches) use ($currentUserId) {
                                    $username = $matches[1];
                                    // Check if it's the current user
                                    $userModel = new User();
                                    $mentionedUser = $userModel->findByUsername($username);
                                    if ($mentionedUser) {
                                        $url = ($currentUserId && $mentionedUser['id'] == $currentUserId)
                                            ? 'index.php?action=profile'
                                            : 'index.php?action=user&username=' . urlencode($username);
                                        return '<a href="' . $url . '" class="mention">@' . $username . '</a>';
                                    }
                                    return '<span class="mention">@' . $username . '</span>';
                                }, $content);
                                echo $content;
                                ?>
                            </p>

                            <!-- Actions -->
                            <div class="post-actions">
                                <?php if ($isShadowMode): ?>
                                    <!-- Shadow mode: Bid system -->
                                    <button class="post-action">
                                        <i data-lucide="trending-up" style="width: 16px; height: 16px;"></i>
                                        <span class="count"><?= number_format($post['amount'] ?? 0.001, 3) ?> SOL</span>
                                    </button>
                                    <button class="post-action">
                                        <i data-lucide="share" style="width: 16px; height: 16px;"></i>
                                    </button>
                                <?php else: ?>
                                    <!-- Public mode: Traditional actions -->
                                    <button class="post-action like <?= $hasLiked ? 'liked' : '' ?>"
                                            onclick="toggleLike(<?= $post['id'] ?>, this)"
                                            data-post-id="<?= $post['id'] ?>">
                                        <i data-lucide="heart" style="width: 16px; height: 16px;"></i>
                                        <span class="count"><?= $likeCount ?></span>
                                    </button>
                                    <button class="post-action comment-btn"
                                            onclick="openCommentModal(<?= $post['id'] ?>)"
                                            data-post-id="<?= $post['id'] ?>">
                                        <i data-lucide="message-circle" style="width: 16px; height: 16px;"></i>
                                        <span class="count"><?= $commentCount ?></span>
                                    </button>
                                    <button class="post-action">
                                        <i data-lucide="share" style="width: 16px; height: 16px;"></i>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="post-card">
                <div class="post-content-wrapper">
                    <div class="post-avatar anonymous"></div>
                    <div class="post-body">
                        <p class="post-text" style="color: var(--muted-foreground);">
                            <?php if ($activeTab === 'following'): ?>
                                <?php if (!isset($_SESSION['user_id'])): ?>
                                    Connect your wallet to see posts from people you follow.
                                <?php else: ?>
                                    You're not following anyone yet. Explore profiles and follow users to see their posts here!
                                <?php endif; ?>
                            <?php else: ?>
                                No posts yet. Be the first to post!
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</main>
