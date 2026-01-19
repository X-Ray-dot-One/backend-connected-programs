<!-- Comment Modal -->
<div class="comment-modal-overlay" id="comment-modal" style="display: none;">
    <div class="comment-modal">
        <!-- Modal Header -->
        <div class="comment-modal-header">
            <button class="comment-modal-close" onclick="closeCommentModal()">
                <i data-lucide="x" style="width: 20px; height: 20px;"></i>
            </button>
            <h2>Comments</h2>
        </div>

        <!-- Original Post -->
        <div class="comment-original-post" id="comment-original-post">
            <!-- Will be populated by JavaScript -->
        </div>

        <!-- Comments List -->
        <div class="comment-list" id="comment-list">
            <!-- Will be populated by JavaScript -->
        </div>

        <!-- Add Comment Form -->
        <?php if (AuthController::isLoggedIn()): ?>
        <div class="comment-form">
            <div class="comment-form-avatar">
                <?php
                $currentUser = AuthController::getCurrentUser();
                if ($currentUser && !empty($currentUser['profile_picture'])): ?>
                    <img src="<?= htmlspecialchars($currentUser['profile_picture']) ?>" alt="Avatar">
                <?php elseif ($currentUser && !empty($currentUser['username'])): ?>
                    <span><?= strtoupper(substr($currentUser['username'], 0, 1)) ?></span>
                <?php else: ?>
                    <span>?</span>
                <?php endif; ?>
            </div>
            <div class="comment-form-input-wrapper">
                <textarea
                    id="comment-input"
                    placeholder="Post your reply..."
                    maxlength="280"
                    rows="2"></textarea>
                <div class="comment-form-actions">
                    <span class="comment-char-count"><span id="comment-char-count">0</span>/280</span>
                    <button class="comment-submit-btn" id="comment-submit-btn" onclick="submitComment()">
                        Reply
                    </button>
                </div>
            </div>
        </div>
        <?php else: ?>
        <div class="comment-form-login">
            <p>Connect your wallet to reply</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<style>
/* Comment Modal Styles */
.comment-modal-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.5);
    z-index: 1000;
    display: flex;
    align-items: flex-start;
    justify-content: center;
    padding: 2rem 1rem;
    overflow-y: auto;
}

.comment-modal {
    background: var(--background);
    border: 1px solid var(--border);
    border-radius: 1rem;
    width: 100%;
    max-width: 560px;
    max-height: calc(100vh - 4rem);
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

.comment-modal-header {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 0.75rem 1rem;
    border-bottom: 1px solid var(--border);
    flex-shrink: 0;
}

.comment-modal-header h2 {
    font-size: 1.125rem;
    font-weight: bold;
    margin: 0;
}

.comment-modal-close {
    padding: 0.25rem;
    border-radius: 50%;
    background: none;
    border: none;
    cursor: pointer;
    color: var(--foreground);
    transition: all 0.2s;
}

.comment-modal-close:hover {
    background: var(--muted);
}

/* Original Post */
.comment-original-post {
    padding: 1rem;
    border-bottom: 1px solid var(--border);
    flex-shrink: 0;
}

.comment-original-post .post-content-wrapper {
    display: flex;
    gap: 0.75rem;
}

.comment-original-post .post-avatar {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    flex-shrink: 0;
    object-fit: cover;
}

.comment-original-post .post-avatar-letter {
    background: linear-gradient(135deg, var(--primary), var(--accent));
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: bold;
    font-size: 1rem;
}

.comment-original-post .post-body {
    flex: 1;
    min-width: 0;
}

.comment-original-post .post-header {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.comment-original-post .post-username {
    font-weight: 600;
    color: var(--foreground);
}

.comment-original-post .post-handle {
    color: var(--muted-foreground);
    font-size: 0.875rem;
}

.comment-original-post .post-time {
    color: var(--muted-foreground);
    font-size: 0.875rem;
}

.comment-original-post .post-text {
    margin-top: 0.25rem;
    color: var(--foreground);
    word-wrap: break-word;
}

/* Comments List */
.comment-list {
    flex: 1;
    overflow-y: auto;
    min-height: 100px;
    max-height: 300px;
}

.comment-item {
    padding: 0.75rem 1rem;
    border-bottom: 1px solid var(--border);
    display: flex;
    gap: 0.75rem;
}

.comment-item:last-child {
    border-bottom: none;
}

.comment-avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    flex-shrink: 0;
    object-fit: cover;
}

.comment-avatar-letter {
    background: linear-gradient(135deg, var(--primary), var(--accent));
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: bold;
    font-size: 0.75rem;
}

.comment-body {
    flex: 1;
    min-width: 0;
}

.comment-header {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.comment-username {
    font-weight: 600;
    font-size: 0.875rem;
    color: var(--foreground);
}

.comment-time {
    font-size: 0.75rem;
    color: var(--muted-foreground);
}

.comment-text {
    margin-top: 0.25rem;
    font-size: 0.875rem;
    color: var(--foreground);
    word-wrap: break-word;
}

.comment-actions {
    margin-top: 0.5rem;
    display: flex;
    gap: 1rem;
}

.comment-action-btn {
    background: none;
    border: none;
    color: var(--muted-foreground);
    font-size: 0.75rem;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 0.25rem;
    transition: color 0.2s;
    font-family: inherit;
}

.comment-action-btn:hover {
    color: var(--primary);
}

.comment-action-btn.delete:hover {
    color: #ef4444;
}

.comment-action-btn.like:hover {
    color: #ef4444;
}

.comment-action-btn.like.liked {
    color: #ef4444;
}

.comment-action-btn.like.liked i,
.comment-action-btn.like.liked svg {
    fill: #ef4444;
}

.comment-like-count {
    min-width: 0.5rem;
}

.comment-empty {
    padding: 2rem;
    text-align: center;
    color: var(--muted-foreground);
}

/* Add Comment Form */
.comment-form {
    padding: 1rem;
    border-top: 1px solid var(--border);
    display: flex;
    gap: 0.75rem;
    flex-shrink: 0;
}

.comment-form-avatar {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--primary), var(--accent));
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: bold;
    font-size: 0.75rem;
    flex-shrink: 0;
    overflow: hidden;
}

.comment-form-avatar img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.comment-form-input-wrapper {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.comment-form-input-wrapper textarea {
    width: 100%;
    padding: 0.5rem;
    border: 1px solid var(--border);
    border-radius: 0.5rem;
    background: var(--background);
    color: var(--foreground);
    font-family: inherit;
    font-size: 0.875rem;
    resize: none;
    transition: border-color 0.2s;
}

.comment-form-input-wrapper textarea:focus {
    outline: none;
    border-color: var(--primary);
}

.comment-form-input-wrapper textarea::placeholder {
    color: var(--muted-foreground);
}

.comment-form-actions {
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.comment-char-count {
    font-size: 0.75rem;
    color: var(--muted-foreground);
}

.comment-submit-btn {
    padding: 0.375rem 1rem;
    border-radius: 9999px;
    background: var(--primary);
    color: var(--primary-foreground);
    border: none;
    font-weight: 500;
    font-size: 0.875rem;
    cursor: pointer;
    transition: all 0.2s;
    font-family: inherit;
}

.comment-submit-btn:hover {
    opacity: 0.9;
}

.comment-submit-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.comment-form-login {
    padding: 1rem;
    border-top: 1px solid var(--border);
    text-align: center;
    color: var(--muted-foreground);
    font-size: 0.875rem;
}

/* Loading state */
.comment-loading {
    padding: 2rem;
    text-align: center;
    color: var(--muted-foreground);
}

.comment-loading-spinner {
    display: inline-block;
    width: 20px;
    height: 20px;
    border: 2px solid var(--border);
    border-top-color: var(--primary);
    border-radius: 50%;
    animation: spin 0.6s linear infinite;
}
</style>
