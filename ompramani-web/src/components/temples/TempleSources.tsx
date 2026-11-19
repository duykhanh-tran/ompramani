import React from "react";
import { TempleSourceItem } from "@/types/temple";

interface TempleSourcesProps {
  sources?: TempleSourceItem[];
  lastVerifiedAt?: string | null;
}

export function TempleSources({ sources = [], lastVerifiedAt }: TempleSourcesProps) {
  const hasSources = sources && sources.length > 0;

  return (
    <section
      aria-labelledby="sources-heading"
      className="bg-surface-container-lowest p-6 rounded-2xl border border-outline-variant/40 space-y-4 shadow-xs"
    >
      <div className="flex items-center justify-between gap-2 border-b border-outline-variant/30 pb-3 flex-wrap">
        <h2
          id="sources-heading"
          className="font-headline-sm text-base sm:text-lg font-semibold text-on-surface flex items-center gap-2"
        >
          <span className="material-symbols-outlined text-primary text-[20px]">
            source
          </span>
          Nguồn Dữ Liệu &amp; Căn Cứ Xác Minh
        </h2>

        {lastVerifiedAt && (
          <span className="text-xs text-on-surface-variant font-label-sm">
            Kiểm tra: {new Date(lastVerifiedAt).toLocaleDateString("vi-VN")}
          </span>
        )}
      </div>

      {hasSources ? (
        <ul className="space-y-3 font-body-sm text-sm text-on-surface-variant">
          {sources.map((src, i) => (
            <li key={i} className="flex items-start gap-2.5 pb-2.5 border-b border-outline-variant/20 last:border-b-0 last:pb-0">
              <span className="material-symbols-outlined text-primary text-[18px] shrink-0 mt-0.5">
                verified_user
              </span>
              <div className="space-y-0.5 flex-1 min-w-0">
                <div className="flex items-center gap-2 flex-wrap">
                  <strong className="text-on-surface font-semibold text-sm">
                    {src.title}
                  </strong>
                  {src.type_label && (
                    <span className="px-2 py-0.5 bg-surface-container rounded text-[11px] text-on-surface-variant">
                      {src.type_label}
                    </span>
                  )}
                </div>

                {src.reference && (
                  <p className="text-xs text-on-surface-variant leading-relaxed">
                    {src.reference}
                  </p>
                )}

                {src.url && (
                  <a
                    href={src.url}
                    target="_blank"
                    rel="noopener noreferrer"
                    className="text-primary hover:underline text-xs inline-flex items-center gap-1 break-all pt-0.5"
                  >
                    <span>{src.url}</span>
                    <span className="material-symbols-outlined text-[13px]">
                      open_in_new
                    </span>
                  </a>
                )}

                {src.checked_at && (
                  <p className="text-[11px] text-on-surface-variant/70 italic pt-0.5">
                    Đối chiếu ngày: {new Date(src.checked_at).toLocaleDateString("vi-VN")}
                  </p>
                )}
              </div>
            </li>
          ))}
        </ul>
      ) : (
        <p className="text-xs sm:text-sm text-on-surface-variant italic">
          Thông tin được biên tập và đối chiếu theo hồ sơ kiểm chứng của Ban Biên tập OmPraMani.
        </p>
      )}

      {/* Stale data disclaimer */}
      <div className="pt-2 border-t border-outline-variant/20 text-[11px] font-body-xs text-on-surface-variant/80 italic leading-relaxed">
        Thông tin tham quan và liên hệ có thể thay đổi theo thời gian. Quý đạo hữu vui lòng liên hệ trước với cơ sở để có thông tin cập nhật nhất.
      </div>
    </section>
  );
}
