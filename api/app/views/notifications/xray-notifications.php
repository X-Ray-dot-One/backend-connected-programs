<?php
/**
 * X-RAY Notifications Page
 */

$isShadowMode = isset($_COOKIE['shadow_mode']) && $_COOKIE['shadow_mode'] === 'true';
?>

<!-- Include Header -->
<?php require_once __DIR__ . '/../layouts/xray-header.php'; ?>

<div class="xray-container">
    <!-- Include Left Sidebar -->
    <?php require_once __DIR__ . '/../layouts/xray-sidebar.php'; ?>

    <!-- Main Content Wrapper -->
    <div class="main-content-wrapper">
        <div class="main-feed">
            <!-- Header -->
            <div class="feed-header">
                <div class="feed-header-content">
                    <h1 class="feed-title">notifications</h1>
                    <p class="feed-subtitle" id="notifications-subtitle">
                        <?= $isShadowMode ? '// position updates' : '// activity on your posts' ?>
                    </p>
                </div>
            </div>

            <!-- Notifications List -->
            <div class="notifications-list" id="notifications-list">
                <!-- Will be populated by JavaScript based on mode -->
            </div>
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
/* Notifications Styles */
.notifications-list {
    border-top: 1px solid var(--border);
}

.notification-item {
    padding: 1rem;
    border-bottom: 1px solid var(--border);
    transition: all 0.2s;
    cursor: pointer;
    display: flex;
    gap: 0.75rem;
}

.notification-item:hover {
    background: color-mix(in srgb, var(--muted) 50%, transparent);
}

.notification-item.shadow-mention {
    background: #f3e8ff;
    border-left: 4px solid #a855f7;
}

.notification-item.shadow-mention:hover {
    background: #ede9fe;
}

body.shadow-mode .notification-item.shadow-mention {
    background: color-mix(in srgb, #a855f7 15%, var(--background));
    border-left: 4px solid #a855f7;
}

body.shadow-mode .notification-item.shadow-mention:hover {
    background: color-mix(in srgb, #a855f7 25%, var(--background));
}

.notification-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.notification-icon.like {
    background: color-mix(in srgb, #ef4444 15%, transparent);
    color: #ef4444;
}

.notification-icon.follow {
    background: color-mix(in srgb, var(--primary) 15%, transparent);
    color: var(--primary);
}

.notification-icon.comment {
    background: color-mix(in srgb, #3b82f6 15%, transparent);
    color: #3b82f6;
}

.notification-icon.mention {
    background: color-mix(in srgb, var(--primary) 15%, transparent);
    color: var(--primary);
}

.notification-icon.shadow {
    background: color-mix(in srgb, #a855f7 15%, transparent);
    color: #a855f7;
}

.notification-icon.position-down {
    background: color-mix(in srgb, #ef4444 20%, transparent);
    color: #ef4444;
}

.notification-content {
    flex: 1;
    min-width: 0;
}

.notification-header {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.notification-username {
    font-weight: 500;
    color: var(--foreground);
}

.notification-username.shadow {
    color: #7c3aed;
}

.notification-action {
    color: var(--muted-foreground);
}

.notification-action.shadow {
    color: #a855f7;
}

.notification-time {
    font-size: 0.75rem;
    color: var(--muted-foreground);
    margin-left: auto;
}

.notification-time.shadow {
    color: #a855f7;
}

.notification-preview {
    font-size: 0.875rem;
    color: var(--muted-foreground);
    margin-top: 0.25rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.notification-preview.shadow {
    color: #374151;
}

body.shadow-mode .notification-preview.shadow {
    color: var(--foreground);
}

.notification-boost {
    font-size: 0.75rem;
    color: #7c3aed;
    margin-top: 0.25rem;
}

.notification-position {
    font-size: 0.75rem;
    color: var(--muted-foreground);
    margin-top: 0.25rem;
}

.notification-position-change {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.notification-target {
    color: var(--primary);
    font-weight: 500;
}

.notification-avatar {
    width: 24px;
    height: 24px;
    border-radius: 50%;
}
</style>

<script>
// Mock notification data
const publicNotifications = [
    {
        id: 1,
        type: "shadow_mention",
        username: "anonymous",
        avatar: "",
        content: "mentioned you in a shadow post",
        time: "5m",
        postPreview: "alex_trader's calls have been pretty solid lately...",
        boost: 5.2
    },
    {
        id: 2,
        type: "like",
        username: "solana_dev",
        avatar: "https://api.dicebear.com/7.x/avataaars/svg?seed=solana",
        content: "liked your post",
        time: "12m",
        postPreview: "Just deployed my first Solana program..."
    },
    {
        id: 3,
        type: "follow",
        username: "crypto_whale",
        avatar: "https://api.dicebear.com/7.x/avataaars/svg?seed=whale",
        content: "started following you",
        time: "25m"
    },
    {
        id: 4,
        type: "shadow_mention",
        username: "anonymous",
        avatar: "",
        content: "mentioned you in a shadow post",
        time: "1h",
        postPreview: "don't trust alex_trader blindly, he's been wrong...",
        boost: 3.8
    },
    {
        id: 5,
        type: "comment",
        username: "defi_queen",
        avatar: "https://api.dicebear.com/7.x/avataaars/svg?seed=queen",
        content: "commented on your post",
        time: "2h",
        postPreview: "The APY on this new protocol is insane..."
    },
    {
        id: 6,
        type: "mention",
        username: "nft_collector",
        avatar: "https://api.dicebear.com/7.x/avataaars/svg?seed=nft",
        content: "mentioned you in a post",
        time: "3h",
        postPreview: "Big shoutout to @you for the alpha..."
    },
    {
        id: 7,
        type: "like",
        username: "phantom_ceo",
        avatar: "https://api.dicebear.com/7.x/avataaars/svg?seed=phantom",
        content: "liked your post",
        time: "4h",
        postPreview: "Phantom wallet integration is seamless..."
    },
    {
        id: 8,
        type: "follow",
        username: "jupiter_og",
        avatar: "https://api.dicebear.com/7.x/avataaars/svg?seed=jupiter",
        content: "started following you",
        time: "6h"
    }
];

const shadowNotifications = [
    {
        id: 1,
        targetAccount: "@alpha_leaker",
        oldPosition: 12,
        newPosition: 13,
        postPreview: "insider alpha on upcoming token launch...",
        time: "5m"
    },
    {
        id: 2,
        targetAccount: "@whale_insider",
        oldPosition: 5,
        newPosition: 8,
        postPreview: "whale wallet just moved 500k to...",
        time: "23m"
    },
    {
        id: 3,
        targetAccount: "@defi_alpha",
        oldPosition: 3,
        newPosition: 4,
        postPreview: "new yield farm with 500% APY...",
        time: "1h"
    },
    {
        id: 4,
        targetAccount: "@secret_dev",
        oldPosition: 45,
        newPosition: 67,
        postPreview: "leaked smart contract shows...",
        time: "2h"
    },
    {
        id: 5,
        targetAccount: "@token_hunter",
        oldPosition: 8,
        newPosition: 12,
        postPreview: "presale allocation strategy that...",
        time: "3h"
    }
];

function getNotificationIcon(type) {
    const icons = {
        like: '<i data-lucide="heart" style="width: 20px; height: 20px;"></i>',
        follow: '<i data-lucide="user-plus" style="width: 20px; height: 20px;"></i>',
        comment: '<i data-lucide="message-circle" style="width: 20px; height: 20px;"></i>',
        mention: '<i data-lucide="at-sign" style="width: 20px; height: 20px;"></i>',
        shadow_mention: '<i data-lucide="eye-off" style="width: 20px; height: 20px;"></i>'
    };
    return icons[type] || icons.mention;
}

function renderPublicNotifications() {
    const container = document.getElementById('notifications-list');
    container.innerHTML = publicNotifications.map(notif => {
        if (notif.type === 'shadow_mention') {
            return `
                <div class="notification-item shadow-mention">
                    <div class="notification-icon shadow">
                        <i data-lucide="eye-off" style="width: 20px; height: 20px;"></i>
                    </div>
                    <div class="notification-content">
                        <div class="notification-header">
                            <span class="notification-username shadow">anonymous</span>
                            <span class="notification-action shadow">${notif.content}</span>
                            <span class="notification-time shadow">${notif.time}</span>
                        </div>
                        ${notif.postPreview ? `<p class="notification-preview shadow">${notif.postPreview}</p>` : ''}
                        ${notif.boost ? `<p class="notification-boost">boosted with ${notif.boost} SOL</p>` : ''}
                    </div>
                </div>
            `;
        } else {
            return `
                <div class="notification-item">
                    <div class="notification-icon ${notif.type}">
                        ${getNotificationIcon(notif.type)}
                    </div>
                    <div class="notification-content">
                        <div class="notification-header">
                            ${notif.avatar ? `<img src="${notif.avatar}" alt="${notif.username}" class="notification-avatar">` : ''}
                            <span class="notification-username">${notif.username}</span>
                            <span class="notification-action">${notif.content}</span>
                            <span class="notification-time">${notif.time}</span>
                        </div>
                        ${notif.postPreview ? `<p class="notification-preview">${notif.postPreview}</p>` : ''}
                    </div>
                </div>
            `;
        }
    }).join('');

    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
}

function renderShadowNotifications() {
    const container = document.getElementById('notifications-list');
    container.innerHTML = shadowNotifications.map(notif => `
        <div class="notification-item">
            <div class="notification-icon position-down">
                <i data-lucide="trending-down" style="width: 20px; height: 20px;"></i>
            </div>
            <div class="notification-content">
                <div class="notification-header">
                    <div class="notification-position-change">
                        <span>your post on</span>
                        <span class="notification-target">${notif.targetAccount}</span>
                        <span>has been passed</span>
                    </div>
                    <span class="notification-time">${notif.time}</span>
                </div>
                <p class="notification-preview">${notif.postPreview}</p>
                <p class="notification-position">#${notif.oldPosition} → #${notif.newPosition}</p>
            </div>
        </div>
    `).join('');

    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
}

function updateNotificationsForMode() {
    const isShadowMode = document.body.classList.contains('shadow-mode');
    const subtitle = document.getElementById('notifications-subtitle');

    if (subtitle) {
        subtitle.textContent = isShadowMode ? '// position updates' : '// activity on your posts';
    }

    if (isShadowMode) {
        renderShadowNotifications();
    } else {
        renderPublicNotifications();
    }
}

// Initial render
document.addEventListener('DOMContentLoaded', function() {
    updateNotificationsForMode();

    // Listen for mode changes
    const observer = new MutationObserver(function(mutations) {
        mutations.forEach(function(mutation) {
            if (mutation.attributeName === 'class') {
                updateNotificationsForMode();
            }
        });
    });

    observer.observe(document.body, { attributes: true });
});
</script>
