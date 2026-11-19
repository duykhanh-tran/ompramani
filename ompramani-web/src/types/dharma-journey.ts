export type DharmaJourneyRange = "7d" | "30d" | "90d" | "all";

export interface DharmaJourneySummary {
  range: DharmaJourneyRange;
  timezone: string;
  date_from: string | null;
  date_to: string;
  completed_sessions: number;
  total_active_duration_seconds: number;
  meditation: {
    sessions: number;
    active_duration_seconds: number;
  };
  mala: {
    sessions: number;
    bead_count: number;
    completed_rounds: number;
  };
  mantra: {
    sessions: number;
    repetition_count: number;
  };
  buddha_name: {
    sessions: number;
    repetition_count: number;
  };
  sutra: {
    sessions: number;
    active_duration_seconds: number;
  };
  bell_wooden_fish: {
    sessions: number;
    bell_count: number;
    wooden_fish_count: number;
  };
}

export interface DailyActivityPoint {
  date: string;
  label: string;
  completed_sessions: number;
  active_duration_seconds: number;
}

export interface DharmaJourneyActivity {
  range: DharmaJourneyRange;
  timezone: string;
  granularity: "day" | "month";
  points: DailyActivityPoint[];
}

export interface PracticeBreakdownItem {
  practice_type: string;
  label: string;
  completed_sessions: number;
  active_duration_seconds: number;
  metrics: {
    bead_count?: number;
    completed_rounds?: number;
    repetition_count?: number;
    bell_count?: number;
    wooden_fish_count?: number;
    [key: string]: number | undefined;
  };
}

export interface DharmaJourneyBreakdown {
  range: DharmaJourneyRange;
  timezone: string;
  items: PracticeBreakdownItem[];
}

export interface DharmaJourneyDashboardData {
  summary: DharmaJourneySummary;
  activity: DharmaJourneyActivity;
  breakdown: DharmaJourneyBreakdown;
}
