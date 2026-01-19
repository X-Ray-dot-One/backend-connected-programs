<?php
require_once __DIR__ . '/../../config/database.php';

class ShadowWallet {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Get wallet count for a user (by hashed userId)
     */
    public function getWalletCount($userId) {
        $stmt = $this->db->prepare("
            SELECT count FROM wallet_counts WHERE user_id = :user_id
        ");
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        $result = $stmt->fetch();

        return $result ? (int)$result['count'] : 0;
    }

    /**
     * Check if a wallet address is premium
     */
    public function isPremium($walletAddress) {
        $stmt = $this->db->prepare("
            SELECT is_premium FROM premium_wallets WHERE wallet_address = :wallet_address
        ");
        $stmt->bindParam(':wallet_address', $walletAddress);
        $stmt->execute();
        $result = $stmt->fetch();

        return $result ? (bool)$result['is_premium'] : false;
    }

    /**
     * Set premium status for a wallet address
     */
    public function setPremium($walletAddress, $isPremium = true) {
        if ($isPremium) {
            $stmt = $this->db->prepare("
                INSERT INTO premium_wallets (wallet_address, is_premium)
                VALUES (:wallet_address, 1)
                ON DUPLICATE KEY UPDATE is_premium = 1
            ");
        } else {
            $stmt = $this->db->prepare("
                DELETE FROM premium_wallets WHERE wallet_address = :wallet_address
            ");
        }
        $stmt->bindParam(':wallet_address', $walletAddress);

        return $stmt->execute();
    }

    /**
     * Increment wallet count for a user
     * Creates the record if it doesn't exist
     */
    public function incrementWalletCount($userId) {
        // Use INSERT ... ON DUPLICATE KEY UPDATE for atomic operation
        $stmt = $this->db->prepare("
            INSERT INTO wallet_counts (user_id, count)
            VALUES (:user_id, 1)
            ON DUPLICATE KEY UPDATE count = count + 1
        ");
        $stmt->bindParam(':user_id', $userId);
        $success = $stmt->execute();

        if ($success) {
            return $this->getWalletCount($userId);
        }
        return false;
    }

    /**
     * Register a new shadow wallet with its name
     */
    public function createShadowWallet($shadowPubkey, $name) {
        $stmt = $this->db->prepare("
            INSERT INTO shadow_wallets (shadow_pubkey, name)
            VALUES (:shadow_pubkey, :name)
        ");
        $stmt->bindParam(':shadow_pubkey', $shadowPubkey);
        $stmt->bindParam(':name', $name);

        return $stmt->execute();
    }

    /**
     * Check if a name already exists
     */
    public function nameExists($name) {
        $stmt = $this->db->prepare("
            SELECT 1 FROM shadow_wallets WHERE name = :name LIMIT 1
        ");
        $stmt->bindParam(':name', $name);
        $stmt->execute();

        return $stmt->fetch() !== false;
    }

    /**
     * Get the name of a shadow wallet by its public key
     */
    public function getNameByPubkey($shadowPubkey) {
        $stmt = $this->db->prepare("
            SELECT name FROM shadow_wallets WHERE shadow_pubkey = :shadow_pubkey
        ");
        $stmt->bindParam(':shadow_pubkey', $shadowPubkey);
        $stmt->execute();
        $result = $stmt->fetch();

        return $result ? $result['name'] : null;
    }

    /**
     * Get a shadow wallet by its name
     * Returns pubkey and created_at
     */
    public function getByName($name) {
        $stmt = $this->db->prepare("
            SELECT shadow_pubkey, name, created_at
            FROM shadow_wallets
            WHERE name = :name
            LIMIT 1
        ");
        $stmt->bindParam(':name', $name);
        $stmt->execute();

        return $stmt->fetch() ?: null;
    }

    /**
     * Get all shadow wallets for debugging/admin
     */
    public function getAllShadowWallets($limit = 100) {
        $stmt = $this->db->prepare("
            SELECT shadow_pubkey, name, created_at
            FROM shadow_wallets
            ORDER BY created_at DESC
            LIMIT :limit
        ");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /**
     * Search shadow wallets by name
     */
    public function searchByName($query, $limit = 20) {
        $searchPattern = '%' . $query . '%';
        $stmt = $this->db->prepare("
            SELECT shadow_pubkey, name, created_at
            FROM shadow_wallets
            WHERE name LIKE :query
            ORDER BY
                CASE WHEN name LIKE :exact THEN 0 ELSE 1 END,
                name ASC
            LIMIT :limit
        ");
        $stmt->bindValue(':query', $searchPattern);
        $stmt->bindValue(':exact', $query . '%');
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }
}
