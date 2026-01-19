<?php

class WalletController {

    /**
     * Récupère le solde d'une adresse Solana sur devnet via proxy PHP
     * Cela évite les problèmes CORS en faisant l'appel depuis le serveur
     */
    public function getBalance() {
        header('Content-Type: application/json');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            echo json_encode(['success' => false, 'error' => 'Method not allowed']);
            exit;
        }

        $input = json_decode(file_get_contents('php://input'), true);
        $walletAddress = $input['walletAddress'] ?? null;

        if (!$walletAddress) {
            echo json_encode(['success' => false, 'error' => 'Wallet address required']);
            exit;
        }

        // Valider le format de l'adresse Solana
        if (!preg_match('/^[1-9A-HJ-NP-Za-km-z]{32,44}$/', $walletAddress)) {
            echo json_encode(['success' => false, 'error' => 'Invalid Solana address']);
            exit;
        }

        // Appeler le RPC Solana devnet via cURL (pas de problème CORS côté serveur)
        $rpcEndpoint = 'https://api.devnet.solana.com';

        $requestData = [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'getBalance',
            'params' => [$walletAddress]
        ];

        $ch = curl_init($rpcEndpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10); // 10 seconds timeout
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5); // 5 seconds connection timeout

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            echo json_encode(['success' => false, 'error' => 'cURL error: ' . $curlError]);
            exit;
        }

        if ($httpCode !== 200) {
            echo json_encode(['success' => false, 'error' => 'RPC request failed with HTTP ' . $httpCode]);
            exit;
        }

        $data = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            echo json_encode(['success' => false, 'error' => 'Invalid JSON response']);
            exit;
        }

        if (isset($data['result']['value'])) {
            $lamports = $data['result']['value'];
            $sol = $lamports / 1000000000; // 1 SOL = 1 milliard de lamports

            echo json_encode([
                'success' => true,
                'balance' => $sol,
                'lamports' => $lamports
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'error' => 'Invalid RPC response',
                'debug' => $data
            ]);
        }

        exit;
    }
}
