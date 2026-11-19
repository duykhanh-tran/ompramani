import { apiClient, ApiError } from "./client";
import { getClientTimezone } from "./dharma-journey";
import { PracticeStreakData, PracticeStreakResponse } from "@/types/practice-streak";

/**
 * Fetch authenticated user's practice streak metrics and activity calendar.
 *
 * @param days Calendar window length (7 | 30 | 90). Defaults to 30.
 * @param timezone Optional IANA timezone override. Defaults to detected client timezone.
 * @returns PracticeStreakData or null if unauthenticated.
 */
export async function getPracticeStreak(
  days: 7 | 30 | 90 = 30,
  timezone?: string
): Promise<PracticeStreakData | null> {
  const tz = timezone || getClientTimezone();
  const query = new URLSearchParams();
  query.set("days", days.toString());
  if (tz) {
    query.set("timezone", tz);
  }

  try {
    const res = await apiClient<PracticeStreakResponse>(
      `/api/v1/me/practice-streak?${query.toString()}`,
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
