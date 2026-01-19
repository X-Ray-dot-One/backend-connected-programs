"use client";

import { useState, useEffect } from "react";
import { useParams } from "next/navigation";
import { AppLayout } from "@/components/app-layout";
import { useMode } from "@/contexts/mode-context";
import { useAuth } from "@/contexts/auth-context";
import * as api from "@/lib/api";
import {
  Heart,
  MessageCircle,
  Share,
  MapPin,
  Link as LinkIcon,
  ArrowLeft,
  Loader2,
  UserPlus,
  UserMinus,
  UserX,
} from "lucide-react";

interface ProfileUser {
  id: number;
  username: string | null;
  wallet_address: string;
  profile_picture: string | null;
  banner_picture: string | null;
  bio: string | null;
  location: string | null;
  website: string | null;
  created_at: string;
}

function UserProfileContent() {
  const params = useParams();
  const username = params.username as string;
  const { isShadowMode } = useMode();
  const { user: currentUser, isAuthenticated, updateFollowingCount } = useAuth();

  const [profileUser, setProfileUser] = useState<ProfileUser | null>(null);
  const [stats, setStats] = useState({ followers: 0, following: 0, posts: 0 });
  const [isFollowing, setIsFollowing] = useState(false);
  const [isOwnProfile, setIsOwnProfile] = useState(false);
  const [posts, setPosts] = useState<api.Post[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [isLoadingPosts, setIsLoadingPosts] = useState(true);
  const [isFollowLoading, setIsFollowLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [activeTab, setActiveTab] = useState<"posts" | "replies" | "likes">("posts");

  // Load user profile
  useEffect(() => {
    if (username) {
      loadProfile();
    }
  }, [username]);

  // Load posts when profile is loaded
  useEffect(() => {
    if (profileUser?.id) {
      loadPosts();
    }
  }, [profileUser?.id]);

  const loadProfile = async () => {
    setIsLoading(true);
    setError(null);
    try {
      const response = await api.getUserProfile(username);
      setProfileUser(response.user);
      setStats(response.stats);
      setIsFollowing(response.is_following);
      setIsOwnProfile(response.is_own_profile);
    } catch (err) {
      console.error("Failed to load profile:", err);
      setError(err instanceof Error ? err.message : "User not found");
    } finally {
      setIsLoading(false);
    }
  };

  const loadPosts = async () => {
    if (!profileUser?.id) return;
    setIsLoadingPosts(true);
    try {
      const response = await api.getPosts({ userId: profileUser.id });
      setPosts(response.posts || []);
    } catch (err) {
      console.error("Failed to load posts:", err);
    } finally {
      setIsLoadingPosts(false);
    }
  };

  const handleFollow = async () => {
    if (!profileUser || !isAuthenticated) return;
    setIsFollowLoading(true);
    try {
      if (isFollowing) {
        const response = await api.unfollowUser(profileUser.id);
        setIsFollowing(false);
        setStats(prev => ({ ...prev, followers: prev.followers - 1 }));
        // Update current user's following count in auth context
        if (response.my_following_count !== undefined) {
          updateFollowingCount(response.my_following_count);
        }
      } else {
        const response = await api.followUser(profileUser.id);
        setIsFollowing(true);
        setStats(prev => ({ ...prev, followers: prev.followers + 1 }));
        // Update current user's following count in auth context
        if (response.my_following_count !== undefined) {
          updateFollowingCount(response.my_following_count);
        }
      }
    } catch (err) {
      console.error("Failed to follow/unfollow:", err);
    } finally {
      setIsFollowLoading(false);
    }
  };

  const handleToggleLike = async (postId: number) => {
    try {
      const response = await api.toggleLike(postId);
      setPosts(posts.map(post =>
        post.id === postId
          ? { ...post, has_liked: response.action === 'liked', like_count: response.like_count }
          : post
      ));
    } catch (err) {
      console.error("Failed to toggle like:", err);
    }
  };

  // Loading state
  if (isLoading) {
    return (
      <div className="border-x border-border min-h-screen flex items-center justify-center">
        <Loader2 className="w-8 h-8 animate-spin text-primary" />
      </div>
    );
  }

  // Error state - user not found
  if (error || !profileUser) {
    return (
      <div className="border-x border-border min-h-screen">
        {/* Header */}
        <div className="sticky top-0 z-10 bg-background/80 backdrop-blur-sm border-b border-border">
          <div className="flex items-center gap-4 px-4 py-3">
            <a href="/" className="p-2 rounded-full hover:bg-muted transition-colors">
              <ArrowLeft className="w-5 h-5 text-foreground" />
            </a>
            <h1 className="text-xl font-bold text-foreground">Profile</h1>
          </div>
        </div>

        <div className="flex flex-col items-center justify-center p-8 mt-20">
          <UserX className="w-16 h-16 text-muted-foreground mb-4" />
          <h2 className="text-xl font-bold text-foreground mb-2">User not found</h2>
          <p className="text-muted-foreground text-center mb-4">
            @{username} doesn&apos;t exist or has been removed
          </p>
          <a
            href="/"
            className="px-6 py-2 rounded-full bg-primary text-primary-foreground hover:bg-primary/90 transition-colors"
          >
            Go to Home
          </a>
        </div>
      </div>
    );
  }

  const avatarUrl = profileUser.profile_picture || `https://api.dicebear.com/7.x/avataaars/svg?seed=${profileUser.wallet_address}`;
  const bannerUrl = profileUser.banner_picture || "https://images.unsplash.com/photo-1639762681485-074b7f938ba0?w=800&h=200&fit=crop";
  const displayName = profileUser.username || "Anonymous";
  const handle = profileUser.username ? `@${profileUser.username}` : `${profileUser.wallet_address.slice(0, 4)}...${profileUser.wallet_address.slice(-4)}`;

  return (
    <div className="border-x border-border min-h-screen">
      {/* Header */}
      <div className="sticky top-0 z-10 bg-background/80 backdrop-blur-sm border-b border-border">
        <div className="flex items-center gap-4 px-4 py-3">
          <a href="/" className="p-2 rounded-full hover:bg-muted transition-colors">
            <ArrowLeft className="w-5 h-5 text-foreground" />
          </a>
          <div>
            <h1 className="text-xl font-bold text-foreground">{displayName}</h1>
            <p className="text-sm text-muted-foreground">{stats.posts} posts</p>
          </div>
        </div>
      </div>

      {/* Banner */}
      <div className="h-32 relative">
        <img
          src={bannerUrl}
          alt="Banner"
          className="w-full h-full object-cover"
        />
      </div>

      {/* Profile Info */}
      <div className="px-4 pb-4 relative">
        {/* Avatar */}
        <div className="absolute -top-12 left-4">
          <img
            src={avatarUrl}
            alt={displayName}
            className="w-24 h-24 rounded-full border-4 border-background"
          />
        </div>

        {/* Follow Button */}
        <div className="flex justify-end pt-3">
          {isOwnProfile ? (
            <a
              href="/profile"
              className="flex items-center gap-2 px-4 py-2 rounded-full border border-border text-foreground hover:bg-muted transition-colors"
            >
              <span className="text-sm">Edit Profile</span>
            </a>
          ) : isAuthenticated ? (
            <button
              onClick={handleFollow}
              disabled={isFollowLoading}
              className={`flex items-center gap-2 px-4 py-2 rounded-full font-medium text-sm transition-colors ${
                isFollowing
                  ? "border border-border text-foreground hover:border-red-500 hover:text-red-500 hover:bg-red-500/10"
                  : "bg-foreground text-background hover:bg-foreground/90"
              }`}
            >
              {isFollowLoading ? (
                <Loader2 className="w-4 h-4 animate-spin" />
              ) : isFollowing ? (
                <>
                  <UserMinus className="w-4 h-4" />
                  <span>Following</span>
                </>
              ) : (
                <>
                  <UserPlus className="w-4 h-4" />
                  <span>Follow</span>
                </>
              )}
            </button>
          ) : null}
        </div>

        {/* Name & Handle */}
        <div className="mt-4">
          <h1 className="text-xl font-bold text-foreground">{displayName}</h1>
          <p className="text-muted-foreground">{handle}</p>
        </div>

        {/* Bio */}
        {profileUser.bio && <p className="mt-3 text-foreground">{profileUser.bio}</p>}

        {/* Meta Info */}
        <div className="flex flex-wrap gap-4 mt-3 text-sm text-muted-foreground">
          {profileUser.location && (
            <div className="flex items-center gap-1">
              <MapPin className="w-4 h-4" />
              <span>{profileUser.location}</span>
            </div>
          )}
          {profileUser.website && (
            <div className="flex items-center gap-1">
              <LinkIcon className="w-4 h-4" />
              <a href={`https://${profileUser.website}`} target="_blank" rel="noopener noreferrer" className="text-primary hover:underline">
                {profileUser.website}
              </a>
            </div>
          )}
        </div>

        {/* Stats */}
        <div className="flex gap-4 mt-4">
          <div className="flex items-center gap-1">
            <span className="font-bold text-foreground">{stats.following.toLocaleString()}</span>
            <span className="text-muted-foreground">Following</span>
          </div>
          <div className="flex items-center gap-1">
            <span className="font-bold text-foreground">{stats.followers.toLocaleString()}</span>
            <span className="text-muted-foreground">Followers</span>
          </div>
        </div>
      </div>

      {/* Tabs */}
      <div className="flex border-b border-border">
        <button
          onClick={() => setActiveTab("posts")}
          className={`flex-1 py-3 text-sm font-medium transition-colors relative ${
            activeTab === "posts" ? "text-foreground" : "text-muted-foreground hover:text-foreground"
          }`}
        >
          Posts
          {activeTab === "posts" && (
            <div className="absolute bottom-0 left-1/2 -translate-x-1/2 w-12 h-1 bg-primary rounded-full" />
          )}
        </button>
        <button
          onClick={() => setActiveTab("replies")}
          className={`flex-1 py-3 text-sm font-medium transition-colors relative ${
            activeTab === "replies" ? "text-foreground" : "text-muted-foreground hover:text-foreground"
          }`}
        >
          Replies
          {activeTab === "replies" && (
            <div className="absolute bottom-0 left-1/2 -translate-x-1/2 w-12 h-1 bg-primary rounded-full" />
          )}
        </button>
        <button
          onClick={() => setActiveTab("likes")}
          className={`flex-1 py-3 text-sm font-medium transition-colors relative ${
            activeTab === "likes" ? "text-foreground" : "text-muted-foreground hover:text-foreground"
          }`}
        >
          Likes
          {activeTab === "likes" && (
            <div className="absolute bottom-0 left-1/2 -translate-x-1/2 w-12 h-1 bg-primary rounded-full" />
          )}
        </button>
      </div>

      {/* Posts */}
      <div className="divide-y divide-border">
        {isLoadingPosts ? (
          <div className="p-8 flex justify-center">
            <Loader2 className="w-6 h-6 animate-spin text-primary" />
          </div>
        ) : posts.length === 0 ? (
          <div className="p-8 text-center">
            <p className="text-muted-foreground">No posts yet</p>
          </div>
        ) : activeTab === "posts" && posts.map((post) => (
          <div
            key={post.id}
            className="p-4 hover:bg-muted/50 transition-colors cursor-pointer"
          >
            <div className="flex gap-3">
              <img
                src={avatarUrl}
                alt={displayName}
                className="w-10 h-10 rounded-full"
              />
              <div className="flex-1">
                <div className="flex items-center gap-2">
                  <span className="font-medium text-foreground">{displayName}</span>
                  <span className="text-muted-foreground text-sm">{handle}</span>
                  <span className="text-muted-foreground text-sm">{post.time_ago}</span>
                </div>
                <p className="mt-1 text-foreground">{post.content}</p>
                <div className="flex items-center gap-6 mt-3">
                  <button
                    onClick={() => handleToggleLike(post.id)}
                    className={`flex items-center gap-1.5 transition-colors ${
                      post.has_liked ? "text-red-500" : "text-muted-foreground hover:text-red-500"
                    }`}
                  >
                    <Heart className={`w-4 h-4 ${post.has_liked ? "fill-current" : ""}`} />
                    <span className="text-sm">{post.like_count}</span>
                  </button>
                  <button className="flex items-center gap-1.5 text-muted-foreground hover:text-primary transition-colors">
                    <MessageCircle className="w-4 h-4" />
                    <span className="text-sm">{post.comment_count}</span>
                  </button>
                  <button className="flex items-center gap-1.5 text-muted-foreground hover:text-primary transition-colors">
                    <Share className="w-4 h-4" />
                  </button>
                </div>
              </div>
            </div>
          </div>
        ))}
      </div>
    </div>
  );
}

export default function UserProfilePage() {
  return (
    <AppLayout>
      <UserProfileContent />
    </AppLayout>
  );
}
