"use client";

import { AppLayout } from "@/components/app-layout";
import {
  Shield,
  EyeOff,
  Wallet,
  Key,
  Lock,
  MessageSquare,
  TrendingUp,
  Zap,
  Crown,
  ChevronDown,
  ChevronUp,
  Hash,
  Send,
  DollarSign,
} from "lucide-react";
import { useState } from "react";

function AccordionItem({
  title,
  icon: Icon,
  children,
  defaultOpen = false
}: {
  title: string;
  icon: React.ElementType;
  children: React.ReactNode;
  defaultOpen?: boolean;
}) {
  const [isOpen, setIsOpen] = useState(defaultOpen);

  return (
    <div className="border border-border rounded-xl overflow-hidden bg-card/50">
      <button
        onClick={() => setIsOpen(!isOpen)}
        className="w-full flex items-center justify-between p-4 hover:bg-muted/50 transition-colors"
      >
        <div className="flex items-center gap-3">
          <div className="p-2 rounded-lg bg-primary/10">
            <Icon className="w-5 h-5 text-primary" />
          </div>
          <span className="font-medium text-foreground">{title}</span>
        </div>
        {isOpen ? (
          <ChevronUp className="w-5 h-5 text-muted-foreground" />
        ) : (
          <ChevronDown className="w-5 h-5 text-muted-foreground" />
        )}
      </button>
      {isOpen && (
        <div className="px-4 pb-4 text-sm text-muted-foreground space-y-3">
          {children}
        </div>
      )}
    </div>
  );
}

function FlowStep({ number, title, description }: { number: number; title: string; description: string }) {
  return (
    <div className="flex gap-3">
      <div className="flex-shrink-0 w-8 h-8 rounded-full bg-primary/20 flex items-center justify-center text-primary font-bold text-sm">
        {number}
      </div>
      <div>
        <div className="font-medium text-foreground">{title}</div>
        <div className="text-muted-foreground text-sm">{description}</div>
      </div>
    </div>
  );
}

function CodeBlock({ children }: { children: string }) {
  return (
    <div className="bg-background/80 border border-border rounded-lg p-3 font-mono text-xs overflow-x-auto">
      <pre className="text-muted-foreground whitespace-pre-wrap">{children}</pre>
    </div>
  );
}

export default function HowItWorksPage() {
  return (
    <AppLayout>
      <div className="max-w-2xl mx-auto py-6 px-4">
        {/* Header */}
        <div className="mb-8">
          <div className="flex items-center gap-2 mb-2">
            <Shield className="w-6 h-6 text-primary" />
            <h1 className="text-2xl font-bold text-primary">// how it works</h1>
          </div>
          <p className="text-muted-foreground">
            X-RAY operates in two modes: public and shadow. This guide explains how the private/shadow system works.
          </p>
        </div>

        {/* Quick Overview */}
        <div className="p-4 rounded-xl border border-primary/30 bg-primary/5 mb-8">
          <div className="flex items-center gap-2 mb-2">
            <EyeOff className="w-5 h-5 text-primary" />
            <span className="font-medium text-primary">shadow mode</span>
          </div>
          <p className="text-sm text-muted-foreground">
            Anonymous posting with derived wallets. Posts are stored on Solana blockchain. Your public wallet is never linked to your shadow identity.
          </p>
        </div>

        {/* Main Content - Accordions */}
        <div className="space-y-4">

          {/* Shadow Wallets */}
          <AccordionItem title="shadow wallets" icon={Wallet} defaultOpen={true}>
            <p>
              Shadow wallets are anonymous identities derived from your main wallet. They cannot be traced back to your public wallet.
            </p>

            <div className="mt-4 space-y-3">
              <FlowStep
                number={1}
                title="sign a message"
                description="You sign 'X-RAY Shadow Wallet Access' with your main wallet"
              />
              <FlowStep
                number={2}
                title="derive keypairs"
                description="Your signature + hashed public key = unique seed for each shadow wallet"
              />
              <FlowStep
                number={3}
                title="generate identities"
                description="Each index (0, 1, 2...) creates a different anonymous wallet"
              />
            </div>

            <div className="mt-4">
              <div className="text-xs text-muted-foreground mb-2">derivation formula:</div>
              <CodeBlock>{`seed = SHA256(signature + hashedUserId + walletIndex)
keypair = Ed25519.fromSeed(seed)`}</CodeBlock>
            </div>

            <div className="mt-4 p-3 rounded-lg bg-primary/10 border border-primary/20">
              <div className="flex items-start gap-2">
                <Lock className="w-4 h-4 text-primary mt-0.5 flex-shrink-0" />
                <div className="text-xs">
                  <span className="text-primary font-medium">one-way derivation:</span>{" "}
                  <span className="text-muted-foreground">
                    shadow wallet addresses cannot reveal your public wallet. Only you (with your private key) can derive your shadow wallets.
                  </span>
                </div>
              </div>
            </div>
          </AccordionItem>

          {/* Anonymous Funding */}
          <AccordionItem title="anonymous funding" icon={Zap}>
            <p>
              To ensure complete anonymity, shadow wallets are funded through Privacy Cash - breaking any link between your public wallet and shadow identity.
            </p>

            <div className="mt-4 space-y-3">
              <FlowStep
                number={1}
                title="deposit to Privacy Cash"
                description="Send SOL from your public wallet to Privacy Cash pool"
              />
              <FlowStep
                number={2}
                title="wait & withdraw"
                description="After some time, withdraw to your shadow wallet address"
              />
              <FlowStep
                number={3}
                title="untraceable funds"
                description="No on-chain link between your public and shadow wallet"
              />
            </div>

            <div className="mt-4 p-3 rounded-lg bg-amber-500/10 border border-amber-500/20">
              <div className="flex items-start gap-2">
                <Zap className="w-4 h-4 text-amber-400 mt-0.5 flex-shrink-0" />
                <div className="text-xs">
                  <span className="text-amber-400 font-medium">why it matters:</span>{" "}
                  <span className="text-muted-foreground">
                    without Privacy Cash, anyone could trace where your shadow wallet got its funds from, revealing your real identity.
                  </span>
                </div>
              </div>
            </div>
          </AccordionItem>

          {/* Shadow Posting */}
          <AccordionItem title="shadow posting" icon={MessageSquare}>
            <p>
              Shadow posts are stored directly on the Solana blockchain, not in any database. Each post includes a SOL bid.
            </p>

            <div className="mt-4 space-y-3">
              <FlowStep
                number={1}
                title="select target"
                description="Choose who you're posting about (X/Twitter handle or X-RAY user)"
              />
              <FlowStep
                number={2}
                title="write content"
                description="Your message (max 512 characters)"
              />
              <FlowStep
                number={3}
                title="set bid"
                description="Higher bids = higher position on the target's wall"
              />
              <FlowStep
                number={4}
                title="fund & post"
                description="Your shadow wallet is auto-funded, then posts on-chain"
              />
            </div>

            <div className="mt-4">
              <div className="text-xs text-muted-foreground mb-2">on-chain storage (PDA):</div>
              <CodeBlock>{`["post", shadow_pubkey, target_bytes] → Post Account
├─ author: shadow wallet pubkey
├─ target: who the post is about
├─ content: your message
├─ bid: lamports (1 SOL = 1B lamports)
└─ timestamp: unix timestamp`}</CodeBlock>
            </div>

            <div className="mt-4 p-3 rounded-lg bg-amber-500/10 border border-amber-500/20">
              <div className="flex items-start gap-2">
                <Zap className="w-4 h-4 text-amber-400 mt-0.5 flex-shrink-0" />
                <div className="text-xs">
                  <span className="text-amber-400 font-medium">atomic transaction:</span>{" "}
                  <span className="text-muted-foreground">
                    post creation and bid transfer happen in the same transaction. impossible to post without paying.
                  </span>
                </div>
              </div>
            </div>
          </AccordionItem>

          {/* Bidding System */}
          <AccordionItem title="bidding system" icon={TrendingUp}>
            <p>
              Your bid determines your position on a target's wall. Higher bid = higher position = more visibility.
            </p>

            <div className="mt-4 grid grid-cols-3 gap-2 text-center">
              <div className="p-3 rounded-lg bg-amber-400/20 border border-amber-400/30">
                <div className="text-amber-400 font-bold">#1</div>
                <div className="text-xs text-muted-foreground">highest bid</div>
              </div>
              <div className="p-3 rounded-lg bg-slate-400/20 border border-slate-400/30">
                <div className="text-slate-400 font-bold">#2</div>
                <div className="text-xs text-muted-foreground">2nd highest</div>
              </div>
              <div className="p-3 rounded-lg bg-amber-600/20 border border-amber-600/30">
                <div className="text-amber-600 font-bold">#3</div>
                <div className="text-xs text-muted-foreground">3rd highest</div>
              </div>
            </div>

            <div className="mt-4">
              <div className="text-xs text-muted-foreground mb-2">position calculation:</div>
              <CodeBlock>{`position = count(bids >= your_bid) + 1

example: bids on @elonmusk = [0.1, 0.5, 1.0] SOL
├─ your bid 1.5 SOL → position #1 (top)
├─ your bid 1.0 SOL → position #1 (tied)
├─ your bid 0.5 SOL → position #2
└─ your bid 0.1 SOL → position #3`}</CodeBlock>
            </div>

            <div className="mt-4 flex items-center gap-2 text-sm">
              <DollarSign className="w-4 h-4 text-primary" />
              <span>minimum bid: <span className="text-primary font-mono">0.007 SOL</span></span>
            </div>
          </AccordionItem>

          {/* Premium NDD */}
          <AccordionItem title="premium identities (NDD)" icon={Crown}>
            <p>
              Premium identities (NDDs) are special shadow wallet names you can purchase with SOL. They include custom profile pictures and a crown badge.
            </p>

            <div className="mt-4 space-y-3">
              <FlowStep
                number={1}
                title="browse marketplace"
                description="See available NDDs with prices and profile pictures"
              />
              <FlowStep
                number={2}
                title="select & pay"
                description="Choose an NDD and pay with your shadow wallet"
              />
              <FlowStep
                number={3}
                title="verification"
                description="Server verifies the payment on-chain and assigns NDD"
              />
            </div>

            <div className="mt-4 p-3 rounded-lg bg-pink-500/10 border border-pink-500/20">
              <div className="flex items-center gap-2">
                <Crown className="w-4 h-4 text-pink-400" />
                <span className="text-pink-400 font-medium text-sm">premium benefits:</span>
              </div>
              <ul className="mt-2 text-xs text-muted-foreground space-y-1 ml-6">
                <li>• custom profile picture</li>
                <li>• crown badge on all posts</li>
                <li>• pink highlighted name</li>
                <li>• + more rewards and benefits</li>
              </ul>
            </div>
          </AccordionItem>

          {/* Private Messages */}
          <AccordionItem title="private messages" icon={Send}>
            <p>
              Send encrypted messages to any shadow identity. Your conversations are fully private and stored on-chain.
            </p>

            <div className="mt-4 space-y-3">
              <FlowStep
                number={1}
                title="find a contact"
                description="Search for any shadow identity by name"
              />
              <FlowStep
                number={2}
                title="send message"
                description="Your message is encrypted end-to-end with X25519"
              />
              <FlowStep
                number={3}
                title="on-chain storage"
                description="Encrypted message stored on Solana via Arcium MPC"
              />
            </div>

            <div className="mt-4">
              <div className="text-xs text-muted-foreground mb-2">layer 1: X25519 encryption</div>
              <CodeBlock>{`// message encrypted with recipient's public key
encrypted = nacl.box(message, nonce, recipientPubKey, senderSecretKey)
// only recipient can decrypt with their private key`}</CodeBlock>
            </div>

            <div className="mt-4">
              <div className="text-xs text-muted-foreground mb-2">layer 2: Arcium MPC</div>
              <CodeBlock>{`// sender & recipient identities hidden on-chain
arcium.encrypt(senderHash, recipientHash)
// no one can see who's talking to whom`}</CodeBlock>
            </div>

            <div className="mt-4 p-3 rounded-lg bg-green-500/10 border border-green-500/20">
              <div className="flex items-start gap-2">
                <Shield className="w-4 h-4 text-green-400 mt-0.5 flex-shrink-0" />
                <div className="text-xs">
                  <span className="text-green-400 font-medium">double encryption:</span>{" "}
                  <span className="text-muted-foreground">
                    message content is encrypted (X25519), and sender/recipient identities are hidden on-chain (Arcium MPC). no one can see who's talking to whom.
                  </span>
                </div>
              </div>
            </div>
          </AccordionItem>

          {/* Security Summary */}
          <AccordionItem title="security guarantees" icon={Shield}>
            <div className="space-y-3">
              <div className="flex items-start gap-3">
                <div className="p-1.5 rounded bg-green-500/20">
                  <Lock className="w-3 h-3 text-green-400" />
                </div>
                <div>
                  <div className="text-sm font-medium text-foreground">one-way privacy</div>
                  <div className="text-xs text-muted-foreground">
                    shadow wallets cannot be traced to your public wallet
                  </div>
                </div>
              </div>

              <div className="flex items-start gap-3">
                <div className="p-1.5 rounded bg-green-500/20">
                  <Zap className="w-3 h-3 text-green-400" />
                </div>
                <div>
                  <div className="text-sm font-medium text-foreground">atomic transactions</div>
                  <div className="text-xs text-muted-foreground">
                    all operations (post + bid + split) happen in one transaction or none
                  </div>
                </div>
              </div>

              <div className="flex items-start gap-3">
                <div className="p-1.5 rounded bg-green-500/20">
                  <Key className="w-3 h-3 text-green-400" />
                </div>
                <div>
                  <div className="text-sm font-medium text-foreground">hardcoded addresses</div>
                  <div className="text-xs text-muted-foreground">
                    revenue wallets are in the Rust bytecode - cannot be modified
                  </div>
                </div>
              </div>

              <div className="flex items-start gap-3">
                <div className="p-1.5 rounded bg-green-500/20">
                  <Hash className="w-3 h-3 text-green-400" />
                </div>
                <div>
                  <div className="text-sm font-medium text-foreground">replay protection</div>
                  <div className="text-xs text-muted-foreground">
                    transaction signatures are stored to prevent replay attacks
                  </div>
                </div>
              </div>

              <div className="flex items-start gap-3">
                <div className="p-1.5 rounded bg-green-500/20">
                  <Shield className="w-3 h-3 text-green-400" />
                </div>
                <div>
                  <div className="text-sm font-medium text-foreground">end-to-end encryption</div>
                  <div className="text-xs text-muted-foreground">
                    private messages use X25519 + Arcium MPC - no one can read them
                  </div>
                </div>
              </div>
            </div>
          </AccordionItem>

        </div>

        {/* Footer */}
        <div className="mt-8 text-center text-xs text-muted-foreground">
          <p>built on Solana • powered by Arcium MPC & Privacy Cash</p>
        </div>
      </div>
    </AppLayout>
  );
}
