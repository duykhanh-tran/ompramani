# OmPraMani Vietnamese Lunisolar Calendar Core

## 1. Overview & Architecture

The **Calendar Core** module provides high-precision, deterministic conversion between Gregorian civil dates and the Vietnamese Lunisolar Calendar (Âm Lịch Việt Nam). It serves as the authoritative mathematical foundation for:
- Task 27: Calendar Core Engine, Conversions & REST API
- Task 28: Buddhist Holidays & Observances (Vía Phật / Bồ Tát, Lịch Ăn Chay)
- Task 29: Daily Pháp Thoại & Kệ Pháp
- Task 30: Hoàng Đạo / Giờ Cát Tinh
- Task 31: Full Vietnamese Lunisolar Calendar UI (Lịch Vạn Niên)

### Key Characteristics
1. **Purely Algorithmic & Offline-Capable**: Operates entirely in-memory using validated astronomical formulae. Zero external HTTP requests and zero runtime database queries.
2. **Vietnamese Meridian Convention**: Computations are pinned to UTC+07:00 (105°E meridian, `Asia/Ho_Chi_Minh`), strictly reflecting modern Vietnamese calendar law and astronomical practice.
3. **Independent Reference-Verified**: 100% passes independent benchmark fixtures across 1900–2100, correctly capturing historical differences between Vietnam and China (such as Tết Ất Sửu 1985 and Tết Canh Tuất 2030).
4. **Bidirectionally Invertible**: All regular and leap months round-trip with zero ambiguity.

---

## 2. Directory & Class Structure

```
app/
├── Http/
│   ├── Controllers/Api/V1/
│   │   └── CalendarController.php
│   └── Requests/Api/V1/Calendar/
│       ├── CalendarDayRequest.php
│       ├── CalendarMonthRequest.php
│       ├── LunarToSolarRequest.php
│       └── SolarToLunarRequest.php
├── Services/
│   └── Calendar/
│       ├── CalendarService.php
│       └── VietnameseLunisolarCalendar.php
config/
└── calendar.php
docs/
├── calendar-core.md
└── calendar-reference.md
tests/
├── Feature/
│   └── CalendarApiTest.php
├── Fixtures/
│   └── calendar-reference.json
└── Unit/
    └── VietnameseLunisolarCalendarTest.php
```

---

## 3. Astronomical Formulation & Leap Month Handling

### 3.1 Julian Day & Astronomical New Moons
- Julian Day Number (JDN) is computed using Gregorian civil calendar conversions (`jdFromDate` / `jdToDate`).
- Astronomical New Moon (Sóc) is calculated using Jean Meeus's *Astronomical Algorithms* (1998, Chapter 49) adapted for UTC+07:00:
  $$JDN_{new\_moon} = \lfloor JD_1 + C_1 - \Delta T + 0.5 + 7.0/24.0 \rfloor$$
  where $k = \lfloor (JDN - 2415021.077) / 29.530588853 \rfloor$ represents the lunation index since 1900.

### 3.2 Month 11 & Winter Solstice (Đông Chí)
According to Vietnamese calendar rules:
- Lunar Month 11 (*Tháng Một*) is always the lunar month that contains the Winter Solstice (*Đông Chí*, Sun longitude $\lambda = 270^\circ$).
- The new moon of Month 11 is determined by `getLunarMonth11($year, 7.0)`.

### 3.3 Leap Year & Leap Month Offset
- If the interval between Month 11 of year $Y$ and Month 11 of year $Y+1$ spans 13 new moons ($b_{11} - a_{11} > 365$), the year contains a leap month (*tháng nhuận*).
- The leap month is the first month in the lunar cycle that does not contain a Major Solar Term (*Trung khí*). A Major Solar Term occurs whenever Sun longitude $\lfloor \lambda / 30^\circ \rfloor$ advances.

### 3.4 Month Length Calculation
- Each lunar month begins on the day of the astronomical New Moon.
- Month length is calculated as the exact number of days between two consecutive New Moons:
  $$\text{length} = JDN_{next\_new\_moon} - JDN_{current\_new\_moon}$$
- Every month is strictly either 29 days (*tháng thiếu*) or 30 days (*tháng đủ*).

### 3.5 Sexagenary Cycle (Can Chi)
- **Thiên Can (10)**: Giáp (0), Ất (1), Bính (2), Đinh (3), Mậu (4), Kỷ (5), Canh (6), Tân (7), Nhâm (8), Quý (9).
- **Địa Chi (12)**: Tý (0), Sửu (1), Dần (2), Mão (3), Thìn (4), Tỵ (5), Ngọ (6), Mùi (7), Thân (8), Dậu (9), Tuất (10), Hợi (11).
- **Year Can Chi**:
  - $Can = (Year + 6) \pmod{10}$
  - $Chi = (Year + 8) \pmod{12}$
- **Day Can Chi**:
  - $Can = (JDN + 9) \pmod{10}$
  - $Chi = (JDN + 1) \pmod{12}$
- **Solar Terms (24 Tiết khí)**:
  - Computed from apparent solar longitude $\lambda$ divided by $15^\circ$: $\lfloor \lambda / 15^\circ \rfloor$.

---

## 4. Supported Date Range & Timezone Policy

- **Calendar Timezone**: `Asia/Ho_Chi_Minh` (meridian 105°E, offset UTC+07:00).
- **Supported Solar Range**: `1900-01-31` to `2100-12-31`.
  - Earliest supported date is `1900-01-31` (which corresponds to Lunar Mùng 1 Tết Canh Tý 1900).
  - Dates prior to `1900-01-31` or after `2100-12-31` are strictly rejected with HTTP 422.
- **Server Independence**: Calculations explicitly supply `timeZone = 7.0` and never rely on PHP's `date_default_timezone_get()`.

---

## 5. REST API Endpoints & Contracts

All endpoints are public (no authentication required) and return JSON with standard wrapping.

### 5.1 Solar to Lunar
`GET /api/v1/calendar/solar-to-lunar`

**Query Parameters:**
- `date`: `YYYY-MM-DD` (e.g. `2026-02-17`)
- Alternatively: `year`, `month`, `day`

**Example Response (200 OK):**
```json
{
  "data": {
    "solar_date": "2026-02-17",
    "solar": {
      "year": 2026,
      "month": 2,
      "day": 17,
      "weekday_iso": 2,
      "weekday_name": "Thứ Ba"
    },
    "lunar": {
      "year": 2026,
      "month": 1,
      "day": 1,
      "is_leap_month": false,
      "month_length": 29,
      "is_leap_year": false,
      "leap_month": null,
      "year_can_chi": "Bính Ngọ",
      "day_can_chi": "Canh Thân",
      "month_can_chi": "Canh Dần",
      "solar_term": "Vũ Thủy"
    },
    "calendar": {
      "system": "vietnamese_lunisolar",
      "timezone": "Asia/Ho_Chi_Minh"
    }
  }
}
```

### 5.2 Lunar to Solar
`GET /api/v1/calendar/lunar-to-solar`

**Query Parameters:**
- `year`: integer (1900..2100)
- `month`: integer (1..12)
- `day`: integer (1..30)
- `is_leap_month`: boolean (`true`/`false` or `1`/`0`, optional, default `false`)

**Example Response (200 OK):**
```json
{
  "data": {
    "lunar": {
      "year": 2026,
      "month": 1,
      "day": 1,
      "is_leap_month": false,
      "month_length": 29,
      "year_can_chi": "Bính Ngọ"
    },
    "solar_date": "2026-02-17",
    "solar": {
      "year": 2026,
      "month": 2,
      "day": 17,
      "weekday_iso": 2,
      "weekday_name": "Thứ Ba"
    },
    "calendar": {
      "system": "vietnamese_lunisolar",
      "timezone": "Asia/Ho_Chi_Minh"
    }
  }
}
```

### 5.3 Day Calendar Data
`GET /api/v1/calendar/day`

**Query Parameters:**
- `date`: `YYYY-MM-DD` (optional, defaults to current date in `Asia/Ho_Chi_Minh`)

**Example Response (200 OK):**
```json
{
  "data": {
    "solar": {
      "date": "2026-02-17",
      "year": 2026,
      "month": 2,
      "day": 17,
      "weekday_iso": 2,
      "weekday_name": "Thứ Ba"
    },
    "lunar": {
      "year": 2026,
      "month": 1,
      "day": 1,
      "is_leap_month": false,
      "month_length": 29,
      "is_leap_year": false,
      "leap_month": null,
      "year_can_chi": "Bính Ngọ",
      "day_can_chi": "Canh Thân",
      "month_can_chi": "Canh Dần",
      "solar_term": "Vũ Thủy"
    },
    "calendar_timezone": "Asia/Ho_Chi_Minh"
  }
}
```

### 5.4 Month Calendar Data
`GET /api/v1/calendar/month`

**Query Parameters:**
- `year`: integer (1900..2100)
- `month`: integer (1..12)

**Example Response (200 OK):**
```json
{
  "data": {
    "solar_year": 2026,
    "solar_month": 2,
    "calendar_timezone": "Asia/Ho_Chi_Minh",
    "first_weekday_iso": 7,
    "days_in_month": 28,
    "days": [
      {
        "solar_date": "2026-02-01",
        "solar_day": 1,
        "weekday_iso": 7,
        "weekday_name": "Chủ Nhật",
        "lunar_day": 14,
        "lunar_month": 12,
        "lunar_year": 2025,
        "is_leap_month": false,
        "year_can_chi": "Ất Tỵ",
        "day_can_chi": "Giáp Ngọ",
        "solar_term": "Đại hàn",
        "is_lunar_first_day": false,
        "is_full_moon": false
      },
      ...
    ]
  }
}
```

### 5.5 Configuration & Metadata
`GET /api/v1/calendar/config`

**Example Response (200 OK):**
```json
{
  "data": {
    "system": "vietnamese_lunisolar",
    "calendar_timezone": "Asia/Ho_Chi_Minh",
    "timezone_offset": "+07:00",
    "min_supported_solar_date": "1900-01-31",
    "max_supported_solar_date": "2100-12-31",
    "min_supported_lunar_year": 1900,
    "max_supported_lunar_year": 2100,
    "algorithm": {
      "name": "Ho Ngoc Duc / Jean Meeus Astronomical Algorithms (AA98)",
      "reference_meridian": "105°E (UTC+7:00)",
      "license": "Public Domain / Free Software"
    },
    "capabilities": [
      "solar_to_lunar",
      "lunar_to_solar",
      "day_view",
      "month_view",
      "leap_month",
      "can_chi",
      "solar_terms"
    ]
  }
}
```

---

## 6. Error Responses (HTTP 422)

The API enforces strict validation. No silent fallback or auto-normalization:
- **Invalid date string / impossible dates** (e.g. `2025-02-29`, `2026-02-30`, `abc`): HTTP 422.
- **Out of supported range** (e.g. `1899-12-31`): HTTP 422 with clear boundary notice.
- **Invalid leap month** (e.g. requesting leap month in a year with none, or the wrong month number): HTTP 422.
- **Day exceeding lunar month length** (e.g. day 30 in a 29-day month): HTTP 422.
