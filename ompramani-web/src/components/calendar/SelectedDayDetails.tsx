'use client';

import React, { useState } from 'react';
import Link from 'next/link';
import { CalendarDayData } from '@/types/calendar';
import { CalendarDayEventsData } from '@/types/calendar-events';
import { AlmanacDayData } from '@/types/almanac';
import {
  parseDateString,
  getTodayVietnamDate,
  VIETNAMESE_WEEKDAYS,
} from '@/lib/utils/calendar-date';
import { getKhongMinhXuatHanh } from '@/lib/utils/khong-minh';

interface SelectedDayDetailsProps {
  selectedDate: string;
  dayData: CalendarDayData | null;
  eventsData: CalendarDayEventsData | null;
  almanacData: AlmanacDayData | null;
  isLoading: boolean;
  onGoToday?: () => void;
}

export function SelectedDayDetails({
  selectedDate,
  dayData,
  eventsData,
  almanacData,
  isLoading,
  onGoToday,
}: SelectedDayDetailsProps) {
  const [copied, setCopied] = useState(false);
  const [showAllHours, setShowAllHours] = useState(false);
  const todayDateStr = getTodayVietnamDate();
  const isToday = selectedDate === todayDateStr;

  const handleCopyLink = async () => {
    try {
      const url = `${window.location.origin}/lich-van-nien?date=${selectedDate}`;
      await navigator.clipboard.writeText(url);
      setCopied(true);
      setTimeout(() => setCopied(false), 2000);
    } catch (err) {
      console.warn('Could not copy link:', err);
    }
  };

  // 1. Resolve Solar Date & Weekday
  const solarParts = parseDateString(selectedDate);
  const solarDateFormatted = solarParts
    ? `${String(solarParts.day).padStart(2, '0')}-${String(solarParts.month).padStart(2, '0')}-${solarParts.year}`
    : selectedDate;

  let weekdayName = dayData?.solar?.weekday_name;
  if (!weekdayName && solarParts) {
    const d = new Date(solarParts.year, solarParts.month - 1, solarParts.day);
    const day = d.getDay();
    const iso = day === 0 ? 7 : day;
    weekdayName = VIETNAMESE_WEEKDAYS[iso] || '';
  }

  // 2. Resolve Lunar Date & Can Chi
  const lunarDay = dayData?.lunar?.day;
  const lunarMonth = dayData?.lunar?.month;
  const lunarYear = dayData?.lunar?.year;
  const lunarDateFormatted =
    typeof lunarDay === 'number' && typeof lunarMonth === 'number' && typeof lunarYear === 'number'
      ? `${lunarDay}-${lunarMonth}-${lunarYear}`
      : '...';

  const canChiDay = dayData?.lunar?.day_can_chi || almanacData?.can_chi?.day || '';
  const canChiMonth = dayData?.lunar?.month_can_chi || almanacData?.can_chi?.month || '';
  const canChiYear = dayData?.lunar?.year_can_chi || almanacData?.can_chi?.year || '';

  // 3. Khổng Minh Xuất Hành
  const khongMinh =
    typeof lunarMonth === 'number' && typeof lunarDay === 'number'
      ? getKhongMinhXuatHanh(lunarMonth, lunarDay)
      : null;

  // 4. Auspicious Hours
  const hours = almanacData?.hours || [];
  const auspiciousHours = hours.filter((h) => h.is_auspicious);
  const formattedAuspiciousHours = auspiciousHours
    .map((h) => {
      const startH = parseInt(h.start_local_time.split(':')[0], 10);
      const endH = parseInt(h.end_local_time.split(':')[0], 10);
      return `${h.branch_name} (${startH}-${endH})`;
    })
    .join(', ');

  // 5. Buddhist Events & Trai Giới
  const events = eventsData?.events || [];
  const vegEvents = events.filter((e) => e.event_type === 'vegetarian_day');
  const buddhistFeasts = events.filter((e) => e.event_type !== 'vegetarian_day');

  // Classification & Solar term
  const isHoangDao = almanacData?.day_classification === 'hoang_dao';
  const isHacDao = almanacData?.day_classification === 'hac_dao';
  const classificationText = isHoangDao
    ? 'Ngày Hoàng Đạo'
    : isHacDao
      ? 'Ngày Hắc Đạo'
      : almanacData?.day_classification === 'neutral'
        ? 'Ngày Bình Thường'
        : 'Đang tra cứu...';

  const deityName = almanacData?.deity_name;
  const solarTerm = dayData?.lunar?.solar_term;

  // Dấu đầu dòng màu chu sa cố định, không phụ thuộc vào dark/light của trình duyệt
  const bulletSymbol = (
    <span className="text-[#8c2d19] font-bold text-base select-none shrink-0" style={{ color: '#8c2d19' }}>
      ✦
    </span>
  );

  return (
    <div className="bg-white border border-[#e7e5e4] rounded-3xl p-5 sm:p-6 shadow-sm flex flex-col justify-between transition-all text-[#211a13] w-full min-h-[580px] lg:min-h-[640px]">
      <div className="space-y-4">
        {/* Card Header */}
        <div className="flex items-center justify-between gap-3 pb-3 border-b border-[#e7e5e4]">
          <div>
            <h2 className="font-headline-md text-xl sm:text-2xl font-bold text-[#211a13] tracking-tight font-serif">
              {isToday ? 'Lịch âm hôm nay' : `Lịch ngày ${solarDateFormatted}`}
            </h2>
            <div className="mt-1 flex items-center gap-2">
              {isToday ? (
                <span className="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-[#fbf3eb] border border-[#d1c5b3] text-xs font-semibold text-[#785915]">
                  <span className="w-1.5 h-1.5 rounded-full bg-[#8c2d19] animate-pulse" />
                  Hôm nay
                </span>
              ) : (
                <button
                  type="button"
                  onClick={onGoToday}
                  className="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-[#f5f5f4] hover:bg-[#e7e5e4] border border-[#d6d3d1] text-xs font-semibold text-[#211a13] transition-all cursor-pointer shadow-2xs"
                >
                  <span className="material-symbols-outlined text-[14px]">today</span>
                  <span>xem lịch âm hôm nay</span>
                </button>
              )}
            </div>
          </div>

          {/* Copy / Share Button */}
          <button
            type="button"
            onClick={handleCopyLink}
            className="p-2 rounded-xl border border-[#d6d3d1] text-[#78716c] hover:text-[#211a13] hover:bg-[#f5f5f4] transition-colors shrink-0 cursor-pointer"
            title="Sao chép liên kết ngày này"
            aria-label="Sao chép liên kết"
          >
            <span className="material-symbols-outlined text-[18px]">
              {copied ? 'check' : 'share'}
            </span>
          </button>
        </div>

        {/* Simplified Daily Details List với màu chữ cố định sắc nét và khung cuộn ổn định */}
        <div className="space-y-3.5 sm:space-y-4 text-[#211a13] text-sm sm:text-[15px] leading-relaxed overflow-y-auto max-h-[460px] lg:max-h-[510px] pr-1 scrollbar-thin">
          {/* 1. Ngày Dương Lịch */}
          <div className="flex items-center gap-2.5 flex-wrap">
            {bulletSymbol}
            <span className="font-semibold text-[#211a13]">Ngày Dương Lịch:</span>
            <span className="px-3 py-0.5 rounded-md border border-[#d6d3d1] bg-[#f5f5f4] text-[#211a13] font-bold font-mono text-sm sm:text-base tracking-wider shadow-2xs">
              {solarDateFormatted}
            </span>
          </div>

          {/* 2. Ngày Âm Lịch */}
          <div className="flex items-center gap-2.5 flex-wrap">
            {bulletSymbol}
            <span className="font-semibold text-[#211a13]">Ngày Âm Lịch:</span>
            <span className="px-3 py-0.5 rounded-md border border-[#d6d3d1] bg-[#f5f5f4] text-[#211a13] font-bold font-mono text-sm sm:text-base tracking-wider shadow-2xs">
              {lunarDateFormatted}
            </span>
            {dayData?.lunar?.is_leap_month && (
              <span className="text-xs px-2 py-0.5 rounded-md bg-[#fef3c7] text-[#92400e] font-semibold border border-[#fde68a]">
                (Tháng nhuận)
              </span>
            )}
          </div>

          {/* 3. Ngày trong tuần */}
          <div className="flex items-center gap-2.5">
            {bulletSymbol}
            <span className="font-semibold text-[#211a13]">Ngày trong tuần:</span>
            <span className="font-bold text-[#211a13]">{weekdayName || '...'}</span>
          </div>

          {/* 4. Can Chi Ngày Tháng Năm */}
          <div className="flex items-start sm:items-center gap-2.5">
            {bulletSymbol}
            <div className="text-[#211a13]">
              {canChiDay ? (
                <span>
                  Ngày <strong className="font-bold text-[#211a13]">{canChiDay}</strong> tháng{' '}
                  <strong className="font-bold text-[#211a13]">{canChiMonth}</strong> năm{' '}
                  <strong className="font-bold text-[#211a13]">{canChiYear}</strong>
                </span>
              ) : (
                <span className="text-[#78716c]">Đang tải thông tin Can Chi...</span>
              )}
            </div>
          </div>

          {/* 5. Phân loại ngày & Tiết khí */}
          <div className="flex items-start gap-2.5">
            {bulletSymbol}
            <div className="flex flex-wrap items-center gap-x-2 gap-y-1">
              <span className="font-semibold text-[#211a13]">Phân loại ngày:</span>
              <span className="px-2.5 py-0.5 rounded-md bg-[#f5f5f4] border border-[#d6d3d1] font-bold text-[#211a13] text-xs sm:text-sm">
                {classificationText}
              </span>
              {deityName && (
                <span className="text-xs text-[#57534e] font-medium">
                  (Sao {deityName})
                </span>
              )}
              {solarTerm && (
                <span className="inline-flex items-center gap-1 text-xs px-2.5 py-0.5 rounded-full bg-[#fef3c7] text-[#92400e] font-semibold border border-[#fde68a]">
                  <span className="material-symbols-outlined text-[13px] text-[#b45309]">eco</span>
                  <span>Tiết {solarTerm}</span>
                </span>
              )}
            </div>
          </div>

          {/* 6. Xuất hành theo Khổng Minh */}
          {khongMinh && (
            <div className="flex items-start gap-2.5">
              {bulletSymbol}
              <div className="leading-relaxed text-[#211a13]">
                <span className="font-semibold text-[#211a13]">Xuất hành: </span>
                <span>
                  ngày <strong className="font-bold text-[#8c2d19]">{khongMinh.name}</strong>
                  <span className="text-[#44403c] font-normal"> – {khongMinh.description}</span>
                </span>
              </div>
            </div>
          )}

          {/* 7. Giờ Hoàng Đạo */}
          <div className="flex items-start gap-2.5">
            {bulletSymbol}
            <div className="space-y-1.5 flex-1">
              <div className="leading-relaxed">
                <span className="font-semibold text-[#211a13]">Giờ Hoàng Đạo: </span>
                {formattedAuspiciousHours ? (
                  <span className="font-bold text-[#211a13]">
                    {formattedAuspiciousHours}
                  </span>
                ) : (
                  <span className="text-[#78716c]">Đang tải giờ Hoàng Đạo...</span>
                )}
              </div>

              {hours.length > 0 && (
                <div>
                  <button
                    type="button"
                    onClick={() => setShowAllHours(!showAllHours)}
                    className="text-xs font-semibold text-[#785915] hover:text-[#5d4200] underline inline-flex items-center gap-0.5 mt-0.5 cursor-pointer"
                  >
                    <span>{showAllHours ? 'Thu gọn giờ địa chi' : 'Xem chi tiết 12 giờ địa chi'}</span>
                    <span className="material-symbols-outlined text-[14px]">
                      {showAllHours ? 'expand_less' : 'expand_more'}
                    </span>
                  </button>

                  {showAllHours && (
                    <div className="grid grid-cols-2 gap-1.5 mt-2 p-2 rounded-xl bg-[#fafaf9] border border-[#e7e5e4] text-xs max-h-[180px] overflow-y-auto scrollbar-thin">
                      {hours.map((h) => (
                        <div
                          key={h.branch_key}
                          className={`p-1.5 rounded-lg border flex items-center justify-between gap-1 text-[11px] ${
                            h.is_auspicious
                              ? 'bg-white border-[#d6d3d1] font-semibold text-[#211a13]'
                              : 'bg-[#f5f5f4] border-[#e7e5e4] text-[#78716c]'
                          }`}
                        >
                          <div>
                            <span className="text-[#211a13] font-medium">Giờ {h.branch_name} ({h.can_chi})</span>
                            <div className="text-[10px] text-[#78716c]">{h.start_local_time} - {h.end_local_time}</div>
                          </div>
                          <span
                            className={`px-1.5 py-0.2 rounded text-[9px] font-bold shrink-0 ${
                              h.is_auspicious
                                ? 'bg-[#fef3c7] text-[#92400e] border border-[#fde68a]'
                                : 'bg-[#e7e5e4] text-[#57534e]'
                            }`}
                          >
                            {h.is_auspicious ? 'Tốt' : 'Hắc'}
                          </span>
                        </div>
                      ))}
                    </div>
                  )}
                </div>
              )}
            </div>
          </div>

          {/* 8. Tinh chỉnh Phật giáo OmPraMani: Sự kiện Phật giáo & Lễ vía */}
          {buddhistFeasts.length > 0 && (
            <div className="flex items-start gap-2.5 pt-1">
              {bulletSymbol}
              <div className="space-y-1.5 flex-1">
                <span className="font-semibold text-[#211a13]">Lễ vía &amp; Phật sự:</span>
                <div className="space-y-2">
                  {buddhistFeasts.map((ev) => (
                    <div
                      key={ev.occurrence_id}
                      className="p-3 rounded-xl bg-[#fffbeb] border border-[#fde68a] text-xs sm:text-sm space-y-1"
                    >
                      <div className="flex items-center justify-between gap-2 flex-wrap">
                        <strong className="font-bold text-[#92400e] text-sm sm:text-base">
                          {ev.title}
                        </strong>
                        <span className="px-2 py-0.5 rounded-full text-[10px] font-label-caps bg-[#fef3c7] text-[#92400e] font-semibold border border-[#fde68a]">
                          {ev.event_type_label || 'Lễ Phật giáo'}
                        </span>
                      </div>
                      {ev.short_description && (
                        <p className="text-xs text-[#57534e] leading-relaxed">
                          {ev.short_description}
                        </p>
                      )}
                      {ev.buddhist_figures && ev.buddhist_figures.length > 0 && (
                        <div className="text-xs text-[#78716c] pt-0.5">
                          Chiêm bái:{' '}
                          {ev.buddhist_figures.map((f) => (
                            <Link
                              key={f.slug}
                              href={`/phat-bo-tat/${f.slug}`}
                              className="text-[#785915] hover:underline font-semibold ml-1"
                            >
                              {f.name}
                            </Link>
                          ))}
                        </div>
                      )}
                    </div>
                  ))}
                </div>
              </div>
            </div>
          )}

          {/* 9. Tinh chỉnh Phật giáo OmPraMani: Trai giới & Ăn chay */}
          {vegEvents.length > 0 && (
            <div className="flex items-start gap-2.5 pt-1">
              {bulletSymbol}
              <div className="space-y-1 flex-1">
                <span className="font-semibold text-[#166534]">Trai giới &amp; Ăn chay:</span>
                <div className="p-2.5 rounded-xl bg-[#f0fdf4] border border-[#bbf7d0] text-[#166534] text-xs sm:text-sm flex items-center gap-2">
                  <span className="material-symbols-outlined text-[18px] text-[#166534] shrink-0">spa</span>
                  <span className="font-medium">Ngày ăn chay thanh tịnh (Thập Trai) – Nuôi dưỡng tâm từ bi đối với muôn loài.</span>
                </div>
              </div>
            </div>
          )}

          {/* 10. Nếu không có lễ đặc biệt -> Thông điệp thanh tịnh */}
          {buddhistFeasts.length === 0 && vegEvents.length === 0 && (
            <div className="flex items-start gap-2.5 pt-1">
              {bulletSymbol}
              <div className="text-xs sm:text-sm text-[#57534e] leading-relaxed">
                <span className="font-semibold text-[#211a13]">Phật sự hôm nay: </span>
                <span>Ngày thanh tịnh, thích hợp tụng kinh, niệm Phật, tu tập thiện hạnh và hồi hướng công đức bình an.</span>
              </div>
            </div>
          )}
        </div>
      </div>

      {/* Footer Ghi chú nhỏ */}
      <div className="pt-3 mt-4 text-[11px] text-[#78716c] border-t border-[#e7e5e4] flex flex-wrap items-center justify-between gap-2">
        <span>* Lịch âm chuẩn giờ Hà Nội (UTC+07:00).</span>
        <span>Xuất hành theo văn hóa dân gian truyền thống.</span>
      </div>
    </div>
  );
}
