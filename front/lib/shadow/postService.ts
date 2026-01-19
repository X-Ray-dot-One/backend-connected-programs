import {
  Connection,
  PublicKey,
  Keypair,
  Transaction,
  TransactionInstruction,
  SystemProgram,
  sendAndConfirmTransaction
} from "@solana/web3.js";
import { invalidatePostsCache } from "./topPosts";

// Configuration from environment variables
const PROGRAM_ID = new PublicKey(
  process.env.NEXT_PUBLIC_SOLANA_PROGRAM_ID || "5gPGpcXTq1R2chrEP9qPaFw4i1ge5ZgG2n7xnrUGZHPk"
);
const RPC_URL = process.env.NEXT_PUBLIC_SOLANA_RPC_URL || "https://devnet.helius-rpc.com/?api-key=64cda369-a212-4064-8133-e0e6827644b7";

// Revenue split wallets (45% / 10% / 45%)
const WALLET_1 = new PublicKey("69TwH2GJiBSA8Eo3DunPGsXGWjNFY267zRrpHptYWCuC"); // EMILE - 45%
const WALLET_2 = new PublicKey("EbhZhYumUZyHQCPbeaLLt57SS2obHiFdp7TMLjUBBqcD"); // GUARDIAN - 10%
const WALLET_3 = new PublicKey("HxtzFZhjNCsQb9ZqEyK8xYftqv6j6AM2MAT6uwWG3KYd"); // SACHA - 45%

let connection: Connection | null = null;

function getConnection(): Connection {
  if (!connection) {
    connection = new Connection(RPC_URL);
  }
  return connection;
}

// ============================================================================
// UTILS
// ============================================================================

async function sha256(data: string): Promise<Uint8Array> {
  const encoder = new TextEncoder();
  const dataBuffer = encoder.encode(data);
  const hashBuffer = await crypto.subtle.digest("SHA-256", dataBuffer);
  return new Uint8Array(hashBuffer);
}

async function getDiscriminator(instructionName: string): Promise<Uint8Array> {
  const hash = await sha256(`global:${instructionName}`);
  return hash.slice(0, 8);
}

function isValidString(str: string): boolean {
  return /^[\x20-\x7E]*$/.test(str);
}

// ============================================================================
// TYPES
// ============================================================================

export interface ShadowPost {
  pubkey: string;
  author: string;
  target: string;
  content: string;
  bid: bigint;
  timestamp: number;
}

// ============================================================================
// POST CREATION
// ============================================================================

/**
 * Create a post on-chain via the X-RAY Anchor program
 * The bid transfer to treasury is now handled atomically inside the Solana program via CPI
 * @param shadowKeypair - The shadow wallet keypair (signer)
 * @param target - Target URL (e.g., "https://x.com/elonmusk" or "https://xray.one/user/username")
 * @param content - Post content (max 512 chars)
 * @param bid - Bid amount in lamports (1 SOL = 1_000_000_000 lamports)
 */
export async function createShadowPost(
  shadowKeypair: Keypair,
  target: string,
  content: string,
  bid: bigint
): Promise<string> {
  const conn = getConnection();

  // Derive PDA for the treasury account
  const [treasuryPDA] = PublicKey.findProgramAddressSync(
    [Buffer.from("treasury")],
    PROGRAM_ID
  );

  // Derive PDA for the post account
  const [postPDA] = PublicKey.findProgramAddressSync(
    [Buffer.from("post"), shadowKeypair.publicKey.toBuffer(), Buffer.from(target)],
    PROGRAM_ID
  );

  const discriminator = await getDiscriminator("create_post");

  // Encode target (String: 4 bytes length + data)
  const targetBytes = new TextEncoder().encode(target);
  const targetLen = new Uint8Array(4);
  new DataView(targetLen.buffer).setUint32(0, targetBytes.length, true);

  // Encode content (String: 4 bytes length + data)
  const contentBytes = new TextEncoder().encode(content);
  const contentLen = new Uint8Array(4);
  new DataView(contentLen.buffer).setUint32(0, contentBytes.length, true);

  // Encode bid (u64: 8 bytes)
  const bidBuffer = new Uint8Array(8);
  new DataView(bidBuffer.buffer).setBigUint64(0, bid, true);

  // Concatenate instruction data
  const data = Buffer.concat([
    discriminator,
    targetLen, targetBytes,
    contentLen, contentBytes,
    bidBuffer
  ]);

  // Account metas (order MUST match Rust struct: author, treasury, wallet_1, wallet_2, wallet_3, post, system_program)
  const keys = [
    { pubkey: shadowKeypair.publicKey, isSigner: true, isWritable: true },   // author
    { pubkey: treasuryPDA, isSigner: false, isWritable: true },              // treasury PDA
    { pubkey: WALLET_1, isSigner: false, isWritable: true },                 // wallet_1 (GRINGO - 45%)
    { pubkey: WALLET_2, isSigner: false, isWritable: true },                 // wallet_2 (GUARDIAN - 10%)
    { pubkey: WALLET_3, isSigner: false, isWritable: true },                 // wallet_3 (SACHA - 45%)
    { pubkey: postPDA, isSigner: false, isWritable: true },                  // post PDA
    { pubkey: SystemProgram.programId, isSigner: false, isWritable: false }  // system_program
  ];

  const postInstruction = new TransactionInstruction({
    programId: PROGRAM_ID,
    keys,
    data
  });

  // Single instruction - transfer is handled inside the program (atomic)
  const transaction = new Transaction().add(postInstruction);

  const signature = await sendAndConfirmTransaction(conn, transaction, [shadowKeypair]);

  // Invalidate cache so new post appears immediately
  invalidatePostsCache();

  return signature;
}

// ============================================================================
// POST FETCHING
// ============================================================================

/**
 * Fetch all posts from the on-chain program
 */
export async function getAllShadowPosts(): Promise<ShadowPost[]> {
  const conn = getConnection();
  const accounts = await conn.getProgramAccounts(PROGRAM_ID);

  const posts: ShadowPost[] = [];

  for (const { pubkey, account } of accounts) {
    try {
      const data = account.data;

      // Minimum size check (discriminator + author + lengths + bid + timestamp + bump)
      if (data.length < 65) continue;

      let offset = 8; // Skip discriminator

      // Author (32 bytes)
      const author = new PublicKey(data.subarray(offset, offset + 32));
      offset += 32;

      // Target (4 bytes len + string)
      const targetLen = new DataView(data.buffer, data.byteOffset + offset, 4).getUint32(0, true);
      if (targetLen > 64 || offset + 4 + targetLen > data.length) continue;
      offset += 4;
      const target = new TextDecoder().decode(data.subarray(offset, offset + targetLen));
      if (!isValidString(target)) continue;
      offset += targetLen;

      // Content (4 bytes len + string)
      const contentLen = new DataView(data.buffer, data.byteOffset + offset, 4).getUint32(0, true);
      if (contentLen > 512 || offset + 4 + contentLen > data.length) continue;
      offset += 4;
      const content = new TextDecoder().decode(data.subarray(offset, offset + contentLen));
      if (!isValidString(content)) continue;
      offset += contentLen;

      // Bid (8 bytes u64)
      const bid = new DataView(data.buffer, data.byteOffset + offset, 8).getBigUint64(0, true);
      offset += 8;

      // Timestamp (8 bytes i64)
      const timestamp = new DataView(data.buffer, data.byteOffset + offset, 8).getBigInt64(0, true);

      // Validate timestamp (2020 to 2030)
      const ts = Number(timestamp);
      if (ts < 1577836800 || ts > 1893456000) continue;

      posts.push({
        pubkey: pubkey.toBase58(),
        author: author.toBase58(),
        target,
        content,
        bid,
        timestamp: ts
      });
    } catch {
      continue;
    }
  }

  // Sort by timestamp (newest first)
  return posts.sort((a, b) => b.timestamp - a.timestamp);
}

// ============================================================================
// HELPERS
// ============================================================================

/**
 * Airdrop SOL to a wallet (devnet only)
 */
export async function airdropSol(publicKey: PublicKey, amount: number = 1_000_000_000): Promise<string> {
  const conn = getConnection();
  const signature = await conn.requestAirdrop(publicKey, amount);
  await conn.confirmTransaction(signature);
  return signature;
}

/**
 * Transfer SOL from public wallet to shadow wallet
 * @param fromPublicKey - The public wallet address (source)
 * @param toPublicKey - The shadow wallet address (destination)
 * @param lamports - Amount to transfer in lamports
 * @param signTransaction - Function to sign the transaction with the public wallet
 */
export async function transferToShadowWallet(
  fromPublicKey: PublicKey,
  toPublicKey: PublicKey,
  lamports: bigint,
  signTransaction: (transaction: Transaction) => Promise<Transaction>
): Promise<string> {
  const conn = getConnection();

  const transaction = new Transaction().add(
    SystemProgram.transfer({
      fromPubkey: fromPublicKey,
      toPubkey: toPublicKey,
      lamports: BigInt(lamports),
    })
  );

  transaction.feePayer = fromPublicKey;
  const { blockhash } = await conn.getLatestBlockhash();
  transaction.recentBlockhash = blockhash;

  const signedTransaction = await signTransaction(transaction);
  const rawTransaction = signedTransaction.serialize();
  const signature = await conn.sendRawTransaction(rawTransaction);
  await conn.confirmTransaction(signature);

  return signature;
}

/**
 * Get wallet balance in lamports
 */
export async function getWalletBalance(publicKey: PublicKey): Promise<number> {
  const conn = getConnection();
  return conn.getBalance(publicKey);
}

/**
 * Format lamports to SOL display
 */
export function formatSol(lamports: bigint | number): string {
  const sol = Number(lamports) / 1_000_000_000;
  return sol.toFixed(sol < 0.01 ? 4 : 2);
}

/**
 * Convert SOL to lamports
 */
export function solToLamports(sol: number): bigint {
  return BigInt(Math.floor(sol * 1_000_000_000));
}

/**
 * Build target URL for a post
 */
export function buildTargetUrl(username: string, platform: 'xray' | 'twitter'): string {
  if (platform === 'twitter') {
    return `https://x.com/${username}`;
  }
  return `https://xray.one/user/${username}`;
}
