"use client";

import React, { useState } from "react";

interface MeditationPreparationProps {
  durationMinutes: number;
  onSelectDuration: (minutes: number) => void;
  soundEnabled: boolean;
  onToggleSound: () => void;
  onStart: () => void;
  isStarting?: boolean;
}

const PRESET_DURATIONS = [5, 10, 15, 30];

export function MeditationPreparation({
  durationMinutes,
  onSelectDuration,
  soundEnabled,
  onToggleSound,
  onStart,
  isStarting = false,
}: MeditationPreparationProps) {
  const [showCustomModal, setShowCustomModal] = useState(false);
  const [customInput, setCustomInput] = useState(
    PRESET_DURATIONS.includes(durationMinutes) ? "20" : durationMinutes.toString()
  );

  const isCustomActive = !PRESET_DURATIONS.includes(durationMinutes);

  const handleApplyCustom = (e: React.FormEvent) => {
    e.preventDefault();
    const val = parseInt(customInput, 10);
    if (!isNaN(val) && val >= 1 && val <= 180) {
      onSelectDuration(val);
      setShowCustomModal(false);
    }
  };

  return (
    <div className="max-w-3xl mx-auto px-4 py-8 sm:py-12">
      {/* Header Banner */}
      <div className="text-center mb-8 sm:mb-10">
        <div className="inline-flex items-center gap-2 px-3.5 py-1 rounded-full bg-secondary-container/60 text-on-secondary-container font-label-caps text-xs tracking-wider uppercase mb-3 border border-secondary-container">
          <span className="material-symbols-outlined text-[16px] text-primary">
            self_improvement
          </span>
          <span>Anapanasati · Quán Niệm Hơi Thở</span>
        </div>
        <h1 className="font-display-lg text-[32px] sm:text-[40px] font-semibold text-on-surface tracking-tight leading-tight mb-3">
          Quán Niệm Hơi Thở
        </h1>
        <p className="font-body-md text-on-surface-variant max-w-xl mx-auto leading-relaxed">
          Chánh niệm trong từng hơi thở vào và thở ra. Đưa tâm trở về với thân,
          an trú trọn vẹn và tĩnh lặng trong giây phút hiện tại.
        </p>
      </div>

      {/* Main Configuration Card */}
      <div className="bg-surface-container-lowest rounded-3xl p-6 sm:p-8 shadow-sm border border-outline-variant/40 mb-8 space-y-8">
        {/* Duration Selection */}
        <div>
          <div className="flex items-center justify-between mb-4">
            <span className="font-label-caps text-xs text-outline tracking-wider uppercase font-semibold">
              Thời Lượng Thiền
            </span>
            <span className="font-label-md text-xs text-primary font-medium">
              Đã chọn: {durationMinutes} phút
            </span>
          </div>

          <div className="grid grid-cols-3 sm:grid-cols-5 gap-3">
            {PRESET_DURATIONS.map((minutes) => {
              const isSelected = durationMinutes === minutes;
              return (
                <button
                  key={minutes}
                  type="button"
                  suppressHydrationWarning
                  onClick={() => onSelectDuration(minutes)}
                  className={`py-3 px-4 rounded-2xl font-label-lg text-sm font-semibold transition-all flex flex-col items-center justify-center cursor-pointer border ${
                    isSelected
                      ? "bg-primary text-on-primary border-primary shadow-sm scale-102 ring-2 ring-primary/20"
                      : "bg-surface-container-low text-on-surface hover:bg-surface-container border-outline-variant/40 hover:border-primary/40"
                  }`}
                >
                  <span className="text-base sm:text-lg">{minutes}</span>
                  <span className="text-xs opacity-80 font-normal">phút</span>
                </button>
              );
            })}

            {/* Custom Duration Button */}
            <button
              type="button"
              suppressHydrationWarning
              onClick={() => setShowCustomModal(true)}
              className={`py-3 px-4 rounded-2xl font-label-lg text-sm font-semibold transition-all flex flex-col items-center justify-center cursor-pointer border ${
                isCustomActive
                  ? "bg-primary text-on-primary border-primary shadow-sm scale-102 ring-2 ring-primary/20"
                  : "bg-surface-container-low text-on-surface hover:bg-surface-container border-outline-variant/40 hover:border-primary/40"
              }`}
            >
              <span className="text-base sm:text-lg">
                {isCustomActive ? `${durationMinutes}p` : "Khác"}
              </span>
              <span className="text-xs opacity-80 font-normal">tùy chỉnh</span>
            </button>
          </div>
        </div>

        {/* Sound & Bell Toggle Option */}
        <div className="flex items-center justify-between p-4 sm:p-5 rounded-2xl bg-surface-container-low/70 border border-outline-variant/30">
          <div className="flex items-center gap-3.5 min-w-0 pr-4">
            <div className="w-11 h-11 rounded-2xl bg-secondary-container text-on-secondary-container flex items-center justify-center shrink-0">
              <span className="material-symbols-outlined text-[24px] text-primary">
                notifications_active
              </span>
            </div>
            <div>
              <h2 className="font-label-lg text-sm font-semibold text-on-surface">
                Nhạc thiền & Tiếng chuông
              </h2>
              <p className="font-body-sm text-xs text-outline line-clamp-1 sm:line-clamp-none">
                Chuông chánh niệm đầu &amp; cuối thời thiền, nhắc nhở định tâm.
              </p>
            </div>
          </div>

          <label className="relative inline-flex items-center cursor-pointer shrink-0">
            <input
              type="checkbox"
              checked={soundEnabled}
              onChange={onToggleSound}
              className="sr-only peer"
            />
            <div className="w-12 h-6.5 bg-outline-variant/60 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[3px] after:left-[4px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-primary shadow-inner"></div>
          </label>
        </div>

        {/* Preparation Steps */}
        <div>
          <div className="mb-4">
            <span className="font-label-caps text-xs text-outline tracking-wider uppercase font-semibold">
              Chuẩn Bị Vào Thiền
            </span>
            <h3 className="font-headline-md text-base sm:text-lg font-semibold text-on-surface mt-0.5">
              3 Bước Để Tâm Định Tĩnh
            </h3>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
            {/* Step 1 */}
            <div className="p-4 rounded-2xl bg-surface-container-low/50 border border-outline-variant/30 flex flex-col justify-between">
              <div className="flex items-center gap-3 mb-2">
                <div className="w-8 h-8 rounded-full bg-surface-container flex items-center justify-center text-primary font-semibold text-xs border border-outline-variant/30">
                  <span className="material-symbols-outlined text-[18px]">
                    wb_twilight
                  </span>
                </div>
                <h4 className="font-label-lg text-sm font-semibold text-on-surface">
                  1. Không gian
                </h4>
              </div>
              <p className="font-body-sm text-xs text-on-surface-variant leading-relaxed">
                Tìm nơi yên tĩnh, thoáng đãng, ánh sáng dịu. Tắt chuông điện thoại để tránh bị gián đoạn.
              </p>
            </div>

            {/* Step 2 */}
            <div className="p-4 rounded-2xl bg-surface-container-low/50 border border-outline-variant/30 flex flex-col justify-between">
              <div className="flex items-center gap-3 mb-2">
                <div className="w-8 h-8 rounded-full bg-surface-container flex items-center justify-center text-primary font-semibold text-xs border border-outline-variant/30">
                  <span className="material-symbols-outlined text-[18px]">
                    self_improvement
                  </span>
                </div>
                <h4 className="font-label-lg text-sm font-semibold text-on-surface">
                  2. Bản thân
                </h4>
              </div>
              <p className="font-body-sm text-xs text-on-surface-variant leading-relaxed">
                Thả lỏng toàn bộ cơ thể từ đỉnh đầu đến gót chân. Tạm gác lại mọi suy nghĩ, lo toan thường nhật.
              </p>
            </div>

            {/* Step 3 */}
            <div className="p-4 rounded-2xl bg-surface-container-low/50 border border-outline-variant/30 flex flex-col justify-between">
              <div className="flex items-center gap-3 mb-2">
                <div className="w-8 h-8 rounded-full bg-surface-container flex items-center justify-center text-primary font-semibold text-xs border border-outline-variant/30">
                  <span className="material-symbols-outlined text-[18px]">
                    spa
                  </span>
                </div>
                <h4 className="font-label-lg text-sm font-semibold text-on-surface">
                  3. Tư thế ngồi
                </h4>
              </div>
              <p className="font-body-sm text-xs text-on-surface-variant leading-relaxed">
                Ngồi kiết già, bán già hoặc trên ghế với lưng thẳng tự nhiên. Hai tay buông thư trên gối, mắt khép hờ.
              </p>
            </div>
          </div>
        </div>

        {/* Master Quote Banner */}
        <div className="p-5 rounded-2xl bg-gradient-to-r from-surface-container-low via-surface-container to-surface-container-low border border-outline-variant/40 flex items-start gap-4">
          <span className="material-symbols-outlined text-[28px] text-primary shrink-0 opacity-80 mt-0.5">
            format_quote
          </span>
          <div className="space-y-1.5">
            <p className="font-quote-editorial text-sm sm:text-base italic text-on-surface leading-relaxed">
              &ldquo;Hơi thở là cầu nối giữa đời sống và ý thức, là chiếc cầu nối giữa cơ thể và tâm trí của bạn. Bất cứ lúc nào tâm trí bạn phân tán, hãy dùng hơi thở như một phương tiện để nắm giữ lại tâm mình.&rdquo;
            </p>
            <p className="font-label-caps text-xs text-primary font-semibold tracking-wider uppercase">
              — Thiền sư Thích Nhất Hạnh
            </p>
          </div>
        </div>
      </div>

      {/* Start Button Container */}
      <div className="flex flex-col items-center justify-center gap-3 text-center">
        <button
          type="button"
          suppressHydrationWarning
          onClick={onStart}
          disabled={isStarting}
          className="group relative flex items-center justify-center w-20 h-20 sm:w-24 sm:h-24 rounded-full bg-gradient-to-br from-[#d4af37] via-[#b38e46] to-[#785915] text-on-primary shadow-lg hover:shadow-xl hover:scale-105 active:scale-95 transition-all duration-300 focus:outline-none ring-4 ring-secondary-container/60 cursor-pointer disabled:opacity-50 disabled:pointer-events-none"
          title={`Bắt đầu tọa thiền ${durationMinutes} phút`}
          aria-label={`Bắt đầu tọa thiền ${durationMinutes} phút`}
        >
          {/* Subtle breathing glow ring */}
          <div className="absolute inset-0 rounded-full bg-primary/20 animate-ping pointer-events-none opacity-40" />
          <span className="material-symbols-outlined text-[36px] sm:text-[44px] ml-1 group-hover:scale-110 transition-transform">
            {isStarting ? "hourglass_empty" : "play_arrow"}
          </span>
        </button>

        <div className="space-y-0.5">
          <p className="font-label-lg text-sm font-semibold text-on-surface">
            Nhấn để bắt đầu tọa thiền
          </p>
          <p className="font-body-sm text-xs text-outline">
            Thời lượng: <strong>{durationMinutes} phút</strong> {soundEnabled ? "· Kèm chuông chánh niệm" : "· Thiền tĩnh lặng"}
          </p>
        </div>
      </div>

      {/* Custom Duration Modal */}
      {showCustomModal && (
        <div className="fixed inset-0 z-50 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4 animate-in fade-in duration-150">
          <div className="bg-surface-container-lowest border border-outline-variant/50 rounded-3xl p-6 sm:p-8 max-w-sm w-full shadow-2xl space-y-5">
            <div className="flex items-center justify-between">
              <h3 className="font-headline-md text-lg font-semibold text-on-surface">
                Tùy chỉnh thời lượng
              </h3>
              <button
                type="button"
                suppressHydrationWarning
                onClick={() => setShowCustomModal(false)}
                className="w-8 h-8 rounded-full flex items-center justify-center text-outline hover:text-on-surface hover:bg-surface-container transition-colors"
              >
                <span className="material-symbols-outlined text-[20px]">
                  close
                </span>
              </button>
            </div>

            <p className="font-body-sm text-xs text-on-surface-variant">
              Nhập số phút bạn muốn tọa thiền (từ 1 đến 180 phút):
            </p>

            <form onSubmit={handleApplyCustom} className="space-y-4">
              <div className="relative">
                <input
                  type="number"
                  min="1"
                  max="180"
                  value={customInput}
                  onChange={(e) => setCustomInput(e.target.value)}
                  className="w-full px-4 py-3 rounded-2xl bg-surface-container-low border border-outline-variant/60 font-headline-md text-xl text-center text-on-surface focus:outline-none focus:ring-2 focus:ring-primary focus:bg-surface-container-lowest"
                  autoFocus
                />
                <span className="absolute right-4 top-1/2 -translate-y-1/2 text-sm text-outline pointer-events-none">
                  phút
                </span>
              </div>

              <div className="flex items-center gap-2 pt-2">
                <button
                  type="button"
                  suppressHydrationWarning
                  onClick={() => setShowCustomModal(false)}
                  className="flex-1 py-2.5 rounded-xl border border-outline-variant/60 text-on-surface font-label-md text-xs hover:bg-surface-container transition-colors"
                >
                  Hủy
                </button>
                <button
                  type="submit"
                  className="flex-1 py-2.5 rounded-xl bg-primary text-on-primary font-label-md text-xs font-semibold hover:bg-primary-hover shadow-xs transition-colors"
                >
                  Áp dụng
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  );
}
