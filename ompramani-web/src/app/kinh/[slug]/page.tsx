import React from "react";
import type { Metadata } from "next";
import { notFound } from "next/navigation";
import { connection } from "next/server";
import { Header } from "@/components/layout/Header";
import { Footer } from "@/components/layout/Footer";
import { SutraHeader } from "@/components/sutra/SutraHeader";
import { SutraVerticalAccordionTOC } from "@/components/sutra/SutraVerticalAccordionTOC";
import {
  getSutra,
  getSutraSections,
  getSutraSection,
  getSutraVersions,
} from "@/lib/api/sutras";
import { flattenSections } from "@/lib/utils/sutra";
import { SutraSectionDetail, SutraSectionTreeNode } from "@/types/api";
import { SITE_CONFIG } from "@/lib/seo/config";
import { resolveMediaUrl } from "@/lib/utils/media";

interface SutraDetailPageProps {
  params: Promise<{
    slug: string;
  }>;
  searchParams: Promise<{
    section?: string;
    version?: string;
  }>;
}

export async function generateMetadata({
  params,
}: SutraDetailPageProps): Promise<Metadata> {
  await connection();
  const { slug } = await params;
  const sutra = await getSutra(slug);

  if (!sutra) {
    return {
      title: "Kinh Phật | OmPraMani",
      description: "Thư viện kinh điển Phật giáo số.",
    };
  }

  const title = `${sutra.title} | OmPraMani`;
  const description =
    sutra.short_description ||
    `Đọc kinh văn ${sutra.title} với đầy đủ bản dịch, mục lục và phân phẩm trang nghiêm.`;
  const coverImage = sutra.thumbnail ? resolveMediaUrl(sutra.thumbnail) : SITE_CONFIG.defaultOgImage;

  return {
    title,
    description,
    alternates: {
      canonical: `/kinh/${sutra.slug}`,
    },
    openGraph: {
      title,
      description,
      url: `/kinh/${sutra.slug}`,
      siteName: SITE_CONFIG.siteName,
      locale: SITE_CONFIG.locale,
      type: "article",
      images: coverImage ? [{ url: coverImage }] : undefined,
    },
    twitter: {
      card: "summary_large_image",
      title,
      description,
      images: coverImage ? [coverImage] : undefined,
    },
  };
}

export default async function SutraDetailPage({
  params,
  searchParams,
}: SutraDetailPageProps) {
  await connection();
  const { slug } = await params;
  const resolvedSearchParams = await searchParams;

  // 1. Fetch Sutra
  const sutra = await getSutra(slug);
  if (!sutra) {
    notFound();
  }

  // 2. Fetch Versions
  const versions = await getSutraVersions(slug);
  const activeVersionId = resolvedSearchParams.version
    ? parseInt(resolvedSearchParams.version, 10)
    : versions.find((v) => v.is_default)?.id || versions[0]?.id;

  // 3. Fetch Tree of Sections
  let tree: SutraSectionTreeNode[] = [];
  if (activeVersionId) {
    tree = await getSutraSections(activeVersionId);
  }
  if (tree.length === 0) {
    tree = await getSutraSections(slug);
  }

  // 4. Flatten Sections for TOC
  const flattenedSections = flattenSections(tree);

  // 5. Initial active section
  const sectionParam = resolvedSearchParams.section;
  let activeSectionNode: SutraSectionTreeNode | undefined;
  if (sectionParam) {
    const parsedId = parseInt(sectionParam, 10);
    activeSectionNode = flattenedSections.find((s) => s.id === parsedId);
  }
  if (!activeSectionNode && flattenedSections.length > 0) {
    activeSectionNode = flattenedSections[0];
  }

  // 6. Fetch Section detail
  let sectionDetail: SutraSectionDetail | null = null;
  if (activeSectionNode) {
    sectionDetail = await getSutraSection(activeSectionNode.id);
  }

  // BreadcrumbList JSON-LD
  const breadcrumbJsonLd = {
    "@context": "https://schema.org",
    "@type": "BreadcrumbList",
    itemListElement: [
      {
        "@type": "ListItem",
        position: 1,
        name: "Trang chủ",
        item: SITE_CONFIG.siteUrl,
      },
      {
        "@type": "ListItem",
        position: 2,
        name: "Thư viện Kinh",
        item: `${SITE_CONFIG.siteUrl}/kinh`,
      },
      {
        "@type": "ListItem",
        position: 3,
        name: sutra.title,
        item: `${SITE_CONFIG.siteUrl}/kinh/${sutra.slug}`,
      },
    ],
  };

  return (
    <div className="min-h-screen flex flex-col bg-surface font-body-md text-on-surface antialiased">
      <script
        type="application/ld+json"
        dangerouslySetInnerHTML={{ __html: JSON.stringify(breadcrumbJsonLd) }}
      />
      <Header />

      <main id="main-content" className="w-full bg-surface flex-1 pt-16">
        <div className="max-w-[1360px] mx-auto px-4 sm:px-6 py-8 space-y-10">
          {/* 1. Phần thông tin chung của bài kinh */}
          <SutraHeader
            sutra={sutra}
            activeVersion={sutra.versions?.find((v) => v.id === activeVersionId)}
            totalSections={flattenedSections.length}
          />

          {/* 2. Mục lục dọc tụng kinh (Accordion: nhấn để mở, không thì đóng lại) */}
          {flattenedSections.length > 0 ? (
            <SutraVerticalAccordionTOC
              sutraSlug={sutra.slug}
              sutraId={sutra.id}
              versionId={activeVersionId}
              sections={flattenedSections}
              initialActiveSectionId={activeSectionNode?.id}
              initialSectionDetail={sectionDetail}
            />
          ) : (
            <div className="bg-surface-container-lowest rounded-3xl p-12 text-center border border-outline-variant/30 space-y-4">
              <span className="material-symbols-outlined text-outline text-[40px]">
                hourglass_empty
              </span>
              <h2 className="font-headline-lg text-headline-lg font-semibold text-on-surface">
                Bản kinh đang được biên tập
              </h2>
              <p className="font-body-md text-on-surface-variant max-w-md mx-auto">
                Kinh văn này đang được Ban Biên Tập hoàn thiện các phẩm mục. Xin
                quý Phật tử hoan hỷ quay lại sau.
              </p>
            </div>
          )}
        </div>
      </main>

      <Footer />
    </div>
  );
}
