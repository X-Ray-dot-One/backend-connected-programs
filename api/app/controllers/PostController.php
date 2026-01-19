<?php
require_once __DIR__ . '/../models/Post.php';
require_once __DIR__ . '/AuthController.php';

class PostController {
    private $postModel;

    public function __construct() {
        $this->postModel = new Post();
    }

    /**
     * Affiche la page d'accueil avec tous les posts
     */
    public function index() {
        $activeTab = $_GET['tab'] ?? 'recently';

        // Si l'onglet "following" est sélectionné et l'utilisateur est connecté
        if ($activeTab === 'following' && AuthController::isLoggedIn()) {
            $userId = AuthController::getCurrentUserId();
            $posts = $this->postModel->getFollowingPosts($userId);
        } else {
            $posts = $this->postModel->getAllPosts();
        }

        require_once __DIR__ . '/../views/posts/xray-index.php';
    }

    /**
     * Affiche le formulaire de création
     */
    public function create() {
        if (!AuthController::isLoggedIn()) {
            $_SESSION['errors'] = ["Vous devez être connecté pour créer un post."];
            header('Location: index.php');
            exit;
        }

        require_once __DIR__ . '/../views/posts/xray-create.php';
    }

    /**
     * Traite la création d'un nouveau post
     */
    public function store() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php');
            exit;
        }

        if (!AuthController::isLoggedIn()) {
            $_SESSION['errors'] = ["Vous devez être connecté pour créer un post."];
            header('Location: index.php?action=signin');
            exit;
        }

        $content = trim($_POST['content'] ?? '');

        if (empty($content)) {
            $_SESSION['errors'] = ["Le contenu du post est requis."];
            $_SESSION['old'] = $_POST;
            header('Location: index.php?action=create');
            exit;
        }

        if (strlen($content) > 280) {
            $_SESSION['errors'] = ["Le contenu ne peut pas dépasser 280 caractères."];
            $_SESSION['old'] = $_POST;
            header('Location: index.php?action=create');
            exit;
        }

        $userId = AuthController::getCurrentUserId();
        $currentUser = AuthController::getCurrentUser();

        $success = $this->postModel->createPost(
            $currentUser['username'] ?? '',
            $content,
            $currentUser['profile_picture'] ?? null,
            $userId
        );

        if ($success) {
            $_SESSION['success'] = "Post créé avec succès !";
        } else {
            $_SESSION['errors'] = ["Erreur lors de la création du post."];
        }

        header('Location: index.php');
        exit;
    }
}
