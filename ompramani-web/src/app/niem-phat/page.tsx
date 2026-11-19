import React, { Suspense } from "react";
import { redirect } from "next/navigation";

interface NiemPhatPageProps {
  searchParams: Promise<{
    figure?: string;
  }>;
}

/**
 * Legacy Niem Phat URL Redirect:
 * Chuyển hướng hợp nhất toàn bộ truy cập cũ về chức năng Trì Chú.
 */
async function RedirectContent({ searchParams }: NiemPhatPageProps): Promise<React.ReactNode> {
  const params = await searchParams;
  const figureSlug = params?.figure;

  if (figureSlug) {
    redirect(`/phat-bo-tat/${encodeURIComponent(figureSlug)}?tab=than-chu`);
  }

  redirect("/tri-chu");
  return null;
}

export default function NiemPhatPage(props: NiemPhatPageProps) {
  return (
    <Suspense
      fallback={
        <div className="min-h-screen bg-surface flex flex-col items-center justify-center p-6 text-center">
          <div className="w-10 h-10 border-3 border-primary border-t-transparent rounded-full animate-spin mb-4" />
          <p className="font-label-md text-xs text-outline">
            Đang chuyển hướng đến không gian Trì Chú...
          </p>
        </div>
      }
    >
      <RedirectContent searchParams={props.searchParams} />
    </Suspense>
  );
}
