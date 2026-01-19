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
 */
export async function getTop20Posts(): Promise<TopPost[]> {
  const conn = getConnection();
  const accounts = await conn.getProgramAccounts(PROGRAM_ID);

  const now = Math.floor(Date.now() / 1000);
  const oneDayAgo = now - 24 * 60 * 60;

  const posts: TopPost[] = [];

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
      const target = new TextDecoder().decode(data.subarray(offset, offset + targetLen));
      offset += targetLen;

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

      // Filter: only posts from last 24h
      if (timestamp < oneDayAgo) continue;

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

  // Sort by bid descending (highest first) and take top 20
  posts.sort((a, b) => Number(b.bid - a.bid));

  return posts.slice(0, 20);
}

/**
 * Get stats for a shadow wallet (posts count, total bid)
 */
export interface ShadowWalletStats {
  postsCount: number;
  totalBid: bigint;
  lastActive: number; // timestamp of most recent post
}

export async function getShadowWalletStats(authorPubkey: string): Promise<ShadowWalletStats> {
  const conn = getConnection();
  const accounts = await conn.getProgramAccounts(PROGRAM_ID);

  let postsCount = 0;
  let totalBid = BigInt(0);
  let lastActive = 0;

  for (const { account } of accounts) {
    const data = account.data;

    if (data.length < 65) continue;

    try {
      let offset = 8;

      const author = new PublicKey(data.subarray(offset, offset + 32));
      if (author.toBase58() !== authorPubkey) continue;

      offset += 32;

      // Skip target
      const targetLen = new DataView(data.buffer, data.byteOffset + offset, 4).getUint32(0, true);
      if (targetLen > 64 || offset + 4 + targetLen > data.length) continue;
      offset += 4 + targetLen;

      // Skip content
      const contentLen = new DataView(data.buffer, data.byteOffset + offset, 4).getUint32(0, true);
      if (contentLen > 512 || offset + 4 + contentLen > data.length) continue;
      offset += 4 + contentLen;

      // Bid
      const bid = new DataView(data.buffer, data.byteOffset + offset, 8).getBigUint64(0, true);
      offset += 8;

      // Timestamp
      const timestamp = Number(new DataView(data.buffer, data.byteOffset + offset, 8).getBigInt64(0, true));

      postsCount++;
      totalBid += bid;
      if (timestamp > lastActive) lastActive = timestamp;
    } catch {
      continue;
    }
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
 * Calculates rank for each post relative to other posts on the same target
 */
export async function getShadowProfileStats(authorPubkey: string): Promise<ShadowProfileStats> {
  const conn = getConnection();
  const accounts = await conn.getProgramAccounts(PROGRAM_ID);

  // First pass: collect all posts grouped by target
  const postsByTarget = new Map<string, { pubkey: string; author: string; content: string; bid: bigint; timestamp: number }[]>();
  const authorPosts: TopPost[] = [];

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

      // Group posts by target for ranking
      const postData = {
        pubkey: pubkey.toBase58(),
        author: author.toBase58(),
        content,
        bid,
        timestamp
      };

      if (!postsByTarget.has(target)) {
        postsByTarget.set(target, []);
      }
      postsByTarget.get(target)!.push(postData);

      // Collect author's posts
      if (author.toBase58() === authorPubkey) {
        authorPosts.push({
          pubkey: pubkey.toBase58(),
          author: author.toBase58(),
          target,
          content,
          bid,
          timestamp
        });
      }
    } catch {
      continue;
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
 */
export async function getPostsByAuthor(authorPubkey: string): Promise<TopPost[]> {
  const conn = getConnection();
  const accounts = await conn.getProgramAccounts(PROGRAM_ID);

  const posts: TopPost[] = [];

  for (const { pubkey, account } of accounts) {
    const data = account.data;

    if (data.length < 65) continue;

    try {
      let offset = 8;

      const author = new PublicKey(data.subarray(offset, offset + 32));
      if (author.toBase58() !== authorPubkey) continue;

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

/**
 * Get all recent posts, sorted by timestamp (newest first)
 * No time filter - shows all posts on-chain
 */
export async function getRecentPosts(limit: number = 50): Promise<TopPost[]> {
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

  return posts.slice(0, limit);
}
