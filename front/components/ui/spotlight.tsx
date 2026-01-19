'use client';

import * as React from 'react';
import { motion, useMotionValue, useSpring, useTransform } from 'motion/react';
import { cn } from '@/lib/utils';

interface SpotlightProps {
  className?: string;
  size?: number;
  springOptions?: {
    stiffness?: number;
    damping?: number;
    mass?: number;
  };
}

export function Spotlight({
  className,
  size = 400,
  springOptions = { stiffness: 200, damping: 40, mass: 0.5 },
}: SpotlightProps) {
  const containerRef = React.useRef<HTMLDivElement>(null);
  const mouseX = useMotionValue(0);
  const mouseY = useMotionValue(0);

  const springX = useSpring(mouseX, springOptions);
  const springY = useSpring(mouseY, springOptions);

  const spotlightLeft = useTransform(springX, (x) => `${x - size / 2}px`);
  const spotlightTop = useTransform(springY, (y) => `${y - size / 2}px`);

  React.useEffect(() => {
    const handleMouseMove = (e: MouseEvent) => {
      const container = containerRef.current;
      if (container) {
        const rect = container.getBoundingClientRect();
        mouseX.set(e.clientX - rect.left);
        mouseY.set(e.clientY - rect.top);
      }
    };

    window.addEventListener('mousemove', handleMouseMove);
    return () => window.removeEventListener('mousemove', handleMouseMove);
  }, [mouseX, mouseY]);

  return (
    <div
      ref={containerRef}
      className={cn(
        'pointer-events-none fixed inset-0 z-0 overflow-hidden',
        className
      )}
    >
      <motion.div
        className="absolute rounded-full"
        style={{
          width: size,
          height: size,
          left: spotlightLeft,
          top: spotlightTop,
          background:
            'radial-gradient(circle, var(--spotlight-color) 0%, transparent 70%)',
          opacity: 0.15,
        }}
      />
    </div>
  );
}
