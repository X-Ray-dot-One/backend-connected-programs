import { Keypair } from "@solana/web3.js";

// ============================================================================
// UTILS - Web Crypto API (browser compatible)
// ============================================================================

async function sha256(data: string): Promise<Uint8Array> {
  const encoder = new TextEncoder();
  const dataBuffer = encoder.encode(data);
  const hashBuffer = await crypto.subtle.digest("SHA-256", dataBuffer);
  return new Uint8Array(hashBuffer);
}

export async function hashUserId(userId: string): Promise<string> {
  const hash = await sha256(userId);
  return Array.from(hash)
    .map((b) => b.toString(16).padStart(2, "0"))
    .join("");
}

// ============================================================================
// CORE - Shadow Wallet Derivation
// ============================================================================

async function deriveSeed(
  signature: string,
  hashedUserId: string,
  walletIndex: number
): Promise<Uint8Array> {
  const message = `${signature}:${hashedUserId}:${walletIndex}`;
  return sha256(message);
}

/**
 * Generate a deterministic shadow wallet from signature + hashed user ID + index
 * Same inputs always produce the same keypair (reproducible)
 */
export async function generateShadowWallet(
  signature: string,
  hashedUserId: string,
  walletIndex: number
): Promise<Keypair> {
  const seed = await deriveSeed(signature, hashedUserId, walletIndex);
  return Keypair.fromSeed(seed);
}

// ============================================================================
// NAME GENERATION
// ============================================================================

const SHADOW_PREFIXES = ["anon", "shadow", "secret", "ghost", "dark", "cipher", "phantom"];
const CHARS = "abcdefghijklmnopqrstuvwxyz0123456789";

export function generateShadowName(existingNames: string[]): string {
  let fullName = "";

  do {
    const prefix = SHADOW_PREFIXES[Math.floor(Math.random() * SHADOW_PREFIXES.length)];
    let suffix = "";
    for (let i = 0; i < 5; i++) {
      suffix += CHARS[Math.floor(Math.random() * CHARS.length)];
    }
    fullName = `${prefix}_${suffix}`;
  } while (existingNames.includes(fullName));

  return fullName;
}

// ============================================================================
// TYPES & API
// ============================================================================

export interface ShadowWallet {
  index: number;
  publicKey: string;
  name: string;
  balance?: number;
}

/**
 * Generate multiple shadow wallets at once
 */
export async function getShadowWallets(
  signature: string,
  hashedUserId: string,
  count: number,
  existingNames: string[]
): Promise<ShadowWallet[]> {
  const wallets: ShadowWallet[] = [];
  const usedNames = [...existingNames];

  for (let i = 0; i < count; i++) {
    const keypair = await generateShadowWallet(signature, hashedUserId, i);
    const name = generateShadowName(usedNames);
    usedNames.push(name);

    wallets.push({
      index: i,
      publicKey: keypair.publicKey.toBase58(),
      name,
    });
  }

  return wallets;
}

/**
 * Get the keypair for a specific shadow wallet (for signing transactions)
 */
export async function getShadowKeypair(
  signature: string,
  hashedUserId: string,
  walletIndex: number
): Promise<Keypair> {
  return generateShadowWallet(signature, hashedUserId, walletIndex);
}
