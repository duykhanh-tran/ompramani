import React from "react";
import type { Metadata } from "next";
import { notFound } from "next/navigation";
import { Header } from "@/components/layout/Header";
import { Footer } from "@/components/layout/Footer";
import { MantraHero } from "@/components/mantra/MantraHero";
import { MantraVersionSelector } from "@/components/mantra/MantraVersionSelector";
import { MantraReaderArea } from "@/components/mantra/MantraReaderArea";
import { RelatedFigureCard } from "@/components/mantra/RelatedFigureCard";
import { MantraChantPlayer } from "@/components/mantra/MantraChantPlayer";
import {
  getMantra,
  getMantraVersions,
  getMantraVersion,
} from "@/lib/api/mantras";
import { resolveMediaUrl } from "@/lib/utils/media";
import { MantraVersion } from "@/types/api";
import { SITE_CONFIG } from "@/lib/seo/config";

interface MantraDetailPageProps {
  params: Promise<{
    slug: string;
  }>;
  searchParams: Promise<{
    version?: string;
  }>;
}

export async function generateMetadata({
  params,
}: MantraDetailPageProps): Promise<Metadata> {
  const { slug } = await params;
  const mantra = await getMantra(slug);

  if (!mantra) {
    return {
      title: "Thần Chú Phật Giáo | OmPraMani",
      description: "Thư viện thần chú và đà la ni trên OmPraMani.",
    };
  }

  const rawImage =
    mantra.thumbnail ||
    mantra.figure?.thumbnail ||
    mantra.buddhist_figure?.thumbnail ||
    mantra.image_url;
  const imageUrl = resolveMediaUrl(rawImage);
  const title = `${mantra.title} | OmPraMani`;
  const description =
    mantra.short_description ||
    `Đọc và trì tụng ${mantra.title} với đầy đủ nguyên văn, phiên âm và hướng dẫn hành trì.`;

  return {
    title,
    description,
    alternates: {
      canonical: `/chu/${mantra.slug}`,
    },
    openGraph: {
      title,
      description,
      url: `/chu/${mantra.slug}`,
      siteName: SITE_CONFIG.siteName,
      locale: SITE_CONFIG.locale,
      type: "article",
      images: imageUrl ? [{ url: imageUrl }] : undefined,
    },
    twitter: {
      card: "summary_large_image",
      title,
      description,
      images: imageUrl ? [imageUrl] : undefined,
    },
  };
}

export default async function MantraDetailPage({
  params,
  searchParams,
}: MantraDetailPageProps) {
  const { slug } = await params;
  const resolvedSearchParams = await searchParams;

  // 1. Fetch Mantra
  const mantra = await getMantra(slug);
  if (!mantra) {
    notFound();
  }

  // 2. Resolve published versions or direct text
  let versions: MantraVersion[] = mantra.versions || [];
  if (versions.length === 0) {
    versions = await getMantraVersions(slug);
  }

  // Filter out any non-published version if status field is present
  const publishedVersions = versions.filter(
    (v) => !v.status || v.status === "published"
  );

  const hasDirectText = Boolean(
    (mantra.original_text && mantra.original_text.trim().length > 0) ||
    (mantra.vietnamese_transcription && mantra.vietnamese_transcription.trim().length > 0)
  );

  if (publishedVersions.length === 0 && !hasDirectText) {
    return (
      <div className="min-h-screen flex flex-col bg-surface font-body-md text-on-surface antialiased">
        <Header />
        <main id="main-content" className="w-full bg-surface flex-1 pt-16">
          <div className="max-w-[1360px] mx-auto px-4 sm:px-6 py-8 space-y-8">
            <MantraHero mantra={mantra} />
            <div className="bg-surface-container-lowest rounded-3xl p-12 text-center border border-outline-variant/30 space-y-4">
              <span className="material-symbols-outlined text-outline text-[40px]">
                hourglass_empty
              </span>
              <h2 className="font-headline-lg text-headline-lg font-semibold text-on-surface">
                Bản thần chú đang được biên tập
              </h2>
              <p className="font-body-md text-on-surface-variant max-w-md mx-auto">
                Thần chú này hiện chưa có phiên bản nguyên văn hoặc phiên âm nào
                được xuất bản công khai. Xin quý Phật tử hoan hỷ quay lại sau.
              </p>
            </div>
            <RelatedFigureCard figure={mantra.figure || mantra.buddhist_figure} />
          </div>
        </main>
        <Footer />
      </div>
    );
  }

  // 3. Resolve active version from ?version= or fallback to default published version / direct text
  const reqVersionId = resolvedSearchParams.version
    ? parseInt(resolvedSearchParams.version, 10)
    : null;

  let activeVersion: MantraVersion | null = null;
  if (publishedVersions.length > 0) {
    activeVersion = publishedVersions.find((v) => v.id === reqVersionId) || null;
    if (!activeVersion) {
      activeVersion =
        publishedVersions.find((v) => v.is_default) ||
        mantra.default_version ||
        publishedVersions[0];
    }
  } else {
    activeVersion = {
      id: 0,
      mantra_id: mantra.id,
      name: mantra.title,
      is_default: true,
      content: mantra.original_text || "",
      transliteration: mantra.vietnamese_transcription || null,
      status: "published",
    };
  }

  // 4. Fetch version detail from GET /api/v1/mantra-versions/{id}
  const versionDetail =
    activeVersion.id > 0
      ? (await getMantraVersion(activeVersion.id)) || activeVersion
      : activeVersion;

  return (
    <div className="min-h-screen flex flex-col bg-surface font-body-md text-on-surface antialiased">
      <Header />

      <main id="main-content" className="w-full bg-surface flex-1 pt-16">
        <div className="max-w-[1360px] mx-auto px-4 sm:px-6 py-8 space-y-8">
          {/* 1. Mantra Hero */}
          <MantraHero mantra={mantra} />

          {/* Mantra Chant Audio Player */}
          <div className="bg-[#FAF7F0] border border-[#dfd2be] rounded-3xl p-6 shadow-sm flex flex-col items-center justify-center">
            <MantraChantPlayer
              audioUrl={mantra.audio_url}
              mantraTitle={mantra.title}
            />
          </div>

          {/* 2. Version Selector */}
          {publishedVersions.length > 1 && (
            <MantraVersionSelector
              mantraSlug={mantra.slug}
              versions={publishedVersions}
              activeVersionId={activeVersion.id}
            />
          )}

          {/* 3. Sacred Mantra Reader Area + Practice Guidance */}
          <MantraReaderArea
            version={versionDetail}
            practiceGuidance={mantra.practice_guidance}
          />

          {/* 4. Related Buddhist Figure Card */}
          <RelatedFigureCard figure={mantra.figure || mantra.buddhist_figure} />
        </div>
      </main>

      <Footer />
    </div>
  );
}
