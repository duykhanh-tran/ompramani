import React, { Suspense } from "react";
import type { Metadata } from "next";
import { notFound } from "next/navigation";
import { Header } from "@/components/layout/Header";
import { Footer } from "@/components/layout/Footer";
import { getTempleBySlug } from "@/lib/api/temples";
import { TempleHero } from "@/components/temples/TempleHero";
import { TempleAddressSection } from "@/components/temples/TempleAddressSection";

interface TempleDetailPageProps {
  params: Promise<{ slug: string }>;
}

export async function generateMetadata({
  params,
}: TempleDetailPageProps): Promise<Metadata> {
  try {
    const { slug } = await params;
    const temple = await getTempleBySlug(slug);

    if (!temple) {
      return {
        title: "Không tìm thấy địa điểm | OmPraMani",
        description: "Địa điểm tự viện không tồn tại hoặc chưa được xuất bản trên OmPraMani.",
      };
    }

    const description =
      temple.short_description ||
      `Thông tin giới thiệu về ${temple.name} tại ${
        temple.address?.province || "Việt Nam"
      } trên Thư viện Tự viện OmPraMani.`;

    return {
      title: `${temple.name} | OmPraMani`,
      description,
      alternates: {
        canonical: `/tim-chua/${temple.slug}`,
      },
      openGraph: {
        title: `${temple.name} | OmPraMani`,
        description,
        url: `/tim-chua/${temple.slug}`,
        siteName: "OmPraMani",
        locale: "vi_VN",
        type: "website",
        images: temple.cover_image
          ? [
              {
                url: temple.cover_image,
                alt: `Ảnh quang cảnh ${temple.name}`,
              },
            ]
          : undefined,
      },
      twitter: {
        card: "summary_large_image",
        title: `${temple.name} | OmPraMani`,
        description,
        images: temple.cover_image ? [temple.cover_image] : undefined,
      },
    };
  } catch {
    return {
      title: "Địa Điểm Phật Giáo | OmPraMani",
      description: "Thư viện chùa, thiền viện, tịnh xá & đạo tràng trên OmPraMani.",
    };
  }
}

async function TempleDetailContent({ params }: TempleDetailPageProps) {
  const { slug } = await params;
  let temple;
  try {
    temple = await getTempleBySlug(slug);
  } catch {
    temple = null;
  }

  if (!temple) {
    notFound();
  }

  const hasVerifiedCoordinates = Boolean(temple.coordinates);

  return (
    <div className="w-full space-y-8">
      {/* Full-width Hero section (Edge-to-edge banner image + Title) */}
      <TempleHero temple={temple} />

      {/* Main Responsive Grid Layout (Constrained container) */}
      <div className="max-w-[1240px] mx-auto px-4 sm:px-6">
        <div className="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
        {/* Left Column (Primary Content: Description, Visit Info, Gallery, Sources) */}
        <div className="lg:col-span-8 space-y-8">
          {/* Highlights */}
          {temple.highlights && (
            <section
              aria-labelledby="highlights-heading"
              className="bg-surface-container-lowest p-6 sm:p-8 rounded-2xl border border-outline-variant/40 space-y-3 shadow-xs"
            >
              <h2
                id="highlights-heading"
                className="font-headline-sm text-lg sm:text-xl font-serif font-bold text-on-surface flex items-center gap-2 border-b border-outline-variant/30 pb-3"
              >
                <span className="material-symbols-outlined text-primary text-[22px]">
                  auto_awesome
                </span>
                Điểm Nổi Bật
              </h2>
              <div className="font-body-md text-sm sm:text-base text-on-surface-variant leading-relaxed whitespace-pre-line">
                {temple.highlights}
              </div>
            </section>
          )}

          {/* History / Detailed Description */}
          {(temple.history || temple.description) && (
            <section
              aria-labelledby="description-heading"
              className="bg-surface-container-lowest p-6 sm:p-8 rounded-2xl border border-outline-variant/40 space-y-4 shadow-xs"
            >
              <h2
                id="description-heading"
                className="font-headline-sm text-lg sm:text-xl font-serif font-bold text-on-surface flex items-center gap-2 border-b border-outline-variant/30 pb-3"
              >
                <span className="material-symbols-outlined text-primary text-[22px]">
                  history_edu
                </span>
                {temple.history ? "Lịch Sử Hình Thành" : "Giới Thiệu & Lược Sử"}
              </h2>
              {temple.history && temple.history.includes("<") ? (
                <div
                  className="font-body-md text-sm sm:text-base text-on-surface-variant leading-relaxed whitespace-pre-line space-y-4"
                  dangerouslySetInnerHTML={{ __html: temple.history }}
                />
              ) : (
                <div className="font-body-md text-sm sm:text-base text-on-surface-variant leading-relaxed whitespace-pre-line space-y-4">
                  {temple.history || temple.description}
                </div>
              )}
            </section>
          )}

          {/* Architecture & Cultural Value */}
          {temple.architecture_culture && (
            <section
              aria-labelledby="architecture-heading"
              className="bg-surface-container-lowest p-6 sm:p-8 rounded-2xl border border-outline-variant/40 space-y-4 shadow-xs"
            >
              <h2
                id="architecture-heading"
                className="font-headline-sm text-lg sm:text-xl font-serif font-bold text-on-surface flex items-center gap-2 border-b border-outline-variant/30 pb-3"
              >
                <span className="material-symbols-outlined text-primary text-[22px]">
                  architecture
                </span>
                Kiến Trúc &amp; Giá Trị Văn Hóa
              </h2>
              {temple.architecture_culture.includes("<") ? (
                <div
                  className="font-body-md text-sm sm:text-base text-on-surface-variant leading-relaxed whitespace-pre-line space-y-4"
                  dangerouslySetInnerHTML={{ __html: temple.architecture_culture }}
                />
              ) : (
                <div className="font-body-md text-sm sm:text-base text-on-surface-variant leading-relaxed whitespace-pre-line space-y-4">
                  {temple.architecture_culture}
                </div>
              )}
            </section>
          )}
        </div>

        {/* Right Column (Sidebar: Address & Visiting facts) */}
        <div className="lg:col-span-4 space-y-6 lg:sticky lg:top-24">
          {/* Visiting & Heritage Quick Facts */}
          {(temple.construction_year || temple.opening_hours || (temple.admission_fee !== undefined && temple.admission_fee !== null)) && (
            <div className="bg-surface-container-lowest p-5 rounded-2xl border border-outline-variant/40 space-y-4 shadow-xs">
              <h3 className="font-title-sm text-sm font-bold text-on-surface uppercase tracking-wider flex items-center gap-2 border-b border-outline-variant/30 pb-2.5">
                <span className="material-symbols-outlined text-primary text-[18px]">
                  info
                </span>
                Thông Tin Tham Quan
              </h3>
              <div className="space-y-3 text-sm">
                {temple.construction_year && (
                  <div className="flex items-start justify-between gap-3">
                    <span className="text-on-surface-variant flex items-center gap-1.5">
                      <span className="material-symbols-outlined text-[16px] text-outline">calendar_month</span>
                      Năm xây dựng:
                    </span>
                    <span className="font-medium text-on-surface text-right">{temple.construction_year}</span>
                  </div>
                )}
                {temple.opening_hours && (
                  <div className="flex items-start justify-between gap-3">
                    <span className="text-on-surface-variant flex items-center gap-1.5">
                      <span className="material-symbols-outlined text-[16px] text-outline">schedule</span>
                      Giờ mở cửa:
                    </span>
                    <span className="font-medium text-on-surface text-right">{temple.opening_hours}</span>
                  </div>
                )}
                {temple.admission_fee !== undefined && temple.admission_fee !== null && (
                  <div className="flex items-start justify-between gap-3">
                    <span className="text-on-surface-variant flex items-center gap-1.5">
                      <span className="material-symbols-outlined text-[16px] text-outline">payments</span>
                      Vé tham quan:
                    </span>
                    <span className="font-semibold text-primary text-right">
                      {temple.admission_fee === 0
                        ? "Miễn phí"
                        : `${temple.admission_fee.toLocaleString("vi-VN")} đ`}
                    </span>
                  </div>
                )}
              </div>
            </div>
          )}

          {/* Verified Address & Directions */}
          <TempleAddressSection
            address={temple.address}
            provinceName={temple.address?.province}
            countryCode={temple.address?.country_code}
            hasVerifiedCoordinates={hasVerifiedCoordinates}
            googleMapsUrl={temple.google_maps_url}
          />
        </div>
      </div>
    </div>
  </div>
  );
}

export default function TempleDetailPage({ params }: TempleDetailPageProps) {
  return (
    <div className="min-h-screen flex flex-col bg-surface font-body-md text-on-surface antialiased">
      <Header />

      <main id="main-content" className="w-full flex-1 pt-16 pb-16">
        <Suspense
          fallback={
            <div className="w-full space-y-8 animate-pulse">
              <div className="w-full h-[50vh] sm:h-[60vh] max-h-[750px] bg-surface-container-high" />
              <div className="max-w-[1240px] mx-auto px-4 sm:px-6 space-y-8">
                <div className="h-10 w-2/3 bg-surface-container-high rounded" />
                <div className="grid grid-cols-1 lg:grid-cols-12 gap-8">
                  <div className="lg:col-span-8 space-y-6">
                    <div className="h-40 bg-surface-container-lowest rounded-2xl border border-outline-variant/30" />
                    <div className="h-48 bg-surface-container-lowest rounded-2xl border border-outline-variant/30" />
                  </div>
                  <div className="lg:col-span-4 space-y-6">
                    <div className="h-44 bg-surface-container-lowest rounded-2xl border border-outline-variant/30" />
                    <div className="h-44 bg-surface-container-lowest rounded-2xl border border-outline-variant/30" />
                  </div>
                </div>
              </div>
            </div>
          }
        >
          <TempleDetailContent params={params} />
        </Suspense>
      </main>

      <Footer />
    </div>
  );
}
