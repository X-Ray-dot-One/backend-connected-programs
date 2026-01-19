<?php
require_once __DIR__ . '/../../config/database.php';

class Post {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Récupère tous les posts triés par date décroissante avec les infos utilisateur
     */
    public function getAllPosts($limit = 50) {
        $stmt = $this->db->prepare("
            SELECT p.id, p.user_id, p.twitter_username, p.twitter_profile_image, p.content, p.created_at,
                   u.wallet_address as user_wallet,
                   u.username as user_username,
                   u.profile_picture as user_profile_picture
            FROM posts p
            LEFT JOIN users u ON p.user_id = u.id
            ORDER BY p.created_at DESC
            LIMIT :limit
        ");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Récupère les posts d'un utilisateur spécifique
     */
    public function getPostsByUserId($userId, $limit = 50) {
        $stmt = $this->db->prepare("
            SELECT id, user_id, twitter_username, twitter_profile_image, content, created_at
            FROM posts
            WHERE user_id = :user_id
            ORDER BY created_at DESC
            LIMIT :limit
        ");
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Crée un nouveau post
     */
    public function createPost($twitterUsername, $content, $profileImage = null, $userId = null) {
        $stmt = $this->db->prepare("
            INSERT INTO posts (user_id, twitter_username, twitter_profile_image, content)
            VALUES (:user_id, :username, :profile_image, :content)
        ");

        return $stmt->execute([
            ':user_id' => $userId,
            ':username' => $twitterUsername,
            ':profile_image' => $profileImage,
            ':content' => $content
        ]);
    }

    /**
     * Récupère un post par son ID
     */
    public function getPostById($id) {
        $stmt = $this->db->prepare("
            SELECT id, user_id, twitter_username, twitter_profile_image, content, created_at
            FROM posts
            WHERE id = :id
        ");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch();
    }

    /**
     * Récupère les posts des utilisateurs suivis par un utilisateur
     */
    public function getFollowingPosts($userId, $limit = 50) {
        $stmt = $this->db->prepare("
            SELECT p.id, p.user_id, p.twitter_username, p.twitter_profile_image, p.content, p.created_at,
                   u.wallet_address as user_wallet,
                   u.username as user_username,
                   u.profile_picture as user_profile_picture
            FROM posts p
            JOIN follows f ON p.user_id = f.following_id
            LEFT JOIN users u ON p.user_id = u.id
            WHERE f.follower_id = :user_id
            ORDER BY p.created_at DESC
            LIMIT :limit
        ");
        $stmt->bindValue(':user_id', $userId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Toggle like on a post (like if not liked, unlike if already liked)
     */
    public function toggleLike($postId, $userId) {
        // Check if already liked
        $stmt = $this->db->prepare("SELECT id FROM likes WHERE user_id = :user_id AND post_id = :post_id");
        $stmt->execute([':user_id' => $userId, ':post_id' => $postId]);
        $existing = $stmt->fetch();

        if ($existing) {
            // Unlike
            $stmt = $this->db->prepare("DELETE FROM likes WHERE user_id = :user_id AND post_id = :post_id");
            $stmt->execute([':user_id' => $userId, ':post_id' => $postId]);
            return ['success' => true, 'action' => 'unliked'];
        } else {
            // Like
            $stmt = $this->db->prepare("INSERT INTO likes (user_id, post_id) VALUES (:user_id, :post_id)");
            $stmt->execute([':user_id' => $userId, ':post_id' => $postId]);
            return ['success' => true, 'action' => 'liked'];
        }
    }

    /**
     * Get like count for a post
     */
    public function getLikeCount($postId) {
        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM likes WHERE post_id = :post_id");
        $stmt->execute([':post_id' => $postId]);
        $result = $stmt->fetch();
        return $result['count'] ?? 0;
    }

    /**
     * Check if user has liked a post
     */
    public function hasUserLiked($postId, $userId) {
        $stmt = $this->db->prepare("SELECT id FROM likes WHERE user_id = :user_id AND post_id = :post_id");
        $stmt->execute([':user_id' => $userId, ':post_id' => $postId]);
        return $stmt->fetch() !== false;
    }

    /**
     * Add a comment to a post
     */
    public function addComment($postId, $userId, $content) {
        $stmt = $this->db->prepare("
            INSERT INTO comments (post_id, user_id, content)
            VALUES (:post_id, :user_id, :content)
        ");
        $success = $stmt->execute([
            ':post_id' => $postId,
            ':user_id' => $userId,
            ':content' => $content
        ]);

        if ($success) {
            return $this->db->lastInsertId();
        }
        return false;
    }

    /**
     * Get comments for a post
     */
    public function getComments($postId, $limit = 50) {
        $stmt = $this->db->prepare("
            SELECT c.id, c.content, c.created_at, c.user_id,
                   u.username, u.profile_picture, u.wallet_address
            FROM comments c
            LEFT JOIN users u ON c.user_id = u.id
            WHERE c.post_id = :post_id
            ORDER BY c.created_at ASC
            LIMIT :limit
        ");
        $stmt->bindValue(':post_id', $postId, PDO::PARAM_INT);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Get comment count for a post
     */
    public function getCommentCount($postId) {
        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM comments WHERE post_id = :post_id");
        $stmt->execute([':post_id' => $postId]);
        $result = $stmt->fetch();
        return $result['count'] ?? 0;
    }

    /**
     * Delete a comment (only by owner)
     */
    public function deleteComment($commentId, $userId) {
        $stmt = $this->db->prepare("DELETE FROM comments WHERE id = :id AND user_id = :user_id");
        return $stmt->execute([':id' => $commentId, ':user_id' => $userId]);
    }

    /**
     * Get a post with user info by ID
     */
    public function getPostWithUserById($postId) {
        $stmt = $this->db->prepare("
            SELECT p.id, p.user_id, p.twitter_username, p.twitter_profile_image, p.content, p.created_at,
                   u.wallet_address as user_wallet,
                   u.username as user_username,
                   u.profile_picture as user_profile_picture
            FROM posts p
            LEFT JOIN users u ON p.user_id = u.id
            WHERE p.id = :id
        ");
        $stmt->execute([':id' => $postId]);
        return $stmt->fetch();
    }

    /**
     * Toggle like on a comment
     */
    public function toggleCommentLike($commentId, $userId) {
        $stmt = $this->db->prepare("SELECT id FROM comment_likes WHERE user_id = :user_id AND comment_id = :comment_id");
        $stmt->execute([':user_id' => $userId, ':comment_id' => $commentId]);
        $existing = $stmt->fetch();

        if ($existing) {
            $stmt = $this->db->prepare("DELETE FROM comment_likes WHERE user_id = :user_id AND comment_id = :comment_id");
            $stmt->execute([':user_id' => $userId, ':comment_id' => $commentId]);
            return ['success' => true, 'action' => 'unliked'];
        } else {
            $stmt = $this->db->prepare("INSERT INTO comment_likes (comment_id, user_id) VALUES (:comment_id, :user_id)");
            $stmt->execute([':comment_id' => $commentId, ':user_id' => $userId]);
            return ['success' => true, 'action' => 'liked'];
        }
    }

    /**
     * Get like count for a comment
     */
    public function getCommentLikeCount($commentId) {
        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM comment_likes WHERE comment_id = :comment_id");
        $stmt->execute([':comment_id' => $commentId]);
        $result = $stmt->fetch();
        return $result['count'] ?? 0;
    }

    /**
     * Check if user has liked a comment
     */
    public function hasUserLikedComment($commentId, $userId) {
        $stmt = $this->db->prepare("SELECT id FROM comment_likes WHERE user_id = :user_id AND comment_id = :comment_id");
        $stmt->execute([':user_id' => $userId, ':comment_id' => $commentId]);
        return $stmt->fetch() !== false;
    }

}
