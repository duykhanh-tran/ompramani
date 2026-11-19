import { MetadataRoute } from "next";
import { SITE_CONFIG } from "@/lib/seo/config";
import { getSutras } from "@/lib/api/sutras";
import { getBuddhistFigures } from "@/lib/api/figures";
import { getMantras } from "@/lib/api/mantras";
import { getArticles } from "@/lib/api/articles";
import { getTemples } from "@/lib/api/temples";
import { getVideos } from "@/lib/api/videos";
import { getProducts } from "@/lib/api/shop";

export default async function sitemap(): Promise<MetadataRoute.Sitemap> {
  const baseUrl = SITE_CONFIG.siteUrl;

  // 1. Static Core Public Pages
  const staticRoutes: MetadataRoute.Sitemap = [
    {
      url: `${baseUrl}`,
      lastModified: new Date(),
      changeFrequency: "daily",
      priority: 1.0,
    },
    {
      url: `${baseUrl}/kinh`,
      lastModified: new Date(),
      changeFrequency: "weekly",
      priority: 0.9,
    },
    {
      url: `${baseUrl}/phat-bo-tat`,
      lastModified: new Date(),
      changeFrequency: "weekly",
      priority: 0.9,
    },
    {
      url: `${baseUrl}/tri-chu`,
      lastModified: new Date(),
      changeFrequency: "weekly",
      priority: 0.9,
    },
    {
      url: `${baseUrl}/bai-viet`,
      lastModified: new Date(),
      changeFrequency: "daily",
      priority: 0.8,
    },
    {
      url: `${baseUrl}/tim-chua`,
      lastModified: new Date(),
      changeFrequency: "weekly",
      priority: 0.8,
    },
    {
      url: `${baseUrl}/video-phat-phap`,
      lastModified: new Date(),
      changeFrequency: "weekly",
      priority: 0.8,
    },
    {
      url: `${baseUrl}/cua-hang`,
      lastModified: new Date(),
      changeFrequency: "daily",
      priority: 0.8,
    },
    {
      url: `${baseUrl}/lich-van-nien`,
      lastModified: new Date(),
      changeFrequency: "daily",
      priority: 0.8,
    },
    {
      url: `${baseUrl}/thien`,
      lastModified: new Date(),
      changeFrequency: "monthly",
      priority: 0.7,
    },
    {
      url: `${baseUrl}/trang-hat`,
      lastModified: new Date(),
      changeFrequency: "monthly",
      priority: 0.7,
    },
    {
      url: `${baseUrl}/chuong-mo`,
      lastModified: new Date(),
      changeFrequency: "monthly",
      priority: 0.7,
    },
    {
      url: `${baseUrl}/niem-phat`,
      lastModified: new Date(),
      changeFrequency: "monthly",
      priority: 0.7,
    },
  ];

  // 2. Dynamic Published Content URLs
  const dynamicRoutes: MetadataRoute.Sitemap = [];

  try {
    const sutrasRes = await getSutras({ per_page: 50 });
    (sutrasRes.data || []).forEach((s) => {
      if (s.slug) {
        dynamicRoutes.push({
          url: `${baseUrl}/kinh/${s.slug}`,
          lastModified: new Date(),
          changeFrequency: "monthly",
          priority: 0.85,
        });
      }
    });
  } catch (err) {
    console.warn("Sitemap: Failed to load sutras:", err);
  }

  try {
    const figuresRes = await getBuddhistFigures(50);
    (figuresRes.data || []).forEach((f) => {
      if (f.slug) {
        dynamicRoutes.push({
          url: `${baseUrl}/phat-bo-tat/${f.slug}`,
          lastModified: f.published_at ? new Date(f.published_at) : new Date(),
          changeFrequency: "monthly",
          priority: 0.85,
        });
      }
    });
  } catch (err) {
    console.warn("Sitemap: Failed to load figures:", err);
  }

  try {
    const mantrasRes = await getMantras({ per_page: 50 });
    (mantrasRes.data || []).forEach((m) => {
      if (m.slug) {
        dynamicRoutes.push({
          url: `${baseUrl}/chu/${m.slug}`,
          lastModified: m.published_at ? new Date(m.published_at) : new Date(),
          changeFrequency: "monthly",
          priority: 0.85,
        });
      }
    });
  } catch (err) {
    console.warn("Sitemap: Failed to load mantras:", err);
  }

  try {
    const articlesRes = await getArticles({ per_page: 50 });
    (articlesRes.data || []).forEach((a) => {
      if (a.slug) {
        dynamicRoutes.push({
          url: `${baseUrl}/bai-viet/${a.slug}`,
          lastModified: a.published_at ? new Date(a.published_at) : new Date(),
          changeFrequency: "weekly",
          priority: 0.75,
        });
      }
    });
  } catch (err) {
    console.warn("Sitemap: Failed to load articles:", err);
  }

  try {
    const templesRes = await getTemples({ per_page: 50 });
    (templesRes.data || []).forEach((t) => {
      if (t.slug) {
        dynamicRoutes.push({
          url: `${baseUrl}/tim-chua/${t.slug}`,
          lastModified: t.published_at ? new Date(t.published_at) : new Date(),
          changeFrequency: "monthly",
          priority: 0.75,
        });
      }
    });
  } catch (err) {
    console.warn("Sitemap: Failed to load temples:", err);
  }

  try {
    const videosRes = await getVideos({ per_page: 50 });
    (videosRes.data || []).forEach((v) => {
      if (v.slug) {
        dynamicRoutes.push({
          url: `${baseUrl}/video-phat-phap/${v.slug}`,
          lastModified: v.published_at ? new Date(v.published_at) : new Date(),
          changeFrequency: "monthly",
          priority: 0.7,
        });
      }
    });
  } catch (err) {
    console.warn("Sitemap: Failed to load videos:", err);
  }

  try {
    const productsRes = await getProducts({ per_page: 50 });
    (productsRes.data || []).forEach((p) => {
      if (p.slug) {
        dynamicRoutes.push({
          url: `${baseUrl}/cua-hang/${p.slug}`,
          lastModified: p.published_at ? new Date(p.published_at) : new Date(),
          changeFrequency: "weekly",
          priority: 0.8,
        });
      }
    });
  } catch (err) {
    console.warn("Sitemap: Failed to load products:", err);
  }

  return [...staticRoutes, ...dynamicRoutes];
}
