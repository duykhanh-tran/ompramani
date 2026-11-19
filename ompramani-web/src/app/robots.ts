import { MetadataRoute } from "next";
import { SITE_CONFIG, isIndexingEnabled } from "@/lib/seo/config";

export default function robots(): MetadataRoute.Robots {
  const allow = isIndexingEnabled();

  // If public indexing is not explicitly enabled (Dev / Staging / Pre-launch), disallow all crawling
  if (!allow) {
    return {
      rules: {
        userAgent: "*",
        disallow: "/",
      },
    };
  }

  // Production approved: Allow public content while protecting private / admin / sensitive routes
  return {
    rules: [
      {
        userAgent: "*",
        allow: "/",
        disallow: [
          "/api/",
          "/dang-nhap",
          "/dang-ky",
          "/quen-mat-khau",
          "/dat-lai-mat-khau",
          "/xac-thuc-email",
          "/gio-hang",
          "/thanh-toan",
          "/don-hang/",
          "/tai-khoan/",
          "/cau-nguyen/",
          "/tim-kiem",
          "/_next/",
        ],
      },
    ],
    sitemap: `${SITE_CONFIG.siteUrl}/sitemap.xml`,
  };
}
