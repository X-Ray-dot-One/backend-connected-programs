import { Connection, PublicKey } from "@solana/web3.js";
import { getXProfile } from "../api";

// Configuration from environment variables
const PROGRAM_ID = new PublicKey(
  process.env.NEXT_PUBLIC_SOLANA_PROGRAM_ID || "5gPGpcXTq1R2chrEP9qPaFw4i1ge5ZgG2n7xnrUGZHPk"
);
const RPC_URL = process.env.NEXT_PUBLIC_SOLANA_RPC_URL || "https://devnet.helius-rpc.com/?api-key=64cda369-a212-4064-8133-e0e6827644b7";

let connection: Connection | null = null;

function getConnection(): Connection {
  if (!connection) {
    connection = new Connection(RPC_URL);
  }
  return connection;
}

// ============================================================================
// TYPES
// ============================================================================

export interface TargetProfile {
  username: string;
  name: string;
  description: string;
  profilePicUrl: string;
  bannerUrl: string;
  profileUrl: string;
  followersCount: number;
  followingCount: number;
  tweetCount: number;
}

export interface TargetPost {
  pubkey: string;
  author: string;
  content: string;
  bid: bigint;
  timestamp: number;
}

// ============================================================================
// URL HELPERS
// ============================================================================

/**
 * Extract username from X/Twitter URL
 */
export function extractXUsername(targetUrl: string): string | null {
  const match = targetUrl.match(/(?:x\.com|twitter\.com)\/([a-zA-Z0-9_]+)/);
  return match?.[1] ?? null;
}

/**
 * Extract username from X-RAY URL
 */
export function extractXrayUsername(targetUrl: string): string | null {
  const match = targetUrl.match(/xray\.one\/user\/([a-zA-Z0-9_]+)/);
  return match?.[1] ?? null;
}

/**
 * Check if target is an X/Twitter profile URL
 */
export function isXProfile(target: string): boolean {
  return /(?:x\.com|twitter\.com)\/[a-zA-Z0-9_]+/.test(target);
}

/**
 * Check if target is an X-RAY profile URL
 */
export function isXrayProfile(target: string): boolean {
  return /xray\.one\/user\/[a-zA-Z0-9_]+/.test(target);
}

// ============================================================================
// PROFILE FETCHING
// ============================================================================

/**
 * Fetch X/Twitter profile using the backend proxy
 */
export async function fetchXProfile(target: string): Promise<TargetProfile | null> {
  const username = extractXUsername(target);
  console.log("fetchXProfile - extracted username:", username, "from target:", target);
  if (!username) return null;

  try {
    console.log("fetchXProfile - calling getXProfile for:", username);
    const data = await getXProfile(username);
    console.log("fetchXProfile - API response:", data);

    if (!data.success) {
      console.log("fetchXProfile - API returned success=false");
      return null;
    }

    return {
      username: data.username || username,
      name: data.name || username,
      description: data.description || "",
      profilePicUrl: data.profile_image_url || "",
      bannerUrl: data.profile_banner_url || "",
      profileUrl: `https://x.com/${username}`,
      followersCount: data.followers_count || 0,
      followingCount: data.following_count || 0,
      tweetCount: data.tweet_count || 0
    };
  } catch (error) {
    console.error("Error fetching X profile:", error);
    return null;
  }
}

/**
 * Get default profile placeholder
 */
export function getDefaultProfile(target: string): TargetProfile {
  const xUsername = extractXUsername(target);
  const xrayUsername = extractXrayUsername(target);
  const username = xUsername || xrayUsername || target;

  return {
    username,
    name: username,
    description: "",
    profilePicUrl: "",
    bannerUrl: "",
    profileUrl: isXProfile(target) ? `https://x.com/${username}` : `https://xray.one/user/${username}`,
    followersCount: 0,
    followingCount: 0,
    tweetCount: 0
  };
}

// ============================================================================
// TARGET POSTS
// ============================================================================

/**
 * Get all posts for a specific target, sorted by bid (highest first)
 */
export async function getPostsForTarget(target: string): Promise<TargetPost[]> {
  const conn = getConnection();
  const accounts = await conn.getProgramAccounts(PROGRAM_ID);
  const posts: TargetPost[] = [];

  for (const { pubkey, account } of accounts) {
    const data = account.data;

    if (data.length < 65) continue;

    try {
      let offset = 8; // Skip discriminator

      // Author (32 bytes)
      const author = new PublicKey(data.subarray(offset, offset + 32));
      offset += 32;

      // Target (4 bytes len + string)
      const targetLen = new DataView(data.buffer, data.byteOffset + offset, 4).getUint32(0, true);
      if (targetLen > 64 || offset + 4 + targetLen > data.length) continue;
      offset += 4;
      const postTarget = new TextDecoder().decode(data.subarray(offset, offset + targetLen));
      offset += targetLen;

      // Only include posts for this target
      if (postTarget !== target) continue;

      // Content (4 bytes len + string)
      const contentLen = new DataView(data.buffer, data.byteOffset + offset, 4).getUint32(0, true);
      if (contentLen > 512 || offset + 4 + contentLen > data.length) continue;
      offset += 4;
      const content = new TextDecoder().decode(data.subarray(offset, offset + contentLen));
      offset += contentLen;

      // Bid (8 bytes u64)
      const bid = new DataView(data.buffer, data.byteOffset + offset, 8).getBigUint64(0, true);
      offset += 8;

      // Timestamp (8 bytes i64)
      const timestamp = Number(new DataView(data.buffer, data.byteOffset + offset, 8).getBigInt64(0, true));

      posts.push({
        pubkey: pubkey.toBase58(),
        author: author.toBase58(),
        content,
        bid,
        timestamp
      });
    } catch {
      continue;
    }
  }

  // Sort by bid descending (highest first)
  posts.sort((a, b) => Number(b.bid - a.bid));
  return posts;
}
