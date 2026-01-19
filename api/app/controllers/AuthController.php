<?php
require_once __DIR__ . '/../models/User.php';

class AuthController {
    private $userModel;

    public function __construct() {
        $this->userModel = new User();
    }

    /**
     * Affiche la page d'inscription
     */
    public function signup() {
        require_once __DIR__ . '/../views/auth/signup.php';
    }

    /**
     * Affiche la page de connexion
     */
    public function signin() {
        require_once __DIR__ . '/../views/auth/signin.php';
    }

    /**
     * Traite l'inscription et génère la clé secrète
     */
    public function register() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?action=signup');
            exit;
        }

        $result = $this->userModel->createUser();

        if ($result['success']) {
            // Stocker l'ID utilisateur en session
            $_SESSION['user_id'] = $result['user_id'];
            $_SESSION['secret_key'] = $result['secret_key'];
            $_SESSION['show_secret_key'] = true;

            header('Location: index.php?action=show-secret-key');
            exit;
        } else {
            $_SESSION['errors'] = ["Erreur lors de la création du compte."];
            header('Location: index.php?action=signup');
            exit;
        }
    }

    /**
     * Affiche la clé secrète générée (une seule fois)
     */
    public function showSecretKey() {
        if (!isset($_SESSION['show_secret_key']) || !isset($_SESSION['secret_key'])) {
            header('Location: index.php');
            exit;
        }

        require_once __DIR__ . '/../views/auth/show-secret-key.php';

        // Supprimer la clé de la session après affichage
        unset($_SESSION['show_secret_key']);
        unset($_SESSION['secret_key']);
    }

    /**
     * Traite la connexion
     */
    public function login() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: index.php?action=signin');
            exit;
        }

        $secretKey = trim($_POST['secret_key'] ?? '');

        if (empty($secretKey)) {
            $_SESSION['errors'] = ["La clé secrète est requise."];
            header('Location: index.php?action=signin');
            exit;
        }

        $result = $this->userModel->authenticate($secretKey);

        if ($result['success']) {
            $_SESSION['user_id'] = $result['user_id'];
            $_SESSION['success'] = "Connexion réussie !";
            header('Location: index.php');
            exit;
        } else {
            $_SESSION['errors'] = ["Clé secrète invalide."];
            header('Location: index.php?action=signin');
            exit;
        }
    }

    /**
     * Déconnexion
     */
    public function logout() {
        header('Content-Type: application/json');
        session_destroy();
        echo json_encode(['success' => true]);
        exit;
    }

    /**
     * Vérifie si l'utilisateur est connecté
     */
    public static function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }

    /**
     * Récupère l'ID de l'utilisateur connecté
     */
    public static function getCurrentUserId() {
        return $_SESSION['user_id'] ?? null;
    }

    /**
     * Récupère l'adresse du wallet connecté
     */
    public static function getCurrentWallet() {
        return $_SESSION['wallet_address'] ?? null;
    }

    /**
     * Récupère les informations de l'utilisateur connecté
     */
    public static function getCurrentUser() {
        if (!self::isLoggedIn()) {
            return null;
        }

        $userModel = new User();
        return $userModel->findById($_SESSION['user_id']);
    }

    /**
     * Récupère le type de wallet connecté
     */
    public static function getCurrentWalletType() {
        return $_SESSION['wallet_type'] ?? null;
    }

    /**
     * Toggle shadow mode (mode anonyme)
     */
    public function toggleShadowMode() {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
            exit;
        }

        // Toggle the shadow mode session variable
        $_SESSION['shadow_mode'] = !($_SESSION['shadow_mode'] ?? false);

        echo json_encode([
            'success' => true,
            'shadow_mode' => $_SESSION['shadow_mode']
        ]);
        exit;
    }

    /**
     * Authentification par wallet Phantom (Solana uniquement)
     */
    public function walletAuth() {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        // Support both walletAddress and wallet_address
        $walletAddress = $input['walletAddress'] ?? $input['wallet_address'] ?? null;

        if (!$walletAddress) {
            echo json_encode(['success' => false, 'error' => 'Wallet address required']);
            exit;
        }

        // Valider le format de l'adresse Solana (base58, 32-44 caractères)
        if (!preg_match('/^[1-9A-HJ-NP-Za-km-z]{32,44}$/', $walletAddress)) {
            echo json_encode(['success' => false, 'error' => 'Invalid Solana wallet address']);
            exit;
        }

        $result = $this->userModel->findOrCreateByWallet($walletAddress);

        if ($result['success']) {
            $_SESSION['user_id'] = $result['user_id'];
            $_SESSION['wallet_address'] = $result['wallet_address'];
            $_SESSION['success'] = $result['is_new'] ? "Compte créé avec succès !" : "Connexion réussie !";

            // Get full user info
            $user = $this->userModel->findById($result['user_id']);

            echo json_encode([
                'success' => true,
                'user_id' => $result['user_id'],
                'wallet_address' => $result['wallet_address'],
                'is_new' => $result['is_new'],
                'user' => $user
            ]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Authentication failed']);
        }
        exit;
    }
}
