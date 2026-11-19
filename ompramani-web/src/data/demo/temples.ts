export interface TempleItem {
  id: string;
  name: string;
  location: string;
  description: string;
  visitorsCount: string;
  imageUrl: string;
  imageAlt: string;
}

export const DEMO_TEMPLES: TempleItem[] = [
  {
    id: "chua-mot-cot",
    name: "Chùa Một Cột (Diên Hựu Tự)",
    location: "Hà Nội",
    description:
      "Biểu tượng văn hóa nghìn năm Thăng Long, kiến trúc đóa sen ngàn cánh nở giữa hồ linh thiêng.",
    visitorsCount: "12.800 Phật tử chiêm bái",
    imageUrl:
      "https://lh3.googleusercontent.com/aida-public/AB6AXuAQhKzwPJq1fGqGNTKv4D5o60K7cVGRqu4PCJD0TY93ie9OlPJSuq1wj82pO3-jQumZTsr5zp5YBqLTwdzBCxflYk--kA9IND6a0RRkqvjcrRcYPIBb6UpDdSdfSAB1tFoo7iyVFVIOGp_OGxnZeqyLAMBGWcMe1LZBrcM9mOxOIpthrNTbwKv1jYAwI27lT0Y81nLvuU9yCE2wNaQ2OofzdWC-WTjKbO0-S912smM",
    imageAlt:
      "Iconic One Pillar Pagoda Chua Mot Cot in Hanoi Vietnam, historical ancient lotus-shaped Buddhist temple pavilion rising from lotus pond surrounded by green trees and cobblestone courtyard.",
  },
  {
    id: "chua-bai-dinh",
    name: "Quần Thể Chùa Bái Đính",
    location: "Ninh Bình",
    description:
      "Ngôi đại tự giữ nhiều kỷ lục Châu Á, bảo điện nghìn mắt nghìn tay rực rỡ sắc vàng linh thiêng.",
    visitorsCount: "45.200 Phật tử chiêm bái",
    imageUrl:
      "https://lh3.googleusercontent.com/aida-public/AB6AXuAZ9hdIBOq4l7-OBHYDZnQen07K8yRhJvvCMHOie3HezzJVn8YcR6wsqnJ8ih0abHDP_9t_pkdOa-MrwBJoGeyK2TS4bnqb1IRMa2bDRUPj6IF-aK7K17Wlpb0uORvQ9uyg-pfIScCx-7x6kqT-Zs5Q22syhfriomEzBkjYBkDuPvtVGcRex6AKhGKQAib5PyzAGA0ZxGEExTDYYiERMMRngcC5HhEPUJh5T7gMLc4",
    imageAlt:
      "Grand interior of Bai Dinh Pagoda sanctuary in Ninh Binh Vietnam showing enormous golden thousand-hand Guan Yin statue illuminated by candlelight and grand wooden beams.",
  },
  {
    id: "chua-dong-yen-tu",
    name: "Chùa Đồng Yên Tử",
    location: "Quảng Ninh",
    description:
      "Cái nôi Thiền phái Trúc Lâm do Phật hoàng Trần Nhân Tông sáng lập trên đỉnh non thiêng mây ngàn.",
    visitorsCount: "38.100 Phật tử chiêm bái",
    imageUrl:
      "https://lh3.googleusercontent.com/aida-public/AB6AXuD-qg3Frg60PJHYVqqWDzKQOqdaVOc9IrUrz6F0XXZgNpcNiOTEt3ao8Pa4b9cyz7dLwVngv_02bQ8dizoXyCrkgrM-Epz4bfxSRrKbo6NAGi9zDeKR4xmwd_hwUtPhN0k-qaUh1_DBHA_Am9UZQhO-SbqlSJ-epfYe6BDWWadfVuHpHcUjzUT8GsAm6FbpnZWa7D5o5QUNTSR3LIJE_eJDaUXs7-Sn-bQgh41j6hg",
    imageAlt:
      "Ancient bronze Dong Pagoda perched on rocky cloudy peak of Yen Tu mountain Vietnam at misty sunrise, holy cradle of Truc Lam Zen Buddhism.",
  },
];
