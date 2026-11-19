"use client";

import React, { useState, useEffect, useCallback } from "react";
import Link from "next/link";
import { useRouter, useSearchParams } from "next/navigation";
import { useAuth } from "@/context/AuthContext";
import {
  DharmaJourneyDashboardData,
  DharmaJourneyRange,
} from "@/types/dharma-journey";
import { getDharmaJourneyDashboard } from "@/lib/api/dharma-journey";
import { usePracticeSession } from "@/hooks/usePracticeSession";
import { JourneyHeader } from "./JourneyHeader";
import { JourneyRangeSelector } from "./JourneyRangeSelector";
import { JourneySummaryCards } from "./JourneySummaryCards";
import { PracticeActivityChart } from "./PracticeActivityChart";
import { PracticeTypeBreakdown } from "./PracticeTypeBreakdown";
import { JourneyRecentSessions } from "./JourneyRecentSessions";
import { JourneyReadingSection } from "./JourneyReadingSection";
import { PracticeShortcuts } from "./PracticeShortcuts";
import { PracticeStreakSection } from "@/components/practice-streak/PracticeStreakSection";

export default function DharmaJourneyDashboard() {
  const router = useRouter();
  const searchParams = useSearchParams();
  const { user, isAuthenticated, isLoading: isAuthLoading } = useAuth();
  const { session: activeSession } = usePracticeSession();

  // Range from query param (7d | 30d | 90d | all)
  const rangeParam = searchParams.get("range") as DharmaJourneyRange | null;
  const validRanges: DharmaJourneyRange[] = ["7d", "30d", "90d", "all"];
  const currentRange: DharmaJourneyRange =
    rangeParam && validRanges.includes(rangeParam) ? rangeParam : "30d";

  const [dashboardData, setDashboardData] =
    useState<DharmaJourneyDashboardData | null>(null);
  const [isLoadingData, setIsLoadingData] = useState<boolean>(true);
  const [loadError, setLoadError] = useState<string | null>(null);

  // Redirect guest to login
  useEffect(() => {
    if (!isAuthLoading && !isAuthenticated) {
      router.replace("/dang-nhap?next=/tai-khoan/hanh-trinh");
    }
  }, [isAuthLoading, isAuthenticated, router]);

  // Load dashboard data
  const loadData = useCallback(async (range: DharmaJourneyRange) => {
    setIsLoadingData(true);
    setLoadError(null);
    try {
      const data = await getDharmaJourneyDashboard(range);
      setDashboardData(data);
    } catch {
      setLoadError("Không thể tải số liệu hành trình tu học lúc này. Vui lòng thử lại sau.");
    } finally {
      setIsLoadingData(false);
    }
  }, []);

  useEffect(() => {
    if (isAuthenticated) {
      // eslint-disable-next-line react-hooks/set-state-in-effect
      loadData(currentRange);
    }
  }, [isAuthenticated, currentRange, loadData]);

  const handleRangeChange = (newRange: DharmaJourneyRange) => {
    const params = new URLSearchParams(searchParams.toString());
    params.set("range", newRange);
    router.push(`/tai-khoan/hanh-trinh?${params.toString()}`);
  };

  if (isAuthLoading || (!isAuthenticated && !loadError)) {
    return (
      <div className="w-full max-w-5xl mx-auto space-y-6 animate-pulse">
        <div className="h-44 rounded-3xl bg-surface-container-low" />
        <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
          {[1, 2, 3, 4].map((n) => (
            <div key={n} className="h-28 rounded-2xl bg-surface-container-low" />
          ))}
        </div>
        <div className="h-64 rounded-3xl bg-surface-container-low" />
      </div>
    );
  }

  if (!user) return null;

  return (
    <div className="w-full max-w-5xl mx-auto space-y-8">
      {/* Active Ongoing Session Reminder Banner */}
      {activeSession && activeSession.status !== "completed" && (
        <div className="p-4 rounded-2xl bg-amber-500/10 border border-amber-500/30 flex flex-col sm:flex-row items-center justify-between gap-3 text-amber-900 animate-in fade-in">
          <div className="flex items-center gap-2.5 text-xs font-label-md">
            <span className="material-symbols-outlined text-[20px] text-amber-700 animate-pulse">
              hourglass_top
            </span>
            <span>
              Bạn đang có một thời khóa tu tập chưa kết thúc ({activeSession.practice_type}).
            </span>
          </div>

          <Link
            href="/tai-khoan"
            className="px-3.5 py-1.5 rounded-xl bg-amber-600 text-white font-label-md text-xs font-semibold hover:bg-amber-700 transition-colors shadow-xs"
          >
            Tiếp tục thời khóa
          </Link>
        </div>
      )}

      {/* A. Personal Journey Header */}
      <JourneyHeader user={user} />

      {/* B. Time Range Selector */}
      <JourneyRangeSelector
        currentRange={currentRange}
        onRangeChange={handleRangeChange}
        isLoading={isLoadingData}
      />

      {loadError ? (
        <div className="p-6 rounded-3xl bg-error/10 border border-error/20 text-center space-y-3">
          <span className="material-symbols-outlined text-error text-[32px]">
            error
          </span>
          <p className="text-xs font-label-md text-error">{loadError}</p>
          <button
            type="button"
            onClick={() => loadData(currentRange)}
            className="px-4 py-2 rounded-xl bg-primary text-on-primary text-xs font-semibold"
          >
            Thử lại
          </button>
        </div>
      ) : dashboardData ? (
        <>
          {/* C. Summary Cards */}
          <JourneySummaryCards summary={dashboardData.summary} />

          {/* D. Practice Streak System (Task 26) */}
          <PracticeStreakSection />

          {/* E. Practice Activity Chart */}
          <PracticeActivityChart activity={dashboardData.activity} />

          {/* F. Practice Type Breakdown */}
          <PracticeTypeBreakdown breakdown={dashboardData.breakdown} />
        </>
      ) : null}

      {/* F. Recent Practice Sessions */}
      <JourneyRecentSessions />

      {/* G. Continue Reading & Saved Sutras Shortcut */}
      <JourneyReadingSection />

      {/* H. Practice Tool Shortcuts */}
      <PracticeShortcuts />
    </div>
  );
}
