import { Connection, PublicKey } from "@solana/web3.js";

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
// CACHE SYSTEM - Reduce RPC calls
// ============================================================================

interface CachedPosts {
  posts: TopPost[];
  timestamp: number;
}

let postsCache: CachedPosts | null = null;
const CACHE_TTL = 30000; // 30 seconds cache

/**
 * Get cached posts or fetch from chain if cache is stale
 */
async function getCachedPosts(): Promise<TopPost[]> {
  const now = Date.now();

  // Return cached posts if still valid
  if (postsCache && (now - postsCache.timestamp) < CACHE_TTL) {
    return postsCache.posts;
  }

  // Fetch fresh data
  const posts = await fetchAllPostsFromChain();
  postsCache = { posts, timestamp: now };

  return posts;
}

/**
 * Force refresh the cache (call after creating a new post)
 */
export function invalidatePostsCache(): void {
  postsCache = null;
}

/**
 * Internal function to fetch all posts from chain (used by cache)
 */
async function fetchAllPostsFromChain(): Promise<TopPost[]> {
  const conn = getConnection();
  const accounts = await conn.getProgramAccounts(PROGRAM_ID);

  const posts: TopPost[] = [];

  for (const { pubkey, account } of accounts) {
    const data = account.data;

    if (data.length < 65) continue;

    try {
      let offset = 8;

      const author = new PublicKey(data.subarray(offset, offset + 32));
      offset += 32;

      const targetLen = new DataView(data.buffer, data.byteOffset + offset, 4).getUint32(0, true);
      if (targetLen > 64 || offset + 4 + targetLen > data.length) continue;
      offset += 4;
      const target = new TextDecoder().decode(data.subarray(offset, offset + targetLen));
      offset += targetLen;

      const contentLen = new DataView(data.buffer, data.byteOffset + offset, 4).getUint32(0, true);
      if (contentLen > 512 || offset + 4 + contentLen > data.length) continue;
      offset += 4;
      const content = new TextDecoder().decode(data.subarray(offset, offset + contentLen));
      offset += contentLen;

      const bid = new DataView(data.buffer, data.byteOffset + offset, 8).getBigUint64(0, true);
      offset += 8;

      const timestamp = Number(new DataView(data.buffer, data.byteOffset + offset, 8).getBigInt64(0, true));

      // Validate timestamp (reasonable range: 2020-2030)
      if (timestamp < 1577836800 || timestamp > 1893456000) continue;

      posts.push({
        pubkey: pubkey.toBase58(),
        author: author.toBase58(),
        target,
        content,
        bid,
        timestamp
      });
    } catch {
      continue;
    }
  }

  // Sort by timestamp (newest first)
  posts.sort((a, b) => b.timestamp - a.timestamp);

  return posts;
}

// ============================================================================
// TYPES
// ============================================================================

export interface TopPost {
  pubkey: string;
  author: string;
  target: string;
  content: string;
  bid: bigint;
  timestamp: number;
}

// ============================================================================
// TOP POSTS
// ============================================================================

/**
 * Get top 20 posts from the last 24 hours, sorted by bid (highest first)
 * Uses cached posts to reduce RPC calls
 */
export async function getTop20Posts(): Promise<TopPost[]> {
  const allPosts = await getCachedPosts();

  const now = Math.floor(Date.now() / 1000);
  const oneDayAgo = now - 24 * 60 * 60;

  // Filter to last 24h and sort by bid
  const recentPosts = allPosts
    .filter(p => p.timestamp >= oneDayAgo)
    .sort((a, b) => Number(b.bid - a.bid));

  return recentPosts.slice(0, 20);
}

/**
 * Get stats for a shadow wallet (posts count, total bid)
 * Uses cached posts to reduce RPC calls
 */
export interface ShadowWalletStats {
  postsCount: number;
  totalBid: bigint;
  lastActive: number; // timestamp of most recent post
}

export async function getShadowWalletStats(authorPubkey: string): Promise<ShadowWalletStats> {
  const allPosts = await getCachedPosts();

  let postsCount = 0;
  let totalBid = BigInt(0);
  let lastActive = 0;

  for (const post of allPosts) {
    if (post.author !== authorPubkey) continue;

    postsCount++;
    totalBid += post.bid;
    if (post.timestamp > lastActive) lastActive = post.timestamp;
  }

  return { postsCount, totalBid, lastActive };
}

/**
 * Extended post with rank information
 */
export interface TopPostWithRank extends TopPost {
  rank: number;
  totalForTarget: number;
}

/**
 * Detailed stats for shadow profile page (includes posts with ranks)
 */
export interface ShadowProfileStats {
  totalPosts: number;
  totalSpent: bigint;
  avgBoost: number;
  avgPosition: number;
  posts: TopPostWithRank[];
}

/**
 * Get detailed stats and posts for a shadow wallet profile
 * Uses cached posts to reduce RPC calls
 * Calculates rank for each post relative to other posts on the same target
 */
export async function getShadowProfileStats(authorPubkey: string): Promise<ShadowProfileStats> {
  const allPosts = await getCachedPosts();

  // Group all posts by target for ranking calculation
  const postsByTarget = new Map<string, TopPost[]>();
  const authorPosts: TopPost[] = [];

  for (const post of allPosts) {
    // Group posts by target
    if (!postsByTarget.has(post.target)) {
      postsByTarget.set(post.target, []);
    }
    postsByTarget.get(post.target)!.push(post);

    // Collect author's posts
    if (post.author === authorPubkey) {
      authorPosts.push(post);
    }
  }

  // Calculate ranks for author's posts
  const postsWithRanks: TopPostWithRank[] = [];
  let totalRankSum = 0;

  for (const post of authorPosts) {
    const targetPosts = postsByTarget.get(post.target) || [];
    // Sort by bid descending
    targetPosts.sort((a, b) => Number(b.bid - a.bid));

    const rank = targetPosts.findIndex(p => p.pubkey === post.pubkey) + 1;
    const totalForTarget = targetPosts.length;

    postsWithRanks.push({
      ...post,
      rank,
      totalForTarget
    });

    totalRankSum += rank;
  }

  // Sort by timestamp (newest first)
  postsWithRanks.sort((a, b) => b.timestamp - a.timestamp);

  // Calculate stats
  const totalPosts = authorPosts.length;
  const totalSpent = authorPosts.reduce((sum, p) => sum + p.bid, BigInt(0));
  const avgBoost = totalPosts > 0 ? Number(totalSpent) / totalPosts / 1e9 : 0; // Convert lamports to SOL
  const avgPosition = totalPosts > 0 ? totalRankSum / totalPosts : 0;

  return {
    totalPosts,
    totalSpent,
    avgBoost,
    avgPosition,
    posts: postsWithRanks
  };
}

/**
 * Get all posts by a specific author, sorted by timestamp (newest first)
 * Uses cached posts to reduce RPC calls
 */
export async function getPostsByAuthor(authorPubkey: string): Promise<TopPost[]> {
  const allPosts = await getCachedPosts();

  return allPosts
    .filter(p => p.author === authorPubkey)
    .sort((a, b) => b.timestamp - a.timestamp);
}

/**
 * Get all recent posts, sorted by timestamp (newest first)
 * Uses cached posts to reduce RPC calls
 */
export async function getRecentPosts(limit: number = 50): Promise<TopPost[]> {
  const allPosts = await getCachedPosts();
  // Already sorted by timestamp (newest first) in fetchAllPostsFromChain
  return allPosts.slice(0, limit);
}
