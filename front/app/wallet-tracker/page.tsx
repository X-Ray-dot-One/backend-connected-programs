"use client";

import { useState, useEffect } from "react";
import { Connection, PublicKey, LAMPORTS_PER_SOL } from "@solana/web3.js";
import { Loader2, RefreshCw } from "lucide-react";

const WALLETS = [
  { address: "69TwH2GJiBSA8Eo3DunPGsXGWjNFY267zRrpHptYWCuC", label: "EMILE" },
  { address: "EbhZhYumUZyHQCPbeaLLt57SS2obHiFdp7TMLjUBBqcD", label: "GUARDIAN" },
  { address: "HxtzFZhjNCsQb9ZqEyK8xYftqv6j6AM2MAT6uwWG3KYd", label: "SACHA" },
  { address: "6v1xwDMjdVeDZoZBLsud5KwfsB6yiZ69eS2vFXdgM93d", label: "TREASURY", isTreasury: true },
];

type Network = "devnet" | "mainnet";

const RPC_URLS: Record<Network, string> = {
  devnet: process.env.NEXT_PUBLIC_SOLANA_RPC_URL || "https://api.devnet.solana.com",
  mainnet: "https://mainnet.helius-rpc.com/?api-key=64cda369-a212-4064-8133-e0e6827644b7",
};

const EXPLORER_CLUSTERS: Record<Network, string> = {
  devnet: "?cluster=devnet",
  mainnet: "",
};

export default function WalletTrackerPage() {
  const [balances, setBalances] = useState<Map<string, number | null>>(new Map());
  const [loading, setLoading] = useState(true);
  const [lastUpdate, setLastUpdate] = useState<Date | null>(null);
  const [network, setNetwork] = useState<Network>("devnet");

  const fetchBalances = async () => {
    setLoading(true);
    const connection = new Connection(RPC_URLS[network], "confirmed");
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
  }, [network]);

  const totalBalance = Array.from(balances.values()).reduce((sum: number, bal) => sum + (bal || 0), 0);

  return (
    <div className="min-h-screen bg-background p-8">
      <div className="max-w-2xl mx-auto">
        <div className="flex items-center justify-between mb-8">
          <h1 className="text-2xl font-bold text-foreground">Wallet Tracker</h1>
          <div className="flex items-center gap-4">
            {/* Network Toggle */}
            <div className="flex items-center gap-2 bg-card border border-border rounded-lg p-1">
              <button
                onClick={() => setNetwork("devnet")}
                className={`px-3 py-1.5 rounded text-sm font-medium transition-colors ${
                  network === "devnet"
                    ? "bg-primary text-primary-foreground"
                    : "text-muted-foreground hover:text-foreground"
                }`}
              >
                Devnet
              </button>
              <button
                onClick={() => setNetwork("mainnet")}
                className={`px-3 py-1.5 rounded text-sm font-medium transition-colors ${
                  network === "mainnet"
                    ? "bg-primary text-primary-foreground"
                    : "text-muted-foreground hover:text-foreground"
                }`}
              >
                Mainnet
              </button>
            </div>
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
                  href={`https://explorer.solana.com/address/${wallet.address}${EXPLORER_CLUSTERS[network]}`}
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
                href={`https://explorer.solana.com/address/${wallet.address}${EXPLORER_CLUSTERS[network]}`}
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
