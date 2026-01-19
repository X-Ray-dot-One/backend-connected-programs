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
// TARGET STATS
// ============================================================================

export interface TargetStats {
  count: number;
  bids: bigint[];
}

/**
 * Get all bids for a specific target
 * Used to calculate bid position preview when creating a post
 * @param target - Full target URL (e.g., "https://x.com/elonmusk")
 */
export async function getTargetStats(target: string): Promise<TargetStats> {
  const conn = getConnection();
  const accounts = await conn.getProgramAccounts(PROGRAM_ID);

  const bids: bigint[] = [];

  for (const { account } of accounts) {
    const data = account.data;

    if (data.length < 65) continue;

    try {
      let offset = 8; // Skip discriminator
      offset += 32;   // Skip author

      // Target (4 bytes len + string)
      const targetLen = new DataView(data.buffer, data.byteOffset + offset, 4).getUint32(0, true);
      if (targetLen > 64 || offset + 4 + targetLen > data.length) continue;
      offset += 4;
      const postTarget = new TextDecoder().decode(data.subarray(offset, offset + targetLen));
      offset += targetLen;

      // Only count posts for this target
      if (postTarget !== target) continue;

      // Skip content
      const contentLen = new DataView(data.buffer, data.byteOffset + offset, 4).getUint32(0, true);
      if (contentLen > 512 || offset + 4 + contentLen > data.length) continue;
      offset += 4 + contentLen;

      // Bid (8 bytes u64)
      const bid = new DataView(data.buffer, data.byteOffset + offset, 8).getBigUint64(0, true);
      bids.push(bid);
    } catch {
      continue;
    }
  }

  // Sort ascending (lowest first)
  bids.sort((a, b) => Number(a - b));

  return {
    count: bids.length,
    bids
  };
}

/**
 * Calculate what position a bid would get for a target
 * @param targetStats - Stats from getTargetStats()
 * @param bidAmount - Your bid amount in lamports
 * @returns Position (1 = top, higher = lower rank)
 */
export function calculateBidPosition(targetStats: TargetStats, bidAmount: bigint): number {
  // Count how many existing bids are >= your bid
  const higherBids = targetStats.bids.filter(b => b >= bidAmount).length;
  return higherBids + 1;
}

/**
 * Get bid position preview for a target
 */
export async function getBidPositionPreview(target: string, bidAmount: bigint): Promise<{
  position: number;
  totalPosts: number;
}> {
  const stats = await getTargetStats(target);
  const position = calculateBidPosition(stats, bidAmount);

  return {
    position,
    totalPosts: stats.count + 1 // +1 because your post will be added
  };
}
