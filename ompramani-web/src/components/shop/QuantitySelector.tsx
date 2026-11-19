"use client";

import React from "react";

interface QuantitySelectorProps {
  quantity: number;
  max?: number;
  onChange: (value: number) => void;
  disabled?: boolean;
}

export function QuantitySelector({
  quantity,
  max = 99,
  onChange,
  disabled = false,
}: QuantitySelectorProps) {
  const min = 1;
  const hardCap = Math.max(1, Math.min(99, max));

  const handleDecrement = () => {
    if (disabled || quantity <= min) return;
    onChange(quantity - 1);
  };

  const handleIncrement = () => {
    if (disabled || quantity >= hardCap) return;
    onChange(quantity + 1);
  };

  const handleInputChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    if (disabled) return;
    const val = parseInt(e.target.value, 10);
    if (isNaN(val)) {
      onChange(min);
      return;
    }
    const clamped = Math.max(min, Math.min(hardCap, val));
    onChange(clamped);
  };

  return (
    <div className="inline-flex items-center rounded-xl border border-outline-variant/60 bg-surface-container-lowest p-1 shadow-xs">
      <button
        type="button"
        disabled={disabled || quantity <= min}
        onClick={handleDecrement}
        className="w-8 h-8 rounded-lg flex items-center justify-center text-on-surface hover:bg-surface-container disabled:opacity-30 disabled:pointer-events-none transition-colors cursor-pointer focus:outline-hidden focus:ring-1 focus:ring-primary"
        aria-label="Giảm số lượng"
      >
        <span className="material-symbols-outlined text-[18px]">remove</span>
      </button>

      <input
        type="number"
        min={min}
        max={hardCap}
        value={quantity}
        disabled={disabled}
        onChange={handleInputChange}
        className="w-12 text-center font-mono font-medium text-sm text-on-surface bg-transparent focus:outline-hidden [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none"
        aria-label="Số lượng vật phẩm"
      />

      <button
        type="button"
        disabled={disabled || quantity >= hardCap}
        onClick={handleIncrement}
        className="w-8 h-8 rounded-lg flex items-center justify-center text-on-surface hover:bg-surface-container disabled:opacity-30 disabled:pointer-events-none transition-colors cursor-pointer focus:outline-hidden focus:ring-1 focus:ring-primary"
        aria-label="Tăng số lượng"
      >
        <span className="material-symbols-outlined text-[18px]">add</span>
      </button>
    </div>
  );
}
