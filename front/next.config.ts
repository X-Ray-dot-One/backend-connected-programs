import type { NextConfig } from "next";

const nextConfig: NextConfig = {
  // Turbopack resolve aliases for Node.js modules not available in browser
  // This is needed for @arcium-hq/client which imports 'fs'
  turbopack: {
    resolveAlias: {
      fs: { browser: "./lib/empty-module.js" },
      path: { browser: "./lib/empty-module.js" },
      crypto: { browser: "./lib/empty-module.js" },
    },
  },
};

export default nextConfig;
