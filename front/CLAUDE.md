# X-RAY UI Front

## Project Overview

X-RAY is a Twitter-like social platform built on Solana with two distinct modes:
- **Public Mode**: Traditional social feed with visible wallet identities
- **Shadow Mode**: Anonymous encrypted feed via Arcium for private interactions

## Tech Stack

- **Framework**: Next.js 15 with App Router
- **Language**: TypeScript
- **Styling**: Tailwind CSS v4 with CSS variables for theming
- **Animations**: Framer Motion (motion/react)
- **Icons**: Lucide React
- **Avatars**: DiceBear API (avataaars for users, shapes for premium NDD)

## Project Structure

```
ui-front/
├── app/
│   ├── page.tsx              # Main page (home feed)
│   ├── layout.tsx            # Root layout with fonts and ModeProvider
│   ├── globals.css           # Theme CSS variables & animations
│   ├── profile/page.tsx      # Profile page (public & shadow modes)
│   ├── notifications/page.tsx # Notifications page
│   └── user/[username]/page.tsx # Dynamic user profile page
├── components/
│   ├── app-layout.tsx        # Main layout with sidebars + PostModal context
│   ├── left-sidebar.tsx      # Navigation, mode toggle, wallet connection
│   ├── main-feed.tsx         # Posts feed with tabs (recently/premium_feed)
│   ├── right-panel.tsx       # Trending/top posts, who to follow/NDD marketplace
│   ├── search-modal.tsx      # Global search modal
│   ├── post-modal.tsx        # Post creation modal (public & shadow modes)
│   ├── toast.tsx             # Toast notification system
│   ├── followers-modal.tsx   # Modal for followers/following lists
│   └── ui/
│       ├── flip-button.tsx   # Animated flip button component
│       └── spotlight.tsx     # Mouse-following spotlight effect
├── contexts/
│   ├── mode-context.tsx      # Mode state with localStorage persistence
│   └── auth-context.tsx      # Auth state, user info, stats, wallet connection
├── lib/
│   └── api.ts                # API client for PHP backend (includes X/Twitter proxy)
└── public/
    ├── public-logo.png       # Logo for public mode
    └── private-logo.png      # Logo for shadow mode
```

## Theming System

The app uses CSS variables for dual-theme support:

### Public Mode (Light)
- Background: warm white (#faf9f7)
- Primary: blue (#0077b6)
- Accent: cyan (#00b4d8)

### Shadow Mode (Dark)
- Background: deep purple-black (oklch)
- Primary: violet (oklch 0.7 0.18 280)
- Accent: magenta/pink (oklch 0.5 0.25 320)

Toggle shadow mode by adding `.shadow-mode` class to root element.

## Key Components

### AppLayout
- Wraps all pages with consistent layout
- Global identity selector (shadow mode only, top-right)
- Includes LeftSidebar and RightPanel
- Provides PostModalContext for opening post modal from anywhere

### LeftSidebar
- Logo (changes based on mode)
- Sun/Moon mode toggle
- Navigation items (home, explore, profile, notifications)
- "messages" shows "coming soon" on hover (FlipButton)
- "How it works" link
- Post/Shadow post button (opens PostModal)
- Wallet connection section

### PostModal
- Opens via usePostModal() hook from anywhere in the app
- Connected to API: creates real posts with toast notification
- User mentions use real API search (debounced) + suggested users
- Target user selection uses real API search
- **Public mode**: Simple post with @ mentions autocomplete
- **Shadow mode**:
  - Identity dropdown to select posting identity (real shadow wallets)
  - Target user selection (X-RAY user or Twitter handle) with lock/verify
  - SOL boost slider with real-time position preview from on-chain data
  - Interpolated positions (any value, not just tiers)
  - Editable position/SOL inputs (bidirectional calculation)
  - Podium colors: gold (#1), silver (#2), bronze (#3)
  - Help tooltip (?) explaining the boost system
  - **Live preview panel**: Shows how post will appear on target's wall
  - **Fund wallet button**: Transfer SOL from public wallet to shadow wallet
  - **Posting progress steps**: Shows "preparing wallet...", "creating post on-chain...", "confirming transaction...", "updating balances..."
  - Balance check before posting (opens fund modal if insufficient)
  - Bid goes to X-RAY treasury wallet (6v1xwDMjdVeDZoZBLsud5KwfsB6yiZ69eS2vFXdgM93d)

### Toast System
- `ToastProvider` wraps app in layout.tsx
- `useToast()` hook returns `showToast(message, type)`
- Types: "success" (default), "error", "info"
- Auto-dismisses after 2 seconds
- Used for: post creation, link copy, errors

### MainFeed
- Header with mode indicator
- Tabs: "Recently" and "Following" (or "Premium_feed" in shadow mode)
- Public posts: likes, comments, reposts, share
- Shadow posts: boost system (SOL), share only
- Premium NDD posts have pink usernames and colorful avatars
- @ mentions highlighted in violet (text-primary)
- **Shadow mode features**:
  - Click target username → navigates to profile with shadow tab open (?tab=shadow)
  - Own posts show "view tx" link to Solana Explorer (devnet)
  - Content has word-wrap for long messages

### RightPanel
- Search bar
- Public: Trending topics, Who to follow
- Shadow: top_posts_24h (with boosts), premium_ndd marketplace
- Premium NDD domains shown with pink names (text-pink-500)
- Footer with links

### Profile Page (/profile)
**Public Mode:**
- Banner, avatar, bio, location, website
- Stats: followers, following
- Tabs: Posts, Replies, Likes, shadow_mentions
- shadow_mentions tab: shows anonymous posts mentioning you (purple theme)
- Edit profile modal with location dropdown and website validation (scam detection)

**Shadow Mode:**
- Anonymous identity avatar with initial
- Stats: total posts, avg boost (SOL), avg position
- Leaderboard line: total spent (amber color) with rank (#45)
- Tabs: top_10 (sortable by boost/position), all_posts, history
- History shows position losses (when posts get passed)

### User Profile Page (/user/[username])
- Dynamic route for viewing other users' profiles
- **Tabs**: Posts, Replies, Shadow (with count badge)
- Shadow tab shows on-chain posts targeting this user (sorted by bid)
- Supports URL param `?tab=shadow` to open shadow tab directly
- Shadow posts display: author name, timestamp, content, bid amount, rank

### X Profile Page (/user/x/[username])
- Profile page for X/Twitter users (fetched via proxy)
- Shows shadow posts targeting their X handle
- Banner, avatar, name, bio from X profile
- Stats: total posts, total bid value, unique posters

### Notifications Page
**Public Mode:** likes, follows, comments, mentions, shadow_mentions
- shadow_mentions appear with purple theme (anonymous posts about you)

**Shadow Mode:** position updates (when posts lose rank)

## Color Conventions

- **Premium NDD usernames**: `text-pink-500` (bright pink)
- **@ mentions**: `text-primary` (violet in shadow mode)
- **Non-premium usernames**: `text-muted-foreground`
- **Boosts/prices**: `text-primary`
- **Total spent leaderboard**: `text-amber-500` (gold/amber)
- **Shadow posts section**: purple theme (purple-100, purple-400, purple-600)
- **Position podium colors**:
  - #1: gold (amber-400/amber-500)
  - #2: silver (slate-300/slate-400)
  - #3: bronze (amber-600/amber-700)

## Data Structures

### Public Post
```typescript
{
  id: number,
  username: string,
  handle: string,
  avatar: string,
  content: string,
  time: string,
  likes: number,
  comments: number,
  reposts: number
}
```

### Shadow Post
```typescript
{
  id: number,
  username: string,
  content: string,
  time: string,
  timeValue: number,    // hours ago (for sorting)
  boost: number,        // SOL amount (not "bid" - less casino-like)
  position: number,     // ranking position on target's wall
  totalBoosters: number,
  isPremiumNdd: boolean
}
```

### Boost Tiers (SOL to Position mapping)
```typescript
const boostTiers = [
  { sol: 0.05, position: 1000 },
  { sol: 0.1, position: 500 },
  { sol: 0.25, position: 250 },
  { sol: 0.5, position: 100 },
  { sol: 1, position: 50 },
  { sol: 2.5, position: 20 },
  { sol: 5, position: 10 },
  { sol: 10, position: 5 },
  { sol: 25, position: 3 },
  { sol: 50, position: 2 },
  { sol: 100, position: 1 },
];
// Positions are interpolated between tiers (linear)
```

### Shadow Profile
```typescript
{
  identity: string,       // e.g., "shadow_7x3k"
  totalPosts: number,
  averageBoost: number,   // in SOL
  averagePosition: number,
  totalSpent: number,     // in SOL
  leaderboardRank: number
}
```

### NDD Marketplace Item
```typescript
{
  domain: string,  // e.g., "defi.anon"
  price: string    // e.g., "45 SOL"
}
```

## API Functions (lib/api.ts)

### Auth
- `walletAuth(walletAddress)` - Authenticate with Solana wallet
- `logout()` - End session
- `getMe()` - Get current user info

### Profile
- `getProfile(username?, userId?)` - Get user profile
- `getUserProfile(username)` - Get public user profile
- `updateProfile(data)` - Update profile fields

### Follow
- `followUser(userId)` - Follow a user
- `unfollowUser(userId)` - Unfollow a user
- `getFollowers(userId)` - Get followers list
- `getFollowing(userId)` - Get following list

### Posts
- `getPosts(options)` - Get posts (feed, user_id, limit)
- `getPost(postId)` - Get single post
- `createPost(content, options)` - Create post
- `toggleLike(postId)` - Like/unlike post

### Comments
- `getComments(postId)` - Get comments
- `addComment(postId, content)` - Add comment
- `deleteComment(commentId, postId)` - Delete comment
- `toggleCommentLike(commentId)` - Like/unlike comment

### Search
- `searchUsers(query)` - Search users
- `getSuggestedUsers()` - Get suggested users

### External
- `getXProfile(username)` - Get X/Twitter profile via proxy

### Shadow Wallets
- `getShadowWalletCount(userId)` - Get wallet count for hashed user ID
- `incrementShadowWalletCount(userId)` - Increment wallet count (+1)
- `createShadowWallet(shadowPubkey, name)` - Register new shadow wallet with name
- `shadowWalletNameExists(name)` - Check if name already exists
- `getShadowWalletName(shadowPubkey)` - Get name by public key (returns null if not found)

## Commands

```bash
npm run dev      # Start development server
npm run build    # Build for production
npm run start    # Start production server
npm run lint     # Run ESLint
```

## Shadow System (lib/shadow/)

### postService.ts
- `createShadowPost(keypair, target, content, bid)` - Create on-chain post (bid transfer is atomic via CPI)
- `transferToShadowWallet(from, to, amount, signTx)` - Fund shadow wallet from public wallet
- `getWalletBalance(publicKey)` - Get balance in lamports
- `formatSol(lamports)` / `solToLamports(sol)` - Conversion helpers
- `buildTargetUrl(username, platform)` - Build target URL for post

### Auto-Funding Flow (post-modal.tsx)
When user clicks "Shadow Post":
1. Check if shadow wallet has enough balance (bid + 0.01 SOL buffer)
2. If not enough: auto-fund from public wallet
   - Check public wallet balance first
   - Transfer exact amount needed + small buffer
   - Wait for transaction confirmation (polling up to 10s)
3. Create post on-chain with shadow keypair

### targetStats.ts
- `getTargetStats(target)` - Get all bids for a target (for position preview)
- `calculateBidPosition(stats, bidAmount)` - Calculate position for a bid
- `getBidPositionPreview(target, bid)` - Combined preview function

### targetProfile.ts
- `fetchXProfile(targetUrl)` - Fetch X/Twitter profile via backend proxy
- `getPostsForTarget(targetUrl)` - Get all on-chain posts for a target
- `extractXUsername(url)` / `extractXrayUsername(url)` - Parse target URLs

### shadowWallet.ts
- `hashUserId(publicKey)` - Hash wallet address for DB key
- `generateShadowWallet(signature, userId, index)` - Derive shadow keypair
- `generateShadowName(existingNames)` - Generate unique shadow name

### Constants
- **Program ID**: `5gPGpcXTq1R2chrEP9qPaFw4i1ge5ZgG2n7xnrUGZHPk` (devnet)
- **RPC URL**: `https://devnet.helius-rpc.com/?api-key=64cda369-a212-4064-8133-e0e6827644b7`
- **Treasury**: `6v1xwDMjdVeDZoZBLsud5KwfsB6yiZ69eS2vFXdgM93d`

### Account Order (CRITICAL)
When creating posts, accounts must be in this exact order (matches Rust struct):
1. `author` (signer, writable)
2. `treasury` (writable)
3. `post` PDA (writable)
4. `system_program`

## Notes

- Premium NDD domains end with `.anon`
- DiceBear shapes avatars use pink/purple backgrounds for premium NDD
- Shadow mode removes comments from posts (boost + share only)
- The `premiumNddPosts` array filters posts where `isPremiumNdd === true`
- Use "boost" instead of "bid" (less casino-like terminology)
- Mode state persists via localStorage
- Identity selector is global (visible on all pages in shadow mode)
- Position history only shows losses (when your post gets passed)
- Shadow identities: auto-generated names like "anon_abc12"
- Shadow posts can target X-RAY users or Twitter handles
- Website validation in profile edit detects scam domains/patterns
- Shadow wallets are derived from signature + hashed user ID + index
- Session persists unlock state via sessionStorage
