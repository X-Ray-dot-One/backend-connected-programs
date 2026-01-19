"use client";

import { useState, useEffect } from "react";
import {
  Home,
  Search,
  Bell,
  Mail,
  User,
  Wallet,
  LogOut,
  Sun,
  Moon,
  HelpCircle,
  PenSquare,
  Loader2,
} from "lucide-react";
import { useMode } from "@/contexts/mode-context";
import { FlipButton } from "./ui/flip-button";
import { usePostModal, useSearchModal } from "./app-layout";
import { useAuth } from "@/contexts/auth-context";
import { getImageUrl } from "@/lib/utils";

const navItems = [
  { icon: Bell, label: "notifications", href: "/notifications", comingSoon: false },
  { icon: Mail, label: "messages", href: "/messages", comingSoon: true },
];

// Wallet types detection
interface SolanaWallet {
  isPhantom?: boolean;
  isSolflare?: boolean;
  publicKey?: { toString(): string };
  connect(): Promise<{ publicKey: { toString(): string } }>;
  disconnect(): Promise<void>;
  on(event: string, callback: () => void): void;
  off(event: string, callback: () => void): void;
}

declare global {
  interface Window {
    solana?: SolanaWallet;
    solflare?: SolanaWallet;
    phantom?: { solana?: SolanaWallet };
  }
}

export function LeftSidebar() {
  const { isShadowMode, toggleMode } = useMode();
  const { openPostModal } = usePostModal();
  const { openSearchModal } = useSearchModal();
  const { user, isAuthenticated, login, logout, isLoading: authLoading } = useAuth();
  const [isConnecting, setIsConnecting] = useState(false);
  const [showWalletMenu, setShowWalletMenu] = useState(false);
  const [availableWallets, setAvailableWallets] = useState<string[]>([]);

  // Detect available wallets
  useEffect(() => {
    const checkWallets = () => {
      const wallets: string[] = [];
      if (window.phantom?.solana || window.solana?.isPhantom) wallets.push("phantom");
      if (window.solflare) wallets.push("solflare");
      // Generic solana provider (could be other wallets)
      if (window.solana && !window.solana.isPhantom && !window.solflare) wallets.push("other");
      setAvailableWallets(wallets);
    };

    // Check immediately and after a delay (wallets inject async)
    checkWallets();
    const timeout = setTimeout(checkWallets, 500);
    return () => clearTimeout(timeout);
  }, []);

  const getWallet = (type: string): SolanaWallet | null => {
    if (type === "phantom") return window.phantom?.solana || window.solana || null;
    if (type === "solflare") return window.solflare || null;
    if (type === "other") return window.solana || null;
    return null;
  };

  const connectWallet = async (walletType: string) => {
    const wallet = getWallet(walletType);
    if (!wallet) {
      alert(`${walletType} wallet not found. Please install it.`);
      return;
    }

    setIsConnecting(true);
    setShowWalletMenu(false);

    try {
      const response = await wallet.connect();
      const publicKey = response.publicKey.toString();

      // Login to our API with the wallet address
      await login(publicKey);
    } catch (error: unknown) {
      console.error("Wallet connection failed:", error);
      const errorMessage = error instanceof Error ? error.message : "Connection failed";
      alert(errorMessage);
    } finally {
      setIsConnecting(false);
    }
  };

  const disconnectWallet = async () => {
    try {
      // Try to disconnect from all possible wallets
      const wallets = [
        window.phantom?.solana,
        window.solana,
        window.solflare,
      ].filter(Boolean);

      for (const wallet of wallets) {
        try {
          await wallet?.disconnect();
        } catch {
          // Ignore disconnect errors
        }
      }

      await logout();
    } catch (error) {
      console.error("Disconnect failed:", error);
    }
  };

  const shortenAddress = (address: string) => {
    return `${address.slice(0, 4)}...${address.slice(-4)}`;
  };

  return (
    <aside className="fixed left-0 top-0 h-screen w-64 border-r border-primary/20 bg-card p-4 flex flex-col transition-colors duration-300">
      {/* Logo */}
      <div className="mb-6 px-3">
        <div className="flex items-center gap-3">
          <div className="w-16 h-16 rounded-xl bg-primary/10 flex items-center justify-center overflow-hidden">
            <img
              src={isShadowMode ? "/private-logo.png" : "/public-logo.png"}
              alt="X-RAY"
              className="w-[175%] h-[175%] object-contain"
            />
          </div>
          <span className="text-2xl font-bold text-foreground">X-RAY</span>
        </div>
      </div>

      {/* Mode Toggle - Switch with Icons */}
      <div className="mb-6 mx-3 flex flex-col items-center">
        <button
          onClick={toggleMode}
          className="flex items-center gap-2 p-1 rounded-full bg-muted/50 transition-colors"
        >
          {/* Sun icon */}
          <div
            className={`w-8 h-8 rounded-full flex items-center justify-center transition-all duration-300 ${
              !isShadowMode
                ? "bg-amber-500 text-white"
                : "text-muted-foreground"
            }`}
          >
            <Sun className="w-4 h-4" />
          </div>

          {/* Moon icon */}
          <div
            className={`w-8 h-8 rounded-full flex items-center justify-center transition-all duration-300 ${
              isShadowMode
                ? "bg-primary text-primary-foreground"
                : "text-muted-foreground"
            }`}
          >
            <Moon className="w-4 h-4" />
          </div>
        </button>

        <p className="text-xs text-muted-foreground mt-2 text-center">
          {isShadowMode ? "// encrypted via Arcium" : "// wallet visible"}
        </p>
      </div>

      {/* Navigation */}
      <nav className="flex-1">
        <ul className="space-y-1">
          <li>
            <a
              href="/"
              className="flex items-center gap-3 px-3 py-3 rounded-lg text-foreground hover:bg-primary/10 hover:text-primary transition-colors"
            >
              <Home className="w-5 h-5" />
              <span>home</span>
            </a>
          </li>
          <li>
            <button
              onClick={openSearchModal}
              className="w-full flex items-center gap-3 px-3 py-3 rounded-lg text-foreground hover:bg-primary/10 hover:text-primary transition-colors"
            >
              <Search className="w-5 h-5" />
              <span>explore</span>
            </button>
          </li>
          {/* Profile - disabled when not connected */}
          <li>
            {isAuthenticated ? (
              <a
                href="/profile"
                className="flex items-center gap-3 px-3 py-3 rounded-lg text-foreground hover:bg-primary/10 hover:text-primary transition-colors"
              >
                <User className="w-5 h-5" />
                <span>profile</span>
              </a>
            ) : (
              <div className="flex items-center gap-3 px-3 py-3 rounded-lg text-muted-foreground/50 cursor-not-allowed">
                <User className="w-5 h-5" />
                <span>profile</span>
              </div>
            )}
          </li>
          {navItems.map((item) => (
            <li key={item.label}>
              {item.comingSoon ? (
                <div className="flex items-center gap-3 px-3 rounded-lg text-muted-foreground">
                  <item.icon className="w-5 h-5" />
                  <FlipButton
                    frontText={item.label}
                    backText="coming soon"
                    from="top"
                    className="h-auto py-3 px-0 text-base font-normal"
                    frontClassName="bg-transparent text-muted-foreground justify-start"
                    backClassName="bg-transparent text-primary justify-start"
                  />
                </div>
              ) : isAuthenticated ? (
                <a
                  href={item.href}
                  className="flex items-center gap-3 px-3 py-3 rounded-lg text-foreground hover:bg-primary/10 hover:text-primary transition-colors"
                >
                  <item.icon className="w-5 h-5" />
                  <span>{item.label}</span>
                </a>
              ) : (
                <div className="flex items-center gap-3 px-3 py-3 rounded-lg text-muted-foreground/50 cursor-not-allowed">
                  <item.icon className="w-5 h-5" />
                  <span>{item.label}</span>
                </div>
              )}
            </li>
          ))}
        </ul>
      </nav>

      {/* How it works */}
      <a
        href="/how-it-works"
        className="flex items-center gap-3 px-3 py-3 mx-3 mb-4 rounded-lg text-primary/70 hover:text-primary hover:bg-primary/10 transition-colors"
      >
        <HelpCircle className="w-5 h-5" />
        <span>how it works</span>
      </a>

      {/* Post Button - disabled when not connected */}
      <button
        onClick={isAuthenticated ? openPostModal : undefined}
        disabled={!isAuthenticated}
        className={`flex items-center justify-center gap-2 w-full py-3 rounded-xl font-medium transition-all mb-4 ${
          isAuthenticated
            ? "bg-primary text-primary-foreground hover:bg-primary/90 shadow-lg shadow-primary/25"
            : "bg-muted text-muted-foreground cursor-not-allowed"
        }`}
      >
        <PenSquare className="w-4 h-4" />
        <span>{isShadowMode ? "shadow post" : "post"}</span>
      </button>

      {/* Wallet Section */}
      <div className="border-t border-border pt-4 relative">
        {isAuthenticated && user ? (
          <div className="flex items-center justify-between px-3 py-2">
            <a href="/profile" className="flex items-center gap-2 hover:opacity-80 transition-opacity">
              <div className="w-8 h-8 rounded-full bg-primary/20 flex items-center justify-center overflow-hidden">
                {user.profile_picture ? (
                  <img src={getImageUrl(user.profile_picture, "")} alt="" className="w-full h-full object-cover" />
                ) : (
                  <Wallet className="w-4 h-4 text-primary" />
                )}
              </div>
              <div className="text-sm">
                <p className="text-foreground">{user.username || "Anonymous"}</p>
                <p className="text-xs text-muted-foreground">{shortenAddress(user.wallet_address)}</p>
              </div>
            </a>
            <button
              onClick={disconnectWallet}
              className="text-muted-foreground hover:text-foreground transition-colors"
              title="Disconnect"
            >
              <LogOut className="w-4 h-4" />
            </button>
          </div>
        ) : (
          <>
            <button
              onClick={() => setShowWalletMenu(!showWalletMenu)}
              disabled={isConnecting || authLoading}
              className="w-full flex items-center justify-center gap-2 px-3 py-3 rounded-lg border border-primary/30 text-primary hover:bg-primary/10 transition-colors disabled:opacity-50"
            >
              {isConnecting || authLoading ? (
                <Loader2 className="w-4 h-4 animate-spin" />
              ) : (
                <Wallet className="w-4 h-4" />
              )}
              <span>{isConnecting ? "connecting..." : "connect_wallet"}</span>
            </button>

            {/* Wallet Selection Menu */}
            {showWalletMenu && (
              <div className="absolute bottom-full left-0 right-0 mb-2 mx-3 bg-card border border-border rounded-lg shadow-lg overflow-hidden z-50">
                <div className="p-2 border-b border-border">
                  <p className="text-xs text-muted-foreground text-center">Select Wallet</p>
                </div>
                {availableWallets.length === 0 ? (
                  <div className="p-3 text-center">
                    <p className="text-sm text-muted-foreground">No wallet detected</p>
                    <a
                      href="https://phantom.app/"
                      target="_blank"
                      rel="noopener noreferrer"
                      className="text-xs text-primary hover:underline"
                    >
                      Install Phantom
                    </a>
                  </div>
                ) : (
                  <div className="p-1">
                    {availableWallets.includes("phantom") && (
                      <button
                        onClick={() => connectWallet("phantom")}
                        className="w-full flex items-center gap-3 px-3 py-2 rounded hover:bg-muted transition-colors"
                      >
                        <img src="https://phantom.app/img/logo.png" alt="Phantom" className="w-5 h-5" />
                        <span className="text-sm text-foreground">Phantom</span>
                      </button>
                    )}
                    {availableWallets.includes("solflare") && (
                      <button
                        onClick={() => connectWallet("solflare")}
                        className="w-full flex items-center gap-3 px-3 py-2 rounded hover:bg-muted transition-colors"
                      >
                        <img src="https://solflare.com/favicon.ico" alt="Solflare" className="w-5 h-5" />
                        <span className="text-sm text-foreground">Solflare</span>
                      </button>
                    )}
                    {availableWallets.includes("other") && (
                      <button
                        onClick={() => connectWallet("other")}
                        className="w-full flex items-center gap-3 px-3 py-2 rounded hover:bg-muted transition-colors"
                      >
                        <Wallet className="w-5 h-5 text-primary" />
                        <span className="text-sm text-foreground">Other Wallet</span>
                      </button>
                    )}
                  </div>
                )}
              </div>
            )}
          </>
        )}
      </div>
    </aside>
  );
}
