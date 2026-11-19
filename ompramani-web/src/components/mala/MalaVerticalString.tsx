"use client";

import React, { useState, useRef, useCallback } from "react";

interface MalaVerticalStringProps {
  currentRoundBeads: number;
  onAdvanceBead: () => void;
  disabled?: boolean;
}

export function MalaVerticalString({
  currentRoundBeads,
  onAdvanceBead,
  disabled = false,
}: MalaVerticalStringProps) {
  const [animShift, setAnimShift] = useState<number>(0);
  const [isDragging, setIsDragging] = useState<boolean>(false);
  const [dragOffset, setDragOffset] = useState<number>(0);

  const startYRef = useRef<number | null>(null);
  const startTimeRef = useRef<number>(0);
  const hasTriggeredRef = useRef<boolean>(false);
  const isCooldownRef = useRef<boolean>(false);

  // Trigger bead advance with haptic/directional animation
  const triggerAdvance = useCallback(
    (direction: "up" | "down" | "tap") => {
      if (disabled || isCooldownRef.current) return;

      isCooldownRef.current = true;

      // Animate bead motion based on swipe direction
      const shiftDistance = direction === "up" ? -24 : 24;
      setAnimShift(shiftDistance);

      onAdvanceBead();

      // Smoothly snap back to origin
      setTimeout(() => {
        setAnimShift(0);
      }, 160);

      setTimeout(() => {
        isCooldownRef.current = false;
      }, 140);
    },
    [disabled, onAdvanceBead]
  );

  // Pointer Down
  const handlePointerDown = (e: React.PointerEvent<HTMLDivElement>) => {
    if (disabled) return;
    try {
      (e.currentTarget as HTMLElement).setPointerCapture(e.pointerId);
    } catch {
      // Ignore if pointer capture fails
    }

    startYRef.current = e.clientY;
    startTimeRef.current = Date.now();
    hasTriggeredRef.current = false;
    setIsDragging(true);
    setDragOffset(0);
  };

  // Pointer Move (with drag resistance and 35px threshold detection)
  const handlePointerMove = (e: React.PointerEvent<HTMLDivElement>) => {
    if (startYRef.current === null || disabled) return;

    const diff = e.clientY - startYRef.current;

    // Apply soft resistance to visual dragging
    const damped = Math.sign(diff) * Math.min(32, Math.abs(diff) * 0.4);
    setDragOffset(damped);

    // Threshold check: 35px swipe up or down
    if (!hasTriggeredRef.current && Math.abs(diff) >= 35) {
      hasTriggeredRef.current = true;
      triggerAdvance(diff < 0 ? "up" : "down");
    }
  };

  // Pointer Up / End
  const handlePointerUp = (e: React.PointerEvent<HTMLDivElement>) => {
    if (startYRef.current !== null && !disabled) {
      const diff = e.clientY - startYRef.current;
      const elapsed = Date.now() - startTimeRef.current;

      // If swipe threshold wasn't reached during move, check on release
      if (!hasTriggeredRef.current) {
        if (Math.abs(diff) >= 30) {
          hasTriggeredRef.current = true;
          triggerAdvance(diff < 0 ? "up" : "down");
        } else if (Math.abs(diff) <= 10 && elapsed < 400) {
          // Clean quick tap
          hasTriggeredRef.current = true;
          triggerAdvance("tap");
        }
      }
    }

    try {
      if ((e.currentTarget as HTMLElement).hasPointerCapture(e.pointerId)) {
        (e.currentTarget as HTMLElement).releasePointerCapture(e.pointerId);
      }
    } catch {
      // Ignore
    }

    startYRef.current = null;
    setIsDragging(false);
    setDragOffset(0);
  };

  const handlePointerCancel = (e: React.PointerEvent<HTMLDivElement>) => {
    try {
      if ((e.currentTarget as HTMLElement).hasPointerCapture(e.pointerId)) {
        (e.currentTarget as HTMLElement).releasePointerCapture(e.pointerId);
      }
    } catch {
      // Ignore
    }
    startYRef.current = null;
    setIsDragging(false);
    setDragOffset(0);
  };

  // Current visual vertical translation
  const currentTranslateY = isDragging ? dragOffset : animShift;

  return (
    <div
      role="region"
      aria-label="Chuỗi hạt 108"
      className="relative w-full max-w-sm sm:max-w-md mx-auto h-[380px] sm:h-[420px] flex items-center justify-center select-none overflow-hidden touch-none"
      onPointerDown={handlePointerDown}
      onPointerMove={handlePointerMove}
      onPointerUp={handlePointerUp}
      onPointerCancel={handlePointerCancel}
      style={{
        backgroundImage:
          "radial-gradient(circle, #d6c7b2 1.3px, transparent 1.3px)",
        backgroundSize: "24px 24px",
      }}
    >
      {/* Sacred Cord Running Vertically Through Beads */}
      <div className="absolute top-0 bottom-0 left-1/2 -translate-x-1/2 w-[3px] bg-gradient-to-b from-[#7a481c] via-[#b67d3c] to-[#7a481c] shadow-xs pointer-events-none z-0" />

      {/* Subtle Crosshair Alignment Indicator */}
      <div className="absolute top-1/2 left-0 right-0 -translate-y-1/2 h-[1px] bg-gradient-to-r from-transparent via-[#bfae99]/60 to-transparent pointer-events-none z-0" />

      {/* Up/Down Floating Gesture Indicator Arrows */}
      <div className="absolute right-4 sm:right-8 top-1/2 -translate-y-1/2 flex flex-col items-center gap-1.5 text-[#8c6b3e]/60 pointer-events-none select-none text-xs">
        <span className="text-base animate-bounce leading-none">▲</span>
        <span className="text-[10px] uppercase tracking-widest font-mono font-medium">Vuốt</span>
        <span className="text-base animate-bounce leading-none">▼</span>
      </div>

      {/* Beads Container */}
      <div
        className={`relative z-10 flex flex-col items-center justify-center gap-3 sm:gap-3.5 transition-transform ${
          isDragging
            ? "transition-none"
            : "duration-180 ease-out"
        }`}
        style={{
          transform: `translateY(${currentTranslateY}px)`,
        }}
      >
        {/* Bead -3 (Smallest top bead) */}
        <div
          className="w-8 h-8 rounded-full shadow-xs shrink-0 pointer-events-none opacity-70"
          style={{
            background:
              "radial-gradient(circle at 35% 35%, #a67341 0%, #70441d 45%, #46270e 80%, #2b1607 100%)",
            boxShadow:
              "inset 1.5px 1.5px 3px rgba(255, 230, 180, 0.35), inset -2px -2px 4px rgba(0, 0, 0, 0.6), 0 3px 6px rgba(60, 35, 10, 0.25)",
          }}
        />

        {/* Bead -2 */}
        <div
          className="w-10 h-10 rounded-full shadow-sm shrink-0 pointer-events-none opacity-85"
          style={{
            background:
              "radial-gradient(circle at 35% 35%, #a67341 0%, #70441d 45%, #46270e 80%, #2b1607 100%)",
            boxShadow:
              "inset 1.5px 1.5px 3px rgba(255, 230, 180, 0.35), inset -2px -2px 4px rgba(0, 0, 0, 0.6), 0 4px 8px rgba(60, 35, 10, 0.25)",
          }}
        />

        {/* Bead -1 */}
        <div
          className="w-12 h-12 rounded-full shadow-md shrink-0 pointer-events-none opacity-95"
          style={{
            background:
              "radial-gradient(circle at 35% 35%, #b17c46 0%, #7b4a20 45%, #4c2b10 80%, #2e1808 100%)",
            boxShadow:
              "inset 2px 2px 4px rgba(255, 230, 180, 0.4), inset -2.5px -2.5px 5px rgba(0, 0, 0, 0.6), 0 5px 10px rgba(60, 35, 10, 0.25)",
          }}
        />

        {/* Central Active Bead (Focal Point) */}
        <button
          type="button"
          disabled={disabled}
          onClick={() => triggerAdvance("tap")}
          className={`relative group w-20 h-20 sm:w-22 sm:h-22 rounded-full focus:outline-none cursor-pointer shrink-0 transition-transform active:scale-95 ${
            disabled ? "opacity-60 cursor-not-allowed" : "hover:scale-102"
          }`}
          title="Vuốt lên/xuống hoặc chạm để lần hạt"
          aria-label={`Hạt trung tâm (Đang ở hạt ${currentRoundBeads} trên 108)`}
        >
          {/* Gentle Golden Aura Glow */}
          <div
            className={`absolute -inset-2.5 rounded-full blur-md pointer-events-none transition-opacity duration-300 ${
              disabled
                ? "opacity-10 bg-amber-800/10"
                : "opacity-70 group-hover:opacity-100 bg-amber-500/25"
            }`}
          />

          {/* 3D Photorealistic Agarwood / Sandalwood Bead Texture */}
          <div
            className="w-full h-full rounded-full relative overflow-hidden"
            style={{
              background:
                "radial-gradient(circle at 33% 33%, #c48c56 0%, #8c5525 38%, #573114 72%, #321908 100%)",
              boxShadow:
                "inset 3px 3px 6px rgba(255, 240, 200, 0.5), inset -4px -4px 8px rgba(0, 0, 0, 0.75), 0 8px 18px rgba(45, 25, 8, 0.4)",
            }}
          >
            {/* Fine Natural Wood Grain Ring Overlays */}
            <div
              className="absolute inset-0 rounded-full opacity-25 mix-blend-overlay pointer-events-none"
              style={{
                background:
                  "repeating-radial-gradient(circle at 35% 35%, transparent 0, transparent 4px, rgba(0,0,0,0.5) 5px, transparent 6px)",
              }}
            />

            {/* Specular Gloss Glint */}
            <div className="absolute top-3 left-4 w-5 h-3 rounded-full bg-white/40 blur-[1px] rotate-[-25deg] pointer-events-none" />
          </div>
        </button>

        {/* Bead +1 */}
        <div
          className="w-12 h-12 rounded-full shadow-md shrink-0 pointer-events-none opacity-95"
          style={{
            background:
              "radial-gradient(circle at 35% 35%, #b17c46 0%, #7b4a20 45%, #4c2b10 80%, #2e1808 100%)",
            boxShadow:
              "inset 2px 2px 4px rgba(255, 230, 180, 0.4), inset -2.5px -2.5px 5px rgba(0, 0, 0, 0.6), 0 5px 10px rgba(60, 35, 10, 0.25)",
          }}
        />

        {/* Bead +2 */}
        <div
          className="w-10 h-10 rounded-full shadow-sm shrink-0 pointer-events-none opacity-85"
          style={{
            background:
              "radial-gradient(circle at 35% 35%, #a67341 0%, #70441d 45%, #46270e 80%, #2b1607 100%)",
            boxShadow:
              "inset 1.5px 1.5px 3px rgba(255, 230, 180, 0.35), inset -2px -2px 4px rgba(0, 0, 0, 0.6), 0 4px 8px rgba(60, 35, 10, 0.25)",
          }}
        />

        {/* Bead +3 (Smallest bottom bead) */}
        <div
          className="w-8 h-8 rounded-full shadow-xs shrink-0 pointer-events-none opacity-70"
          style={{
            background:
              "radial-gradient(circle at 35% 35%, #a67341 0%, #70441d 45%, #46270e 80%, #2b1607 100%)",
            boxShadow:
              "inset 1.5px 1.5px 3px rgba(255, 230, 180, 0.35), inset -2px -2px 4px rgba(0, 0, 0, 0.6), 0 3px 6px rgba(60, 35, 10, 0.25)",
          }}
        />
      </div>
    </div>
  );
}
