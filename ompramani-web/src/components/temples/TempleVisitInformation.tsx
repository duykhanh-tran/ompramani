import React from "react";
import { TempleVisitInformation as VisitInfoType, TemplePublicAccessStatus } from "@/types/temple";

interface TempleVisitInformationProps {
  visitInfo?: VisitInfoType | null;
  publicAccessStatus?: TemplePublicAccessStatus;
  publicAccessStatusLabel?: string;
  hasContacts?: boolean;
}

export function TempleVisitInformation({
  visitInfo,
  publicAccessStatus = "unknown",
  publicAccessStatusLabel = "Chưa rõ thông tin",
  hasContacts,
}: TempleVisitInformationProps) {
  const status = visitInfo?.access_status || publicAccessStatus;
  const statusLabel = visitInfo?.access_status_label || publicAccessStatusLabel;
  const notes = visitInfo?.notes;
  const source = visitInfo?.source;
  const verifiedAt = visitInfo?.verified_at;

  const getStatusDescription = (st: TemplePublicAccessStatus) => {
    switch (st) {
      case "publicly_accessible":
        return "Tự viện có mở cửa đón tiếp quý Phật tử và khách thập phương chiêm bái, lễ Phật.";
      case "by_appointment":
        return "Không gian tu học chuyên sâu, quý khách cần liên hệ trước với ban quản trị hoặc tri khách tự viện.";
      case "restricted":
        return "Khu vực tịnh tu nội viện hạn chế tiếp đón khách vãng lai.";
      case "unknown":
      default:
        return "Điều kiện tiếp đón chưa được xác nhận chính thức. Quý Phật tử nên tìm hiểu trước khi đến viếng.";
    }
  };

  return (
    <section
      aria-labelledby="visit-guidance-heading"
      className="bg-surface-container-lowest p-6 rounded-2xl border border-outline-variant/40 space-y-4 shadow-xs"
    >
      <div className="flex items-center justify-between gap-2 border-b border-outline-variant/30 pb-3 flex-wrap">
        <h2
          id="visit-guidance-heading"
          className="font-headline-sm text-base sm:text-lg font-semibold text-on-surface flex items-center gap-2"
        >
          <span className="material-symbols-outlined text-primary text-[20px]">
            info
          </span>
          Thông Tin Thăm Viếng
        </h2>

        <span className="px-2.5 py-0.5 rounded-full text-xs font-medium bg-surface-container-high text-on-surface-variant">
          {statusLabel}
        </span>
      </div>

      {/* Access status description */}
      <div className="p-3.5 rounded-xl bg-surface-container-low/60 border border-outline-variant/20 text-xs sm:text-sm text-on-surface-variant leading-relaxed">
        {getStatusDescription(status)}
      </div>

      {/* Specific Verified visit notes if present */}
      {notes ? (
        <div className="space-y-2 pt-1">
          <h3 className="font-label-md text-xs font-semibold text-on-surface uppercase tracking-wider">
            Hướng Dẫn Chi Tiết Đã Xác Minh:
          </h3>
          <div className="font-body-md text-sm text-on-surface leading-relaxed whitespace-pre-line bg-surface-container-lowest p-4 rounded-xl border border-outline-variant/30">
            {notes}
          </div>

          {source && (
            <div className="text-xs text-on-surface-variant flex items-center gap-1.5 pt-1">
              <span className="material-symbols-outlined text-[15px] text-primary">
                verified
              </span>
              <span>Căn cứ xác minh:</span>
              {source.url ? (
                <a
                  href={source.url}
                  target="_blank"
                  rel="noopener noreferrer"
                  className="text-primary hover:underline font-medium inline-flex items-center gap-0.5"
                >
                  <span>{source.title}</span>
                  <span className="material-symbols-outlined text-[12px]">open_in_new</span>
                </a>
              ) : (
                <span className="font-medium text-on-surface">{source.title}</span>
              )}
            </div>
          )}

          {verifiedAt && (
            <div className="text-[11px] text-on-surface-variant/70 italic">
              Thời điểm kiểm chứng hướng dẫn: {new Date(verifiedAt).toLocaleDateString("vi-VN")}
            </div>
          )}
        </div>
      ) : (
        <div className="space-y-2 text-xs sm:text-sm text-on-surface-variant italic">
          <p>
            Chưa có hướng dẫn thăm viếng chi tiết được cơ sở công bố.
          </p>
          {hasContacts && (
            <p className="text-on-surface-variant/90 not-italic">
              Quý đạo hữu nên tham khảo kênh liên hệ chính thức trước khi đến chiêm bái.
            </p>
          )}
        </div>
      )}

      {/* General editorial etiquette reminder */}
      <div className="p-3.5 rounded-xl bg-primary/5 border border-primary/15 text-xs text-on-surface-variant space-y-1">
        <div className="font-medium text-primary flex items-center gap-1">
          <span className="material-symbols-outlined text-[16px]">self_improvement</span>
          <span>Tâm niệm khi chiêm bái:</span>
        </div>
        <p className="leading-relaxed">
          Kính mong quý Phật tử giữ thân tâm trang nghiêm, ăn mặc kín đáo, bước nhẹ, nói khẽ và tuân thủ các quy định chung của chốn tịnh tu.
        </p>
      </div>
    </section>
  );
}
