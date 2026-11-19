export interface CalendarDay {
  dayOfWeek: string;
  solarDay: string;
  lunarDay: string;
  isToday?: boolean;
  tag?: string;
  tagType?: "today" | "veg" | "first_day";
  isSunday?: boolean;
}

export const DEMO_CALENDAR_DAYS: CalendarDay[] = [
  {
    dayOfWeek: "Thứ 2",
    solarDay: "05",
    lunarDay: "25",
  },
  {
    dayOfWeek: "Thứ 3",
    solarDay: "06",
    lunarDay: "26",
  },
  {
    dayOfWeek: "Thứ 4",
    solarDay: "07",
    lunarDay: "27/08 AL",
    isToday: true,
    tag: "Hôm nay",
    tagType: "today",
  },
  {
    dayOfWeek: "Thứ 5",
    solarDay: "08",
    lunarDay: "28",
    tag: "Ăn chay",
    tagType: "veg",
  },
  {
    dayOfWeek: "Thứ 6",
    solarDay: "09",
    lunarDay: "29",
  },
  {
    dayOfWeek: "Thứ 7",
    solarDay: "10",
    lunarDay: "01/09",
    tag: "Mùng 1",
    tagType: "first_day",
  },
  {
    dayOfWeek: "Chủ Nhật",
    solarDay: "11",
    lunarDay: "02",
    isSunday: true,
  },
];
