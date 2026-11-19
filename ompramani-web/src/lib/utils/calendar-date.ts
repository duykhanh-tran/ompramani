/**
 * Timezone-safe calendar date utilities for OmPraMani.
 * Standardizes on civil date strings (YYYY-MM-DD) in Asia/Ho_Chi_Minh timezone.
 */

export const VIETNAM_TIMEZONE = 'Asia/Ho_Chi_Minh';
export const MIN_SUPPORTED_YEAR = 1900;
export const MAX_SUPPORTED_YEAR = 2100;
export const MIN_SUPPORTED_DATE = '1900-01-31';
export const MAX_SUPPORTED_DATE = '2100-12-31';

export const VIETNAMESE_WEEKDAYS: Record<number, string> = {
  1: 'Thứ Hai',
  2: 'Thứ Ba',
  3: 'Thứ Tư',
  4: 'Thứ Năm',
  5: 'Thứ Sáu',
  6: 'Thứ Bảy',
  7: 'Chủ Nhật',
};

export const VIETNAMESE_MONTH_NAMES: Record<number, string> = {
  1: 'Tháng 1',
  2: 'Tháng 2',
  3: 'Tháng 3',
  4: 'Tháng 4',
  5: 'Tháng 5',
  6: 'Tháng 6',
  7: 'Tháng 7',
  8: 'Tháng 8',
  9: 'Tháng 9',
  10: 'Tháng 10',
  11: 'Tháng 11',
  12: 'Tháng 12',
};

/**
 * Returns today's civil date string (YYYY-MM-DD) in Asia/Ho_Chi_Minh timezone.
 */
export function getTodayVietnamDate(): string {
  try {
    const formatter = new Intl.DateTimeFormat('en-CA', {
      timeZone: VIETNAM_TIMEZONE,
      year: 'numeric',
      month: '2-digit',
      day: '2-digit',
    });
    return formatter.format(new Date());
  } catch {
    const now = new Date();
    const y = now.getFullYear();
    const m = String(now.getMonth() + 1).padStart(2, '0');
    const d = String(now.getDate()).padStart(2, '0');
    return `${y}-${m}-${d}`;
  }
}

/**
 * Parse a YYYY-MM-DD string into integer components without UTC drift.
 */
export function parseDateString(dateStr: string): { year: number; month: number; day: number } | null {
  if (!isValidDateString(dateStr)) {
    return null;
  }
  const [y, m, d] = dateStr.split('-').map((v) => parseInt(v, 10));
  return { year: y, month: m, day: d };
}

/**
 * Format year, month, day into YYYY-MM-DD.
 */
export function formatDateString(year: number, month: number, day: number): string {
  const y = String(year).padStart(4, '0');
  const m = String(month).padStart(2, '0');
  const d = String(day).padStart(2, '0');
  return `${y}-${m}-${d}`;
}

/**
 * Validate YYYY-MM-DD format and valid calendar date.
 */
export function isValidDateString(dateStr: string): boolean {
  if (!/^\d{4}-\d{2}-\d{2}$/.test(dateStr)) {
    return false;
  }
  const [y, m, d] = dateStr.split('-').map((v) => parseInt(v, 10));
  if (m < 1 || m > 12 || d < 1 || d > 31) {
    return false;
  }
  if (y < MIN_SUPPORTED_YEAR || y > MAX_SUPPORTED_YEAR) {
    return false;
  }
  // Check day of month validity
  const daysInMonth = new Date(y, m, 0).getDate();
  return d <= daysInMonth;
}

/**
 * Validate YYYY-MM format.
 */
export function isValidMonthString(monthStr: string): boolean {
  if (!/^\d{4}-\d{2}$/.test(monthStr)) {
    return false;
  }
  const [y, m] = monthStr.split('-').map((v) => parseInt(v, 10));
  return y >= MIN_SUPPORTED_YEAR && y <= MAX_SUPPORTED_YEAR && m >= 1 && m <= 12;
}

/**
 * Add or subtract months safely.
 */
export function addMonths(year: number, month: number, delta: number): { year: number; month: number } {
  const totalMonths = year * 12 + (month - 1) + delta;
  const newYear = Math.floor(totalMonths / 12);
  const newMonth = (totalMonths % 12) + 1;
  const clampedYear = Math.max(MIN_SUPPORTED_YEAR, Math.min(MAX_SUPPORTED_YEAR, newYear));
  return { year: clampedYear, month: newMonth };
}

/**
 * Format full Vietnamese date display: e.g. "Thứ Sáu, ngày 16 tháng 10 năm 2026"
 */
export function formatVietnameseFullDate(dateStr: string, weekdayIso?: number): string {
  const parts = parseDateString(dateStr);
  if (!parts) return dateStr;

  let weekdayName = '';
  if (typeof weekdayIso === 'number' && VIETNAMESE_WEEKDAYS[weekdayIso]) {
    weekdayName = VIETNAMESE_WEEKDAYS[weekdayIso] + ', ';
  } else {
    // Derive weekday ISO (1 = Monday, 7 = Sunday)
    const date = new Date(parts.year, parts.month - 1, parts.day);
    const day = date.getDay(); // 0 = Sunday, 1 = Monday
    const iso = day === 0 ? 7 : day;
    weekdayName = (VIETNAMESE_WEEKDAYS[iso] || '') + ', ';
  }

  return `${weekdayName}ngày ${parts.day} tháng ${parts.month} năm ${parts.year}`;
}
