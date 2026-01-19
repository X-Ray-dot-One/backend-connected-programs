<?php
/**
 * X-RAY User Profile Page - View other users' profiles
 */

$isShadowMode = isset($_COOKIE['shadow_mode']) && $_COOKIE['shadow_mode'] === 'true';

// Get username from URL
$username = isset($_GET['username']) ? trim($_GET['username']) : '';

// Load user data from database
require_once __DIR__ . '/../../models/User.php';
require_once __DIR__ . '/../../models/Post.php';

$userModel = new User();
$postModel = new Post();
$profileUser = null;
$profilePosts = [];
$isFollowing = false;
$followersCount = 0;
$followingCount = 0;
$currentUserId = $_SESSION['user_id'] ?? null;

if (!empty($username)) {
    $profileUser = $userModel->findByUsername($username);
    if ($profileUser) {
        // Si l'utilisateur regarde son propre profil, rediriger vers ?action=profile
        if ($currentUserId && $profileUser['id'] == $currentUserId) {
            header('Location: index.php?action=profile');
            exit;
        }

        $profilePosts = $postModel->getPostsByUserId($profileUser['id']);
        $followersCount = $userModel->getFollowersCount($profileUser['id']);
        $followingCount = $userModel->getFollowingCount($profileUser['id']);

        // Check if current user follows this profile
        if ($currentUserId && $currentUserId != $profileUser['id']) {
            $isFollowing = $userModel->isFollowing($currentUserId, $profileUser['id']);
        }
    }
}
?>

<!-- Include Header -->
<?php require_once __DIR__ . '/../layouts/xray-header.php'; ?>

<div class="xray-container">
    <!-- Include Left Sidebar -->
    <?php require_once __DIR__ . '/../layouts/xray-sidebar.php'; ?>

    <!-- Main Content Wrapper -->
    <div class="main-content-wrapper">
        <div class="main-feed" id="user-profile-content">
            <?php if (!$profileUser): ?>
            <!-- User Not Found -->
            <div id="user-not-found">
                <div class="not-found-container">
                    <p class="not-found-title">User not found</p>
                    <p class="not-found-subtitle">@<?= htmlspecialchars($username) ?> doesn't exist</p>
                </div>
            </div>
            <?php else: ?>
            <!-- User Profile -->
            <div id="user-profile">
                <!-- Banner -->
                <div class="profile-banner">
                    <?php if (!empty($profileUser['banner_picture'])): ?>
                        <img src="<?= htmlspecialchars($profileUser['banner_picture']) ?>" alt="Banner" class="banner-image">
                    <?php endif; ?>
                </div>

                <!-- Profile Info -->
                <div class="profile-info">
                    <!-- Avatar -->
                    <div class="profile-avatar-container">
                        <?php if (!empty($profileUser['profile_picture'])): ?>
                            <img src="<?= htmlspecialchars($profileUser['profile_picture']) ?>" alt="Avatar" class="profile-avatar-large">
                        <?php else: ?>
                            <div class="profile-avatar-large" style="background: linear-gradient(135deg, var(--primary), var(--accent)); display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 2rem;">
                                <?= strtoupper(substr($profileUser['username'] ?? 'U', 0, 1)) ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Follow Button (only show if not viewing own profile) -->
                    <?php if ($currentUserId && $currentUserId != $profileUser['id']): ?>
                    <div class="profile-actions">
                        <button class="follow-btn <?= $isFollowing ? 'following' : '' ?>" id="follow-btn" data-user-id="<?= $profileUser['id'] ?>">
                            <?= $isFollowing ? 'Following' : 'Follow' ?>
                        </button>
                    </div>
                    <?php elseif (!$currentUserId): ?>
                    <div class="profile-actions">
                        <button class="follow-btn" onclick="alert('Connect your wallet to follow users')">
                            Follow
                        </button>
                    </div>
                    <?php endif; ?>

                    <!-- Name & Handle -->
                    <div class="profile-name-section">
                        <h1 class="profile-username"><?= htmlspecialchars($profileUser['username']) ?></h1>
                        <p class="profile-handle">@<?= htmlspecialchars($profileUser['username']) ?></p>
                    </div>

                    <!-- Bio -->
                    <?php if (!empty($profileUser['bio'])): ?>
                    <p class="profile-bio"><?= htmlspecialchars($profileUser['bio']) ?></p>
                    <?php endif; ?>

                    <!-- Meta Info -->
                    <div class="profile-meta">
                        <?php if (!empty($profileUser['location'])): ?>
                        <div class="meta-item">
                            <i data-lucide="map-pin" style="width: 16px; height: 16px;"></i>
                            <span><?= htmlspecialchars($profileUser['location']) ?></span>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($profileUser['website'])): ?>
                        <div class="meta-item">
                            <i data-lucide="link" style="width: 16px; height: 16px;"></i>
                            <a href="https://<?= htmlspecialchars($profileUser['website']) ?>" class="website-link" target="_blank"><?= htmlspecialchars($profileUser['website']) ?></a>
                        </div>
                        <?php endif; ?>
                        <div class="meta-item">
                            <i data-lucide="calendar" style="width: 16px; height: 16px;"></i>
                            <span>Joined <?= date('F Y', strtotime($profileUser['created_at'])) ?></span>
                        </div>
                    </div>

                    <!-- Stats -->
                    <div class="profile-stats">
                        <div class="stat-item">
                            <span class="stat-value"><?= number_format($followingCount) ?></span>
                            <span class="stat-label">Following</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-value" id="followers-count"><?= number_format($followersCount) ?></span>
                            <span class="stat-label">Followers</span>
                        </div>
                    </div>
                </div>

                <!-- Tabs -->
                <div class="user-profile-tabs">
                    <button class="user-profile-tab active" data-tab="posts">
                        <i data-lucide="eye" style="width: 16px; height: 16px;"></i>
                        Posts
                    </button>
                    <button class="user-profile-tab shadow-tab" data-tab="shadow">
                        <i data-lucide="eye-off" style="width: 16px; height: 16px;"></i>
                        shadow_posts
                        <span class="shadow-count">0</span>
                    </button>
                </div>

                <!-- Posts Content -->
                <div id="user-posts-content">
                    <?php if (empty($profilePosts)): ?>
                        <div class="post-card">
                            <div class="post-content-wrapper">
                                <div class="post-avatar anonymous"></div>
                                <div class="post-body">
                                    <p class="post-text" style="color: var(--muted-foreground);">
                                        @<?= htmlspecialchars($profileUser['username']) ?> hasn't posted anything yet.
                                    </p>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($profilePosts as $post):
                            // Get like and comment info for this post
                            $likeCount = $postModel->getLikeCount($post['id']);
                            $hasLiked = $currentUserId ? $postModel->hasUserLiked($post['id'], $currentUserId) : false;
                            $commentCount = $postModel->getCommentCount($post['id']);
                        ?>
                            <div class="post-card">
                                <div class="post-content-wrapper">
                                    <?php if (!empty($profileUser['profile_picture'])): ?>
                                        <img src="<?= htmlspecialchars($profileUser['profile_picture']) ?>" alt="Avatar" class="post-avatar">
                                    <?php else: ?>
                                        <div class="post-avatar" style="background: linear-gradient(135deg, var(--primary), var(--accent)); display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 1.25rem;">
                                            <?= strtoupper(substr($profileUser['username'], 0, 1)) ?>
                                        </div>
                                    <?php endif; ?>
                                    <div class="post-body">
                                        <div class="post-header">
                                            <span class="post-username"><?= htmlspecialchars($profileUser['username']) ?></span>
                                            <span class="post-handle">@<?= htmlspecialchars($profileUser['username']) ?></span>
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
                                        <p class="post-text">
                                            <?php
                                            $content = htmlspecialchars($post['content']);
                                            $content = preg_replace('/@(\w+)/', '<span class="mention">@$1</span>', $content);
                                            echo $content;
                                            ?>
                                        </p>
                                        <div class="post-actions">
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
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Include Right Panel -->
    <?php require_once __DIR__ . '/../layouts/xray-right-panel.php'; ?>
</div>

<!-- Include Profile Modal -->
<?php require_once __DIR__ . '/../layouts/xray-profile-modal.php'; ?>

<!-- Include Footer -->
<?php require_once __DIR__ . '/../layouts/xray-footer.php'; ?>

<style>
/* User Profile Styles */
.not-found-container {
    min-height: 50vh;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
}

.not-found-title {
    font-size: 1.5rem;
    font-weight: bold;
    color: var(--foreground);
}

.not-found-subtitle {
    color: var(--muted-foreground);
    margin-top: 0.5rem;
}

.profile-banner {
    height: 150px;
    background: linear-gradient(135deg, color-mix(in srgb, var(--primary) 30%, var(--background)), color-mix(in srgb, var(--accent) 30%, var(--background)));
    position: relative;
    overflow: hidden;
}

.profile-banner .banner-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.profile-info {
    padding: 0 1rem 1rem;
    position: relative;
    padding-top: 60px;
}

.profile-avatar-container {
    position: absolute;
    top: -50px;
    left: 1rem;
    z-index: 10;
}

.profile-avatar-large {
    width: 100px;
    height: 100px;
    border-radius: 50%;
    border: 4px solid var(--background);
    object-fit: cover;
    background: var(--background);
}

.profile-actions {
    position: absolute;
    top: 12px;
    right: 1rem;
}

.follow-btn {
    padding: 0.5rem 1.25rem;
    border-radius: 9999px;
    background: var(--primary);
    color: var(--primary-foreground);
    border: none;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
    font-family: inherit;
    font-size: 0.875rem;
}

.follow-btn:hover {
    opacity: 0.9;
}

.follow-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.follow-btn.following {
    background: transparent;
    border: 1px solid var(--border);
    color: var(--foreground);
}

.follow-btn.following:hover {
    border-color: #ef4444;
    color: #ef4444;
    background: rgba(239, 68, 68, 0.1);
}

.profile-name-section {
    margin-top: 0;
}

.profile-username {
    font-size: 1.25rem;
    font-weight: bold;
    color: var(--foreground);
    margin: 0;
}

.profile-handle {
    color: var(--muted-foreground);
    margin: 0;
}

.profile-bio {
    margin-top: 0.75rem;
    color: var(--foreground);
}

.profile-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    margin-top: 0.75rem;
}

.meta-item {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    font-size: 0.875rem;
    color: var(--muted-foreground);
}

.website-link {
    color: var(--primary);
    text-decoration: none;
}

.website-link:hover {
    text-decoration: underline;
}

.profile-stats {
    display: flex;
    gap: 1rem;
    margin-top: 1rem;
}

.stat-item {
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.stat-value {
    font-weight: bold;
    color: var(--foreground);
}

.stat-label {
    color: var(--muted-foreground);
}

/* User Profile Tabs */
.user-profile-tabs {
    display: flex;
    border-bottom: 1px solid var(--border);
}

.user-profile-tab {
    flex: 1;
    padding: 0.75rem;
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--muted-foreground);
    background: none;
    border: none;
    cursor: pointer;
    position: relative;
    transition: all 0.2s;
    font-family: inherit;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
}

.user-profile-tab:hover {
    color: var(--foreground);
}

.user-profile-tab.active {
    color: var(--foreground);
}

.user-profile-tab.active::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 50%;
    transform: translateX(-50%);
    width: 3rem;
    height: 4px;
    background: var(--primary);
    border-radius: 9999px;
}

.user-profile-tab.shadow-tab:hover {
    color: #7c3aed;
}

.user-profile-tab.shadow-tab.active {
    color: #7c3aed;
}

.user-profile-tab.shadow-tab.active::after {
    background: #a855f7;
}

.shadow-count {
    font-size: 0.75rem;
    padding: 0.125rem 0.375rem;
    border-radius: 9999px;
    background: #f3e8ff;
    color: #7c3aed;
}

/* Shadow Posts Header */
.shadow-posts-header {
    padding: 0.75rem 1rem;
    background: #f3e8ff;
    border-bottom: 1px solid #e9d5ff;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

body.shadow-mode .shadow-posts-header {
    background: color-mix(in srgb, #a855f7 15%, var(--background));
    border-bottom: 1px solid color-mix(in srgb, #a855f7 30%, var(--border));
}

.shadow-posts-label {
    font-size: 0.75rem;
    color: #7c3aed;
}

.shadow-posts-sort {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.shadow-posts-sort-label {
    font-size: 0.75rem;
    color: #a855f7;
}

.shadow-posts-sort-btn {
    font-size: 0.75rem;
    padding: 0.25rem 0.5rem;
    border-radius: 9999px;
    background: #e9d5ff;
    color: #7c3aed;
    border: none;
    cursor: pointer;
    transition: all 0.2s;
    font-family: inherit;
}

.shadow-posts-sort-btn:hover {
    background: #ddd6fe;
}

.shadow-posts-sort-btn.active {
    background: #7c3aed;
    color: white;
}

/* Shadow Post Item */
.shadow-post-item {
    padding: 1rem;
    border-bottom: 1px solid #f3e8ff;
    border-left: 4px solid #a855f7;
    transition: all 0.2s;
    cursor: pointer;
}

.shadow-post-item:hover {
    background: #faf5ff;
}

body.shadow-mode .shadow-post-item {
    border-bottom: 1px solid color-mix(in srgb, #a855f7 20%, var(--border));
}

body.shadow-mode .shadow-post-item:hover {
    background: color-mix(in srgb, #a855f7 10%, var(--background));
}

.shadow-post-content {
    display: flex;
    gap: 0.75rem;
}

.shadow-post-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #f3e8ff;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #7c3aed;
    flex-shrink: 0;
}

.shadow-post-body {
    flex: 1;
}

.shadow-post-header {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.shadow-post-username {
    font-weight: 500;
    color: #7c3aed;
}

.shadow-post-time {
    font-size: 0.875rem;
    color: #a855f7;
}

.shadow-post-text {
    margin-top: 0.25rem;
    color: #374151;
}

body.shadow-mode .shadow-post-text {
    color: var(--foreground);
}

.shadow-post-stats {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-top: 0.75rem;
}

.shadow-post-boost {
    display: flex;
    align-items: center;
    gap: 0.375rem;
    color: #7c3aed;
    font-size: 0.875rem;
    font-weight: 500;
}

.shadow-post-position {
    font-size: 0.75rem;
    color: #a855f7;
}

/* Empty State */
.empty-shadow-posts {
    padding: 2rem;
    text-align: center;
    background: #faf5ff;
}

body.shadow-mode .empty-shadow-posts {
    background: color-mix(in srgb, #a855f7 10%, var(--background));
}

.empty-shadow-icon {
    width: 48px;
    height: 48px;
    color: #d8b4fe;
    margin: 0 auto 0.75rem;
}

.empty-shadow-text {
    color: #a855f7;
}
</style>

<script>
// Follow button handler
document.getElementById('follow-btn')?.addEventListener('click', async function() {
    const btn = this;
    const userId = btn.dataset.userId;
    const isFollowing = btn.classList.contains('following');

    btn.disabled = true;

    try {
        const action = isFollowing ? 'unfollow' : 'follow';
        const response = await fetch(`index.php?action=${action}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ user_id: userId })
        });

        const data = await response.json();

        if (data.success) {
            if (data.is_following) {
                btn.classList.add('following');
                btn.textContent = 'Following';
            } else {
                btn.classList.remove('following');
                btn.textContent = 'Follow';
            }
            // Update followers count
            const followersEl = document.getElementById('followers-count');
            if (followersEl) {
                followersEl.textContent = data.followers_count.toLocaleString();
            }
        } else {
            alert(data.error || 'An error occurred');
        }
    } catch (error) {
        console.error('Follow error:', error);
        alert('An error occurred. Please try again.');
    } finally {
        btn.disabled = false;
    }
});

// Initialize icons
document.addEventListener('DOMContentLoaded', function() {
    if (typeof lucide !== 'undefined') lucide.createIcons();
});
</script>
