"use client";

import React, { useState, useRef, useEffect, useCallback } from "react";

interface MantraChantPlayerProps {
  audioUrl?: string | null;
  mantraTitle: string;
}

function formatDuration(seconds: number): string {
  if (isNaN(seconds) || seconds < 0) return "0:00";
  const m = Math.floor(seconds / 60);
  const s = Math.floor(seconds % 60);
  return `${m}:${s < 10 ? "0" : ""}${s}`;
}

export function MantraChantPlayer({
  audioUrl,
  mantraTitle,
}: MantraChantPlayerProps) {
  const [isPlaying, setIsPlaying] = useState<boolean>(false);
  const [isLooping, setIsLooping] = useState<boolean>(true); // Default loop is true for chanting
  const [currentTime, setCurrentTime] = useState<number>(0);
  const [duration, setDuration] = useState<number>(0);
  const [audioError, setAudioError] = useState<boolean>(false);
  const [isOpen, setIsOpen] = useState<boolean>(false);

  const audioRef = useRef<HTMLAudioElement | null>(null);

  // Stop & reset when audioUrl changes
  useEffect(() => {
    if (audioRef.current) {
      audioRef.current.pause();
      audioRef.current.currentTime = 0;
    }
    setIsPlaying(false);
    setCurrentTime(0);
    setDuration(0);
    setAudioError(false);
  }, [audioUrl]);

  // Cleanup on unmount
  useEffect(() => {
    return () => {
      if (audioRef.current) {
        audioRef.current.pause();
        audioRef.current = null;
      }
    };
  }, []);

  const handleTogglePlay = useCallback(() => {
    if (!audioUrl) return;

    if (!audioRef.current) {
      const audio = new Audio(audioUrl);
      audio.loop = isLooping;

      audio.addEventListener("timeupdate", () => {
        if (audioRef.current) {
          setCurrentTime(audioRef.current.currentTime);
        }
      });

      audio.addEventListener("loadedmetadata", () => {
        if (audioRef.current) {
          setDuration(audioRef.current.duration);
        }
      });

      audio.addEventListener("ended", () => {
        if (!audioRef.current?.loop) {
          setIsPlaying(false);
          setCurrentTime(0);
        }
      });

      audio.addEventListener("error", () => {
        setAudioError(true);
        setIsPlaying(false);
      });

      audioRef.current = audio;
    }

    if (isPlaying) {
      audioRef.current.pause();
      setIsPlaying(false);
    } else {
      setAudioError(false);
      audioRef.current
        .play()
        .then(() => {
          setIsPlaying(true);
        })
        .catch((err) => {
          console.warn("Chant audio playback prevented:", err);
          setIsPlaying(false);
        });
    }
  }, [audioUrl, isLooping, isPlaying]);

  const handleToggleLoop = () => {
    setIsLooping((prev) => {
      const next = !prev;
      if (audioRef.current) {
        audioRef.current.loop = next;
      }
      return next;
    });
  };

  const handleSeek = (e: React.ChangeEvent<HTMLInputElement>) => {
    const newTime = parseFloat(e.target.value);
    setCurrentTime(newTime);
    if (audioRef.current) {
      audioRef.current.currentTime = newTime;
    }
  };

  const handleMainButtonClick = () => {
    if (!isOpen) {
      setIsOpen(true);
      if (audioUrl && !isPlaying) {
        // Automatically start on first click of "Trì chú"
        handleTogglePlay();
      }
    } else {
      handleTogglePlay();
    }
  };

  return (
    <div className="w-full flex flex-col items-center gap-3">
      {/* 1. Large Main Action Button: "Trì Chú" */}
      <button
        type="button"
        onClick={handleMainButtonClick}
        className={`w-full max-w-md py-3.5 px-6 rounded-full font-serif text-base sm:text-lg font-bold shadow-sm transition-all duration-200 flex items-center justify-center gap-2.5 active:scale-98 ${
          isPlaying
            ? "bg-[#8f5a28] text-white hover:bg-[#7a481c] ring-3 ring-[#8f5a28]/30"
            : "bg-gradient-to-r from-[#b8860b] via-[#a37930] to-[#8f5a28] text-white hover:opacity-95 shadow-md"
        }`}
        title={audioUrl ? (isPlaying ? "Tạm dừng trì chú" : "Bắt đầu trì chú") : "Trì chú"}
        aria-label={audioUrl ? (isPlaying ? "Tạm dừng trì chú" : "Bắt đầu trì chú") : "Trì chú"}
      >
        <span className="material-symbols-outlined text-2xl leading-none">
          {isPlaying ? "pause_circle" : "play_circle"}
        </span>
        <span>{isPlaying ? "Đang Trì Chú (Tạm dừng)" : "Trì Chú"}</span>
      </button>

      {/* 2. Notice if No Audio is Available */}
      {!audioUrl && isOpen && (
        <div className="w-full max-w-md px-4 py-2.5 rounded-2xl bg-[#faf5eb] border border-[#e2d5c3] text-[#735d45] text-xs sm:text-sm text-center font-sans animate-in fade-in duration-200 flex items-center justify-center gap-2">
          <span className="material-symbols-outlined text-lg text-[#b8860b] leading-none">
            info
          </span>
          <span>
            Chưa có bản thu âm cho thần chú này. Bạn có thể đọc tụng theo bản Chú gốc và Phiên âm.
          </span>
        </div>
      )}

      {/* 3. Audio Error Notice */}
      {audioError && (
        <div className="w-full max-w-md px-4 py-2 rounded-2xl bg-rose-50 border border-rose-200 text-rose-700 text-xs text-center font-sans">
          Không thể tải tệp âm thanh này. Xin vui lòng thử lại sau.
        </div>
      )}

      {/* 4. Minimal Chanting Player Controls (Expanded when opened & audio exists) */}
      {audioUrl && isOpen && (
        <div className="w-full max-w-md p-4 sm:p-5 rounded-3xl bg-[#FAF7F0] border border-[#dfd2be] shadow-sm space-y-3 animate-in fade-in slide-in-from-top-2 duration-200 text-[#382716]">
          {/* Track title & looping indicator */}
          <div className="flex items-center justify-between text-xs">
            <span className="font-serif font-bold text-[#5c462e] truncate max-w-[220px]">
              {mantraTitle}
            </span>
            <span
              className={`inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-medium transition-colors ${
                isLooping
                  ? "bg-[#edd6a4]/60 text-[#694808] border border-[#cca656]/50"
                  : "bg-[#ece4d9] text-[#786a5a]"
              }`}
            >
              <span className="material-symbols-outlined text-xs leading-none">
                {isLooping ? "repeat_one" : "repeat"}
              </span>
              <span>{isLooping ? "Lặp lại: Bật" : "Lặp lại: Tắt"}</span>
            </span>
          </div>

          {/* Progress Slider */}
          <div className="space-y-1">
            <input
              type="range"
              min={0}
              max={duration || 100}
              value={currentTime}
              onChange={handleSeek}
              className="w-full h-1.5 bg-[#e2d5c3] rounded-lg appearance-none cursor-pointer accent-[#8f5a28]"
              aria-label="Thanh tiến trình âm thanh"
            />
            <div className="flex justify-between text-[11px] text-[#8c7457] font-mono">
              <span>{formatDuration(currentTime)}</span>
              <span>{formatDuration(duration)}</span>
            </div>
          </div>

          {/* Playback Action Buttons */}
          <div className="flex items-center justify-center gap-4 pt-1">
            {/* Repeat Toggle Button */}
            <button
              type="button"
              onClick={handleToggleLoop}
              className={`p-2 rounded-full border transition-colors flex items-center justify-center ${
                isLooping
                  ? "border-[#c49a45] bg-[#fbf5e8] text-[#855e16] shadow-xs"
                  : "border-[#d8ccbc] bg-[#f0eae1] text-[#8c7b68] hover:bg-[#e7dfd4]"
              }`}
              title={isLooping ? "Tắt phát lặp lại" : "Bật phát lặp lại liên tục"}
              aria-label={isLooping ? "Tắt phát lặp lại" : "Bật phát lặp lại liên tục"}
            >
              <span className="material-symbols-outlined text-xl leading-none">
                {isLooping ? "repeat_one" : "repeat"}
              </span>
            </button>

            {/* Play / Pause Toggle Button */}
            <button
              type="button"
              onClick={handleTogglePlay}
              className="w-12 h-12 rounded-full bg-[#8f5a28] hover:bg-[#784617] text-white shadow-sm flex items-center justify-center active:scale-95 transition-all"
              title={isPlaying ? "Tạm dừng" : "Phát âm thanh"}
              aria-label={isPlaying ? "Tạm dừng" : "Phát âm thanh"}
            >
              <span className="material-symbols-outlined text-2xl leading-none">
                {isPlaying ? "pause" : "play_arrow"}
              </span>
            </button>

            {/* Stop / Rewind to start */}
            <button
              type="button"
              onClick={() => {
                if (audioRef.current) {
                  audioRef.current.currentTime = 0;
                  setCurrentTime(0);
                }
              }}
              className="p-2 rounded-full border border-[#d8ccbc] bg-[#f0eae1] text-[#8c7b68] hover:bg-[#e7dfd4] transition-colors flex items-center justify-center"
              title="Phát lại từ đầu"
              aria-label="Phát lại từ đầu"
            >
              <span className="material-symbols-outlined text-xl leading-none">
                skip_previous
              </span>
            </button>
          </div>
        </div>
      )}
    </div>
  );
}
