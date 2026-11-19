import { apiClient, ApiError } from "./client";
import {
  DharmaJourneyActivity,
  DharmaJourneyBreakdown,
  DharmaJourneyDashboardData,
  DharmaJourneyRange,
  DharmaJourneySummary,
} from "@/types/dharma-journey";

/**
 * Detect client timezone safely with fallback
 */
export function getClientTimezone(): string {
  try {
    if (typeof Intl !== "undefined" && typeof Intl.DateTimeFormat === "function") {
      const tz = Intl.DateTimeFormat().resolvedOptions().timeZone;
      if (tz) {
        if (tz === "Asia/Saigon") {
          return "Asia/Ho_Chi_Minh";
        }
        return tz;
      }
    }
  } catch {
    // ignore
  }
  return "Asia/Ho_Chi_Minh";
}

/**
 * Fetch complete dharma journey dashboard (composite summary, activity, breakdown).
 */
export async function getDharmaJourneyDashboard(
  range: DharmaJourneyRange = "30d",
  timezone?: string
): Promise<DharmaJourneyDashboardData | null> {
  const tz = timezone || getClientTimezone();
  const query = new URLSearchParams();
  query.set("range", range);
  query.set("timezone", tz);

  try {
    const res = await apiClient<{ data: DharmaJourneyDashboardData }>(
      `/api/v1/me/dharma-journey?${query.toString()}`,
      {
        method: "GET",
        cache: "no-store",
      }
    );

    return res.data;
  } catch (err) {
    if (err instanceof ApiError && err.status === 401) {
      return null;
    }
    throw err;
  }
}

/**
 * Fetch summary metrics only.
 */
export async function getDharmaJourneySummary(
  range: DharmaJourneyRange = "30d",
  timezone?: string
): Promise<DharmaJourneySummary | null> {
  const tz = timezone || getClientTimezone();
  const query = new URLSearchParams();
  query.set("range", range);
  query.set("timezone", tz);

  try {
    const res = await apiClient<{ data: DharmaJourneySummary }>(
      `/api/v1/me/dharma-journey/summary?${query.toString()}`,
      {
        method: "GET",
        cache: "no-store",
      }
    );

    return res.data;
  } catch (err) {
    if (err instanceof ApiError && err.status === 401) {
      return null;
    }
    throw err;
  }
}

/**
 * Fetch activity chart data points only.
 */
export async function getDharmaJourneyActivity(
  range: DharmaJourneyRange = "30d",
  timezone?: string
): Promise<DharmaJourneyActivity | null> {
  const tz = timezone || getClientTimezone();
  const query = new URLSearchParams();
  query.set("range", range);
  query.set("timezone", tz);

  try {
    const res = await apiClient<{ data: DharmaJourneyActivity }>(
      `/api/v1/me/dharma-journey/activity?${query.toString()}`,
      {
        method: "GET",
        cache: "no-store",
      }
    );

    return res.data;
  } catch (err) {
    if (err instanceof ApiError && err.status === 401) {
      return null;
    }
    throw err;
  }
}

/**
 * Fetch practice type breakdown only.
 */
export async function getDharmaJourneyBreakdown(
  range: DharmaJourneyRange = "30d",
  timezone?: string
): Promise<DharmaJourneyBreakdown | null> {
  const tz = timezone || getClientTimezone();
  const query = new URLSearchParams();
  query.set("range", range);
  query.set("timezone", tz);

  try {
    const res = await apiClient<{ data: DharmaJourneyBreakdown }>(
      `/api/v1/me/dharma-journey/breakdown?${query.toString()}`,
      {
        method: "GET",
        cache: "no-store",
      }
    );

    return res.data;
  } catch (err) {
    if (err instanceof ApiError && err.status === 401) {
      return null;
    }
    throw err;
  }
}
