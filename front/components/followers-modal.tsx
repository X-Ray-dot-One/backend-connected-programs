"use client";

import { useState, useEffect } from "react";
import { X, Loader2, UserPlus, UserMinus } from "lucide-react";
import { motion, AnimatePresence } from "motion/react";
import * as api from "@/lib/api";
import { getImageUrl } from "@/lib/utils";
import { useAuth } from "@/contexts/auth-context";

interface FollowersModalProps {
  isOpen: boolean;
  onClose: () => void;
  userId: number;
  username: string;
  initialTab: "followers" | "following";
  followersCount: number;
  followingCount: number;
}

export function FollowersModal({
  isOpen,
  onClose,
  userId,
  username,
  initialTab,
  followersCount,
  followingCount,
}: FollowersModalProps) {
  const { user: currentUser, isAuthenticated } = useAuth();
  const [activeTab, setActiveTab] = useState<"followers" | "following">(initialTab);
  const [users, setUsers] = useState<api.FollowUser[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [followingStates, setFollowingStates] = useState<Record<number, boolean>>({});
  const [loadingStates, setLoadingStates] = useState<Record<number, boolean>>({});

  useEffect(() => {
    setActiveTab(initialTab);
  }, [initialTab]);

  useEffect(() => {
    if (isOpen) {
      loadUsers();
    }
  }, [isOpen, activeTab, userId]);

  const loadUsers = async () => {
    setIsLoading(true);
    try {
      const response = activeTab === "followers"
        ? await api.getFollowers(userId)
        : await api.getFollowing(userId);

      if (response.success && response.users) {
        setUsers(response.users);
        // Initialize following states from API response
        const initialStates: Record<number, boolean> = {};
        response.users.forEach(user => {
          if (user.is_followed_by_me !== undefined) {
            initialStates[user.id] = user.is_followed_by_me;
          }
        });
        setFollowingStates(initialStates);
      }
    } catch (err) {
      console.error("Failed to load users:", err);
    } finally {
      setIsLoading(false);
    }
  };

  const handleFollow = async (targetUserId: number) => {
    if (!isAuthenticated) return;

    setLoadingStates(prev => ({ ...prev, [targetUserId]: true }));
    try {
      if (followingStates[targetUserId]) {
        await api.unfollowUser(targetUserId);
        setFollowingStates(prev => ({ ...prev, [targetUserId]: false }));
      } else {
        await api.followUser(targetUserId);
        setFollowingStates(prev => ({ ...prev, [targetUserId]: true }));
      }
    } catch (err) {
      console.error("Failed to follow/unfollow:", err);
    } finally {
      setLoadingStates(prev => ({ ...prev, [targetUserId]: false }));
    }
  };

  const getAvatarUrl = (user: api.FollowUser) => {
    const fallback = `https://api.dicebear.com/7.x/avataaars/svg?seed=${user.username || user.wallet_address}`;
    return getImageUrl(user.profile_picture, fallback);
  };

  const getDisplayName = (user: api.FollowUser) => {
    return user.username || `${user.wallet_address.slice(0, 4)}...${user.wallet_address.slice(-4)}`;
  };

  const getHandle = (user: api.FollowUser) => {
    if (user.username) return `@${user.username}`;
    return `${user.wallet_address.slice(0, 6)}...${user.wallet_address.slice(-4)}`;
  };

  return (
    <AnimatePresence>
      {isOpen && (
        <>
          {/* Backdrop */}
          <motion.div
            initial={{ opacity: 0 }}
            animate={{ opacity: 1 }}
            exit={{ opacity: 0 }}
            transition={{ duration: 0.2 }}
            className="fixed inset-0 bg-black/50 z-50"
            onClick={onClose}
          />

          {/* Modal */}
          <motion.div
            initial={{ opacity: 0, y: 100, scale: 0.95 }}
            animate={{ opacity: 1, y: 0, scale: 1 }}
            exit={{ opacity: 0, y: 100, scale: 0.95 }}
            transition={{ type: "spring", damping: 25, stiffness: 300 }}
            className="fixed left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 w-full max-w-md bg-card border border-border rounded-2xl shadow-2xl z-50 max-h-[80vh] flex flex-col"
          >
            {/* Header */}
            <div className="flex items-center justify-between px-4 py-3 border-b border-border">
              <h2 className="text-lg font-bold text-foreground">@{username}</h2>
              <button
                onClick={onClose}
                className="p-1.5 rounded-full hover:bg-muted transition-colors"
              >
                <X className="w-5 h-5 text-foreground" />
              </button>
            </div>

            {/* Tabs */}
            <div className="flex border-b border-border">
              <button
                onClick={() => setActiveTab("followers")}
                className={`flex-1 py-3 text-sm font-medium transition-colors relative ${
                  activeTab === "followers" ? "text-foreground" : "text-muted-foreground hover:text-foreground"
                }`}
              >
                <span>Followers</span>
                <span className="ml-1 text-muted-foreground">({followersCount})</span>
                {activeTab === "followers" && (
                  <div className="absolute bottom-0 left-1/2 -translate-x-1/2 w-16 h-1 bg-primary rounded-full" />
                )}
              </button>
              <button
                onClick={() => setActiveTab("following")}
                className={`flex-1 py-3 text-sm font-medium transition-colors relative ${
                  activeTab === "following" ? "text-foreground" : "text-muted-foreground hover:text-foreground"
                }`}
              >
                <span>Following</span>
                <span className="ml-1 text-muted-foreground">({followingCount})</span>
                {activeTab === "following" && (
                  <div className="absolute bottom-0 left-1/2 -translate-x-1/2 w-16 h-1 bg-primary rounded-full" />
                )}
              </button>
            </div>

            {/* Users List */}
            <div className="flex-1 overflow-y-auto">
              {isLoading ? (
                <div className="flex items-center justify-center py-12">
                  <Loader2 className="w-6 h-6 animate-spin text-primary" />
                </div>
              ) : users.length === 0 ? (
                <div className="flex flex-col items-center justify-center py-12 text-muted-foreground">
                  <p className="text-sm">
                    {activeTab === "followers" ? "No followers yet" : "Not following anyone"}
                  </p>
                </div>
              ) : (
                <div className="divide-y divide-border">
                  {users.map((user) => (
                    <div
                      key={user.id}
                      className="flex items-center gap-3 p-4 hover:bg-muted/50 transition-colors"
                    >
                      <a href={`/user/${user.username || user.id}`}>
                        <img
                          src={getAvatarUrl(user)}
                          alt={getDisplayName(user)}
                          className="w-12 h-12 rounded-full object-cover"
                        />
                      </a>
                      <div className="flex-1 min-w-0">
                        <a
                          href={`/user/${user.username || user.id}`}
                          className="font-medium text-foreground hover:underline block truncate"
                        >
                          {getDisplayName(user)}
                        </a>
                        <a
                          href={`/user/${user.username || user.id}`}
                          className="text-sm text-muted-foreground hover:underline block truncate"
                        >
                          {getHandle(user)}
                        </a>
                        {user.bio && (
                          <p className="text-sm text-muted-foreground mt-1 line-clamp-2">
                            {user.bio}
                          </p>
                        )}
                      </div>
                      {isAuthenticated && currentUser?.id !== user.id && (
                        <button
                          onClick={() => handleFollow(user.id)}
                          disabled={loadingStates[user.id]}
                          className={`flex items-center gap-1.5 px-3 py-1.5 rounded-full text-sm font-medium transition-colors ${
                            followingStates[user.id]
                              ? "border border-border text-foreground hover:border-red-500 hover:text-red-500 hover:bg-red-500/10"
                              : "bg-foreground text-background hover:bg-foreground/90"
                          }`}
                        >
                          {loadingStates[user.id] ? (
                            <Loader2 className="w-4 h-4 animate-spin" />
                          ) : followingStates[user.id] ? (
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
                      )}
                    </div>
                  ))}
                </div>
              )}
            </div>
          </motion.div>
        </>
      )}
    </AnimatePresence>
  );
}
