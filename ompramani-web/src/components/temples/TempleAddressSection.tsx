import React from "react";
import { TempleAddress } from "@/types/temple";
import { isValidGoogleMapsUrl } from "@/lib/utils/maps";

interface TempleAddressSectionProps {
  address?: TempleAddress;
  provinceName?: string | null;
  countryCode?: string;
  hasVerifiedCoordinates?: boolean;
  googleMapsUrl?: string | null;
}

export function TempleAddressSection({
  address,
  provinceName,
  countryCode,
  hasVerifiedCoordinates,
  googleMapsUrl,
}: TempleAddressSectionProps) {
  const formatted = address?.formatted;
  const province = address?.province || provinceName;
  const country = address?.country_code || countryCode;
  const countryLabel = country === "VN" ? "Việt Nam" : country;

  const hasAddress = Boolean(formatted || province);
  const hasValidMap = isValidGoogleMapsUrl(googleMapsUrl);

  return (
    <section
      aria-labelledby="location-heading"
      className="bg-surface-container-lowest p-6 rounded-2xl border border-outline-variant/40 space-y-4 shadow-xs"
    >
      <div className="flex items-center justify-between gap-2 border-b border-outline-variant/30 pb-3">
        <h2
          id="location-heading"
          className="font-headline-sm text-base sm:text-lg font-semibold text-on-surface flex items-center gap-2"
        >
          <span className="material-symbols-outlined text-primary text-[20px]">
            location_on
          </span>
          Địa Chỉ &amp; Vị Trí
        </h2>

        {hasVerifiedCoordinates && (
          <span className="text-[11px] font-label-sm text-emerald-700 dark:text-emerald-300 bg-emerald-50 dark:bg-emerald-950/40 px-2 py-0.5 rounded border border-emerald-500/20">
            Tọa độ kiểm chứng
          </span>
        )}
      </div>

      {hasAddress ? (
        <div className="space-y-2 text-sm font-body-md text-on-surface leading-relaxed">
          {formatted && (
            <div>
              <span className="text-on-surface-variant font-medium block text-xs">
                Địa chỉ chi tiết:
              </span>
              <p className="mt-0.5 font-normal">{formatted}</p>
            </div>
          )}

          {province && (
            <div className="flex items-center gap-2 pt-1 text-xs text-on-surface-variant">
              <span>Tỉnh / Thành phố:</span>
              <span className="text-on-surface font-medium">{province}</span>
            </div>
          )}

          {countryLabel && (
            <div className="flex items-center gap-2 text-xs text-on-surface-variant">
              <span>Quốc gia:</span>
              <span className="text-on-surface font-medium">{countryLabel}</span>
            </div>
          )}
        </div>
      ) : (
        <p className="text-xs sm:text-sm text-on-surface-variant italic">
          Chưa có địa chỉ công khai được xác minh cho địa điểm này.
        </p>
      )}

      {/* Nút Chỉ đường: mở Google Maps khi có link hợp lệ */}
      {hasValidMap && (
        <div className="pt-2">
          <a
            href={googleMapsUrl!}
            target="_blank"
            rel="noopener noreferrer"
            className="w-full inline-flex items-center justify-center gap-2 px-5 py-2.5 rounded-xl bg-primary text-on-primary hover:bg-primary/90 font-label-md text-sm font-semibold transition-all shadow-xs hover:shadow-sm active:scale-[0.99] focus-visible:outline-2 focus-visible:outline-primary"
            title="Mở chỉ đường trên Google Maps (tab mới)"
          >
            <span className="material-symbols-outlined text-[18px]">directions</span>
            <span>Chỉ đường</span>
          </a>
        </div>
      )}

      <div className="pt-2 border-t border-outline-variant/20 text-[11px] font-body-xs text-on-surface-variant/70 italic">
        Vị trí hành chính được đối chiếu theo danh bạ tự viện chính thức.
      </div>
    </section>
  );
}
