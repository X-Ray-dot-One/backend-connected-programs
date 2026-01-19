"use client";

import { createContext, useContext, useState, useCallback, useEffect, ReactNode } from "react";

interface ModeContextType {
  isShadowMode: boolean;
  toggleMode: () => void;
  isReady: boolean;
}

const ModeContext = createContext<ModeContextType | undefined>(undefined);

export function ModeProvider({ children }: { children: ReactNode }) {
  const [isShadowMode, setIsShadowMode] = useState(false);
  const [isReady, setIsReady] = useState(false);

  // Load mode from localStorage on mount
  useEffect(() => {
    const saved = localStorage.getItem("shadowMode");
    setIsShadowMode(saved === "true");
    setIsReady(true);
  }, []);

  // Save mode to localStorage when it changes
  useEffect(() => {
    if (isReady) {
      localStorage.setItem("shadowMode", String(isShadowMode));
    }
  }, [isShadowMode, isReady]);

  const toggleMode = useCallback(() => {
    setIsShadowMode(prev => !prev);
  }, []);

  // Don't render children until we've loaded the mode from localStorage
  if (!isReady) {
    return null;
  }

  return (
    <ModeContext.Provider
      value={{
        isShadowMode,
        toggleMode,
        isReady,
      }}
    >
      {children}
    </ModeContext.Provider>
  );
}

export function useMode() {
  const context = useContext(ModeContext);
  if (context === undefined) {
    throw new Error("useMode must be used within a ModeProvider");
  }
  return context;
}
