"use client";

import { useState, useEffect, memo } from "react";

const LAUNCH_DATE = new Date("2026-01-31T19:00:00Z").getTime();

function getTimeLeft() {
  const now = Date.now();
  const diff = Math.max(0, LAUNCH_DATE - now);
  return {
    days: Math.floor(diff / (1000 * 60 * 60 * 24)),
    hours: Math.floor((diff / (1000 * 60 * 60)) % 24),
    minutes: Math.floor((diff / (1000 * 60)) % 60),
    seconds: Math.floor((diff / 1000) % 60),
    total: diff,
  };
}

function seededRandom(seed: number) {
  const x = Math.sin(seed * 9301 + 49297) * 233280;
  return x - Math.floor(x);
}

const Particle = memo(function Particle({ index }: { index: number }) {
  const size = 2 + seededRandom(index * 10 + 1) * 3;
  const x = seededRandom(index * 10 + 2) * 100;
  const duration = 15 + seededRandom(index * 10 + 3) * 25;
  const delay = seededRandom(index * 10 + 4) * duration;
  const opacity = 0.1 + seededRandom(index * 10 + 5) * 0.3;

  return (
    <div
      className="absolute rounded-full"
      style={{
        width: size,
        height: size,
        left: `${x}%`,
        bottom: "-5%",
        background: index % 3 === 0 ? "#a855f7" : index % 3 === 1 ? "#6366f1" : "#0ea5e9",
        opacity,
        animation: `rise ${duration}s linear ${delay}s infinite`,
      }}
    />
  );
});

export default function CountdownPage() {
  const [time, setTime] = useState(getTimeLeft);
  const [mounted, setMounted] = useState(false);

  useEffect(() => {
    setMounted(true);
    const interval = setInterval(() => setTime(getTimeLeft()), 1000);
    return () => clearInterval(interval);
  }, []);

  if (!mounted) return null;

  return (
    <div className="relative min-h-screen overflow-hidden bg-[#06060e] text-white flex flex-col items-center justify-center selection:bg-purple-500/30">
      {/* Animated background */}
      <div className="absolute inset-0">
        <div className="absolute top-1/4 left-1/4 w-[600px] h-[600px] bg-purple-600/10 rounded-full blur-[120px] animate-pulse" />
        <div className="absolute bottom-1/4 right-1/4 w-[500px] h-[500px] bg-indigo-600/10 rounded-full blur-[100px] animate-pulse" style={{ animationDelay: "1s" }} />
        <div className="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[400px] h-[400px] bg-cyan-600/5 rounded-full blur-[80px] animate-pulse" style={{ animationDelay: "2s" }} />

        <div
          className="absolute inset-0 opacity-[0.03]"
          style={{
            backgroundImage: "linear-gradient(rgba(255,255,255,0.1) 1px, transparent 1px), linear-gradient(90deg, rgba(255,255,255,0.1) 1px, transparent 1px)",
            backgroundSize: "60px 60px",
          }}
        />

        {Array.from({ length: 30 }).map((_, i) => (
          <Particle key={i} index={i} />
        ))}
      </div>

      {/* Content */}
      <div className="relative z-10 flex flex-col items-center gap-8 px-6 max-w-4xl w-full">
        {/* Logo */}
        <div className="relative mb-4">
          <img
            src="/private-logo.png"
            alt="X-RAY"
            className="w-24 h-24 md:w-32 md:h-32 object-contain"
          />
          <div className="absolute -inset-4 bg-gradient-to-r from-purple-500/20 to-cyan-500/20 blur-2xl -z-10 rounded-full" />
        </div>

        {/* Tagline */}
        <p className="text-lg md:text-xl text-white/40 font-light tracking-wide text-center">
          anonymous social layer on solana
        </p>

        {/* Countdown or Live */}
        <div className="flex items-center gap-1.5 sm:gap-3 md:gap-5 mt-4">
            {[
              { value: time.days, label: "days" },
              { value: time.hours, label: "hours" },
              { value: time.minutes, label: "min" },
              { value: time.seconds, label: "sec" },
            ].map((unit, i) => (
              <div key={unit.label} className="flex items-center gap-1.5 sm:gap-3 md:gap-5">
                <div className="flex flex-col items-center">
                  <div className="relative group">
                    <div className="w-[56px] h-[70px] sm:w-[72px] sm:h-[88px] md:w-[100px] md:h-[120px] bg-white/[0.03] backdrop-blur-sm border border-white/[0.06] rounded-xl sm:rounded-2xl flex items-center justify-center">
                      <span className="text-2xl sm:text-3xl md:text-5xl font-mono font-bold tabular-nums bg-gradient-to-b from-white to-white/60 bg-clip-text text-transparent">
                        {String(unit.value).padStart(2, "0")}
                      </span>
                    </div>
                    <div className="absolute inset-0 bg-gradient-to-b from-purple-500/5 to-transparent rounded-xl sm:rounded-2xl opacity-0 group-hover:opacity-100 transition-opacity" />
                  </div>
                  <span className="text-[8px] sm:text-[10px] md:text-xs text-white/25 mt-1.5 sm:mt-2 uppercase tracking-[0.15em] sm:tracking-[0.2em]">
                    {unit.label}
                  </span>
                </div>
                {i < 3 && (
                  <span className="text-lg sm:text-2xl md:text-3xl text-white/10 font-light -mt-4 sm:-mt-6 md:-mt-8">:</span>
                )}
              </div>
            ))}
        </div>

        {/* Links */}
        <div className="flex items-center gap-6 mt-8">
          <a
            href="https://x-ray.one"
            target="_blank"
            rel="noopener noreferrer"
            className="text-sm text-white/20 hover:text-white/50 transition-colors"
          >
            waitlist
          </a>
          <span className="text-white/10">·</span>
          <a
            href="https://ray-paper.x-ray.one"
            target="_blank"
            rel="noopener noreferrer"
            className="text-sm text-white/20 hover:text-white/50 transition-colors"
          >
            docs
          </a>
          <span className="text-white/10">·</span>
          <a
            href="https://linktr.ee/XrayOne"
            target="_blank"
            rel="noopener noreferrer"
            className="text-sm text-white/20 hover:text-white/50 transition-colors"
          >
            linktree
          </a>
        </div>

        {/* Footer */}
        <p className="text-sm text-white/40 mt-4 tracking-wide">
          january 31st · 7 PM UTC
        </p>
      </div>
    </div>
  );
}
