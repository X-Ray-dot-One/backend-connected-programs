    </div> <!-- End xray-container -->

    <!-- Profile Setup Modal -->
    <?php require_once __DIR__ . '/xray-profile-modal.php'; ?>

    <!-- Post Modal -->
    <?php require_once __DIR__ . '/xray-post-modal.php'; ?>

    <!-- Comment Modal -->
    <?php require_once __DIR__ . '/xray-comment-modal.php'; ?>

    <script>
    // Initialize Lucide icons
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }

    /**
     * Toggle Shadow Mode
     */
    function toggleShadowMode() {
        fetch('index.php?action=toggle-shadow-mode', {
            method: 'POST'
        }).then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.reload();
            }
        });
    }

    /**
     * Current post ID for comments
     */
    let currentCommentPostId = null;

    /**
     * Open Comment Modal
     */
    async function openCommentModal(postId) {
        currentCommentPostId = postId;
        const modal = document.getElementById('comment-modal');
        const originalPostEl = document.getElementById('comment-original-post');
        const commentListEl = document.getElementById('comment-list');

        // Show modal with loading state
        modal.style.display = 'flex';
        originalPostEl.innerHTML = '<div class="comment-loading"><div class="comment-loading-spinner"></div></div>';
        commentListEl.innerHTML = '<div class="comment-loading"><div class="comment-loading-spinner"></div></div>';

        try {
            const response = await fetch(`index.php?action=get-comments&post_id=${postId}`);
            const data = await response.json();

            if (data.success) {
                // Render original post
                renderOriginalPost(data.post);
                // Render comments
                renderComments(data.comments);
            } else {
                commentListEl.innerHTML = '<div class="comment-empty">Failed to load comments</div>';
            }
        } catch (error) {
            console.error('Error loading comments:', error);
            commentListEl.innerHTML = '<div class="comment-empty">Failed to load comments</div>';
        }

        // Re-render icons
        if (typeof lucide !== 'undefined') lucide.createIcons();

        // Focus input
        const input = document.getElementById('comment-input');
        if (input) {
            setTimeout(() => input.focus(), 100);
        }
    }

    /**
     * Close Comment Modal
     */
    function closeCommentModal() {
        const modal = document.getElementById('comment-modal');
        modal.style.display = 'none';
        currentCommentPostId = null;

        // Clear input
        const input = document.getElementById('comment-input');
        if (input) {
            input.value = '';
            document.getElementById('comment-char-count').textContent = '0';
        }
    }

    /**
     * Render Original Post
     */
    function renderOriginalPost(post) {
        const el = document.getElementById('comment-original-post');
        if (!post) {
            el.innerHTML = '<div class="comment-empty">Post not found</div>';
            return;
        }

        const username = post.user_username || 'Anonymous';
        const profilePicture = post.user_profile_picture;

        // Calculate time ago
        const time = new Date(post.created_at).getTime();
        const diff = Math.floor((Date.now() - time) / 1000);
        let timeAgo;
        if (diff < 60) timeAgo = diff + 's';
        else if (diff < 3600) timeAgo = Math.floor(diff / 60) + 'm';
        else if (diff < 86400) timeAgo = Math.floor(diff / 3600) + 'h';
        else timeAgo = Math.floor(diff / 86400) + 'd';

        el.innerHTML = `
            <div class="post-content-wrapper">
                ${profilePicture
                    ? `<img src="${profilePicture}" alt="Avatar" class="post-avatar">`
                    : `<div class="post-avatar post-avatar-letter">${username.charAt(0).toUpperCase()}</div>`
                }
                <div class="post-body">
                    <div class="post-header">
                        <span class="post-username">${username}</span>
                        <span class="post-handle">@${username}</span>
                        <span class="post-time">${timeAgo}</span>
                    </div>
                    <p class="post-text">${escapeHtml(post.content)}</p>
                </div>
            </div>
        `;
    }

    /**
     * Render Comments
     */
    function renderComments(comments) {
        const el = document.getElementById('comment-list');
        const currentUserId = <?= json_encode($_SESSION['user_id'] ?? null) ?>;

        if (!comments || comments.length === 0) {
            el.innerHTML = '<div class="comment-empty">No comments yet. Be the first to reply!</div>';
            return;
        }

        el.innerHTML = comments.map(comment => {
            const isOwner = currentUserId && comment.user_id == currentUserId;
            const hasLiked = comment.has_liked || false;
            const likeCount = comment.like_count || 0;
            return `
                <div class="comment-item" data-comment-id="${comment.id}">
                    ${comment.profile_picture
                        ? `<img src="${comment.profile_picture}" alt="Avatar" class="comment-avatar">`
                        : `<div class="comment-avatar comment-avatar-letter">${(comment.username || 'A').charAt(0).toUpperCase()}</div>`
                    }
                    <div class="comment-body">
                        <div class="comment-header">
                            <span class="comment-username">${comment.username || 'Anonymous'}</span>
                            <span class="comment-time">${comment.time_ago}</span>
                        </div>
                        <p class="comment-text">${escapeHtml(comment.content)}</p>
                        <div class="comment-actions">
                            <button class="comment-action-btn like ${hasLiked ? 'liked' : ''}" onclick="toggleCommentLike(${comment.id}, this)">
                                <i data-lucide="heart" style="width: 12px; height: 12px;"></i>
                                <span class="comment-like-count">${likeCount > 0 ? likeCount : ''}</span>
                            </button>
                            ${isOwner ? `
                                <button class="comment-action-btn delete" onclick="deleteComment(${comment.id})">
                                    <i data-lucide="trash-2" style="width: 12px; height: 12px;"></i>
                                    Delete
                                </button>
                            ` : ''}
                        </div>
                    </div>
                </div>
            `;
        }).join('');

        if (typeof lucide !== 'undefined') lucide.createIcons();
    }

    /**
     * Submit Comment
     */
    async function submitComment() {
        const input = document.getElementById('comment-input');
        const submitBtn = document.getElementById('comment-submit-btn');
        const content = input.value.trim();

        if (!content || !currentCommentPostId) return;

        submitBtn.disabled = true;
        submitBtn.textContent = 'Posting...';

        try {
            const response = await fetch('index.php?action=add-comment', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    post_id: currentCommentPostId,
                    content: content
                })
            });

            const data = await response.json();

            if (data.success) {
                // Clear input
                input.value = '';
                document.getElementById('comment-char-count').textContent = '0';

                // Reload comments
                const commentsResponse = await fetch(`index.php?action=get-comments&post_id=${currentCommentPostId}`);
                const commentsData = await commentsResponse.json();
                if (commentsData.success) {
                    renderComments(commentsData.comments);
                }

                // Update comment count in feed
                updateCommentCount(currentCommentPostId, data.comment_count);
            } else {
                alert(data.error || 'Failed to post comment');
            }
        } catch (error) {
            console.error('Error posting comment:', error);
            alert('Failed to post comment');
        }

        submitBtn.disabled = false;
        submitBtn.textContent = 'Reply';
    }

    /**
     * Delete Comment
     */
    async function deleteComment(commentId) {
        if (!confirm('Delete this comment?')) return;

        try {
            const response = await fetch('index.php?action=delete-comment', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    comment_id: commentId,
                    post_id: currentCommentPostId
                })
            });

            const data = await response.json();

            if (data.success) {
                // Remove comment from DOM
                const commentEl = document.querySelector(`[data-comment-id="${commentId}"]`);
                if (commentEl) commentEl.remove();

                // Check if no comments left
                const commentList = document.getElementById('comment-list');
                if (!commentList.querySelector('.comment-item')) {
                    commentList.innerHTML = '<div class="comment-empty">No comments yet. Be the first to reply!</div>';
                }

                // Update comment count in feed
                updateCommentCount(currentCommentPostId, data.comment_count);
            } else {
                alert(data.error || 'Failed to delete comment');
            }
        } catch (error) {
            console.error('Error deleting comment:', error);
            alert('Failed to delete comment');
        }
    }

    /**
     * Update comment count in feed
     */
    function updateCommentCount(postId, count) {
        const btn = document.querySelector(`[data-post-id="${postId}"].comment-btn`);
        if (btn) {
            const countSpan = btn.querySelector('.count');
            if (countSpan) countSpan.textContent = count;
        }
    }

    /**
     * Toggle Like on a comment
     */
    async function toggleCommentLike(commentId, button) {
        <?php if (!AuthController::isLoggedIn()): ?>
        alert('Please connect your wallet to like comments');
        return;
        <?php endif; ?>

        try {
            const response = await fetch('index.php?action=toggle-comment-like', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ comment_id: commentId })
            });

            const data = await response.json();

            if (data.success) {
                // Update button state
                if (data.action === 'liked') {
                    button.classList.add('liked');
                } else {
                    button.classList.remove('liked');
                }
                // Update count
                const countSpan = button.querySelector('.comment-like-count');
                if (countSpan) {
                    countSpan.textContent = data.likes > 0 ? data.likes : '';
                }
                // Re-render icons
                if (typeof lucide !== 'undefined') lucide.createIcons();
            } else {
                if (data.error === 'Not authenticated') {
                    alert('Please connect your wallet to like comments');
                }
            }
        } catch (error) {
            console.error('Error toggling comment like:', error);
        }
    }

    /**
     * Escape HTML
     */
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Comment input character counter
    document.getElementById('comment-input')?.addEventListener('input', function() {
        document.getElementById('comment-char-count').textContent = this.value.length;
    });

    // Close modal on overlay click
    document.getElementById('comment-modal')?.addEventListener('click', function(e) {
        if (e.target === this) closeCommentModal();
    });

    // Close modal on Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && document.getElementById('comment-modal').style.display === 'flex') {
            closeCommentModal();
        }
    });

    /**
     * Toggle Like on a post
     */
    async function toggleLike(postId, button) {
        <?php if (!AuthController::isLoggedIn()): ?>
        alert('Please connect your wallet to like posts');
        return;
        <?php endif; ?>

        try {
            const response = await fetch('index.php?action=toggle-like', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ post_id: postId })
            });

            const data = await response.json();

            if (data.success) {
                // Update button state
                const countSpan = button.querySelector('.count');
                if (data.action === 'liked') {
                    button.classList.add('liked');
                } else {
                    button.classList.remove('liked');
                }
                // Update count
                countSpan.textContent = data.likes;

                // Re-render lucide icons
                lucide.createIcons();
            } else {
                console.error('Like error:', data.error);
                if (data.error === 'Not authenticated') {
                    alert('Please connect your wallet to like posts');
                }
            }
        } catch (error) {
            console.error('Error toggling like:', error);
        }
    }

    /**
     * Connexion Phantom Wallet
     */
    async function connectPhantom() {
        const btn = document.getElementById('connectPhantomBtn');

        // Check if Phantom is installed
        if (!phantomWallet || !phantomWallet.isInstalled()) {
            alert('Phantom Wallet n\'est pas installé.\n\nVeuillez installer Phantom depuis phantom.app');
            window.open('https://phantom.app/', '_blank');
            return;
        }

        console.log('🚀 Début de la connexion Phantom...');

        btn.disabled = true;
        btn.innerHTML = '<span style="display: inline-block; width: 16px; height: 16px; border: 2px solid currentColor; border-top-color: transparent; border-radius: 50%; animation: spin 0.6s linear infinite;"></span><span>Connecting...</span>';

        try {
            console.log('📱 Appel de phantomWallet.connect()...');
            const walletAddress = await phantomWallet.connect();
            console.log('✅ Wallet address reçue:', walletAddress);

            if (walletAddress) {
                console.log('🔐 Authentification avec le backend...');
                // Authentifier avec le backend
                const authResult = await phantomWallet.authenticateWithBackend(walletAddress, 'solana');
                console.log('✅ Résultat authentification:', authResult);

                if (authResult.success) {
                    console.log('✅ Connexion réussie, redirection...');
                    // Rediriger vers la page d'accueil
                    window.location.href = 'index.php';
                } else {
                    console.error('❌ Échec authentification:', authResult.error);
                    alert('Erreur d\'authentification: ' + (authResult.error || 'Erreur inconnue'));
                    btn.disabled = false;
                    btn.innerHTML = '<i data-lucide="wallet" style="width: 16px; height: 16px;"></i><span>connect_wallet</span>';
                    lucide.createIcons();
                }
            } else {
                console.warn('⚠️ Pas d\'adresse wallet reçue');
                btn.disabled = false;
                btn.innerHTML = '<i data-lucide="wallet" style="width: 16px; height: 16px;"></i><span>connect_wallet</span>';
                lucide.createIcons();
            }
        } catch (error) {
            console.error('❌ Erreur de connexion:', error);

            // User-friendly error message
            let errorMessage = 'Erreur de connexion';
            if (error.message) {
                errorMessage = error.message;
            }

            alert(errorMessage);
            btn.disabled = false;
            btn.innerHTML = '<i data-lucide="wallet" style="width: 16px; height: 16px;"></i><span>connect_wallet</span>';
            lucide.createIcons();
        }
    }

    /**
     * Check if user has profile and show modal if not
     */
    async function checkUserProfile() {
        <?php if (AuthController::isLoggedIn()): ?>
        try {
            const response = await fetch('index.php?action=has-profile');
            const data = await response.json();

            if (data.success && !data.has_profile) {
                // User doesn't have a profile, show modal
                setTimeout(() => {
                    showProfileModal();
                }, 500);
            }
        } catch (error) {
            console.error('Error checking profile:', error);
        }
        <?php endif; ?>
    }

    /**
     * Load wallet balance on page load
     */
    window.addEventListener('load', () => {
        console.log('🔄 Page chargée, vérification de Phantom...');

        // Debug info
        if (typeof phantomWallet !== 'undefined') {
            console.log('✅ phantomWallet global existe');
            console.log('📦 phantomWallet.isInstalled():', phantomWallet.isInstalled());
            console.log('📦 phantomWallet.provider:', phantomWallet.provider);
        } else {
            console.error('❌ phantomWallet global n\'existe pas !');
        }

        if ('solana' in window) {
            console.log('✅ window.solana existe');
            console.log('📦 window.solana.isPhantom:', window.solana.isPhantom);
            console.log('📦 window.solana.isConnected:', window.solana.isConnected);
        } else {
            console.warn('⚠️ window.solana n\'existe pas - Phantom non installé ?');
        }

        // Check user profile
        checkUserProfile();

        const balanceElement = document.getElementById('wallet-balance-sidebar');
        if (balanceElement && phantomWallet && phantomWallet.isInstalled()) {
            console.log('💰 Récupération du solde...');
            // If Phantom is not connected, try silent reconnection
            if (!window.solana.isConnected) {
                console.log('🔄 Tentative de reconnexion silencieuse...');
                window.solana.connect({ onlyIfTrusted: true })
                    .then(response => {
                        console.log('✅ Reconnexion réussie');
                        phantomWallet.publicKey = response.publicKey;
                        return phantomWallet.getBalance();
                    })
                    .then(balance => {
                        sessionStorage.setItem('wallet_balance', balance);
                        balanceElement.textContent = balance.toFixed(3) + ' SOL';
                        console.log('✅ Solde affiché:', balance);
                    })
                    .catch(err => {
                        console.warn('⚠️ Erreur reconnexion/solde:', err);
                        // Keep cached balance on error, don't overwrite
                    });
            } else {
                // Phantom already connected
                console.log('✅ Phantom déjà connecté');
                setTimeout(() => {
                    if (phantomWallet.publicKey) {
                        phantomWallet.getBalance().then(balance => {
                            sessionStorage.setItem('wallet_balance', balance);
                            balanceElement.textContent = balance.toFixed(3) + ' SOL';
                            console.log('✅ Solde affiché:', balance);
                        }).catch(err => {
                            console.error('❌ Erreur récupération solde:', err);
                            // Keep cached balance on error, don't overwrite
                        });
                    }
                }, 500);
            }
        } else if (balanceElement) {
            console.warn('⚠️ Phantom non disponible pour récupération du solde');
        }
    });
    </script>

    <style>
    @keyframes spin {
        to { transform: rotate(360deg); }
    }
    </style>
</body>
</html>
