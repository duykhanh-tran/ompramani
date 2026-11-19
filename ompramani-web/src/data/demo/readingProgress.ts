export interface ReadingProgressItem {
  id: string;
  title: string;
  subtitle: string;
  badge: string;
  badgeVariant?: "primary" | "secondary";
  progressLabel: string;
  progressPercent: number;
  lastRead: string;
  imageUrl: string;
  imageAlt: string;
}

export const DEMO_READING_PROGRESS: ReadingProgressItem[] = [
  {
    id: "kinh-dia-tang",
    title: "Kinh Địa Tạng Bồ Tát Bổn Nguyện",
    subtitle: "Quyển Thượng · Phẩm Phổ Hiền Bồ Tát",
    badge: "Đang hành trì",
    badgeVariant: "primary",
    progressLabel: "1/4 Quyển (25%)",
    progressPercent: 25,
    lastRead: "Đọc hôm qua",
    imageUrl:
      "https://lh3.googleusercontent.com/aida-public/AB6AXuB0hlcuu5H2VBTtS4pv4CmAHaQZN5EpoolHtuc8vTIYvdr_wBKY3rNNMum3ar_xw6lG02d9_FYplYVPH6KbULvJt0qD4kNx_Y4_mTKOQHEO7Tv_z0HcTDcZUpwFn24VD8G9FIENfiTXxHTU9EwfoVoA1NX9sNyA_bGj2eFZgVIzxsxIQuTNIhx8qaPPXdB9dcXkIq7gOi7lLOdCGLHzfG07OCUjQJz2mTDEniqNQVM",
    imageAlt:
      "Traditional sacred Buddhist painting of Ksitigarbha Bodhisattva with golden staff and wish-granting pearl sitting on a lion throne with heavenly clouds and warm amber glowing backdrop.",
  },
  {
    id: "kinh-phap-cu",
    title: "Kinh Pháp Cú (Dhammapada)",
    subtitle: "Phẩm Bà La Môn · Kệ số 383",
    badge: "Sắp hoàn thành",
    badgeVariant: "secondary",
    progressLabel: "25/26 Phẩm (96%)",
    progressPercent: 96,
    lastRead: "Đọc 2 giờ trước",
    imageUrl:
      "https://lh3.googleusercontent.com/aida-public/AB6AXuAd_rAfKjkR8LZSZf5YT3aSvK-8ftrruVjoyuVPj11gEl1F6mkwlkgFA-NxQGm6peNUQzVXYkREv7Mox_1U6Zk5ah4XOCjrcyRwL9uXegU25UY1xVB0iVNdwUZnr5eB8YNfGwJCllG4fKaIZguoequ3N31611x1DID-BwsQ9LSRIDmO_siCXmdHGlRWazlYLlB91BY3Hvr92VfRi1K2Tv4dnSRYpm4U45-Kcp2fgyU",
    imageAlt:
      "Intricate classical woodblock style illustration of Sakyamuni Buddha beneath white cherry blossoms on deep crimson lacquer background, ancient Vietnamese pagoda Buddhist aesthetic.",
  },
  {
    id: "kinh-a-di-da",
    title: "Kinh Vô Lượng Thọ",
    subtitle: "48 Đại Nguyện Của Đức A Di Đà",
    badge: "Tịnh Độ tông",
    badgeVariant: "primary",
    progressLabel: "12/48 Nguyện (25%)",
    progressPercent: 25,
    lastRead: "Đọc 3 ngày trước",
    imageUrl:
      "https://lh3.googleusercontent.com/aida-public/AB6AXuDc4anIH3YFRmtLjf61EPqo00ipwGvH7cLxyTAWnq16sMIDgA3a71yqlwVoK57wuXZHZvAl6XG4kTnnn0ivPVZpkRSz2n8jueaOebaphsy0Cx65L_7_YB2xHgcIUjmZtQvOliDH13VB3ZMSM77T07e9S-ju5kTgiuQwsaFn7nWNVkeDlr2UZHAn1kxtlgQZuxoMnyIOvm3OCGr6p5guIYKTp2HwB3U6TEMi2egHQas",
    imageAlt:
      "Sacred tranquil lotus pond with dozens of softly glowing pink water lilies floating in pure calm reflective water, Buddhist Pure Land atmosphere in gentle sunset tones.",
  },
];
