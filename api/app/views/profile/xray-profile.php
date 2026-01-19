<?php
/**
 * X-RAY Profile Page - My Profile
 */

$isShadowMode = isset($_COOKIE['shadow_mode']) && $_COOKIE['shadow_mode'] === 'true';

// Get current user data from session
$currentUser = null;
$userPosts = [];
$followersCount = 0;
$followingCount = 0;
if (isset($_SESSION['user_id'])) {
    require_once __DIR__ . '/../../models/User.php';
    require_once __DIR__ . '/../../models/Post.php';
    $userModel = new User();
    $postModel = new Post();
    $currentUser = $userModel->findById($_SESSION['user_id']);
    $userPosts = $postModel->getPostsByUserId($_SESSION['user_id']);
    $followersCount = $userModel->getFollowersCount($_SESSION['user_id']);
    $followingCount = $userModel->getFollowingCount($_SESSION['user_id']);
}

// Default banner
$defaultBanner = "https://images.unsplash.com/photo-1639762681485-074b7f938ba0?w=800&h=200&fit=crop";
$bannerUrl = !empty($currentUser['banner_picture']) ? $currentUser['banner_picture'] : $defaultBanner;
?>

<!-- Include Header -->
<?php require_once __DIR__ . '/../layouts/xray-header.php'; ?>

<div class="xray-container">
    <!-- Include Left Sidebar -->
    <?php require_once __DIR__ . '/../layouts/xray-sidebar.php'; ?>

    <!-- Main Content Wrapper -->
    <div class="main-content-wrapper">
        <div class="main-feed" id="profile-content">
            <!-- Public Profile View -->
            <div id="public-profile" style="<?= $isShadowMode ? 'display: none;' : '' ?>">
                <!-- Banner -->
                <div class="profile-banner">
                    <img src="<?= htmlspecialchars($bannerUrl) ?>" alt="Banner" class="banner-image" id="display-banner">
                </div>

                <!-- Profile Info -->
                <div class="profile-info">
                    <!-- Avatar -->
                    <div class="profile-avatar-container">
                        <?php if ($currentUser && !empty($currentUser['profile_picture'])): ?>
                            <img src="<?= htmlspecialchars($currentUser['profile_picture']) ?>" alt="Avatar" class="profile-avatar-large" id="profile-avatar-img">
                        <?php else: ?>
                            <div class="profile-avatar-large profile-avatar-letter" id="profile-avatar-letter">
                                <?= $currentUser && !empty($currentUser['username']) ? strtoupper(substr($currentUser['username'], 0, 1)) : '?' ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Edit Button -->
                    <div class="profile-actions">
                        <button class="edit-profile-btn" onclick="openEditModal()">
                            <i data-lucide="edit-3" style="width: 16px; height: 16px;"></i>
                            <span>Edit Profile</span>
                        </button>
                    </div>

                    <!-- Name & Handle -->
                    <div class="profile-name-section">
                        <h1 class="profile-username" id="display-username"><?= $currentUser && !empty($currentUser['username']) ? htmlspecialchars($currentUser['username']) : 'Anonymous' ?></h1>
                        <p class="profile-handle" id="display-handle">@<?= $currentUser && !empty($currentUser['username']) ? htmlspecialchars($currentUser['username']) : 'anonymous' ?></p>
                    </div>

                    <!-- Bio -->
                    <p class="profile-bio" id="display-bio"><?= $currentUser && !empty($currentUser['bio']) ? htmlspecialchars($currentUser['bio']) : 'No bio yet' ?></p>

                    <!-- Meta Info -->
                    <div class="profile-meta">
                        <div class="meta-item" id="display-location" style="<?= empty($currentUser['location']) ? 'display: none;' : '' ?>">
                            <i data-lucide="map-pin" style="width: 16px; height: 16px;"></i>
                            <span><?= $currentUser && !empty($currentUser['location']) ? htmlspecialchars($currentUser['location']) : '' ?></span>
                        </div>
                        <div class="meta-item" id="display-website" style="<?= empty($currentUser['website']) ? 'display: none;' : '' ?>">
                            <i data-lucide="link" style="width: 16px; height: 16px;"></i>
                            <a href="https://<?= $currentUser && !empty($currentUser['website']) ? htmlspecialchars($currentUser['website']) : '' ?>" class="website-link" target="_blank"><?= $currentUser && !empty($currentUser['website']) ? htmlspecialchars($currentUser['website']) : '' ?></a>
                        </div>
                    </div>

                    <!-- Stats -->
                    <div class="profile-stats">
                        <div class="stat-item">
                            <span class="stat-value"><?= number_format($followingCount) ?></span>
                            <span class="stat-label">Following</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-value"><?= number_format($followersCount) ?></span>
                            <span class="stat-label">Followers</span>
                        </div>
                    </div>
                </div>

                <!-- Tabs -->
                <div class="profile-tabs">
                    <button class="profile-tab active" data-tab="posts">Posts</button>
                    <button class="profile-tab" data-tab="replies">Replies</button>
                    <button class="profile-tab" data-tab="likes">Likes</button>
                    <button class="profile-tab shadow-tab" data-tab="shadow_mentions">
                        <i data-lucide="eye-off" style="width: 16px; height: 16px;"></i>
                        shadow
                        <span class="shadow-count">0</span>
                    </button>
                </div>

                <!-- Posts Content -->
                <div class="profile-posts" id="profile-posts">
                    <?php if (empty($userPosts)): ?>
                        <div class="empty-posts">
                            <p>No posts yet</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($userPosts as $post):
                            // Get like and comment info for this post
                            $likeCount = $postModel->getLikeCount($post['id']);
                            $hasLiked = isset($_SESSION['user_id']) ? $postModel->hasUserLiked($post['id'], $_SESSION['user_id']) : false;
                            $commentCount = $postModel->getCommentCount($post['id']);

                            $postTime = time() - strtotime($post['created_at']);
                            if ($postTime < 60) {
                                $timeAgo = $postTime . 's';
                            } elseif ($postTime < 3600) {
                                $timeAgo = floor($postTime / 60) . 'm';
                            } elseif ($postTime < 86400) {
                                $timeAgo = floor($postTime / 3600) . 'h';
                            } else {
                                $timeAgo = floor($postTime / 86400) . 'd';
                            }
                        ?>
                            <div class="post-card">
                                <div class="post-content-wrapper">
                                    <!-- Avatar -->
                                    <?php if (!empty($currentUser['profile_picture'])): ?>
                                        <img src="<?= htmlspecialchars($currentUser['profile_picture']) ?>" alt="Avatar" class="post-avatar">
                                    <?php elseif (!empty($currentUser['username'])): ?>
                                        <div class="post-avatar" style="background: linear-gradient(135deg, var(--primary), var(--accent)); display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 1.25rem;">
                                            <?= strtoupper(substr($currentUser['username'], 0, 1)) ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="post-avatar" style="background: var(--muted); display: flex; align-items: center; justify-content: center; color: var(--muted-foreground);">
                                            <i data-lucide="user" style="width: 20px; height: 20px;"></i>
                                        </div>
                                    <?php endif; ?>

                                    <div class="post-body">
                                        <div class="post-header">
                                            <span class="post-username"><?= !empty($currentUser['username']) ? htmlspecialchars($currentUser['username']) : 'Anonymous' ?></span>
                                            <span class="post-handle">@<?= !empty($currentUser['username']) ? htmlspecialchars($currentUser['username']) : 'anonymous' ?></span>
                                            <span class="post-time"><?= $timeAgo ?></span>
                                        </div>
                                        <p class="post-text"><?= nl2br(htmlspecialchars($post['content'])) ?></p>
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

            <!-- Shadow Profile View -->
            <div id="shadow-profile" style="<?= $isShadowMode ? '' : 'display: none;' ?>">
                <!-- Shadow Profile Header -->
                <div class="shadow-profile-header">
                    <div class="shadow-avatar-section">
                        <div class="shadow-avatar-ring">
                            <div class="shadow-avatar-inner" id="shadow-avatar-letter">S</div>
                        </div>
                        <div class="shadow-identity">
                            <h1 class="shadow-identity-name">shadow_<?= substr(md5($_SESSION['user_id'] ?? 'anon'), 0, 4) ?></h1>
                            <p class="shadow-identity-label">// anonymous identity</p>
                        </div>
                    </div>

                    <!-- Shadow Stats -->
                    <div class="shadow-stats-grid">
                        <div class="shadow-stat-box">
                            <p class="shadow-stat-value">0</p>
                            <p class="shadow-stat-label">total posts</p>
                        </div>
                        <div class="shadow-stat-box">
                            <p class="shadow-stat-value">0 SOL</p>
                            <p class="shadow-stat-label">avg boost</p>
                        </div>
                        <div class="shadow-stat-box">
                            <p class="shadow-stat-value">-</p>
                            <p class="shadow-stat-label">avg position</p>
                        </div>
                    </div>

                    <!-- Total Spent -->
                    <div class="shadow-total-spent">
                        <span class="spent-label">total spent</span>
                        <span class="spent-value">0 SOL</span>
                        <span class="spent-rank">-</span>
                    </div>
                </div>

                <!-- Shadow Tabs -->
                <div class="shadow-tabs">
                    <button class="shadow-tab-btn active" data-shadow-tab="top">top_10</button>
                    <button class="shadow-tab-btn" data-shadow-tab="posts">all_posts</button>
                    <button class="shadow-tab-btn" data-shadow-tab="history">history</button>
                </div>

                <!-- Shadow Posts Content -->
                <div class="shadow-posts" id="shadow-posts">
                    <div class="empty-posts">
                        <p>No shadow posts yet</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Include Right Panel -->
    <?php require_once __DIR__ . '/../layouts/xray-right-panel.php'; ?>
</div>

<!-- Edit Profile Modal -->
<div class="edit-modal-overlay" id="edit-modal" style="display: none;">
    <div class="edit-modal">
        <!-- Modal Header -->
        <div class="edit-modal-header">
            <div class="edit-modal-header-left">
                <button class="edit-modal-close" onclick="closeEditModal()">
                    <i data-lucide="x" style="width: 20px; height: 20px;"></i>
                </button>
                <h2>Edit profile</h2>
            </div>
            <button class="edit-modal-save" id="save-profile-btn" onclick="saveProfile()">Save</button>
        </div>

        <!-- Banner & Avatar -->
        <div class="edit-banner-section">
            <div class="edit-banner">
                <img src="<?= htmlspecialchars($bannerUrl) ?>" alt="Banner" class="edit-banner-img" id="edit-banner-preview">
                <label class="edit-banner-btn">
                    <i data-lucide="camera" style="width: 20px; height: 20px;"></i>
                    <input type="file" id="edit-banner-input" accept="image/*" style="display: none;">
                </label>
            </div>
            <div class="edit-avatar-container">
                <div class="edit-avatar" id="edit-avatar-preview">
                    <?php if ($currentUser && !empty($currentUser['profile_picture'])): ?>
                        <img src="<?= htmlspecialchars($currentUser['profile_picture']) ?>" alt="Avatar">
                    <?php else: ?>
                        <span><?= $currentUser && !empty($currentUser['username']) ? strtoupper(substr($currentUser['username'], 0, 1)) : '?' ?></span>
                    <?php endif; ?>
                </div>
                <label class="edit-avatar-btn">
                    <i data-lucide="camera" style="width: 20px; height: 20px;"></i>
                    <input type="file" id="edit-avatar-input" accept="image/*" style="display: none;">
                </label>
            </div>
        </div>

        <!-- Form Fields -->
        <div class="edit-form">
            <div class="edit-field">
                <label>Name</label>
                <input type="text" id="edit-username" value="<?= $currentUser && !empty($currentUser['username']) ? htmlspecialchars($currentUser['username']) : '' ?>">
            </div>
            <div class="edit-field">
                <label>Bio</label>
                <textarea id="edit-bio" rows="3" maxlength="500"><?= $currentUser && !empty($currentUser['bio']) ? htmlspecialchars($currentUser['bio']) : '' ?></textarea>
                <p class="char-count"><span id="bio-char-count"><?= strlen($currentUser['bio'] ?? '') ?></span>/500</p>
            </div>
            <div class="edit-field">
                <label>Location</label>
                <select id="edit-location">
                    <option value="">Select a location</option>
                    <optgroup label="Popular Cities">
                        <option value="New York, USA" <?= ($currentUser['location'] ?? '') === 'New York, USA' ? 'selected' : '' ?>>New York, USA</option>
                        <option value="Los Angeles, USA" <?= ($currentUser['location'] ?? '') === 'Los Angeles, USA' ? 'selected' : '' ?>>Los Angeles, USA</option>
                        <option value="San Francisco, USA" <?= ($currentUser['location'] ?? '') === 'San Francisco, USA' ? 'selected' : '' ?>>San Francisco, USA</option>
                        <option value="Miami, USA" <?= ($currentUser['location'] ?? '') === 'Miami, USA' ? 'selected' : '' ?>>Miami, USA</option>
                        <option value="London, UK" <?= ($currentUser['location'] ?? '') === 'London, UK' ? 'selected' : '' ?>>London, UK</option>
                        <option value="Paris, France" <?= ($currentUser['location'] ?? '') === 'Paris, France' ? 'selected' : '' ?>>Paris, France</option>
                        <option value="Berlin, Germany" <?= ($currentUser['location'] ?? '') === 'Berlin, Germany' ? 'selected' : '' ?>>Berlin, Germany</option>
                        <option value="Amsterdam, Netherlands" <?= ($currentUser['location'] ?? '') === 'Amsterdam, Netherlands' ? 'selected' : '' ?>>Amsterdam, Netherlands</option>
                        <option value="Dubai, UAE" <?= ($currentUser['location'] ?? '') === 'Dubai, UAE' ? 'selected' : '' ?>>Dubai, UAE</option>
                        <option value="Singapore" <?= ($currentUser['location'] ?? '') === 'Singapore' ? 'selected' : '' ?>>Singapore</option>
                        <option value="Hong Kong" <?= ($currentUser['location'] ?? '') === 'Hong Kong' ? 'selected' : '' ?>>Hong Kong</option>
                        <option value="Tokyo, Japan" <?= ($currentUser['location'] ?? '') === 'Tokyo, Japan' ? 'selected' : '' ?>>Tokyo, Japan</option>
                        <option value="Seoul, South Korea" <?= ($currentUser['location'] ?? '') === 'Seoul, South Korea' ? 'selected' : '' ?>>Seoul, South Korea</option>
                        <option value="Sydney, Australia" <?= ($currentUser['location'] ?? '') === 'Sydney, Australia' ? 'selected' : '' ?>>Sydney, Australia</option>
                        <option value="Toronto, Canada" <?= ($currentUser['location'] ?? '') === 'Toronto, Canada' ? 'selected' : '' ?>>Toronto, Canada</option>
                    </optgroup>
                    <optgroup label="Countries">
                        <option value="United States" <?= ($currentUser['location'] ?? '') === 'United States' ? 'selected' : '' ?>>United States</option>
                        <option value="United Kingdom" <?= ($currentUser['location'] ?? '') === 'United Kingdom' ? 'selected' : '' ?>>United Kingdom</option>
                        <option value="France" <?= ($currentUser['location'] ?? '') === 'France' ? 'selected' : '' ?>>France</option>
                        <option value="Germany" <?= ($currentUser['location'] ?? '') === 'Germany' ? 'selected' : '' ?>>Germany</option>
                        <option value="Spain" <?= ($currentUser['location'] ?? '') === 'Spain' ? 'selected' : '' ?>>Spain</option>
                        <option value="Italy" <?= ($currentUser['location'] ?? '') === 'Italy' ? 'selected' : '' ?>>Italy</option>
                        <option value="Japan" <?= ($currentUser['location'] ?? '') === 'Japan' ? 'selected' : '' ?>>Japan</option>
                        <option value="South Korea" <?= ($currentUser['location'] ?? '') === 'South Korea' ? 'selected' : '' ?>>South Korea</option>
                        <option value="Australia" <?= ($currentUser['location'] ?? '') === 'Australia' ? 'selected' : '' ?>>Australia</option>
                        <option value="Canada" <?= ($currentUser['location'] ?? '') === 'Canada' ? 'selected' : '' ?>>Canada</option>
                        <option value="Brazil" <?= ($currentUser['location'] ?? '') === 'Brazil' ? 'selected' : '' ?>>Brazil</option>
                    </optgroup>
                </select>
            </div>
            <div class="edit-field">
                <label>Website</label>
                <input type="text" id="edit-website" placeholder="your-website.com" value="<?= $currentUser && !empty($currentUser['website']) ? htmlspecialchars($currentUser['website']) : '' ?>">
                <p class="edit-field-error" id="website-error" style="display: none;"></p>
            </div>
        </div>
    </div>
</div>

<!-- Include Profile Modal -->
<?php require_once __DIR__ . '/../layouts/xray-profile-modal.php'; ?>

<!-- Include Footer -->
<?php require_once __DIR__ . '/../layouts/xray-footer.php'; ?>

<style>
/* Profile Styles */
.profile-banner {
    height: 128px;
    background: linear-gradient(135deg, color-mix(in srgb, var(--primary) 20%, transparent), color-mix(in srgb, var(--accent) 20%, transparent));
    position: relative;
}

.banner-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.profile-info {
    padding: 0 1rem 1rem;
    position: relative;
}

.profile-avatar-container {
    position: absolute;
    top: -48px;
    left: 1rem;
}

.profile-avatar-large {
    width: 96px;
    height: 96px;
    border-radius: 50%;
    border: 4px solid var(--background);
    object-fit: cover;
}

.profile-avatar-letter {
    background: linear-gradient(135deg, var(--primary), var(--accent));
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: bold;
    font-size: 2rem;
}

.profile-actions {
    display: flex;
    justify-content: flex-end;
    padding-top: 0.75rem;
}

.edit-profile-btn {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    border-radius: 9999px;
    border: 1px solid var(--border);
    background: transparent;
    color: var(--foreground);
    cursor: pointer;
    transition: all 0.2s;
    font-family: inherit;
    font-size: 0.875rem;
}

.edit-profile-btn:hover {
    background: var(--muted);
}

.profile-name-section {
    margin-top: 1rem;
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

/* Profile Tabs */
.profile-tabs {
    display: flex;
    border-bottom: 1px solid var(--border);
}

.profile-tab {
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
}

.profile-tab:hover {
    color: var(--foreground);
}

.profile-tab.active {
    color: var(--foreground);
}

.profile-tab.active::after {
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

.shadow-tab {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.375rem;
}

.shadow-tab.active {
    color: #7c3aed;
}

.shadow-tab.active::after {
    background: #a855f7;
}

.shadow-count {
    font-size: 0.75rem;
    padding: 0.125rem 0.375rem;
    border-radius: 9999px;
    background: #f3e8ff;
    color: #7c3aed;
}

/* Empty Posts */
.empty-posts {
    padding: 3rem;
    text-align: center;
    color: var(--muted-foreground);
}

/* Shadow Profile */
.shadow-profile-header {
    padding: 1.5rem;
    border-bottom: 1px solid var(--border);
}

.shadow-avatar-section {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.shadow-avatar-ring {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: color-mix(in srgb, var(--primary) 20%, transparent);
    display: flex;
    align-items: center;
    justify-content: center;
}

.shadow-avatar-inner {
    width: 64px;
    height: 64px;
    border-radius: 50%;
    background: color-mix(in srgb, var(--primary) 30%, transparent);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    font-weight: bold;
    color: var(--primary);
}

.shadow-identity-name {
    font-size: 1.5rem;
    font-weight: bold;
    color: var(--primary);
    margin: 0;
}

.shadow-identity-label {
    font-size: 0.875rem;
    color: var(--muted-foreground);
    margin: 0;
}

.shadow-stats-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1rem;
    margin-top: 1.5rem;
}

.shadow-stat-box {
    background: color-mix(in srgb, var(--primary) 10%, transparent);
    border-radius: 0.5rem;
    padding: 0.75rem;
    text-align: center;
}

.shadow-stat-value {
    font-size: 1.5rem;
    font-weight: bold;
    color: var(--primary);
    margin: 0;
}

.shadow-stat-label {
    font-size: 0.75rem;
    color: var(--muted-foreground);
    margin: 0;
}

.shadow-total-spent {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-top: 1.25rem;
}

.spent-label {
    font-size: 0.75rem;
    color: var(--muted-foreground);
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.spent-value {
    font-size: 1.25rem;
    font-weight: bold;
    color: #f59e0b;
}

.spent-rank {
    padding: 0.25rem 0.5rem;
    border-radius: 9999px;
    background: color-mix(in srgb, #f59e0b 20%, transparent);
    color: #f59e0b;
    font-weight: bold;
    font-size: 0.875rem;
}

/* Shadow Tabs */
.shadow-tabs {
    display: flex;
    border-bottom: 1px solid var(--border);
}

.shadow-tab-btn {
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
}

.shadow-tab-btn:hover {
    color: var(--foreground);
}

.shadow-tab-btn.active {
    color: var(--foreground);
}

.shadow-tab-btn.active::after {
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

/* Edit Modal */
.edit-modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.5);
    z-index: 1000;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1rem;
}

.edit-modal {
    background: var(--background);
    border: 1px solid var(--border);
    border-radius: 1rem;
    width: 100%;
    max-width: 512px;
    max-height: 90vh;
    overflow-y: auto;
}

.edit-modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.75rem 1rem;
    border-bottom: 1px solid var(--border);
}

.edit-modal-header-left {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.edit-modal-header h2 {
    font-size: 1.125rem;
    font-weight: bold;
    margin: 0;
}

.edit-modal-close {
    padding: 0.25rem;
    border-radius: 50%;
    background: none;
    border: none;
    cursor: pointer;
    color: var(--foreground);
    transition: all 0.2s;
}

.edit-modal-close:hover {
    background: var(--muted);
}

.edit-modal-save {
    padding: 0.375rem 1rem;
    border-radius: 9999px;
    background: var(--foreground);
    color: var(--background);
    border: none;
    font-weight: 500;
    font-size: 0.875rem;
    cursor: pointer;
    transition: all 0.2s;
    font-family: inherit;
}

.edit-modal-save:hover {
    opacity: 0.9;
}

.edit-modal-save:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.edit-banner-section {
    position: relative;
}

.edit-banner {
    height: 128px;
    background: linear-gradient(135deg, color-mix(in srgb, var(--primary) 20%, transparent), color-mix(in srgb, var(--accent) 20%, transparent));
    position: relative;
    overflow: hidden;
}

.edit-banner-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.edit-banner-btn {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(0, 0, 0, 0.3);
    opacity: 0;
    transition: opacity 0.2s;
    cursor: pointer;
    color: white;
}

.edit-banner:hover .edit-banner-btn {
    opacity: 1;
}

.edit-avatar-container {
    position: absolute;
    bottom: -48px;
    left: 1rem;
}

.edit-avatar {
    width: 96px;
    height: 96px;
    border-radius: 50%;
    border: 4px solid var(--background);
    background: linear-gradient(135deg, var(--primary), var(--accent));
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: bold;
    font-size: 2rem;
    overflow: hidden;
}

.edit-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.edit-avatar-btn {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(0, 0, 0, 0.3);
    border-radius: 50%;
    opacity: 0;
    transition: opacity 0.2s;
    cursor: pointer;
    color: white;
}

.edit-avatar-container:hover .edit-avatar-btn {
    opacity: 1;
}

.edit-form {
    padding: 4rem 1rem 1rem;
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.edit-field label {
    display: block;
    font-size: 0.75rem;
    color: var(--muted-foreground);
    margin-bottom: 0.25rem;
}

.edit-field input,
.edit-field textarea,
.edit-field select {
    width: 100%;
    padding: 0.5rem 0.75rem;
    border-radius: 0.5rem;
    border: 1px solid var(--border);
    background: var(--background);
    color: var(--foreground);
    font-family: inherit;
    font-size: 0.875rem;
    transition: all 0.2s;
}

.edit-field input:focus,
.edit-field textarea:focus,
.edit-field select:focus {
    outline: none;
    border-color: var(--primary);
    box-shadow: 0 0 0 2px color-mix(in srgb, var(--primary) 20%, transparent);
}

.edit-field textarea {
    resize: none;
}

.edit-field-error {
    font-size: 0.75rem;
    color: #ef4444;
    margin-top: 0.25rem;
}

.char-count {
    font-size: 0.75rem;
    color: var(--muted-foreground);
    text-align: right;
    margin-top: 0.25rem;
}
</style>

<script>
// File input references
let selectedAvatarFile = null;
let selectedBannerFile = null;

// Edit Modal
function openEditModal() {
    document.getElementById('edit-modal').style.display = 'flex';
    if (typeof lucide !== 'undefined') lucide.createIcons();
}

function closeEditModal() {
    document.getElementById('edit-modal').style.display = 'none';
    selectedAvatarFile = null;
    selectedBannerFile = null;
}

// Avatar upload preview
document.getElementById('edit-avatar-input')?.addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        if (file.size > 2 * 1024 * 1024) {
            alert('Profile picture must be less than 2MB');
            return;
        }
        selectedAvatarFile = file;
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('edit-avatar-preview').innerHTML = `<img src="${e.target.result}" alt="Avatar">`;
        };
        reader.readAsDataURL(file);
    }
});

// Banner upload preview
document.getElementById('edit-banner-input')?.addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        if (file.size > 5 * 1024 * 1024) {
            alert('Banner must be less than 5MB');
            return;
        }
        selectedBannerFile = file;
        const reader = new FileReader();
        reader.onload = function(e) {
            document.getElementById('edit-banner-preview').src = e.target.result;
        };
        reader.readAsDataURL(file);
    }
});

// Bio character counter
document.getElementById('edit-bio')?.addEventListener('input', function() {
    document.getElementById('bio-char-count').textContent = this.value.length;
});

// Website validation
document.getElementById('edit-website')?.addEventListener('input', function() {
    const value = this.value.toLowerCase();
    const errorEl = document.getElementById('website-error');
    const saveBtn = document.getElementById('save-profile-btn');

    const dangerousTlds = ['.ru', '.cn', '.tk', '.ml', '.ga', '.cf', '.gq', '.zip', '.mov'];
    let error = null;

    for (const tld of dangerousTlds) {
        if (value.endsWith(tld)) {
            error = `Warning: ${tld} domains are not allowed`;
            break;
        }
    }

    if (error) {
        errorEl.textContent = error;
        errorEl.style.display = 'block';
        saveBtn.disabled = true;
    } else {
        errorEl.style.display = 'none';
        saveBtn.disabled = false;
    }
});

// Save profile
async function saveProfile() {
    const saveBtn = document.getElementById('save-profile-btn');
    const originalText = saveBtn.textContent;
    saveBtn.textContent = 'Saving...';
    saveBtn.disabled = true;

    const formData = new FormData();
    formData.append('username', document.getElementById('edit-username').value);
    formData.append('bio', document.getElementById('edit-bio').value);
    formData.append('location', document.getElementById('edit-location').value);
    formData.append('website', document.getElementById('edit-website').value);

    if (selectedAvatarFile) {
        formData.append('profile_picture', selectedAvatarFile);
    }
    if (selectedBannerFile) {
        formData.append('banner_picture', selectedBannerFile);
    }

    try {
        const response = await fetch('index.php?action=update-profile', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            // Update display
            const profile = data.profile;

            document.getElementById('display-username').textContent = profile.username;
            document.getElementById('display-handle').textContent = '@' + profile.username;
            document.getElementById('display-bio').textContent = profile.bio || 'No bio yet';

            // Update location
            const locationEl = document.getElementById('display-location');
            if (profile.location) {
                locationEl.innerHTML = `<i data-lucide="map-pin" style="width: 16px; height: 16px;"></i><span>${profile.location}</span>`;
                locationEl.style.display = 'flex';
            } else {
                locationEl.style.display = 'none';
            }

            // Update website
            const websiteEl = document.getElementById('display-website');
            if (profile.website) {
                websiteEl.innerHTML = `<i data-lucide="link" style="width: 16px; height: 16px;"></i><a href="https://${profile.website}" class="website-link" target="_blank">${profile.website}</a>`;
                websiteEl.style.display = 'flex';
            } else {
                websiteEl.style.display = 'none';
            }

            // Update avatar if changed
            if (profile.profile_picture) {
                const avatarContainer = document.querySelector('.profile-avatar-container');
                avatarContainer.innerHTML = `<img src="${profile.profile_picture}" alt="Avatar" class="profile-avatar-large" id="profile-avatar-img">`;
            }

            // Update banner if changed
            if (profile.banner_picture) {
                document.getElementById('display-banner').src = profile.banner_picture;
            }

            if (typeof lucide !== 'undefined') lucide.createIcons();
            closeEditModal();
        } else {
            alert(data.errors ? data.errors.join('\n') : 'Failed to update profile');
        }
    } catch (error) {
        console.error('Error:', error);
        alert('An error occurred while saving');
    }

    saveBtn.textContent = originalText;
    saveBtn.disabled = false;
}

// Mode change handler
function updateProfileForMode() {
    const isShadowMode = document.body.classList.contains('shadow-mode');
    document.getElementById('public-profile').style.display = isShadowMode ? 'none' : 'block';
    document.getElementById('shadow-profile').style.display = isShadowMode ? 'block' : 'none';
}

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    updateProfileForMode();

    // Listen for mode changes
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.attributeName === 'class') {
                updateProfileForMode();
            }
        });
    });

    observer.observe(document.body, { attributes: true });
});

// Close modal on overlay click
document.getElementById('edit-modal')?.addEventListener('click', function(e) {
    if (e.target === this) closeEditModal();
});
</script>
