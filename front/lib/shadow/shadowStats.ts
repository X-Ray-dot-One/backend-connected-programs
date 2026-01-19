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

export interface ShadowPostWithRank {
  pubkey: string;
  target: string;
  content: string;
  bid: bigint;
  timestamp: number;
  rank: number;
  totalForTarget: number;
}

export interface ShadowStats {
  totalPosts: number;
  totalSpent: bigint;
  avgBoost: number;      // Average bid in SOL
  avgPosition: number;   // Average rank (lower is better)
  posts: ShadowPostWithRank[];
}

// ============================================================================
// STATS FETCHING
// ============================================================================

/**
 * Get statistics for a specific shadow wallet
 * Calculates ranking position for each post within its target
 */
export async function getShadowStats(shadowPubkey: string): Promise<ShadowStats> {
  const conn = getConnection();
  const accounts = await conn.getProgramAccounts(PROGRAM_ID);

  // Collect all posts
  const allPosts: {
    pubkey: string;
    author: string;
    target: string;
    content: string;
    bid: bigint;
    timestamp: number;
  }[] = [];

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

      allPosts.push({
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

  // Group posts by target
  const postsByTarget: Record<string, typeof allPosts> = {};
  for (const post of allPosts) {
    if (!postsByTarget[post.target]) {
      postsByTarget[post.target] = [];
    }
    postsByTarget[post.target].push(post);
  }

  // Sort each group by bid descending (highest first = rank 1)
  for (const target in postsByTarget) {
    postsByTarget[target].sort((a, b) => Number(b.bid - a.bid));
  }

  // Filter posts by shadow wallet and calculate stats
  const shadowPosts: ShadowPostWithRank[] = [];
  let totalSpent = BigInt(0);
  let totalRank = 0;

  for (const target in postsByTarget) {
    const targetPosts = postsByTarget[target];
    const total = targetPosts.length;

    targetPosts.forEach((post, index) => {
      if (post.author === shadowPubkey) {
        const rank = index + 1;
        shadowPosts.push({
          pubkey: post.pubkey,
          target: post.target,
          content: post.content,
          bid: post.bid,
          timestamp: post.timestamp,
          rank,
          totalForTarget: total
        });
        totalSpent += post.bid;
        totalRank += rank;
      }
    });
  }

  // Sort shadow posts by bid descending
  shadowPosts.sort((a, b) => Number(b.bid - a.bid));

  const totalPosts = shadowPosts.length;
  const avgBoost = totalPosts > 0 ? Number(totalSpent) / totalPosts / 1_000_000_000 : 0;
  const avgPosition = totalPosts > 0 ? totalRank / totalPosts : 0;

  return {
    totalPosts,
    totalSpent,
    avgBoost,
    avgPosition,
    posts: shadowPosts
  };
}
