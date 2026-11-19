export const SITE_CONFIG = {
  siteUrl: process.env.NEXT_PUBLIC_SITE_URL || "https://ompramani.com",
  siteName: "OmPraMani",
  defaultTitle: "OmPraMani — Cổng Thông Tin & Không Gian Tu Học Phật Giáo Số",
  titleTemplate: "%s | OmPraMani",
  defaultDescription:
    "Không gian tu tập và đọc kinh Phật giáo trực tuyến. Cung cấp thư viện kinh điển Tịnh Độ, Thần chú, Đại nguyện Phật & Bồ Tát, Lịch vạn niên và Pháp bảo tu học trang nghiêm.",
  defaultOgImage:
    "https://lh3.googleusercontent.com/aida-public/AB6AXuA0yHXbepcasIOm7sdXStFD3jiTbEWkLEAuhYWHZoReJePvbIXopc820f28MkNX-wB7UZi4KzW1h5duIxv32pDxQIQCJs93zhGbWD2RuO41QWZZdoHgw9FUZ6G_5NLYvZHXMWde_BDeXdHmHhOmCpaNqKjG9fS9CESHLSlHBD4666w6Ib9scXz19o_ydlmVm8s5DqEMixxNsiVmq7vEIoiocrfJVJZOk9Jok8kDE6A",
  locale: "vi_VN",
};

/**
 * Strict environment policy:
 * Pre-production / Staging / Development must remain NOINDEX by default.
 * Only enable public indexing when NEXT_PUBLIC_INDEXING_ENABLED or SEO_INDEXING_ENABLED is explicitly "true".
 */
export const isIndexingEnabled = (): boolean => {
  return (
    process.env.NEXT_PUBLIC_INDEXING_ENABLED === "true" ||
    process.env.SEO_INDEXING_ENABLED === "true"
  );
};

export const getRobotsPolicy = (forceNoindex: boolean = false) => {
  const allow = isIndexingEnabled() && !forceNoindex;

  return {
    index: allow,
    follow: allow,
    nocache: !allow,
    googleBot: {
      index: allow,
      follow: allow,
      noimageindex: !allow,
      "max-video-preview": allow ? -1 : 0,
      "max-image-preview": allow ? ("large" as const) : ("none" as const),
      "max-snippet": allow ? -1 : 0,
    },
  };
};

export const getCanonicalUrl = (pathname: string): string => {
  const cleanPath = pathname.startsWith("/") ? pathname : `/${pathname}`;
  return `${SITE_CONFIG.siteUrl}${cleanPath}`;
};

export const stripHtml = (html: string): string => {
  return html
    .replace(/<[^>]*>?/gm, "")
    .replace(/\s+/g, " ")
    .trim();
};
