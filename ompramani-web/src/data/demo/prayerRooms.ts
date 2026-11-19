export interface UnifiedPrayerRoomItem {
  id: string;
  category: string;
  title: string;
  description: string;
  statusLabel: string;
  primaryButtonLabel: string;
  secondaryButtonLabel: string;
  primaryIcon: string;
  topRightIcon: string;
  imageUrl: string;
  imageAlt: string;
}

export const UNIFIED_PRAYER_ROOM: UnifiedPrayerRoomItem = {
  id: "phong-loi-nguyen",
  category: "Cầu an & Hồi hướng thiện lành",
  title: "Phòng Lời Nguyện Cộng Đồng",
  description:
    "Nơi đồng đạo khắp muôn phương cùng nhau gửi lời nguyện bình an cho gia đạo, sẻ chia sự động viên và hồi hướng thiện lành trong tinh thần tương trợ lẫn nhau.",
  statusLabel: "Đang diễn ra trực tuyến",
  primaryButtonLabel: "Vào Phòng Lời Nguyện",
  secondaryButtonLabel: "Xem lời nguyện cộng đồng",
  primaryIcon: "forum",
  topRightIcon: "favorite",
  imageUrl:
    "https://lh3.googleusercontent.com/aida-public/AB6AXuBShNOAGaooz2BCjKT708PdMXV28fe13hLAGcBazDceu8rg-Fqrh9dMlhXFDqOipuN1hO1vzA80mSF-OwYr6ceAFOYaoYzQSJGV4q4TJaXlHPHCPyEH-hf5ITwaLUhaSC0FGlJRnrxXjzqXb-IiwRbI3OYzuJqwpYxjwivu8pFp73qItSWPh_LGvTR-nMh7WRCNaMzXBTORmNZjpDQ01j7PqQAGU-VwVh4qlVPr-aQ",
  imageAlt:
    "Traditional sacred portrait of Avalokiteshvara Bodhisattva Quan Am standing upon a blooming pink lotus holding willow branch and pure nectar vase, golden halo, gentle compassionate expression in silk scroll style.",
};

// Kept for backward-compatibility if needed
export interface PrayerRoomItem {
  id: string;
  type: "peace" | "rebirth";
  category: string;
  title: string;
  description: string;
  participantsCount: string;
  prayersCount: string;
  primaryButtonLabel: string;
  secondaryButtonLabel: string;
  primaryIcon: string;
  topRightIcon: string;
  imageUrl: string;
  imageAlt: string;
}

export const DEMO_PRAYER_ROOMS: PrayerRoomItem[] = [
  {
    id: "cau-an",
    type: "peace",
    category: "Hồi hướng bình an",
    title: "Phòng Cầu An Đại Bi",
    description:
      "Gửi tâm nguyện cầu cho thân quyến tiêu trừ tật bệnh, tai qua nạn khỏi, gia đạo an yên thịnh vượng.",
    participantsCount: "1.250 đồng đạo",
    prayersCount: "Xem 3.420 lời cầu nguyện",
    primaryButtonLabel: "Viết lời ước nguyện & Hồi hướng",
    secondaryButtonLabel: "Xem 3.420 lời cầu nguyện",
    primaryIcon: "edit_note",
    topRightIcon: "volunteer_activism",
    imageUrl:
      "https://lh3.googleusercontent.com/aida-public/AB6AXuBShNOAGaooz2BCjKT708PdMXV28fe13hLAGcBazDceu8rg-Fqrh9dMlhXFDqOipuN1hO1vzA80mSF-OwYr6ceAFOYaoYzQSJGV4q4TJaXlHPHCPyEH-hf5ITwaLUhaSC0FGlJRnrxXjzqXb-IiwRbI3OYzuJqwpYxjwivu8pFp73qItSWPh_LGvTR-nMh7WRCNaMzXBTORmNZjpDQ01j7PqQAGU-VwVh4qlVPr-aQ",
    imageAlt:
      "Traditional sacred portrait of Avalokiteshvara Bodhisattva Quan Am standing upon a blooming pink lotus holding willow branch and pure nectar vase, golden halo, gentle compassionate expression in silk scroll style.",
  },
];
