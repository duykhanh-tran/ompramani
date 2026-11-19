import { Metadata } from "next";
import { InstrumentsClient } from "./InstrumentsClient";

export const metadata: Metadata = {
  title: "Chuông & Mõ Điện Tử — Giữ Nhịp Hành Trì | OmPraMani",
  description:
    "Công cụ chuông và mõ điện tử Phật giáo hỗ trợ giữ nhịp công phu, tụng kinh, niệm Phật và thiền định trong không gian tu tập của bạn.",
  alternates: {
    canonical: "/chuong-mo",
  },
  openGraph: {
    title: "Chuông & Mõ Điện Tử — OmPraMani",
    description:
      "Giữ nhịp hành trì với âm thanh chuông và mõ trong không gian tu tập của bạn.",
    url: "/chuong-mo",
    type: "website",
  },
};

export default function ChuongMoPage() {
  return <InstrumentsClient />;
}
