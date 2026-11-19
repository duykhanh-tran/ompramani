export interface PracticeToolItem {
  id: string;
  name: string;
  icon: string;
  positionClass?: string;
  isCenter?: boolean;
  subLabel?: string;
  href?: string;
  description?: string;
}

export const DEMO_PRACTICE_TOOLS: PracticeToolItem[] = [
  {
    id: "meditation",
    name: "Thiền định",
    icon: "self_improvement",
    isCenter: true,
    subLabel: "Tâm điểm định tĩnh",
    href: "/thien",
    description: "Tọa thiền, định tâm và quán niệm hơi thở",
  },
  {
    id: "chanting",
    name: "Tụng kinh",
    icon: "menu_book",
    positionClass: "top-0 left-1/2 -translate-x-1/2",
    href: "/kinh",
    description: "Thư viện kinh văn Phật giáo và nghi thức tụng niệm",
  },
  {
    id: "video_lectures",
    name: "Video bài giảng",
    icon: "play_circle",
    positionClass: "top-[26%] right-0 translate-x-2 sm:translate-x-4",
    href: "/video-phat-phap",
    description: "Pháp thoại, bài giảng và video Phật giáo tuyển chọn",
  },
  {
    id: "mantra",
    name: "Trì chú",
    icon: "spa",
    positionClass: "bottom-[10%] right-[10%]",
    href: "/tri-chu",
    description: "Trì tụng thần chú Mật tông và âm điệu Phạn văn",
  },
  {
    id: "beads",
    name: "Tràng hạt",
    icon: "filter_vintage",
    positionClass: "bottom-[10%] left-[10%]",
    href: "/trang-hat",
    description: "Tràng hạt điện tử 108 hạt ghi nhận công phu",
  },
  {
    id: "bell",
    name: "Chuông mõ",
    icon: "notifications_active",
    positionClass: "top-[26%] left-0 -translate-x-2 sm:-translate-x-4",
    href: "/chuong-mo",
    description: "Chuông và mõ điện tử giữ nhịp công phu thanh tịnh",
  },
];
