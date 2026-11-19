export interface HeroVerse {
  solarDate: string;
  lunarDate: string;
  yearName: string;
  verseLines: string[];
  source: string;
  activeMeditators: string;
  backgroundImageUrl: string;
  backgroundImageAlt: string;
}

export interface PracticeStreak {
  days: number;
  rank: string;
}

export const DEMO_HERO_VERSE: HeroVerse = {
  solarDate: "Hôm nay",
  yearName: "Năm Bính Ngọ",
  lunarDate: "Lịch Âm Dương",
  verseLines: [
    "“Vạn pháp đều vô thường,",
    "Như mộng huyễn bào ảnh.",
    "Như lộ diệc như điện,",
    "Nên quán như thế ấy.”",
  ],
  source: "Trích Kinh Kim Cang",
  activeMeditators: "Không gian tĩnh lặng · Khởi tâm chánh niệm",
  backgroundImageUrl: "/images/hero-meditation.jpg",
  backgroundImageAlt:
    "Đức Phật tọa thiền uy nghi giữa đỉnh non hùng vĩ lúc bình minh rạng rỡ, ánh sáng từ bi bao phủ không gian thanh tịnh.",
};

export const DEMO_PRACTICE_STREAK: PracticeStreak = {
  days: 21,
  rank: "Hạng 14/174 QG",
};
