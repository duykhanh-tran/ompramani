import { SolarDateData, LunarDateData } from './calendar';

export type DayClassification = 'hoang_dao' | 'hac_dao' | 'neutral' | 'unknown';
export type HourClassification = 'auspicious' | 'inauspicious' | 'unknown';
export type AlmanacVerificationStatus = 'verified' | 'pending_review' | 'unavailable' | 'unsupported';

export interface EarthlyBranchInfo {
  index: number;
  key: string;
  label: string;
  start_time: string;
  end_time: string;
  crosses_midnight: boolean;
}

export interface HeavenlyStemInfo {
  index: number;
  key: string;
  label: string;
}

export interface YellowBlackDeityInfo {
  index: number;
  key: string;
  name: string;
  is_auspicious: boolean;
  day_classification: DayClassification;
  hour_classification: HourClassification;
}

export interface AlmanacHour {
  branch_index: number;
  branch_key: string;
  branch_name: string;
  can_chi: string;
  start_local_time: string;
  end_local_time: string;
  crosses_midnight: boolean;
  start_local_datetime: string;
  end_local_datetime: string;
  classification: HourClassification;
  deity_name: string;
  is_auspicious: boolean;
}

export interface AlmanacMethodMeta {
  key: string;
  name: string;
  version: string;
  description?: string;
  source_title?: string;
  reference_source?: string;
  license?: string;
  calendar_system?: string;
  calendar_timezone?: string;
  supported_start_date?: string;
  supported_end_date?: string;
  hour_anchoring?: string;
  disclaimer?: string;
  verification_status?: AlmanacVerificationStatus;
  is_primary?: boolean;
}

export interface AlmanacDayData {
  solar_date: string;
  calendar_timezone: string;
  solar: SolarDateData;
  lunar: LunarDateData;
  can_chi: {
    year: string;
    month: string;
    day: string;
    hour: string | null;
    method: string;
  };
  day_classification: DayClassification;
  deity_name: string;
  is_auspicious: boolean;
  has_override: boolean;
  override_reason?: string;
  override_source?: string;
  hours: AlmanacHour[];
  method: AlmanacMethodMeta;
  verification_status: AlmanacVerificationStatus;
  disclaimer: string;
}

export interface AlmanacDayResponse {
  data: AlmanacDayData;
}

export interface AlmanacMonthDaySummary {
  solar_date: string;
  solar_day: number;
  weekday_iso: number;
  weekday_name: string;
  lunar_day: number;
  lunar_month: number;
  lunar_year: number;
  is_leap_month: boolean;
  year_can_chi: string;
  day_can_chi: string;
  day_classification: DayClassification;
  deity_name: string;
  is_auspicious: boolean;
  auspicious_hours_count: number;
  verification_status: AlmanacVerificationStatus;
  has_override?: boolean;
}

export interface AlmanacMonthData {
  solar_year: number;
  solar_month: number;
  calendar_timezone: string;
  method: {
    key: string;
    name: string;
    version: string;
  };
  days: AlmanacMonthDaySummary[];
}

export interface AlmanacMonthResponse {
  data: AlmanacMonthData;
}

export interface AlmanacMethodsResponse {
  data: AlmanacMethodMeta[];
}

export interface AlmanacConfigData {
  system: string;
  calendar_timezone: string;
  earthly_branches: EarthlyBranchInfo[];
  heavenly_stems: HeavenlyStemInfo[];
  yellow_black_deities: YellowBlackDeityInfo[];
  supported_range: {
    start_date: string;
    end_date: string;
  };
  disclaimer: string;
}

export interface AlmanacConfigResponse {
  data: AlmanacConfigData;
}
