export interface CountryBadge {
  flag: string;
  name: string;
  percentage?: string;
}

export interface WallpaperItem {
  id: string;
  imageUrl: string;
  imageAlt: string;
}

export const DEMO_COMMUNITY_COUNTRIES: CountryBadge[] = [
  { flag: "🇻🇳", name: "Việt Nam", percentage: "68%" },
  { flag: "🇺🇸", name: "Hoa Kỳ" },
  { flag: "🇦🇺", name: "Úc" },
  { flag: "🇯🇵", name: "Nhật Bản" },
  { flag: "🇰🇷", name: "Hàn Quốc" },
  { flag: "🇨🇦", name: "Canada" },
  { flag: "🇫🇷", name: "Pháp" },
];

export const DEMO_WALLPAPERS: WallpaperItem[] = [
  {
    id: "buddha-statue",
    imageUrl:
      "https://lh3.googleusercontent.com/aida-public/AB6AXuA7iKs3MgsN5lr4-YslaSqRPFLkVLkPgqLYv67kGte1HeRSbHEr0GOKTuXYjeqQQYhHDEc6Fl70pS6bg1fwNpIU6bLSe73kCkfurzb-Jsuh2aeCajAE8YCa-hdg6RsnA6HuY8Otyts65It7cyGHeAnnB7IxvRBKUbR71AM7Oz12BurCts0PbOY3_D486Wh6xXPdBEYfjvucVFEFokXhuLMtY_ZcMSc9Aigx2OJDops",
    imageAlt:
      "High resolution wallpaper crop of sacred golden Buddha statue head with serene eyes in sunlight.",
  },
  {
    id: "lotus-pond",
    imageUrl:
      "https://lh3.googleusercontent.com/aida-public/AB6AXuBWnImBoXhSW_fUYCIIiJTYavkHBI5oqN66dKQkm8Yijbc7Lh42D5K14zc2a8OOQIl75-D9H4R-p3D2aK4pLm5gDB4MB7jQHhJzRxu29tQRDtdE_BGau7x0-F7_5rHXsIi3yDrDCltxbLwedq3pQA8dM944LRt1IJwPOoNmVcdjOoC4q6soetsIitwLZgv9q4qgY--rfh6pShiyQ7Q3d9KPSgsjQKtBPJbKdyN5TSA",
    imageAlt:
      "Wallpaper crop of misty lotus flower pond in serene morning dew with Buddhist temple pagoda in soft focus background.",
  },
  {
    id: "prayer-wheels",
    imageUrl:
      "https://lh3.googleusercontent.com/aida-public/AB6AXuBbY6aXu4CpZINPSwhtlf0nBfSH9cEsR8zOCbN3CdVQPJWKfjz3Z5oK8fJwlCinjT7CnDUroTyNOrChcjfiO0Mq61B-cxmbp6ZxntNHuMlxjtZ4AU11Z0pYmSi37J4MRbNo95LHSQzTpvFYNJoLa-kHhnQWcsHyy_mE5uZWtGbo7e5zoOpR-ICcBNPG9WcXWhhkJH7gEZRJ8dJ8gXeejvhoApRGLwbbFdosSy9oCZA",
    imageAlt:
      "Wallpaper crop of sacred Tibetan prayer wheels spinning in golden afternoon mountain sunlight.",
  },
];
