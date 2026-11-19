import React, { Suspense } from "react";
import type { Metadata } from "next";
import { notFound } from "next/navigation";
import { Header } from "@/components/layout/Header";
import { Footer } from "@/components/layout/Footer";
import { getVideoBySlug, getVideos } from "@/lib/api/videos";
import { VideoWatchArea } from "@/components/videos/VideoWatchArea";
import { RelatedVideosSidebar } from "@/components/videos/RelatedVideosSidebar";
import { FavoriteButton } from "@/components/favorites/FavoriteButton";
import { VideoSummary } from "@/types/video";

interface VideoDetailPageProps {
  params: Promise<{ slug: string }>;
}

export async function generateMetadata({
  params,
}: VideoDetailPageProps): Promise<Metadata> {
  try {
    const { slug } = await params;
    const video = await getVideoBySlug(slug);

    if (!video) {
      return {
        title: "Không tìm thấy video | OmPraMani",
        description: "Video Phật pháp không tồn tại hoặc chưa được xuất bản.",
      };
    }

    const description =
      video.short_description ||
      `Theo dõi bài giảng ${video.title} ${
        video.speaker_name ? `do ${video.speaker_name} thuyết giảng` : ""
      } trên Thư viện Video OmPraMani.`;

    return {
      title: `${video.title} | OmPraMani`,
      description,
      alternates: {
        canonical: `/video-phat-phap/${video.slug}`,
      },
      openGraph: {
        title: `${video.title} | OmPraMani`,
        description,
        url: `/video-phat-phap/${video.slug}`,
        siteName: "OmPraMani",
        locale: "vi_VN",
        type: "video.other",
        images: video.thumbnail_url
          ? [
              {
                url: video.thumbnail_url,
                alt: video.title,
              },
            ]
          : undefined,
      },
    };
  } catch {
    return {
      title: "Video Phật Pháp | OmPraMani",
      description: "Thư viện video Phật pháp chính thống trên OmPraMani.",
    };
  }
}

async function VideoDetailContent({ params }: VideoDetailPageProps) {
  const { slug } = await params;
  let video;

  try {
    video = await getVideoBySlug(slug);
  } catch {
    video = null;
  }

  if (!video) {
    notFound();
  }

  // Fetch related lecture videos for the 20% right sidebar
  let relatedVideos: VideoSummary[] = [];
  try {
    const res = await getVideos({
      per_page: 8,
      category: video.category !== "other" ? video.category : undefined,
    });
    relatedVideos = res.data || [];
  } catch {
    relatedVideos = [];
  }

  const jsonLd = {
    "@context": "https://schema.org",
    "@type": "VideoObject",
    name: video.title,
    description: video.short_description || video.title,
    thumbnailUrl: video.thumbnail_url ? [video.thumbnail_url] : undefined,
    uploadDate: video.published_at,
    embedUrl: video.embed_url || undefined,
  };

  return (
    <>
      <script
        type="application/ld+json"
        dangerouslySetInnerHTML={{ __html: JSON.stringify(jsonLd) }}
      />
      <div className="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8">
      <div className="flex flex-col lg:flex-row items-start gap-8 lg:gap-10">
        {/* Left 80%: Video + Title & Favorite + Description */}
        <div className="w-full lg:w-[80%] min-w-0 space-y-6">
          {/* Video Player Frame */}
          <VideoWatchArea video={video} />

          {/* Title and Favorite Button */}
          <div className="space-y-3">
            <div className="flex items-start justify-between gap-4">
              <h1 className="font-headline-xl text-2xl sm:text-3xl font-serif font-bold text-on-surface tracking-tight leading-tight flex-1">
                {video.title}
              </h1>

              <div className="shrink-0 pt-1">
                <FavoriteButton
                  contentType="video"
                  contentPublicId={video.slug}
                  variant="pill"
                  showLabel
                />
              </div>
            </div>

            {video.speaker_name && (
              <div className="flex items-center gap-2 pt-1 text-sm font-body-md text-on-surface">
                <span className="material-symbols-outlined text-[18px] text-primary">
                  record_voice_over
                </span>
                <span className="text-on-surface-variant">Giảng sư / Thuyết giảng:</span>
                <strong className="font-semibold">{video.speaker_name}</strong>
              </div>
            )}

            {video.short_description && (
              <p className="font-body-md text-sm sm:text-base text-on-surface-variant pt-2 leading-relaxed border-t border-outline-variant/30">
                {video.short_description}
              </p>
            )}
          </div>

          {/* Detailed Description */}
          {video.description && (
            <section
              aria-labelledby="video-description-heading"
              className="bg-surface-container-lowest p-6 sm:p-8 rounded-2xl border border-outline-variant/40 space-y-4 shadow-xs"
            >
              <h2
                id="video-description-heading"
                className="font-headline-sm text-base sm:text-lg font-serif font-bold text-on-surface flex items-center gap-2 border-b border-outline-variant/30 pb-3"
              >
                <span className="material-symbols-outlined text-primary text-[20px]">
                  description
                </span>
                Nội Dung Bài Giảng
              </h2>
              <div className="font-body-md text-sm sm:text-base text-on-surface-variant leading-relaxed whitespace-pre-line space-y-4">
                {video.description}
              </div>
            </section>
          )}
        </div>

        {/* Right 20%: Related Videos Sidebar */}
        <aside className="w-full lg:w-[20%] shrink-0 lg:sticky lg:top-24">
          <RelatedVideosSidebar
            videos={relatedVideos}
            currentSlug={video.slug}
          />
        </aside>
      </div>
    </div>
    </>
  );
}

export default function VideoDetailPage({ params }: VideoDetailPageProps) {
  return (
    <div className="min-h-screen flex flex-col bg-surface font-body-md text-on-surface antialiased">
      <Header />

      <main id="main-content" className="w-full flex-1 pt-20 pb-16">
        <Suspense
          fallback={
            <div className="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8 space-y-6 animate-pulse">
              <div className="flex flex-col lg:flex-row gap-8">
                <div className="w-full lg:w-[80%] space-y-6">
                  <div className="aspect-video w-full bg-surface-container-high rounded-3xl" />
                  <div className="h-8 w-2/3 bg-surface-container-high rounded" />
                </div>
                <div className="w-full lg:w-[20%] space-y-4">
                  <div className="h-6 w-32 bg-surface-container-high rounded" />
                  <div className="aspect-video w-full bg-surface-container-high rounded-xl" />
                  <div className="aspect-video w-full bg-surface-container-high rounded-xl" />
                </div>
              </div>
            </div>
          }
        >
          <VideoDetailContent params={params} />
        </Suspense>
      </main>

      <Footer />
    </div>
  );
}
