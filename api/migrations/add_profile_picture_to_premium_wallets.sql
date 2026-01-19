-- Migration: Add profile_picture column to premium_wallets table
-- Run this on the database: 109.176.199.253

ALTER TABLE premium_wallets
ADD COLUMN profile_picture VARCHAR(255) DEFAULT NULL
AFTER is_premium;

-- Example usage:
-- UPDATE premium_wallets SET profile_picture = 'uploads/premium/avatar.jpg' WHERE wallet_address = 'xxx';
