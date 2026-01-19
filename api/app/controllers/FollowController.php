<?php
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/AuthController.php';

class FollowController {
    private $userModel;

    public function __construct() {
        $this->userModel = new User();
    }

    /**
     * Follow a user
     */
    public function follow() {
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
        $followingId = $input['user_id'] ?? null;

        if (!$followingId) {
            echo json_encode(['success' => false, 'error' => 'User ID required']);
            exit;
        }

        $followerId = AuthController::getCurrentUserId();

        // Check if trying to follow self
        if ($followerId == $followingId) {
            echo json_encode(['success' => false, 'error' => 'Cannot follow yourself']);
            exit;
        }

        // Check if user exists
        $targetUser = $this->userModel->findById($followingId);
        if (!$targetUser) {
            echo json_encode(['success' => false, 'error' => 'User not found']);
            exit;
        }

        $success = $this->userModel->follow($followerId, $followingId);

        if ($success) {
            $followersCount = $this->userModel->getFollowersCount($followingId);
            $myFollowingCount = $this->userModel->getFollowingCount($followerId);
            echo json_encode([
                'success' => true,
                'is_following' => true,
                'followers_count' => $followersCount,
                'my_following_count' => $myFollowingCount
            ]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to follow user']);
        }
        exit;
    }

    /**
     * Unfollow a user
     */
    public function unfollow() {
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
        $followingId = $input['user_id'] ?? null;

        if (!$followingId) {
            echo json_encode(['success' => false, 'error' => 'User ID required']);
            exit;
        }

        $followerId = AuthController::getCurrentUserId();
        $success = $this->userModel->unfollow($followerId, $followingId);

        if ($success) {
            $followersCount = $this->userModel->getFollowersCount($followingId);
            $myFollowingCount = $this->userModel->getFollowingCount($followerId);
            echo json_encode([
                'success' => true,
                'is_following' => false,
                'followers_count' => $followersCount,
                'my_following_count' => $myFollowingCount
            ]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Failed to unfollow user']);
        }
        exit;
    }

    /**
     * Check if current user follows a specific user
     */
    public function checkFollow() {
        header('Content-Type: application/json');

        if (!AuthController::isLoggedIn()) {
            echo json_encode(['success' => false, 'error' => 'Not authenticated']);
            exit;
        }

        $followingId = $_GET['user_id'] ?? null;

        if (!$followingId) {
            echo json_encode(['success' => false, 'error' => 'User ID required']);
            exit;
        }

        $followerId = AuthController::getCurrentUserId();
        $isFollowing = $this->userModel->isFollowing($followerId, $followingId);
        $followersCount = $this->userModel->getFollowersCount($followingId);
        $followingCount = $this->userModel->getFollowingCount($followingId);

        echo json_encode([
            'success' => true,
            'is_following' => $isFollowing,
            'followers_count' => $followersCount,
            'following_count' => $followingCount
        ]);
        exit;
    }

    /**
     * Get followers list
     */
    public function getFollowers() {
        header('Content-Type: application/json');

        $userId = $_GET['user_id'] ?? null;
        $limit = intval($_GET['limit'] ?? 50);

        if (!$userId) {
            echo json_encode(['success' => false, 'error' => 'User ID required']);
            exit;
        }

        $followers = $this->userModel->getFollowers($userId, $limit);
        $count = $this->userModel->getFollowersCount($userId);

        // Add is_followed_by_me for each user if current user is logged in
        $currentUserId = AuthController::isLoggedIn() ? AuthController::getCurrentUserId() : null;
        if ($currentUserId) {
            foreach ($followers as &$user) {
                $user['is_followed_by_me'] = $this->userModel->isFollowing($currentUserId, $user['id']);
            }
        }

        echo json_encode([
            'success' => true,
            'users' => $followers,
            'count' => $count
        ]);
        exit;
    }

    /**
     * Get following list
     */
    public function getFollowing() {
        header('Content-Type: application/json');

        $userId = $_GET['user_id'] ?? null;
        $limit = intval($_GET['limit'] ?? 50);

        if (!$userId) {
            echo json_encode(['success' => false, 'error' => 'User ID required']);
            exit;
        }

        $following = $this->userModel->getFollowing($userId, $limit);
        $count = $this->userModel->getFollowingCount($userId);

        // Add is_followed_by_me for each user if current user is logged in
        $currentUserId = AuthController::isLoggedIn() ? AuthController::getCurrentUserId() : null;
        if ($currentUserId) {
            foreach ($following as &$user) {
                $user['is_followed_by_me'] = $this->userModel->isFollowing($currentUserId, $user['id']);
            }
        }

        echo json_encode([
            'success' => true,
            'users' => $following,
            'count' => $count
        ]);
        exit;
    }
}
