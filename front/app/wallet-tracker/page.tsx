"use client";

import { useState, useEffect } from "react";
import { Connection, PublicKey, LAMPORTS_PER_SOL } from "@solana/web3.js";
import { Loader2, RefreshCw } from "lucide-react";

const WALLETS = [
  { address: "69TwH2GJiBSA8Eo3DunPGsXGWjNFY267zRrpHptYWCuC", label: "GRINGO" },
  { address: "EbhZhYumUZyHQCPbeaLLt57SS2obHiFdp7TMLjUBBqcD", label: "GUARDIAN" },
  { address: "HxtzFZhjNCsQb9ZqEyK8xYftqv6j6AM2MAT6uwWG3KYd", label: "SACHA" },
  { address: "6v1xwDMjdVeDZoZBLsud5KwfsB6yiZ69eS2vFXdgM93d", label: "TREASURY", isTreasury: true },
];

const RPC_URL = process.env.NEXT_PUBLIC_SOLANA_RPC_URL || "https://api.devnet.solana.com";

export default function WalletTrackerPage() {
  const [balances, setBalances] = useState<Map<string, number | null>>(new Map());
  const [loading, setLoading] = useState(true);
  const [lastUpdate, setLastUpdate] = useState<Date | null>(null);

  const fetchBalances = async () => {
    setLoading(true);
    const connection = new Connection(RPC_URL, "confirmed");
    const newBalances = new Map<string, number | null>();

    for (const wallet of WALLETS) {
      try {
        const pubkey = new PublicKey(wallet.address);
        const balance = await connection.getBalance(pubkey);
        newBalances.set(wallet.address, balance / LAMPORTS_PER_SOL);
      } catch (err) {
        console.error(`Failed to fetch balance for ${wallet.address}:`, err);
        newBalances.set(wallet.address, null);
      }
    }

    setBalances(newBalances);
    setLastUpdate(new Date());
    setLoading(false);
  };

  useEffect(() => {
    fetchBalances();
    // Refresh every 30 seconds
    const interval = setInterval(fetchBalances, 30000);
    return () => clearInterval(interval);
  }, []);

  const totalBalance = Array.from(balances.values()).reduce((sum, bal) => sum + (bal || 0), 0);

  return (
    <div className="min-h-screen bg-background p-8">
      <div className="max-w-2xl mx-auto">
        <div className="flex items-center justify-between mb-8">
          <h1 className="text-2xl font-bold text-foreground">Wallet Tracker (Devnet)</h1>
          <button
            onClick={fetchBalances}
            disabled={loading}
            className="flex items-center gap-2 px-4 py-2 bg-primary text-primary-foreground rounded-lg hover:bg-primary/90 disabled:opacity-50"
          >
            {loading ? (
              <Loader2 className="w-4 h-4 animate-spin" />
            ) : (
              <RefreshCw className="w-4 h-4" />
            )}
            Refresh
          </button>
        </div>

        <div className="space-y-4">
          {WALLETS.filter(w => !w.isTreasury).map((wallet) => {
            const balance = balances.get(wallet.address);
            return (
              <div
                key={wallet.address}
                className="p-4 bg-card border border-border rounded-lg"
              >
                <div className="flex items-center justify-between">
                  <div>
                    <p className="font-medium text-foreground">{wallet.label}</p>
                    <p className="text-xs text-muted-foreground font-mono">
                      {wallet.address}
                    </p>
                  </div>
                  <div className="text-right">
                    {balance === null ? (
                      <p className="text-red-500">Error</p>
                    ) : balance === undefined ? (
                      <Loader2 className="w-4 h-4 animate-spin text-muted-foreground" />
                    ) : (
                      <p className="text-xl font-bold text-primary">
                        {balance.toFixed(4)} SOL
                      </p>
                    )}
                  </div>
                </div>
                <a
                  href={`https://explorer.solana.com/address/${wallet.address}?cluster=devnet`}
                  target="_blank"
                  rel="noopener noreferrer"
                  className="text-xs text-primary hover:underline mt-2 inline-block"
                >
                  View on Explorer →
                </a>
              </div>
            );
          })}
        </div>

        {/* Treasury */}
        {WALLETS.filter(w => w.isTreasury).map((wallet) => {
          const balance = balances.get(wallet.address);
          return (
            <div
              key={wallet.address}
              className="mt-6 p-4 bg-amber-500/10 border border-amber-500/30 rounded-lg"
            >
              <div className="flex items-center justify-between">
                <div>
                  <p className="font-medium text-amber-500">{wallet.label}</p>
                  <p className="text-xs text-muted-foreground font-mono">
                    {wallet.address}
                  </p>
                </div>
                <div className="text-right">
                  {balance === null ? (
                    <p className="text-red-500">Error</p>
                  ) : balance === undefined ? (
                    <Loader2 className="w-4 h-4 animate-spin text-muted-foreground" />
                  ) : (
                    <p className="text-2xl font-bold text-amber-500">
                      {balance.toFixed(4)} SOL
                    </p>
                  )}
                </div>
              </div>
              <a
                href={`https://explorer.solana.com/address/${wallet.address}?cluster=devnet`}
                target="_blank"
                rel="noopener noreferrer"
                className="text-xs text-amber-500 hover:underline mt-2 inline-block"
              >
                View on Explorer →
              </a>
            </div>
          );
        })}

        {/* Total */}
        <div className="mt-6 p-4 bg-primary/10 border border-primary/30 rounded-lg">
          <div className="flex items-center justify-between">
            <p className="font-medium text-foreground">Total Balance</p>
            <p className="text-2xl font-bold text-primary">
              {totalBalance.toFixed(4)} SOL
            </p>
          </div>
        </div>

        {lastUpdate && (
          <p className="text-xs text-muted-foreground text-center mt-4">
            Last updated: {lastUpdate.toLocaleTimeString()}
          </p>
        )}
      </div>
    </div>
  );
}
