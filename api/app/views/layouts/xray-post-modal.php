<?php
/**
 * X-RAY Post Modal Component
 * Based on the frontend design from frontend-dev
 */

$isShadowMode = isset($_SESSION['shadow_mode']) && $_SESSION['shadow_mode'];
$isLoggedIn = AuthController::isLoggedIn();
$currentUser = $isLoggedIn ? AuthController::getCurrentUser() : null;
$shadowIdentity = 'shadow_' . substr(md5($_SESSION['user_id'] ?? 'anon'), 0, 4);
?>

<!-- Post Modal -->
<div id="postModal" class="post-modal-backdrop" style="display: none;">
    <div class="post-modal-container">
        <!-- Header -->
        <div class="post-modal-header">
            <button onclick="closePostModal()" class="post-modal-close">
                <i data-lucide="x" style="width: 20px; height: 20px;"></i>
            </button>
            <button onclick="submitPost()" id="postSubmitBtn" class="post-modal-submit" disabled>
                <?= $isShadowMode ? 'shadow post' : 'post' ?>
            </button>
        </div>

        <?php if ($isShadowMode): ?>
        <!-- Shadow Mode Header -->
        <div class="post-modal-shadow-header">
            <div class="shadow-header-content">
                <div class="shadow-header-left">
                    <i data-lucide="eye-off" style="width: 16px; height: 16px; color: var(--primary);"></i>
                    <span class="shadow-header-label">post as</span>

                    <!-- Identity Dropdown -->
                    <div class="identity-dropdown">
                        <button onclick="toggleIdentityDropdown()" class="identity-dropdown-trigger">
                            <span class="identity-name" id="currentIdentity"><?= $shadowIdentity ?></span>
                            <i data-lucide="chevron-down" style="width: 14px; height: 14px;"></i>
                        </button>
                        <div id="identityDropdownMenu" class="identity-dropdown-menu" style="display: none;">
                            <button onclick="selectIdentity('<?= $shadowIdentity ?>')" class="identity-option active">
                                <span><?= $shadowIdentity ?></span>
                                <i data-lucide="check" style="width: 16px; height: 16px;"></i>
                            </button>
                            <button onclick="selectIdentity('anon_<?= substr(md5(time()), 0, 4) ?>')" class="identity-option">
                                <span>anon_<?= substr(md5(time()), 0, 4) ?></span>
                            </button>
                            <button onclick="selectIdentity('ghost_<?= substr(md5(uniqid()), 0, 4) ?>')" class="identity-option">
                                <span>ghost_<?= substr(md5(uniqid()), 0, 4) ?></span>
                            </button>
                            <div class="identity-dropdown-divider"></div>
                            <button onclick="generateNewIdentity()" class="identity-option generate">
                                <i data-lucide="plus" style="width: 16px; height: 16px;"></i>
                                <span>generate new</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Help Tooltip -->
                <div class="help-tooltip-wrapper">
                    <button onclick="toggleHelpTooltip()" class="help-tooltip-trigger">
                        <i data-lucide="help-circle" style="width: 16px; height: 16px;"></i>
                    </button>
                    <div id="helpTooltip" class="help-tooltip" style="display: none;">
                        <p class="help-tooltip-title">how does it work?</p>
                        <p class="help-tooltip-text">
                            in shadow mode, you post anonymously on a target's wall.
                            the boost determines your position: more SOL = higher visibility.
                        </p>
                        <a href="index.php?action=how-it-works" class="help-tooltip-link">see more</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Target User Selector -->
        <div class="post-modal-target">
            <div class="target-header">
                <i data-lucide="target" style="width: 16px; height: 16px; color: var(--primary);"></i>
                <span class="target-label">target user</span>
            </div>

            <!-- Platform Toggle -->
            <div class="target-platform-toggle">
                <button onclick="setTargetPlatform('xray')" class="platform-btn active" id="platformXray">
                    <i data-lucide="eye-off" style="width: 14px; height: 14px;"></i>
                    <span>x-ray</span>
                </button>
                <button onclick="setTargetPlatform('twitter')" class="platform-btn" id="platformTwitter">
                    <svg viewBox="0 0 24 24" style="width: 14px; height: 14px; fill: currentColor;">
                        <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z" />
                    </svg>
                    <span>X</span>
                </button>
            </div>

            <!-- Target Input -->
            <div class="target-input-container">
                <div class="target-input-wrapper">
                    <input
                        type="text"
                        id="targetUserInput"
                        placeholder="@username"
                        class="target-input"
                        oninput="handleTargetInput(this.value)"
                    >
                    <div id="targetUserDropdown" class="target-dropdown" style="display: none;">
                        <!-- Users will be populated here -->
                    </div>
                </div>
                <button onclick="toggleTargetLock()" id="targetLockBtn" class="target-lock-btn" disabled>
                    <i data-lucide="lock" style="width: 16px; height: 16px;"></i>
                    <span>lock</span>
                </button>
            </div>

            <!-- Boost Section (visible after target is locked) -->
            <div id="boostSection" class="boost-section" style="display: none;">
                <div class="boost-header">
                    <i data-lucide="zap" style="width: 16px; height: 16px; color: #f59e0b;"></i>
                    <span class="boost-label">boost</span>
                </div>

                <!-- SOL and Position Inputs -->
                <div class="boost-inputs">
                    <div class="boost-sol-input">
                        <input type="text" id="boostSolInput" value="1" oninput="handleBoostSolChange(this.value)">
                        <span class="boost-sol-label">SOL</span>
                    </div>
                    <span class="boost-equals">=</span>
                    <div class="boost-position-input" id="boostPositionBox">
                        <span class="boost-hash">#</span>
                        <input type="text" id="boostPositionInput" value="50" oninput="handleBoostPositionChange(this.value)">
                        <span id="positionMedal"></span>
                    </div>
                </div>

                <!-- Slider -->
                <input
                    type="range"
                    id="boostSlider"
                    min="0.05"
                    max="100"
                    step="0.05"
                    value="1"
                    class="boost-slider"
                    oninput="handleBoostSliderChange(this.value)"
                >

                <div class="boost-slider-labels">
                    <span>#1000</span>
                    <span class="boost-gold-label">🥇 #1</span>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Content Area -->
        <div class="post-modal-content">
            <div class="post-compose-area">
                <?php if (!$isShadowMode): ?>
                <!-- Avatar (Public mode only) -->
                <div class="post-compose-avatar">
                    <?php if ($currentUser && !empty($currentUser['profile_picture'])): ?>
                        <img src="<?= htmlspecialchars($currentUser['profile_picture']) ?>" alt="Avatar">
                    <?php elseif ($currentUser && !empty($currentUser['username'])): ?>
                        <div class="avatar-letter"><?= strtoupper(substr($currentUser['username'], 0, 1)) ?></div>
                    <?php else: ?>
                        <div class="avatar-letter">?</div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <div class="post-compose-input-area">
                    <!-- Text Area -->
                    <div class="post-textarea-wrapper">
                        <textarea
                            id="postContent"
                            placeholder="What's happening?"
                            class="post-textarea"
                            oninput="handlePostInput(this)"
                            onkeydown="handlePostKeydown(event)"
                        ></textarea>

                        <!-- Highlighted Preview -->
                        <div id="postContentPreview" class="post-content-preview">
                            <span class="placeholder">What's happening?</span>
                        </div>
                    </div>

                    <!-- Mention Suggestions -->
                    <div id="mentionSuggestions" class="mention-suggestions" style="display: none;">
                        <!-- Users will be populated here -->
                    </div>

                    <div class="post-compose-divider"></div>

                    <!-- Bottom Actions -->
                    <div class="post-compose-actions">
                        <div class="post-compose-tools">
                            <?php if (!$isShadowMode): ?>
                            <button class="post-tool-btn">
                                <i data-lucide="image" style="width: 20px; height: 20px;"></i>
                            </button>
                            <?php endif; ?>
                        </div>

                        <!-- Character Counter -->
                        <div id="charCounter" class="char-counter" style="display: none;">
                            <svg class="char-counter-circle" viewBox="0 0 24 24">
                                <circle cx="12" cy="12" r="10" class="char-counter-bg"></circle>
                                <circle cx="12" cy="12" r="10" class="char-counter-progress" id="charProgress"></circle>
                            </svg>
                            <span id="charCountText" class="char-count-text"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Post Modal State
const postModalState = {
    isShadowMode: <?= $isShadowMode ? 'true' : 'false' ?>,
    targetPlatform: 'xray',
    targetUser: '',
    isTargetLocked: false,
    boostAmount: 1,
    content: '',
    mentionQuery: null,
    mentionStartIndex: 0,
    selectedMentionIndex: 0,
    MAX_CHARS: 280
};

// Boost tiers for SOL to position conversion
const boostTiers = [
    { sol: 0.05, position: 1000 },
    { sol: 0.1, position: 500 },
    { sol: 0.25, position: 250 },
    { sol: 0.5, position: 100 },
    { sol: 1, position: 50 },
    { sol: 2.5, position: 20 },
    { sol: 5, position: 10 },
    { sol: 10, position: 5 },
    { sol: 25, position: 3 },
    { sol: 50, position: 2 },
    { sol: 100, position: 1 },
];

// Open/Close Modal
function openPostModal() {
    <?php if (!$isLoggedIn): ?>
    alert('Connect your wallet to post');
    return;
    <?php endif; ?>

    document.getElementById('postModal').style.display = 'flex';
    document.body.style.overflow = 'hidden';
    setTimeout(() => {
        document.getElementById('postContent').focus();
    }, 100);
    if (typeof lucide !== 'undefined') lucide.createIcons();
}

function closePostModal() {
    document.getElementById('postModal').style.display = 'none';
    document.body.style.overflow = '';
    resetPostModal();
}

function resetPostModal() {
    postModalState.content = '';
    postModalState.targetUser = '';
    postModalState.isTargetLocked = false;
    postModalState.boostAmount = 1;
    document.getElementById('postContent').value = '';
    document.getElementById('postContentPreview').innerHTML = '<span class="placeholder">What\'s happening?</span>';
    document.getElementById('postSubmitBtn').disabled = true;
    document.getElementById('charCounter').style.display = 'none';

    if (postModalState.isShadowMode) {
        document.getElementById('targetUserInput').value = '';
        document.getElementById('targetUserInput').disabled = false;
        document.getElementById('targetLockBtn').disabled = true;
        document.getElementById('targetLockBtn').innerHTML = '<i data-lucide="lock" style="width: 16px; height: 16px;"></i><span>lock</span>';
        document.getElementById('targetLockBtn').className = 'target-lock-btn';
        document.getElementById('boostSection').style.display = 'none';
        document.getElementById('boostSolInput').value = '1';
        document.getElementById('boostPositionInput').value = '50';
        document.getElementById('boostSlider').value = 1;

        // Re-enable platform buttons
        document.querySelectorAll('.platform-btn').forEach(btn => btn.disabled = false);

        if (typeof lucide !== 'undefined') lucide.createIcons();
    }
}

// Handle Post Content Input
function handlePostInput(textarea) {
    const content = textarea.value;
    postModalState.content = content;

    // Update preview with highlighted mentions
    const preview = document.getElementById('postContentPreview');
    if (content.length > 0) {
        const highlighted = content.replace(/(^|\s)@(\w+)/g, '$1<span class="mention">@$2</span>');
        preview.innerHTML = highlighted || '<span class="placeholder">What\'s happening?</span>';
    } else {
        preview.innerHTML = '<span class="placeholder">What\'s happening?</span>';
    }

    // Check for mentions
    const cursorPos = textarea.selectionStart;
    const textBeforeCursor = content.slice(0, cursorPos);
    const lastAtIndex = textBeforeCursor.lastIndexOf('@');

    if (lastAtIndex !== -1) {
        const textAfterAt = textBeforeCursor.slice(lastAtIndex + 1);
        const charBeforeAt = lastAtIndex > 0 ? content[lastAtIndex - 1] : ' ';

        if (!textAfterAt.includes(' ') && (charBeforeAt === ' ' || charBeforeAt === '\n' || lastAtIndex === 0)) {
            postModalState.mentionQuery = textAfterAt;
            postModalState.mentionStartIndex = lastAtIndex;
            postModalState.selectedMentionIndex = 0;
            showMentionSuggestions(textAfterAt);
        } else {
            hideMentionSuggestions();
        }
    } else {
        hideMentionSuggestions();
    }

    // Update character counter
    updateCharCounter(content.length);

    // Update submit button state
    updateSubmitButton();
}

function handlePostKeydown(event) {
    const suggestions = document.getElementById('mentionSuggestions');
    if (suggestions.style.display !== 'none') {
        const items = suggestions.querySelectorAll('.mention-item');
        if (items.length > 0) {
            if (event.key === 'ArrowDown') {
                event.preventDefault();
                postModalState.selectedMentionIndex = (postModalState.selectedMentionIndex + 1) % items.length;
                updateMentionSelection();
            } else if (event.key === 'ArrowUp') {
                event.preventDefault();
                postModalState.selectedMentionIndex = (postModalState.selectedMentionIndex - 1 + items.length) % items.length;
                updateMentionSelection();
            } else if (event.key === 'Enter' || event.key === 'Tab') {
                event.preventDefault();
                const selectedItem = items[postModalState.selectedMentionIndex];
                if (selectedItem) {
                    insertMention(selectedItem.dataset.username);
                }
            } else if (event.key === 'Escape') {
                hideMentionSuggestions();
            }
        }
    }
}

async function showMentionSuggestions(query) {
    if (query.length === 0) {
        hideMentionSuggestions();
        return;
    }

    try {
        const response = await fetch('index.php?action=searchUsers&q=' + encodeURIComponent(query));
        const data = await response.json();

        if (data.success && data.users.length > 0) {
            const suggestions = document.getElementById('mentionSuggestions');
            suggestions.innerHTML = data.users.slice(0, 5).map((user, index) => `
                <button class="mention-item ${index === 0 ? 'selected' : ''}" data-username="${user.username}" onclick="insertMention('${user.username}')">
                    <img src="${user.avatar}" alt="${user.username}" class="mention-avatar">
                    <div class="mention-info">
                        <span class="mention-name">${user.username}</span>
                        <span class="mention-handle">@${user.username}</span>
                    </div>
                </button>
            `).join('');
            suggestions.style.display = 'block';
        } else {
            hideMentionSuggestions();
        }
    } catch (error) {
        console.error('Error fetching mentions:', error);
        hideMentionSuggestions();
    }
}

function hideMentionSuggestions() {
    postModalState.mentionQuery = null;
    document.getElementById('mentionSuggestions').style.display = 'none';
}

function updateMentionSelection() {
    const items = document.querySelectorAll('.mention-item');
    items.forEach((item, index) => {
        item.classList.toggle('selected', index === postModalState.selectedMentionIndex);
    });
}

function insertMention(username) {
    const textarea = document.getElementById('postContent');
    const content = textarea.value;
    const before = content.slice(0, postModalState.mentionStartIndex);
    const after = content.slice(postModalState.mentionStartIndex + 1 + (postModalState.mentionQuery?.length || 0));
    const newContent = `${before}@${username} ${after}`;

    textarea.value = newContent;
    postModalState.content = newContent;
    hideMentionSuggestions();

    // Update preview
    handlePostInput(textarea);

    // Set cursor position
    const newCursorPos = postModalState.mentionStartIndex + username.length + 2;
    textarea.focus();
    textarea.setSelectionRange(newCursorPos, newCursorPos);
}

// Character Counter
function updateCharCounter(length) {
    const counter = document.getElementById('charCounter');
    const progress = document.getElementById('charProgress');
    const text = document.getElementById('charCountText');

    if (length > 0) {
        counter.style.display = 'flex';
        const percentage = Math.min(length / postModalState.MAX_CHARS, 1);
        const circumference = 2 * Math.PI * 10;
        progress.style.strokeDasharray = `${percentage * circumference} ${circumference}`;

        const remaining = postModalState.MAX_CHARS - length;
        if (remaining < 0) {
            progress.style.stroke = '#ef4444';
            text.textContent = 'too many characters';
            text.style.color = '#ef4444';
            text.style.display = 'inline';
        } else if (remaining <= 20) {
            progress.style.stroke = '#f59e0b';
            text.textContent = remaining;
            text.style.color = '#f59e0b';
            text.style.display = 'inline';
        } else {
            progress.style.stroke = 'var(--primary)';
            text.style.display = 'none';
        }
    } else {
        counter.style.display = 'none';
    }
}

// Update Submit Button State
function updateSubmitButton() {
    const btn = document.getElementById('postSubmitBtn');
    const hasContent = postModalState.content.trim().length > 0;
    const notOverLimit = postModalState.content.length <= postModalState.MAX_CHARS;

    if (postModalState.isShadowMode) {
        btn.disabled = !(hasContent && notOverLimit && postModalState.isTargetLocked);
    } else {
        btn.disabled = !(hasContent && notOverLimit);
    }
}

<?php if ($isShadowMode): ?>
// Identity Dropdown
function toggleIdentityDropdown() {
    const menu = document.getElementById('identityDropdownMenu');
    menu.style.display = menu.style.display === 'none' ? 'block' : 'none';
}

function selectIdentity(identity) {
    document.getElementById('currentIdentity').textContent = identity;
    document.querySelectorAll('.identity-option').forEach(opt => opt.classList.remove('active'));
    event.target.closest('.identity-option').classList.add('active');
    toggleIdentityDropdown();
}

function generateNewIdentity() {
    const newIdentity = 'shadow_' + Math.random().toString(36).substr(2, 4);
    selectIdentity(newIdentity);
}

// Help Tooltip
function toggleHelpTooltip() {
    const tooltip = document.getElementById('helpTooltip');
    tooltip.style.display = tooltip.style.display === 'none' ? 'block' : 'none';
}

// Target Platform
function setTargetPlatform(platform) {
    if (postModalState.isTargetLocked) return;

    postModalState.targetPlatform = platform;
    document.getElementById('platformXray').classList.toggle('active', platform === 'xray');
    document.getElementById('platformTwitter').classList.toggle('active', platform === 'twitter');
    document.getElementById('targetUserInput').placeholder = platform === 'xray' ? '@username' : '@twitter_handle';
}

// Target User Input
async function handleTargetInput(value) {
    postModalState.targetUser = value;
    document.getElementById('targetLockBtn').disabled = !value.trim();

    if (postModalState.targetPlatform === 'xray' && value.length > 0) {
        // Show dropdown with matching users
        try {
            const response = await fetch('index.php?action=searchUsers&q=' + encodeURIComponent(value.replace('@', '')));
            const data = await response.json();

            if (data.success && data.users.length > 0) {
                const dropdown = document.getElementById('targetUserDropdown');
                dropdown.innerHTML = data.users.slice(0, 5).map(user => `
                    <button class="target-user-item" onclick="selectTargetUser('${user.username}')">
                        <img src="${user.avatar}" alt="${user.username}" class="target-user-avatar">
                        <div class="target-user-info">
                            <span class="target-user-name">${user.username}</span>
                            <span class="target-user-handle">@${user.username}</span>
                        </div>
                    </button>
                `).join('');
                dropdown.style.display = 'block';
            } else {
                document.getElementById('targetUserDropdown').style.display = 'none';
            }
        } catch (error) {
            document.getElementById('targetUserDropdown').style.display = 'none';
        }
    } else {
        document.getElementById('targetUserDropdown').style.display = 'none';
    }
}

function selectTargetUser(username) {
    document.getElementById('targetUserInput').value = username;
    postModalState.targetUser = username;
    document.getElementById('targetUserDropdown').style.display = 'none';
    document.getElementById('targetLockBtn').disabled = false;
}

// Target Lock
function toggleTargetLock() {
    if (postModalState.isTargetLocked) {
        // Unlock
        postModalState.isTargetLocked = false;
        document.getElementById('targetUserInput').disabled = false;
        document.getElementById('targetLockBtn').innerHTML = '<i data-lucide="lock" style="width: 16px; height: 16px;"></i><span>lock</span>';
        document.getElementById('targetLockBtn').className = 'target-lock-btn';
        document.getElementById('boostSection').style.display = 'none';
        document.querySelectorAll('.platform-btn').forEach(btn => btn.disabled = false);
    } else {
        // Lock
        if (postModalState.targetUser.trim()) {
            postModalState.isTargetLocked = true;
            document.getElementById('targetUserInput').disabled = true;
            document.getElementById('targetLockBtn').innerHTML = '<i data-lucide="check" style="width: 16px; height: 16px;"></i><span>locked</span>';
            document.getElementById('targetLockBtn').className = 'target-lock-btn locked';
            document.getElementById('boostSection').style.display = 'block';
            document.querySelectorAll('.platform-btn').forEach(btn => btn.disabled = true);
        }
    }
    if (typeof lucide !== 'undefined') lucide.createIcons();
    updateSubmitButton();
}

// Boost Functions
function getInterpolatedPosition(sol) {
    if (sol <= boostTiers[0].sol) return boostTiers[0].position;
    if (sol >= boostTiers[boostTiers.length - 1].sol) return boostTiers[boostTiers.length - 1].position;

    for (let i = 0; i < boostTiers.length - 1; i++) {
        const lower = boostTiers[i];
        const upper = boostTiers[i + 1];
        if (sol >= lower.sol && sol <= upper.sol) {
            const solRatio = (sol - lower.sol) / (upper.sol - lower.sol);
            const positionDiff = lower.position - upper.position;
            return Math.round(lower.position - solRatio * positionDiff);
        }
    }
    return boostTiers[0].position;
}

function getSOLForPosition(targetPosition) {
    if (targetPosition >= boostTiers[0].position) return boostTiers[0].sol;
    if (targetPosition <= boostTiers[boostTiers.length - 1].position) return boostTiers[boostTiers.length - 1].sol;

    for (let i = 0; i < boostTiers.length - 1; i++) {
        const lower = boostTiers[i];
        const upper = boostTiers[i + 1];
        if (targetPosition <= lower.position && targetPosition >= upper.position) {
            const positionRatio = (lower.position - targetPosition) / (lower.position - upper.position);
            const solDiff = upper.sol - lower.sol;
            return Math.round((lower.sol + positionRatio * solDiff) * 100) / 100;
        }
    }
    return boostTiers[0].sol;
}

function handleBoostSolChange(value) {
    const numVal = parseFloat(value);
    if (!isNaN(numVal) && numVal > 0) {
        postModalState.boostAmount = numVal;
        const position = getInterpolatedPosition(numVal);
        document.getElementById('boostPositionInput').value = position;
        document.getElementById('boostSlider').value = Math.min(numVal, 100);
        updatePositionStyle(position);
    }
}

function handleBoostPositionChange(value) {
    const numVal = parseInt(value);
    if (!isNaN(numVal) && numVal >= 1) {
        const solNeeded = getSOLForPosition(numVal);
        postModalState.boostAmount = solNeeded;
        document.getElementById('boostSolInput').value = solNeeded;
        document.getElementById('boostSlider').value = Math.min(solNeeded, 100);
        updatePositionStyle(numVal);
    }
}

function handleBoostSliderChange(value) {
    const numVal = parseFloat(value);
    postModalState.boostAmount = numVal;
    document.getElementById('boostSolInput').value = numVal;
    const position = getInterpolatedPosition(numVal);
    document.getElementById('boostPositionInput').value = position;
    updatePositionStyle(position);
}

function updatePositionStyle(position) {
    const box = document.getElementById('boostPositionBox');
    const medal = document.getElementById('positionMedal');

    // Remove all position classes
    box.className = 'boost-position-input';

    if (position === 1) {
        box.classList.add('gold');
        medal.textContent = '🥇';
    } else if (position === 2) {
        box.classList.add('silver');
        medal.textContent = '🥈';
    } else if (position === 3) {
        box.classList.add('bronze');
        medal.textContent = '🥉';
    } else {
        medal.textContent = '';
    }
}
<?php endif; ?>

// Submit Post
async function submitPost() {
    const content = postModalState.content.trim();
    if (!content || content.length > postModalState.MAX_CHARS) return;

    <?php if ($isShadowMode): ?>
    if (!postModalState.isTargetLocked) return;
    <?php endif; ?>

    const btn = document.getElementById('postSubmitBtn');
    btn.disabled = true;
    btn.textContent = 'Posting...';

    try {
        const formData = new FormData();
        formData.append('content', content);

        <?php if ($isShadowMode): ?>
        formData.append('shadow_mode', '1');
        formData.append('target_user', postModalState.targetUser);
        formData.append('target_platform', postModalState.targetPlatform);
        formData.append('boost_amount', postModalState.boostAmount);
        formData.append('identity', document.getElementById('currentIdentity').textContent);
        <?php endif; ?>

        const response = await fetch('index.php?action=create-post', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            closePostModal();
            window.location.reload();
        } else {
            alert(data.error || 'Failed to create post');
            btn.disabled = false;
            btn.textContent = '<?= $isShadowMode ? 'shadow post' : 'post' ?>';
        }
    } catch (error) {
        console.error('Error creating post:', error);
        alert('An error occurred. Please try again.');
        btn.disabled = false;
        btn.textContent = '<?= $isShadowMode ? 'shadow post' : 'post' ?>';
    }
}

// Close modal on backdrop click
document.getElementById('postModal')?.addEventListener('click', function(e) {
    if (e.target === this) closePostModal();
});

// Close modal on escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && document.getElementById('postModal').style.display === 'flex') {
        closePostModal();
    }
});

// Close dropdowns when clicking outside
document.addEventListener('click', function(e) {
    <?php if ($isShadowMode): ?>
    // Close identity dropdown
    if (!e.target.closest('.identity-dropdown')) {
        document.getElementById('identityDropdownMenu').style.display = 'none';
    }
    // Close help tooltip
    if (!e.target.closest('.help-tooltip-wrapper')) {
        document.getElementById('helpTooltip').style.display = 'none';
    }
    // Close target dropdown
    if (!e.target.closest('.target-input-wrapper')) {
        document.getElementById('targetUserDropdown').style.display = 'none';
    }
    <?php endif; ?>
});
</script>
