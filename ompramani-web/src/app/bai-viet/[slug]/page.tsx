import React from "react";
import type { Metadata } from "next";
import { notFound } from "next/navigation";
import Script from "next/script";
import { Header } from "@/components/layout/Header";
import { Footer } from "@/components/layout/Footer";
import { ArticleHero } from "@/components/article/ArticleHero";
import { ArticleContent } from "@/components/article/ArticleContent";
import { ArticleTags } from "@/components/article/ArticleTags";
import { RelatedBuddhistContentSection } from "@/components/article/RelatedBuddhistContentCard";
import { RelatedArticlesSidebar } from "@/components/article/RelatedArticlesSidebar";
import { getArticle, getArticles } from "@/lib/api/articles";
import { resolveMediaUrl } from "@/lib/utils/media";
import { SITE_CONFIG } from "@/lib/seo/config";

interface ArticleDetailPageProps {
  params: Promise<{
    slug: string;
  }>;
}

export async function generateMetadata({
  params,
}: ArticleDetailPageProps): Promise<Metadata> {
  const { slug } = await params;
  const article = await getArticle(slug);

  if (!article) {
    return {
      title: "Bài Viết | OmPraMani",
      description: "Thư viện bài viết và pháp thoại trên nền tảng Phật giáo số OmPraMani.",
    };
  }

  const coverUrl = resolveMediaUrl(article.cover_image);
  const title = article.meta_title || article.title;
  const description =
    article.meta_description ||
    article.excerpt ||
    article.summary ||
    `Bài viết ${article.title} trên nền tảng Phật giáo số OmPraMani.`;

  return {
    title: `${title} | OmPraMani`,
    description,
    alternates: {
      canonical: `/bai-viet/${article.slug}`,
    },
    openGraph: {
      title: `${title} | OmPraMani`,
      description,
      url: `/bai-viet/${article.slug}`,
      siteName: SITE_CONFIG.siteName,
      locale: SITE_CONFIG.locale,
      type: "article",
      publishedTime: article.published_at || undefined,
      authors: article.author?.name ? [article.author.name] : undefined,
      images: coverUrl ? [{ url: coverUrl }] : undefined,
    },
    twitter: {
      card: "summary_large_image",
      title: `${title} | OmPraMani`,
      description,
      images: coverUrl ? [coverUrl] : undefined,
    },
  };
}

export default async function ArticleDetailPage({
  params,
}: ArticleDetailPageProps) {
  const { slug } = await params;
  const article = await getArticle(slug);

  if (!article) {
    notFound();
  }

  const coverUrl = resolveMediaUrl(article.cover_image);
  const jsonLd = {
    "@context": "https://schema.org",
    "@type": "Article",
    headline: article.title,
    description: article.excerpt || article.summary || "",
    image: coverUrl ? [coverUrl] : undefined,
    datePublished: article.published_at,
    author: {
      "@type": "Person",
      name: article.author?.name || "Ban Biên Tập OmPraMani",
    },
    publisher: {
      "@type": "Organization",
      name: "OmPraMani",
      logo: {
        "@type": "ImageObject",
        url: "https://ompramani.com/logo.png",
      },
    },
    mainEntityOfPage: {
      "@type": "WebPage",
      "@id": `https://ompramani.com/bai-viet/${article.slug}`,
    },
  };

  let sidebarArticles = article.related_articles || [];
  if (sidebarArticles.length === 0) {
    try {
      const fallback = await getArticles({
        per_page: 6,
        category: article.category?.slug,
      });
      sidebarArticles = fallback.data || [];
    } catch {
      sidebarArticles = [];
    }
  }

  return (
    <div className="min-h-screen flex flex-col bg-surface font-body-md text-on-surface antialiased">
      <Header />

      {/* Structured Data JSON-LD */}
      <Script
        id={`article-jsonld-${article.id}`}
        type="application/ld+json"
        dangerouslySetInnerHTML={{ __html: JSON.stringify(jsonLd) }}
      />

      <main id="main-content" className="w-full bg-surface flex-1 pt-16">
        <div className="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8 py-8 sm:py-12">
          <div className="flex flex-col lg:flex-row items-start gap-8 lg:gap-10">
            {/* Left 80%: Full Article Body */}
            <div className="w-full lg:w-[80%] min-w-0 space-y-8">
              {/* Article Hero */}
              <ArticleHero article={article} />

              {/* Article Main Content */}
              <ArticleContent
                content={article.content}
                articleTitle={article.title}
              />

              {/* Article Tags */}
              <ArticleTags tags={article.tags} />

              {/* Related Buddhist Content (Sutra, Mantra, Figure) */}
              <RelatedBuddhistContentSection items={article.related_content} />
            </div>

            {/* Right 20%: Related Articles Sidebar */}
            <aside className="w-full lg:w-[20%] shrink-0 lg:sticky lg:top-24">
              <RelatedArticlesSidebar
                articles={sidebarArticles}
                currentSlug={article.slug}
              />
            </aside>
          </div>
        </div>
      </main>

      <Footer />
    </div>
  );
}
