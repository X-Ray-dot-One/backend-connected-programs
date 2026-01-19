<?php
session_start();

// Autoloader simple
spl_autoload_register(function ($class) {
    $paths = [
        __DIR__ . '/app/controllers/' . $class . '.php',
        __DIR__ . '/app/models/' . $class . '.php'
    ];

    foreach ($paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            return;
        }
    }
});

// Routeur simple
$action = $_GET['action'] ?? 'index';

// Routes d'authentification
if (in_array($action, ['signup', 'signin', 'register', 'login', 'logout', 'show-secret-key', 'wallet-auth', 'toggle-shadow-mode'])) {
    $authController = new AuthController();

    switch ($action) {
        case 'signup':
            $authController->signup();
            break;

        case 'signin':
            $authController->signin();
            break;

        case 'register':
            $authController->register();
            break;

        case 'login':
            $authController->login();
            break;

        case 'logout':
            $authController->logout();
            break;

        case 'show-secret-key':
            $authController->showSecretKey();
            break;

        case 'wallet-auth':
            $authController->walletAuth();
            break;

        case 'toggle-shadow-mode':
            $authController->toggleShadowMode();
            break;
    }
}
// Routes de profil
elseif (in_array($action, ['has-profile', 'update-profile', 'get-profile'])) {
    $profileController = new ProfileController();

    switch ($action) {
        case 'has-profile':
            $profileController->hasProfile();
            break;

        case 'update-profile':
            $profileController->updateProfile();
            break;

        case 'get-profile':
            $profileController->getProfile();
            break;
    }
}
// Routes de follow
elseif (in_array($action, ['follow', 'unfollow', 'check-follow', 'get-followers', 'get-following'])) {
    $followController = new FollowController();

    switch ($action) {
        case 'follow':
            $followController->follow();
            break;

        case 'unfollow':
            $followController->unfollow();
            break;

        case 'check-follow':
            $followController->checkFollow();
            break;

        case 'get-followers':
            $followController->getFollowers();
            break;

        case 'get-following':
            $followController->getFollowing();
            break;
    }
}
// Route pour récupérer le solde via proxy PHP (évite CORS)
elseif ($action === 'get-balance') {
    $walletController = new WalletController();
    $walletController->getBalance();
}
// ============================================
// API Routes pour Shadow Wallets
// ============================================
// GET /api/wallets/count/:userId - Récupère le nombre de wallets générés
elseif ($action === 'api-wallets-count') {
    $shadowWalletController = new ShadowWalletController();
    $shadowWalletController->getCount();
}
// POST /api/wallets/increment - Incrémente le compteur de wallets
elseif ($action === 'api-wallets-increment') {
    $shadowWalletController = new ShadowWalletController();
    $shadowWalletController->increment();
}
// POST /api/wallets - Enregistre un nouveau shadow wallet
elseif ($action === 'api-wallets-create') {
    $shadowWalletController = new ShadowWalletController();
    $shadowWalletController->create();
}
// GET /api/wallets/name-exists/:name - Vérifie si un nom existe
elseif ($action === 'api-wallets-name-exists') {
    $shadowWalletController = new ShadowWalletController();
    $shadowWalletController->nameExists();
}
// GET /api/wallets/name/:shadowPubkey - Récupère le nom d'un shadow wallet
elseif ($action === 'api-wallets-name') {
    $shadowWalletController = new ShadowWalletController();
    $shadowWalletController->getName();
}
// GET /api/wallets/is-premium/:userId - Vérifie si un user est premium
elseif ($action === 'api-wallets-is-premium') {
    $shadowWalletController = new ShadowWalletController();
    $shadowWalletController->isPremium();
}
// POST /api/wallets/set-premium - Définit le statut premium d'un user
elseif ($action === 'api-wallets-set-premium') {
    $shadowWalletController = new ShadowWalletController();
    $shadowWalletController->setPremium();
}
// GET /api/wallets/search?q=query - Recherche des shadow wallets par nom
elseif ($action === 'api-wallets-search') {
    $shadowWalletController = new ShadowWalletController();
    $shadowWalletController->search();
}
// GET /api/wallets/by-name?name=xxx - Récupère un shadow wallet par son nom
elseif ($action === 'api-wallets-by-name') {
    $shadowWalletController = new ShadowWalletController();
    $shadowWalletController->getByName();
}
// ============================================
// API JSON Routes pour React Frontend
// ============================================

// GET current user info
elseif ($action === 'me') {
    header('Content-Type: application/json');

    if (!AuthController::isLoggedIn()) {
        echo json_encode(['success' => false, 'error' => 'Not authenticated']);
        exit;
    }

    $user = AuthController::getCurrentUser();
    $shadowMode = $_SESSION['shadow_mode'] ?? false;

    echo json_encode([
        'success' => true,
        'user' => $user,
        'shadow_mode' => $shadowMode
    ]);
    exit;
}

// GET single post by ID
elseif ($action === 'get-post') {
    header('Content-Type: application/json');

    $postId = intval($_GET['id'] ?? 0);

    if (!$postId) {
        echo json_encode(['success' => false, 'error' => 'Post ID required']);
        exit;
    }

    require_once __DIR__ . '/app/models/Post.php';
    require_once __DIR__ . '/app/models/User.php';
    $postModel = new Post();
    $userModel = new User();

    $post = $postModel->getPostById($postId);

    if (!$post) {
        echo json_encode(['success' => false, 'error' => 'Post not found']);
        exit;
    }

    $currentUserId = AuthController::getCurrentUserId();

    // Get user info
    $postUser = $userModel->findById($post['user_id']);

    // Calculate time ago
    $time = strtotime($post['created_at']);
    $diff = time() - $time;
    if ($diff < 60) {
        $timeAgo = $diff . 's';
    } elseif ($diff < 3600) {
        $timeAgo = floor($diff / 60) . 'm';
    } elseif ($diff < 86400) {
        $timeAgo = floor($diff / 3600) . 'h';
    } else {
        $timeAgo = floor($diff / 86400) . 'd';
    }

    echo json_encode([
        'success' => true,
        'post' => [
            'id' => $post['id'],
            'content' => $post['content'],
            'user_id' => $post['user_id'],
            'username' => $postUser['username'] ?? $post['twitter_username'] ?? 'Anonymous',
            'profile_picture' => $postUser['profile_picture'] ?? $post['twitter_profile_image'],
            'wallet_address' => $postUser['wallet_address'] ?? null,
            'created_at' => $post['created_at'],
            'time_ago' => $timeAgo,
            'like_count' => $postModel->getLikeCount($post['id']),
            'comment_count' => $postModel->getCommentCount($post['id']),
            'has_liked' => $currentUserId ? $postModel->hasUserLiked($post['id'], $currentUserId) : false
        ]
    ]);
    exit;
}

// GET posts (JSON API for React)
elseif ($action === 'get-posts') {
    header('Content-Type: application/json');

    require_once __DIR__ . '/app/models/Post.php';
    $postModel = new Post();

    $page = intval($_GET['page'] ?? 1);
    $limit = intval($_GET['limit'] ?? 50);
    $userId = isset($_GET['user_id']) ? intval($_GET['user_id']) : null;
    $feed = $_GET['feed'] ?? 'all';

    $currentUserId = AuthController::getCurrentUserId();

    // Fetch posts based on feed type
    if ($userId) {
        $posts = $postModel->getPostsByUserId($userId, $limit);
    } elseif ($feed === 'following' && $currentUserId) {
        $posts = $postModel->getFollowingPosts($currentUserId, $limit);
    } else {
        $posts = $postModel->getAllPosts($limit);
    }

    // Format posts for frontend
    $formattedPosts = array_map(function($post) use ($postModel, $currentUserId) {
        $time = strtotime($post['created_at']);
        $diff = time() - $time;
        if ($diff < 60) {
            $timeAgo = $diff . 's';
        } elseif ($diff < 3600) {
            $timeAgo = floor($diff / 60) . 'm';
        } elseif ($diff < 86400) {
            $timeAgo = floor($diff / 3600) . 'h';
        } else {
            $timeAgo = floor($diff / 86400) . 'd';
        }

        return [
            'id' => $post['id'],
            'content' => $post['content'],
            'user_id' => $post['user_id'],
            'username' => $post['user_username'] ?? $post['twitter_username'] ?? 'Anonymous',
            'profile_picture' => $post['user_profile_picture'] ?? $post['twitter_profile_image'],
            'wallet_address' => $post['user_wallet'] ?? null,
            'created_at' => $post['created_at'],
            'time_ago' => $timeAgo,
            'like_count' => $postModel->getLikeCount($post['id']),
            'comment_count' => $postModel->getCommentCount($post['id']),
            'has_liked' => $currentUserId ? $postModel->hasUserLiked($post['id'], $currentUserId) : false
        ];
    }, $posts);

    echo json_encode([
        'success' => true,
        'posts' => $formattedPosts
    ]);
    exit;
}

// Get user profile by username (public endpoint)
elseif ($action === 'user-profile') {
    header('Content-Type: application/json');

    $username = $_GET['username'] ?? '';

    if (empty($username)) {
        echo json_encode(['success' => false, 'error' => 'Username required']);
        exit;
    }

    require_once __DIR__ . '/app/models/User.php';
    require_once __DIR__ . '/app/models/Post.php';

    $userModel = new User();

    $user = $userModel->findByUsername($username);

    if (!$user) {
        echo json_encode(['success' => false, 'error' => 'User not found']);
        exit;
    }

    $currentUserId = AuthController::getCurrentUserId();

    // Get stats
    $followersCount = $userModel->getFollowersCount($user['id']);
    $followingCount = $userModel->getFollowingCount($user['id']);
    $postsCount = $userModel->getUserPostCount($user['id']);

    // Check if current user follows this user
    $isFollowing = $currentUserId ? $userModel->isFollowing($currentUserId, $user['id']) : false;
    $isOwnProfile = $currentUserId && $currentUserId == $user['id'];

    echo json_encode([
        'success' => true,
        'user' => [
            'id' => $user['id'],
            'username' => $user['username'],
            'wallet_address' => $user['wallet_address'],
            'profile_picture' => $user['profile_picture'],
            'banner_picture' => $user['banner_picture'],
            'bio' => $user['bio'],
            'location' => $user['location'],
            'website' => $user['website'],
            'created_at' => $user['created_at']
        ],
        'stats' => [
            'followers' => $followersCount,
            'following' => $followingCount,
            'posts' => $postsCount
        ],
        'is_following' => $isFollowing,
        'is_own_profile' => $isOwnProfile
    ]);
    exit;
}

// Search users (renamed for consistency)
elseif ($action === 'search-users') {
    header('Content-Type: application/json');
    $query = $_GET['q'] ?? '';

    if (empty($query)) {
        echo json_encode(['success' => false, 'error' => 'Query required', 'users' => []]);
        exit;
    }

    require_once __DIR__ . '/app/models/User.php';
    $userModel = new User();
    $users = $userModel->searchUsers($query);

    $formattedUsers = array_map(function($user) {
        return [
            'id' => $user['id'],
            'username' => $user['username'],
            'bio' => $user['bio'] ?? '',
            'profile_picture' => $user['profile_picture'] ?? 'https://api.dicebear.com/7.x/avataaars/svg?seed=' . $user['username']
        ];
    }, $users);

    echo json_encode(['success' => true, 'users' => $formattedUsers]);
    exit;
}

// Get suggested users (users with most followers, excluding current user)
elseif ($action === 'suggested-users') {
    header('Content-Type: application/json');

    require_once __DIR__ . '/app/models/User.php';
    $userModel = new User();

    $currentUserId = AuthController::getCurrentUserId();
    $limit = intval($_GET['limit'] ?? 10);

    $users = $userModel->getSuggestedUsers($currentUserId, $limit);

    $formattedUsers = array_map(function($user) {
        return [
            'id' => $user['id'],
            'username' => $user['username'],
            'bio' => $user['bio'] ?? '',
            'profile_picture' => $user['profile_picture'] ?? 'https://api.dicebear.com/7.x/avataaars/svg?seed=' . $user['username']
        ];
    }, $users);

    echo json_encode(['success' => true, 'users' => $formattedUsers]);
    exit;
}

// Get X (Twitter) profile via proxy
elseif ($action === 'x-profile') {
    header('Content-Type: application/json');

    $username = $_GET['username'] ?? '';

    if (empty($username)) {
        echo json_encode(['success' => false, 'error' => 'Username required']);
        exit;
    }

    // Sanitize username (alphanumeric and underscore only)
    $username = preg_replace('/[^a-zA-Z0-9_]/', '', $username);

    $url = 'https://twittermedia.b-cdn.net/profile-pic/?username=' . urlencode($username);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Origin: https://snaplytics.io',
        'Referer: https://snaplytics.io/',
        'User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        echo json_encode(['success' => false, 'error' => 'Failed to fetch profile: ' . $error]);
        exit;
    }

    if ($httpCode !== 200) {
        echo json_encode(['success' => false, 'error' => 'Profile not found', 'http_code' => $httpCode]);
        exit;
    }

    // Return the JSON response as-is
    $data = json_decode($response, true);
    if ($data) {
        echo json_encode(array_merge(['success' => true], $data));
    } else {
        echo $response;
    }
    exit;
}

// Route pour rechercher des utilisateurs (old endpoint, kept for compatibility)
elseif ($action === 'searchUsers') {
    header('Content-Type: application/json');
    $query = $_GET['q'] ?? '';

    if (empty($query)) {
        echo json_encode(['success' => false, 'error' => 'Query required', 'users' => []]);
        exit;
    }

    require_once __DIR__ . '/app/models/User.php';
    $userModel = new User();
    $users = $userModel->searchUsers($query);

    // Format users for frontend
    $formattedUsers = array_map(function($user) {
        return [
            'id' => $user['id'],
            'username' => $user['username'],
            'bio' => $user['bio'] ?? '',
            'avatar' => $user['profile_picture'] ?? 'https://api.dicebear.com/7.x/avataaars/svg?seed=' . $user['username']
        ];
    }, $users);

    echo json_encode(['success' => true, 'users' => $formattedUsers]);
    exit;
}
// Route pour toggle like sur un post
elseif ($action === 'toggle-like') {
    header('Content-Type: application/json');

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        exit;
    }

    if (!AuthController::isLoggedIn()) {
        echo json_encode(['success' => false, 'error' => 'Not authenticated']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $postId = intval($input['post_id'] ?? 0);

    if (!$postId) {
        echo json_encode(['success' => false, 'error' => 'Post ID required']);
        exit;
    }

    require_once __DIR__ . '/app/models/Post.php';
    $postModel = new Post();

    $userId = AuthController::getCurrentUserId();
    $result = $postModel->toggleLike($postId, $userId);
    $likeCount = $postModel->getLikeCount($postId);

    echo json_encode([
        'success' => true,
        'action' => $result['action'],
        'like_count' => $likeCount
    ]);
    exit;
}
// Routes pour les commentaires
elseif ($action === 'add-comment') {
    header('Content-Type: application/json');

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        exit;
    }

    if (!AuthController::isLoggedIn()) {
        echo json_encode(['success' => false, 'error' => 'Not authenticated']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $postId = intval($input['post_id'] ?? 0);
    $content = trim($input['content'] ?? '');

    if (!$postId) {
        echo json_encode(['success' => false, 'error' => 'Post ID required']);
        exit;
    }

    if (empty($content)) {
        echo json_encode(['success' => false, 'error' => 'Comment content required']);
        exit;
    }

    if (strlen($content) > 280) {
        echo json_encode(['success' => false, 'error' => 'Comment exceeds 280 characters']);
        exit;
    }

    require_once __DIR__ . '/app/models/Post.php';
    $postModel = new Post();

    $userId = AuthController::getCurrentUserId();
    $commentId = $postModel->addComment($postId, $userId, $content);

    if ($commentId) {
        $currentUser = AuthController::getCurrentUser();
        $commentCount = $postModel->getCommentCount($postId);

        echo json_encode([
            'success' => true,
            'comment' => [
                'id' => $commentId,
                'content' => $content,
                'username' => $currentUser['username'] ?? 'Anonymous',
                'profile_picture' => $currentUser['profile_picture'] ?? null,
                'created_at' => date('Y-m-d H:i:s')
            ],
            'comment_count' => $commentCount
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to add comment']);
    }
    exit;
}
elseif ($action === 'get-comments') {
    header('Content-Type: application/json');

    $postId = intval($_GET['post_id'] ?? 0);

    if (!$postId) {
        echo json_encode(['success' => false, 'error' => 'Post ID required']);
        exit;
    }

    require_once __DIR__ . '/app/models/Post.php';
    $postModel = new Post();

    $comments = $postModel->getComments($postId);
    $post = $postModel->getPostWithUserById($postId);
    $currentUserId = AuthController::getCurrentUserId();

    // Format comments with time ago and like info
    $formattedComments = array_map(function($comment) use ($postModel, $currentUserId) {
        $time = strtotime($comment['created_at']);
        $diff = time() - $time;
        if ($diff < 60) {
            $timeAgo = $diff . 's';
        } elseif ($diff < 3600) {
            $timeAgo = floor($diff / 60) . 'm';
        } elseif ($diff < 86400) {
            $timeAgo = floor($diff / 3600) . 'h';
        } else {
            $timeAgo = floor($diff / 86400) . 'd';
        }

        return [
            'id' => $comment['id'],
            'content' => $comment['content'],
            'username' => $comment['username'] ?? 'Anonymous',
            'profile_picture' => $comment['profile_picture'],
            'wallet_address' => $comment['wallet_address'],
            'user_id' => $comment['user_id'],
            'time_ago' => $timeAgo,
            'like_count' => $postModel->getCommentLikeCount($comment['id']),
            'has_liked' => $currentUserId ? $postModel->hasUserLikedComment($comment['id'], $currentUserId) : false
        ];
    }, $comments);

    echo json_encode([
        'success' => true,
        'post' => $post,
        'comments' => $formattedComments,
        'comment_count' => count($comments)
    ]);
    exit;
}
elseif ($action === 'delete-comment') {
    header('Content-Type: application/json');

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        exit;
    }

    if (!AuthController::isLoggedIn()) {
        echo json_encode(['success' => false, 'error' => 'Not authenticated']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $commentId = intval($input['comment_id'] ?? 0);
    $postId = intval($input['post_id'] ?? 0);

    if (!$commentId) {
        echo json_encode(['success' => false, 'error' => 'Comment ID required']);
        exit;
    }

    require_once __DIR__ . '/app/models/Post.php';
    $postModel = new Post();

    $userId = AuthController::getCurrentUserId();
    $success = $postModel->deleteComment($commentId, $userId);

    if ($success) {
        $commentCount = $postModel->getCommentCount($postId);
        echo json_encode(['success' => true, 'comment_count' => $commentCount]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to delete comment']);
    }
    exit;
}
elseif ($action === 'toggle-comment-like') {
    header('Content-Type: application/json');

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        exit;
    }

    if (!AuthController::isLoggedIn()) {
        echo json_encode(['success' => false, 'error' => 'Not authenticated']);
        exit;
    }

    $input = json_decode(file_get_contents('php://input'), true);
    $commentId = intval($input['comment_id'] ?? 0);

    if (!$commentId) {
        echo json_encode(['success' => false, 'error' => 'Comment ID required']);
        exit;
    }

    require_once __DIR__ . '/app/models/Post.php';
    $postModel = new Post();

    $userId = AuthController::getCurrentUserId();
    $result = $postModel->toggleCommentLike($commentId, $userId);
    $likeCount = $postModel->getCommentLikeCount($commentId);

    echo json_encode([
        'success' => true,
        'action' => $result['action'],
        'like_count' => $likeCount
    ]);
    exit;
}
// Route pour créer un post via API JSON (modal)
elseif ($action === 'create-post') {
    header('Content-Type: application/json');

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        exit;
    }

    if (!AuthController::isLoggedIn()) {
        echo json_encode(['success' => false, 'error' => 'Not authenticated']);
        exit;
    }

    // Support both JSON and form data
    $input = json_decode(file_get_contents('php://input'), true);
    if ($input) {
        $content = trim($input['content'] ?? '');
        $isShadowMode = !empty($input['shadow_mode']);
        $targetUser = trim($input['target_user'] ?? '');
        $targetPlatform = trim($input['target_platform'] ?? 'xray');
        $boostAmount = floatval($input['boost_amount'] ?? 0);
        $identity = trim($input['identity'] ?? '');
    } else {
        $content = trim($_POST['content'] ?? '');
        $isShadowMode = isset($_POST['shadow_mode']) && $_POST['shadow_mode'] === '1';
        $targetUser = trim($_POST['target_user'] ?? '');
        $targetPlatform = trim($_POST['target_platform'] ?? 'xray');
        $boostAmount = floatval($_POST['boost_amount'] ?? 0);
        $identity = trim($_POST['identity'] ?? '');
    }

    // Validation
    if (empty($content)) {
        echo json_encode(['success' => false, 'error' => 'Content is required']);
        exit;
    }

    if (strlen($content) > 280) {
        echo json_encode(['success' => false, 'error' => 'Content exceeds 280 characters']);
        exit;
    }

    if ($isShadowMode && empty($targetUser)) {
        echo json_encode(['success' => false, 'error' => 'Target user is required in shadow mode']);
        exit;
    }

    require_once __DIR__ . '/app/models/Post.php';
    $postModel = new Post();

    $userId = AuthController::getCurrentUserId();
    $currentUser = AuthController::getCurrentUser();
    $walletAddress = $currentUser['wallet_address'] ?? '';

    // Create the post
    $postId = $postModel->createPost(
        $currentUser['username'] ?? '',
        $content,
        $currentUser['profile_picture'] ?? null,
        $userId,
        $isShadowMode ? $boostAmount : 0.001
    );

    if ($postId) {
        echo json_encode(['success' => true, 'post_id' => $postId, 'message' => 'Post created successfully']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to create post']);
    }
    exit;
}
// Routes des pages
elseif (in_array($action, ['notifications', 'profile', 'user'])) {
    switch ($action) {
        case 'notifications':
            require_once __DIR__ . '/app/views/notifications/xray-notifications.php';
            break;

        case 'profile':
            require_once __DIR__ . '/app/views/profile/xray-profile.php';
            break;

        case 'user':
            require_once __DIR__ . '/app/views/user/xray-user-profile.php';
            break;
    }
} else {
    // Routes des posts
    $controller = new PostController();

    switch ($action) {
        case 'index':
            $controller->index();
            break;

        case 'create':
            $controller->create();
            break;

        case 'store':
            $controller->store();
            break;

        default:
            $controller->index();
            break;
    }
}
