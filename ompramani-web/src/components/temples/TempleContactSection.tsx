import React from "react";
import { TempleContact } from "@/types/temple";

interface TempleContactSectionProps {
  contacts?: TempleContact;
}

export function TempleContactSection({ contacts }: TempleContactSectionProps) {
  const hasPhone = Boolean(contacts?.phone);
  const hasEmail = Boolean(contacts?.email);
  const hasWebsite = Boolean(contacts?.website);

  const hasAnyContact = hasPhone || hasEmail || hasWebsite;

  return (
    <section
      aria-labelledby="contact-heading"
      className="bg-surface-container-lowest p-6 rounded-2xl border border-outline-variant/40 space-y-4 shadow-xs"
    >
      <h2
        id="contact-heading"
        className="font-headline-sm text-base sm:text-lg font-semibold text-on-surface flex items-center gap-2 border-b border-outline-variant/30 pb-3"
      >
        <span className="material-symbols-outlined text-primary text-[20px]">
          contact_support
        </span>
        Liên Hệ Công Khai
      </h2>

      {hasAnyContact ? (
        <div className="space-y-3 font-body-sm text-sm">
          {hasPhone && contacts?.phone && (
            <div className="flex items-start gap-2.5">
              <span className="material-symbols-outlined text-primary text-[18px] shrink-0 mt-0.5">
                call
              </span>
              <div>
                <span className="text-xs text-on-surface-variant block font-medium">
                  Điện thoại liên hệ:
                </span>
                <a
                  href={`tel:${contacts.phone.replace(/\s+/g, "")}`}
                  className="font-medium text-primary hover:underline"
                >
                  {contacts.phone}
                </a>
              </div>
            </div>
          )}

          {hasEmail && contacts?.email && (
            <div className="flex items-start gap-2.5">
              <span className="material-symbols-outlined text-primary text-[18px] shrink-0 mt-0.5">
                mail
              </span>
              <div>
                <span className="text-xs text-on-surface-variant block font-medium">
                  Thư điện tử:
                </span>
                <a
                  href={`mailto:${contacts.email}`}
                  className="font-medium text-primary hover:underline break-all"
                >
                  {contacts.email}
                </a>
              </div>
            </div>
          )}

          {hasWebsite && contacts?.website && (
            <div className="flex items-start gap-2.5">
              <span className="material-symbols-outlined text-primary text-[18px] shrink-0 mt-0.5">
                language
              </span>
              <div>
                <span className="text-xs text-on-surface-variant block font-medium">
                  Website chính thức:
                </span>
                <a
                  href={contacts.website}
                  target="_blank"
                  rel="noopener noreferrer"
                  className="font-medium text-primary hover:underline break-all inline-flex items-center gap-1"
                >
                  <span>Mở website chính thức</span>
                  <span className="material-symbols-outlined text-[14px]">
                    open_in_new
                  </span>
                </a>
              </div>
            </div>
          )}
        </div>
      ) : (
        <div className="space-y-2">
          <p className="text-xs sm:text-sm text-on-surface-variant italic leading-relaxed">
            Cơ sở tự viện chưa công bố kênh liên hệ trực tiếp.
          </p>
          <p className="text-xs text-on-surface-variant/80">
            Quý đạo hữu có thể theo dõi thông tin qua Ban Trị sự Phật giáo địa phương.
          </p>
        </div>
      )}

      <div className="pt-2 border-t border-outline-variant/20 text-[11px] font-body-xs text-on-surface-variant/70 italic">
        Chỉ cung cấp kênh liên lạc đã được xác nhận với ban quản trị hoặc ban biên tập.
      </div>
    </section>
  );
}
