import React, { Suspense } from "react";
import type { Metadata } from "next";
import { notFound } from "next/navigation";
import { Header } from "@/components/layout/Header";
import { Footer } from "@/components/layout/Footer";
import { getProductBySlug, getProducts } from "@/lib/api/shop";
import { ProductSummary } from "@/types/shop";
import { ProductDetailClient } from "@/components/shop/ProductDetailClient";
import { SITE_CONFIG } from "@/lib/seo/config";

interface ProductDetailPageProps {
  params: Promise<{ slug: string }>;
}

export async function generateMetadata({
  params,
}: ProductDetailPageProps): Promise<Metadata> {
  try {
    const { slug } = await params;
    const product = await getProductBySlug(slug);

    if (!product) {
      return {
        title: "Không tìm thấy vật phẩm | OmPraMani",
        description: "Vật phẩm không tồn tại hoặc chưa được xuất bản.",
      };
    }

    const title = `${product.name} — Cửa Hàng OmPraMani`;
    const description =
      product.short_description ||
      `Chi tiết vật phẩm tu học ${product.name} tại Cửa Hàng Phật Giáo OmPraMani.`;

    return {
      title,
      description,
      alternates: {
        canonical: `/cua-hang/${product.slug}`,
      },
      openGraph: {
        title,
        description,
        url: `/cua-hang/${product.slug}`,
        siteName: SITE_CONFIG.siteName,
        locale: SITE_CONFIG.locale,
        type: "website",
        images: product.cover_image ? [{ url: product.cover_image }] : [],
      },
    };
  } catch {
    return {
      title: "Vật Phẩm Phật Giáo | OmPraMani",
      description: "Cửa hàng vật phẩm Phật giáo OmPraMani.",
    };
  }
}

async function ProductDetailContent({ params }: ProductDetailPageProps) {
  const { slug } = await params;
  let product;

  try {
    product = await getProductBySlug(slug);
  } catch {
    product = null;
  }

  if (!product) {
    notFound();
  }

  let relatedProducts: ProductSummary[] = [];
  try {
    const relatedRes = await getProducts({
      category: product.category?.slug,
      per_page: 8,
    });
    relatedProducts = (relatedRes.data || []).filter(
      (p) => p.slug !== product.slug
    );
    if (relatedProducts.length < 4) {
      const fallbackRes = await getProducts({ per_page: 8 });
      const fallbacks = (fallbackRes.data || []).filter(
        (p) =>
          p.slug !== product.slug &&
          !relatedProducts.some((rp) => rp.slug === p.slug)
      );
      relatedProducts = [...relatedProducts, ...fallbacks].slice(0, 5);
    }
  } catch {
    relatedProducts = [];
  }

  const jsonLd = {
    "@context": "https://schema.org",
    "@type": "Product",
    name: product.name,
    description: product.short_description || product.name,
    image: product.cover_image ? [product.cover_image] : undefined,
    sku: product.sku || undefined,
    offers: {
      "@type": "Offer",
      price: product.price_vnd,
      priceCurrency: "VND",
      availability:
        product.is_available
          ? "https://schema.org/InStock"
          : "https://schema.org/OutOfStock",
      url: `${SITE_CONFIG.siteUrl}/cua-hang/${product.slug}`,
    },
  };

  return (
    <>
      <script
        type="application/ld+json"
        dangerouslySetInnerHTML={{ __html: JSON.stringify(jsonLd) }}
      />
      <ProductDetailClient
        product={product}
        relatedProducts={relatedProducts}
      />
    </>
  );
}

export default function ProductDetailPage({ params }: ProductDetailPageProps) {
  return (
    <div className="min-h-screen flex flex-col bg-surface font-body-md text-on-surface antialiased">
      <Header />

      <main id="main-content" className="w-full flex-1 pt-20 pb-16">
        <div className="max-w-[1360px] mx-auto px-4 sm:px-6">
          <Suspense
            fallback={
              <div className="max-w-4xl mx-auto px-4 sm:px-6 space-y-6 animate-pulse">
                <div className="h-5 w-40 bg-surface-container-high rounded" />
                <div className="aspect-square max-w-md w-full bg-surface-container-high rounded-3xl" />
                <div className="h-8 w-2/3 bg-surface-container-high rounded" />
                <div className="h-24 bg-surface-container-lowest rounded-2xl border border-outline-variant/30" />
              </div>
            }
          >
            <ProductDetailContent params={params} />
          </Suspense>
        </div>
      </main>

      <Footer />
    </div>
  );
}
