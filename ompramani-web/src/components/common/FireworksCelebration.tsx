"use client";

import React, { useEffect, useRef } from "react";

interface Particle {
  x: number;
  y: number;
  vx: number;
  vy: number;
  alpha: number;
  color: string;
  size: number;
  decay: number;
  gravity: number;
  flicker: boolean;
}

interface FireworkRocket {
  x: number;
  y: number;
  targetY: number;
  speed: number;
  color: string;
  exploded: boolean;
}

const PALETTE = [
  "#f59e0b", // Amber gold
  "#ef4444", // Ruby red
  "#10b981", // Emerald green
  "#8b5cf6", // Lotus purple
  "#f97316", // Saffron orange
  "#06b6d4", // Cyan
  "#fbbf24", // Golden yellow
  "#ec4899", // Lotus pink
];

export function FireworksCelebration({ durationMs = 6000 }: { durationMs?: number }) {
  const canvasRef = useRef<HTMLCanvasElement | null>(null);

  useEffect(() => {
    const canvas = canvasRef.current;
    if (!canvas) return;

    const ctx = canvas.getContext("2d");
    if (!ctx) return;

    let animId: number;
    let isActive = true;
    const startTime = Date.now();

    const resize = () => {
      canvas.width = window.innerWidth;
      canvas.height = window.innerHeight;
    };
    resize();
    window.addEventListener("resize", resize);

    const particles: Particle[] = [];
    const rockets: FireworkRocket[] = [];

    const createExplosion = (x: number, y: number, color: string) => {
      const count = 45 + Math.floor(Math.random() * 25);
      for (let i = 0; i < count; i++) {
        const angle = (Math.PI * 2 * i) / count + (Math.random() - 0.5) * 0.4;
        const speed = 2 + Math.random() * 5.5;
        particles.push({
          x,
          y,
          vx: Math.cos(angle) * speed,
          vy: Math.sin(angle) * speed,
          alpha: 1,
          color: Math.random() > 0.3 ? color : PALETTE[Math.floor(Math.random() * PALETTE.length)],
          size: 2 + Math.random() * 2.5,
          decay: 0.012 + Math.random() * 0.015,
          gravity: 0.08 + Math.random() * 0.04,
          flicker: Math.random() > 0.5,
        });
      }
    };

    const launchRocket = () => {
      const x = window.innerWidth * 0.15 + Math.random() * (window.innerWidth * 0.7);
      const targetY = window.innerHeight * 0.15 + Math.random() * (window.innerHeight * 0.35);
      const color = PALETTE[Math.floor(Math.random() * PALETTE.length)];
      rockets.push({
        x,
        y: window.innerHeight,
        targetY,
        speed: 10 + Math.random() * 5,
        color,
        exploded: false,
      });
    };

    // Initial volley
    for (let i = 0; i < 3; i++) {
      setTimeout(() => {
        if (isActive) launchRocket();
      }, i * 350);
    }

    // Interval launcher
    const launchInterval = setInterval(() => {
      if (!isActive) return;
      if (Date.now() - startTime > durationMs) {
        clearInterval(launchInterval);
        return;
      }
      launchRocket();
      if (Math.random() > 0.5) {
        setTimeout(() => {
          if (isActive) launchRocket();
        }, 200);
      }
    }, 700);

    const loop = () => {
      ctx.clearRect(0, 0, canvas.width, canvas.height);

      // Update rockets
      for (let i = rockets.length - 1; i >= 0; i--) {
        const r = rockets[i];
        r.y -= r.speed;

        // Draw trail
        ctx.save();
        ctx.beginPath();
        ctx.arc(r.x, r.y, 2.5, 0, Math.PI * 2);
        ctx.fillStyle = r.color;
        ctx.shadowColor = r.color;
        ctx.shadowBlur = 10;
        ctx.fill();
        ctx.restore();

        if (r.y <= r.targetY) {
          createExplosion(r.x, r.y, r.color);
          rockets.splice(i, 1);
        }
      }

      // Update particles
      for (let i = particles.length - 1; i >= 0; i--) {
        const p = particles[i];
        p.x += p.vx;
        p.y += p.vy;
        p.vy += p.gravity;
        p.vx *= 0.98;
        p.vy *= 0.98;
        p.alpha -= p.decay;

        if (p.alpha <= 0) {
          particles.splice(i, 1);
          continue;
        }

        ctx.save();
        ctx.globalAlpha = Math.max(0, p.alpha * (p.flicker && Math.random() > 0.3 ? 0.6 : 1));
        ctx.beginPath();
        ctx.arc(p.x, p.y, p.size, 0, Math.PI * 2);
        ctx.fillStyle = p.color;
        ctx.shadowColor = p.color;
        ctx.shadowBlur = 8;
        ctx.fill();
        ctx.restore();
      }

      // Continue animation if rockets or particles remain
      if (isActive && (rockets.length > 0 || particles.length > 0 || Date.now() - startTime <= durationMs)) {
        animId = requestAnimationFrame(loop);
      } else {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
      }
    };

    animId = requestAnimationFrame(loop);

    return () => {
      isActive = false;
      clearInterval(launchInterval);
      window.removeEventListener("resize", resize);
      cancelAnimationFrame(animId);
    };
  }, [durationMs]);

  return (
    <canvas
      ref={canvasRef}
      className="fixed inset-0 pointer-events-none z-50"
      aria-hidden="true"
    />
  );
}
