'use client';

import React, { useState, useEffect } from 'react';
import Link from 'next/link';
import {
  PracticePlan,
  PracticePlanItem,
  PracticePlanOccurrence,
  CreatePracticePlanInput,
  UpdatePracticePlanInput,
  CreatePracticePlanItemInput,
  UpdatePracticePlanItemInput,
} from '@/types/practice-plan';
import {
  getPracticePlans,
  createPracticePlan,
  updatePracticePlan,
  deletePracticePlan,
  pausePracticePlan,
  resumePracticePlan,
  archivePracticePlan,
  createPracticePlanItem,
  updatePracticePlanItem,
  deletePracticePlanItem,
  getTodayOccurrences,
  getWeekOccurrences,
  linkPracticeSession,
  unlinkPracticeSession,
  skipPracticeOccurrence,
  restorePracticeOccurrence,
} from '@/lib/api/practice-plans';
import { PracticeWeekGrid } from './PracticeWeekGrid';
import { PracticeActivityCard } from './PracticeActivityCard';
import { PracticePlanFormModal } from './PracticePlanFormModal';
import { PracticePlanItemFormModal } from './PracticePlanItemFormModal';
import { PracticeSessionLinkDialog } from './PracticeSessionLinkDialog';
import { PracticePlannerEmptyState } from './PracticePlannerEmptyState';

type PlannerTab = 'week' | 'today' | 'plans';

export function PracticePlannerManager() {
  const [plans, setPlans] = useState<PracticePlan[]>([]);
  const [selectedPlanId, setSelectedPlanId] = useState<number | undefined>(undefined);
  const [currentTab, setCurrentTab] = useState<PlannerTab>('week');
  const [isLoading, setIsLoading] = useState(true);

  // Week data state
  const [weekOccurrences, setWeekOccurrences] = useState<PracticePlanOccurrence[]>([]);
  const [weekRange, setWeekRange] = useState<{ from: string; to: string; today: string }>({
    from: '',
    to: '',
    today: '',
  });
  const [currentWeekDate, setCurrentWeekDate] = useState<string | undefined>(undefined);

  // Today data state
  const [todayOccurrences, setTodayOccurrences] = useState<PracticePlanOccurrence[]>([]);
  const [todayDateStr, setTodayDateStr] = useState<string>('');

  // Modals state
  const [isPlanModalOpen, setIsPlanModalOpen] = useState(false);
  const [planToEdit, setPlanToEdit] = useState<PracticePlan | null>(null);

  const [isItemModalOpen, setIsItemModalOpen] = useState(false);
  const [itemToEdit, setItemToEdit] = useState<PracticePlanItem | null>(null);

  const [linkDialogOccurrence, setLinkDialogOccurrence] =
    useState<PracticePlanOccurrence | null>(null);

  const [isOperating, setIsOperating] = useState(false);
  const [feedback, setFeedback] = useState<{
    type: 'success' | 'error';
    message: string;
  } | null>(null);

  // Initial load of plans and week occurrences
  useEffect(() => {
    let active = true;

    async function init() {
      try {
        const [plansData, weekData] = await Promise.all([
          getPracticePlans(),
          getWeekOccurrences(),
        ]);
        if (active) {
          setPlans(plansData);
          setWeekOccurrences(weekData.data || []);
          setWeekRange(weekData.week);
        }
      } catch (err: unknown) {
        if (active) {
          console.error('Lỗi khởi tạo kế hoạch tu tập:', err);
          setFeedback({
            type: 'error',
            message: 'Không thể tải dữ liệu kế hoạch tu tập. Vui lòng thử lại.',
          });
        }
      } finally {
        if (active) {
          setIsLoading(false);
        }
      }
    }

    init();

    return () => {
      active = false;
    };
  }, []);

  // Fetch week occurrences whenever week date or plan filter changes
  const refreshWeek = async (targetDate?: string, planId?: number) => {
    try {
      const res = await getWeekOccurrences(targetDate, planId);
      setWeekOccurrences(res.data || []);
      setWeekRange(res.week);
      setCurrentWeekDate(targetDate);
    } catch (err: unknown) {
      console.error('Lỗi tải thời khóa tuần:', err);
    }
  };

  // Fetch today occurrences
  const refreshToday = async (planId?: number) => {
    try {
      const res = await getTodayOccurrences(planId);
      setTodayOccurrences(res.data || []);
      setTodayDateStr(res.today);
    } catch (err: unknown) {
      console.error('Lỗi tải thời khóa hôm nay:', err);
    }
  };

  // Handle plan switch
  const handleSelectPlan = (planId?: number) => {
    setSelectedPlanId(planId);
    if (currentTab === 'week') {
      refreshWeek(currentWeekDate, planId);
    } else if (currentTab === 'today') {
      refreshToday(planId);
    }
  };

  // Week navigation
  const handlePrevWeek = () => {
    if (!weekRange.from) return;
    const prev = new Date(`${weekRange.from}T00:00:00`);
    prev.setDate(prev.getDate() - 7);
    const dateStr = prev.toISOString().split('T')[0];
    refreshWeek(dateStr, selectedPlanId);
  };

  const handleNextWeek = () => {
    if (!weekRange.from) return;
    const next = new Date(`${weekRange.from}T00:00:00`);
    next.setDate(next.getDate() + 7);
    const dateStr = next.toISOString().split('T')[0];
    refreshWeek(dateStr, selectedPlanId);
  };

  const handleTodayWeek = () => {
    refreshWeek(undefined, selectedPlanId);
  };

  // Tab switch
  const handleTabChange = (tab: PlannerTab) => {
    setCurrentTab(tab);
    if (tab === 'today') {
      refreshToday(selectedPlanId);
    } else if (tab === 'week') {
      refreshWeek(currentWeekDate, selectedPlanId);
    }
  };

  // Plan CRUD handlers
  const handleSavePlan = async (
    data: CreatePracticePlanInput | UpdatePracticePlanInput
  ) => {
    setIsOperating(true);
    try {
      if (planToEdit) {
        const updated = await updatePracticePlan(planToEdit.id, data);
        setPlans((prev) => prev.map((p) => (p.id === updated.id ? updated : p)));
        setFeedback({
          type: 'success',
          message: 'Đã cập nhật kế hoạch tu tập thành công.',
        });
      } else {
        const created = await createPracticePlan(data as CreatePracticePlanInput);
        setPlans((prev) => [created, ...prev]);
        setSelectedPlanId(created.id);
        setFeedback({
          type: 'success',
          message: 'Đã tạo kế hoạch tu tập mới thành công.',
        });
      }
      refreshWeek(currentWeekDate, selectedPlanId);
    } finally {
      setIsOperating(false);
    }
  };

  const handlePausePlan = async (plan: PracticePlan) => {
    setIsOperating(true);
    try {
      const updated = await pausePracticePlan(plan.id);
      setPlans((prev) => prev.map((p) => (p.id === updated.id ? updated : p)));
      refreshWeek(currentWeekDate, selectedPlanId);
      setFeedback({
        type: 'success',
        message: 'Đã tạm dừng kế hoạch tu tập.',
      });
    } finally {
      setIsOperating(false);
    }
  };

  const handleResumePlan = async (plan: PracticePlan) => {
    setIsOperating(true);
    try {
      const updated = await resumePracticePlan(plan.id);
      setPlans((prev) => prev.map((p) => (p.id === updated.id ? updated : p)));
      refreshWeek(currentWeekDate, selectedPlanId);
      setFeedback({
        type: 'success',
        message: 'Đã kích hoạt lại kế hoạch tu tập.',
      });
    } finally {
      setIsOperating(false);
    }
  };

  const handleArchivePlan = async (plan: PracticePlan) => {
    setIsOperating(true);
    try {
      const updated = await archivePracticePlan(plan.id);
      setPlans((prev) => prev.map((p) => (p.id === updated.id ? updated : p)));
      refreshWeek(currentWeekDate, selectedPlanId);
      setFeedback({
        type: 'success',
        message: 'Đã lưu trữ kế hoạch tu tập.',
      });
    } finally {
      setIsOperating(false);
    }
  };

  const handleDeletePlan = async (planId: number) => {
    if (!window.confirm('Quý vị có chắc chắn muốn xóa kế hoạch này? (Các buổi thực hành đã ghi nhận vẫn được bảo toàn nguyên vẹn)')) {
      return;
    }
    setIsOperating(true);
    try {
      await deletePracticePlan(planId);
      setPlans((prev) => prev.filter((p) => p.id !== planId));
      if (selectedPlanId === planId) {
        setSelectedPlanId(undefined);
      }
      refreshWeek(currentWeekDate, undefined);
      setFeedback({
        type: 'success',
        message: 'Đã xóa kế hoạch tu tập.',
      });
    } finally {
      setIsOperating(false);
    }
  };

  // Plan Item CRUD handlers
  const handleSaveItem = async (
    data: CreatePracticePlanItemInput | UpdatePracticePlanItemInput
  ) => {
    const targetPlanId = selectedPlanId || plans[0]?.id;
    if (!targetPlanId) return;

    setIsOperating(true);
    try {
      if (itemToEdit) {
        await updatePracticePlanItem(itemToEdit.id, data);
        setFeedback({
          type: 'success',
          message: 'Đã cập nhật hoạt động tu tập.',
        });
      } else {
        await createPracticePlanItem(targetPlanId, data as CreatePracticePlanItemInput);
        setFeedback({
          type: 'success',
          message: 'Đã thêm hoạt động vào kế hoạch tu tập.',
        });
      }
      // Reload plans to refresh item counts
      const updatedPlans = await getPracticePlans();
      setPlans(updatedPlans);
      refreshWeek(currentWeekDate, selectedPlanId);
    } finally {
      setIsOperating(false);
    }
  };

  const handleDeleteItem = async (itemId: number) => {
    if (!window.confirm('Xóa hoạt động này khỏi kế hoạch tu tập?')) return;
    setIsOperating(true);
    try {
      await deletePracticePlanItem(itemId);
      const updatedPlans = await getPracticePlans();
      setPlans(updatedPlans);
      refreshWeek(currentWeekDate, selectedPlanId);
      setFeedback({
        type: 'success',
        message: 'Đã xóa hoạt động khỏi kế hoạch.',
      });
    } finally {
      setIsOperating(false);
    }
  };

  // Occurrence Skip / Restore / Link handlers
  const handleSkipOccurrence = async (occ: PracticePlanOccurrence) => {
    setIsOperating(true);
    try {
      await skipPracticeOccurrence(occ.plan_item_id, occ.local_date);
      refreshWeek(currentWeekDate, selectedPlanId);
      if (currentTab === 'today') refreshToday(selectedPlanId);
      setFeedback({
        type: 'success',
        message: 'Đã bỏ qua thời khóa này trong an tĩnh.',
      });
    } catch (err: unknown) {
      console.error('Lỗi bỏ qua thời khóa:', err);
    } finally {
      setIsOperating(false);
    }
  };

  const handleRestoreOccurrence = async (occ: PracticePlanOccurrence) => {
    setIsOperating(true);
    try {
      await restorePracticeOccurrence(occ.plan_item_id, occ.local_date);
      refreshWeek(currentWeekDate, selectedPlanId);
      if (currentTab === 'today') refreshToday(selectedPlanId);
      setFeedback({
        type: 'success',
        message: 'Đã khôi phục hoạt động dự kiến.',
      });
    } catch (err: unknown) {
      console.error('Lỗi khôi phục thời khóa:', err);
    } finally {
      setIsOperating(false);
    }
  };

  const handleUnlinkSession = async (occ: PracticePlanOccurrence) => {
    if (!window.confirm('Gỡ liên kết buổi thực hành này khỏi thời khóa dự kiến? (Buổi thực hành gốc vẫn được lưu trữ)')) {
      return;
    }
    setIsOperating(true);
    try {
      await unlinkPracticeSession(occ.plan_item_id, occ.local_date);
      refreshWeek(currentWeekDate, selectedPlanId);
      if (currentTab === 'today') refreshToday(selectedPlanId);
      setFeedback({
        type: 'success',
        message: 'Đã gỡ liên kết buổi thực hành thành công.',
      });
    } catch (err: unknown) {
      console.error('Lỗi gỡ liên kết:', err);
    } finally {
      setIsOperating(false);
    }
  };

  const handleConfirmLinkSession = async (
    planItemId: number,
    localDate: string,
    sessionId: number
  ) => {
    setIsOperating(true);
    try {
      await linkPracticeSession(planItemId, localDate, sessionId);
      refreshWeek(currentWeekDate, selectedPlanId);
      if (currentTab === 'today') refreshToday(selectedPlanId);
      setFeedback({
        type: 'success',
        message: 'Đã liên kết buổi thực hành đã hoàn tất với thời khóa thành công!',
      });
    } finally {
      setIsOperating(false);
    }
  };

  const activePlan = plans.find((p) => p.id === selectedPlanId) || plans[0];

  return (
    <div className="w-full max-w-5xl mx-auto space-y-6">
      {/* Breadcrumb */}
      <div className="flex items-center gap-2 text-xs font-label-md text-outline">
        <Link
          href="/tai-khoan"
          className="hover:text-primary transition-colors flex items-center gap-1"
        >
          <span className="material-symbols-outlined text-[16px]">arrow_back</span>
          <span>Tài khoản tu tập</span>
        </Link>
        <span>/</span>
        <span className="text-on-surface font-semibold">Kế hoạch tu tập</span>
      </div>

      {/* Header Banner */}
      <div className="p-6 sm:p-8 rounded-3xl bg-gradient-to-br from-surface-container-lowest to-surface-container-low border border-outline-variant/30 shadow-2xs relative overflow-hidden">
        <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
          <div className="space-y-1.5 max-w-xl">
            <div className="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-primary/10 text-primary text-xs font-label-md font-bold mb-1">
              <span className="material-symbols-outlined text-[16px]">
                calendar_month
              </span>
              <span>Thời Khóa & Hành Trì</span>
            </div>
            <h1 className="font-serif text-2xl sm:text-3xl font-bold text-on-surface">
              Kế Hoạch Tu Tập Cá Nhân
            </h1>
            <p className="font-body-md text-xs sm:text-sm text-on-surface-variant leading-relaxed">
              Tự sắp xếp thời khóa thiền định, niệm Phật, trì chú, tụng kinh phù hợp với nhịp sống của bạn. Tôn trọng tự do hành trì, không áp lực điểm số hay thành tích.
            </p>
          </div>

          <div className="flex items-center gap-2.5 flex-wrap shrink-0">
            {plans.length > 0 && (
              <button
                type="button"
                onClick={() => {
                  setItemToEdit(null);
                  setIsItemModalOpen(true);
                }}
                className="flex items-center gap-1.5 px-4 py-2.5 rounded-2xl border border-outline-variant/40 bg-surface text-on-surface text-xs font-label-md font-semibold hover:bg-surface-container transition-colors cursor-pointer"
              >
                <span className="material-symbols-outlined text-[18px]">add</span>
                <span>Thêm hoạt động</span>
              </button>
            )}

            <button
              type="button"
              onClick={() => {
                setPlanToEdit(null);
                setIsPlanModalOpen(true);
              }}
              className="flex items-center gap-1.5 px-5 py-2.5 rounded-2xl bg-primary text-on-primary text-xs font-label-md font-bold shadow-xs hover:bg-primary-variant transition-colors cursor-pointer"
            >
              <span className="material-symbols-outlined text-[18px]">add_circle</span>
              <span>Tạo kế hoạch mới</span>
            </button>
          </div>
        </div>
      </div>

      {/* Feedback Toast */}
      {feedback && (
        <div
          className={`p-3.5 rounded-2xl text-xs font-label-md flex items-center justify-between gap-2 animate-in fade-in duration-200 ${
            feedback.type === 'success'
              ? 'bg-emerald-500/10 border border-emerald-500/30 text-emerald-800'
              : 'bg-rose-500/10 border border-rose-500/30 text-rose-800'
          }`}
        >
          <div className="flex items-center gap-2">
            <span className="material-symbols-outlined text-[18px]">
              {feedback.type === 'success' ? 'check_circle' : 'error'}
            </span>
            <span>{feedback.message}</span>
          </div>
          <button
            type="button"
            onClick={() => setFeedback(null)}
            className="p-1 rounded-lg text-outline hover:text-on-surface"
          >
            <span className="material-symbols-outlined text-[16px]">close</span>
          </button>
        </div>
      )}

      {/* Main Content Area */}
      {isLoading ? (
        <div className="space-y-4 animate-pulse">
          <div className="h-12 rounded-2xl bg-surface-container-low w-64" />
          <div className="h-64 rounded-3xl bg-surface-container-low" />
        </div>
      ) : plans.length === 0 ? (
        <PracticePlannerEmptyState
          onCreatePlan={() => {
            setPlanToEdit(null);
            setIsPlanModalOpen(true);
          }}
        />
      ) : (
        <div className="space-y-5">
          {/* Controls Bar: Plan Selector & Tab Switcher */}
          <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3 p-3 rounded-2xl bg-surface-container-lowest border border-outline-variant/30">
            {/* Plan Selector */}
            <div className="flex items-center gap-2">
              <span className="text-xs font-label-md font-semibold text-outline hidden sm:inline">
                Kế hoạch:
              </span>
              <select
                value={selectedPlanId ?? ''}
                onChange={(e) =>
                  handleSelectPlan(
                    e.target.value ? parseInt(e.target.value, 10) : undefined
                  )
                }
                className="px-3 py-1.5 rounded-xl border border-outline-variant/40 bg-surface text-on-surface text-xs font-label-md font-medium focus:outline-none focus:border-primary"
              >
                <option value="">Tất cả kế hoạch</option>
                {plans.map((p) => (
                  <option key={p.id} value={p.id}>
                    {p.name} ({p.status_label})
                  </option>
                ))}
              </select>
            </div>

            {/* Tabs */}
            <div className="flex items-center gap-1 bg-surface-container-low p-1 rounded-xl self-start sm:self-auto">
              <button
                type="button"
                onClick={() => handleTabChange('week')}
                className={`px-3 py-1.5 rounded-lg text-xs font-label-md font-semibold transition-all cursor-pointer ${
                  currentTab === 'week'
                    ? 'bg-surface text-primary shadow-2xs'
                    : 'text-on-surface-variant hover:text-on-surface'
                }`}
              >
                Thời khóa tuần
              </button>
              <button
                type="button"
                onClick={() => handleTabChange('today')}
                className={`px-3 py-1.5 rounded-lg text-xs font-label-md font-semibold transition-all cursor-pointer ${
                  currentTab === 'today'
                    ? 'bg-surface text-primary shadow-2xs'
                    : 'text-on-surface-variant hover:text-on-surface'
                }`}
              >
                Hôm nay
              </button>
              <button
                type="button"
                onClick={() => handleTabChange('plans')}
                className={`px-3 py-1.5 rounded-lg text-xs font-label-md font-semibold transition-all cursor-pointer ${
                  currentTab === 'plans'
                    ? 'bg-surface text-primary shadow-2xs'
                    : 'text-on-surface-variant hover:text-on-surface'
                }`}
              >
                Quản lý ({plans.length})
              </button>
            </div>
          </div>

          {/* TAB 1: Week Planner Grid */}
          {currentTab === 'week' && (
            <PracticeWeekGrid
              occurrences={weekOccurrences}
              week={weekRange}
              onPrevWeek={handlePrevWeek}
              onNextWeek={handleNextWeek}
              onTodayWeek={handleTodayWeek}
              onLinkSession={(occ) => setLinkDialogOccurrence(occ)}
              onUnlinkSession={handleUnlinkSession}
              onSkip={handleSkipOccurrence}
              onRestore={handleRestoreOccurrence}
              isOperating={isOperating}
            />
          )}

          {/* TAB 2: Today View */}
          {currentTab === 'today' && (
            <div className="space-y-4">
              <div className="flex items-center justify-between p-4 rounded-3xl bg-surface-container-lowest border border-outline-variant/30">
                <div className="flex items-center gap-2">
                  <span className="material-symbols-outlined text-[22px] text-primary">
                    today
                  </span>
                  <span className="font-serif text-base font-bold text-on-surface">
                    Thời khóa hôm nay ({todayDateStr})
                  </span>
                </div>
                <span className="text-xs font-label-md text-outline">
                  {todayOccurrences.length} thời khóa dự kiến
                </span>
              </div>

              {todayOccurrences.length === 0 ? (
                <div className="p-8 rounded-3xl bg-surface-container-lowest border border-outline-variant/30 text-center space-y-2">
                  <span className="material-symbols-outlined text-[32px] text-outline opacity-50">
                    event_available
                  </span>
                  <p className="text-sm font-serif font-semibold text-on-surface">
                    Hôm nay không có thời khóa nào trong kế hoạch.
                  </p>
                  <p className="text-xs text-outline">
                    Quý vị có thể thêm thời khóa mới hoặc xem toàn bộ tuần.
                  </p>
                </div>
              ) : (
                <div className="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                  {todayOccurrences.map((occ) => (
                    <PracticeActivityCard
                      key={occ.occurrence_id}
                      occurrence={occ}
                      onLinkSession={(o) => setLinkDialogOccurrence(o)}
                      onUnlinkSession={handleUnlinkSession}
                      onSkip={handleSkipOccurrence}
                      onRestore={handleRestoreOccurrence}
                      isOperating={isOperating}
                    />
                  ))}
                </div>
              )}
            </div>
          )}

          {/* TAB 3: Manage Plans & Items */}
          {currentTab === 'plans' && (
            <div className="space-y-6">
              {plans.map((plan) => (
                <div
                  key={plan.id}
                  className="p-5 sm:p-6 rounded-3xl bg-surface-container-lowest border border-outline-variant/30 space-y-4"
                >
                  <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-outline-variant/20 pb-4">
                    <div>
                      <div className="flex items-center gap-2 flex-wrap">
                        <h3 className="font-serif text-lg font-bold text-on-surface">
                          {plan.name}
                        </h3>
                        <span
                          className={`text-[11px] font-label-sm font-semibold px-2 py-0.5 rounded-full ${
                            plan.status === 'active'
                              ? 'bg-primary/15 text-primary'
                              : plan.status === 'paused'
                              ? 'bg-amber-500/15 text-amber-700'
                              : 'bg-surface-container-high text-outline'
                          }`}
                        >
                          {plan.status_label}
                        </span>
                      </div>
                      {plan.description && (
                        <p className="text-xs text-outline mt-1 font-body-sm">
                          {plan.description}
                        </p>
                      )}
                      <p className="text-[11px] text-outline font-label-md mt-0.5">
                        Múi giờ: {plan.timezone} • {plan.item_count} hoạt động
                      </p>
                    </div>

                    <div className="flex items-center gap-1.5 flex-wrap">
                      {plan.status === 'active' ? (
                        <button
                          type="button"
                          onClick={() => handlePausePlan(plan)}
                          disabled={isOperating}
                          className="px-2.5 py-1 rounded-xl text-xs font-label-md border border-outline-variant/40 hover:bg-surface-container text-on-surface transition-colors cursor-pointer"
                        >
                          Tạm dừng
                        </button>
                      ) : (
                        <button
                          type="button"
                          onClick={() => handleResumePlan(plan)}
                          disabled={isOperating}
                          className="px-2.5 py-1 rounded-xl text-xs font-label-md bg-primary text-on-primary hover:bg-primary-variant transition-colors cursor-pointer"
                        >
                          Kích hoạt
                        </button>
                      )}

                      <button
                        type="button"
                        onClick={() => {
                          setPlanToEdit(plan);
                          setIsPlanModalOpen(true);
                        }}
                        disabled={isOperating}
                        className="p-1.5 rounded-xl text-outline hover:text-primary hover:bg-surface-container transition-colors cursor-pointer"
                        title="Chỉnh sửa kế hoạch"
                      >
                        <span className="material-symbols-outlined text-[18px]">
                          edit
                        </span>
                      </button>

                      <button
                        type="button"
                        onClick={() => handleArchivePlan(plan)}
                        disabled={isOperating}
                        className="p-1.5 rounded-xl text-outline hover:text-amber-700 hover:bg-surface-container transition-colors cursor-pointer"
                        title="Lưu trữ kế hoạch"
                      >
                        <span className="material-symbols-outlined text-[18px]">
                          archive
                        </span>
                      </button>

                      <button
                        type="button"
                        onClick={() => handleDeletePlan(plan.id)}
                        disabled={isOperating}
                        className="p-1.5 rounded-xl text-outline hover:text-rose-600 hover:bg-rose-500/10 transition-colors cursor-pointer"
                        title="Xóa kế hoạch"
                      >
                        <span className="material-symbols-outlined text-[18px]">
                          delete
                        </span>
                      </button>
                    </div>
                  </div>

                  {/* Plan Items List */}
                  <div className="space-y-2.5">
                    <div className="flex items-center justify-between">
                      <h4 className="text-xs font-label-md font-bold text-outline uppercase tracking-wider">
                        Danh sách các hoạt động trong kế hoạch
                      </h4>
                      <button
                        type="button"
                        onClick={() => {
                          setSelectedPlanId(plan.id);
                          setItemToEdit(null);
                          setIsItemModalOpen(true);
                        }}
                        className="text-xs font-label-md font-semibold text-primary hover:underline flex items-center gap-1 cursor-pointer"
                      >
                        <span className="material-symbols-outlined text-[15px]">add</span>
                        <span>Thêm hoạt động</span>
                      </button>
                    </div>

                    {(!plan.items || plan.items.length === 0) ? (
                      <p className="text-xs text-outline italic py-2">
                        Chưa có hoạt động nào trong kế hoạch này.
                      </p>
                    ) : (
                      <div className="space-y-2">
                        {plan.items.map((item) => (
                          <div
                            key={item.id}
                            className="flex items-center justify-between p-3 rounded-2xl bg-surface-container-low border border-outline-variant/20 gap-3"
                          >
                            <div>
                              <p className="text-xs font-label-md font-semibold text-on-surface">
                                {item.title || item.practice_label}
                              </p>
                              <p className="text-[11px] text-outline font-body-sm mt-0.5">
                                {item.practice_label} • {item.schedule_description}
                              </p>
                            </div>

                            <div className="flex items-center gap-1 shrink-0">
                              <button
                                type="button"
                                onClick={() => {
                                  setSelectedPlanId(plan.id);
                                  setItemToEdit(item);
                                  setIsItemModalOpen(true);
                                }}
                                className="p-1.5 rounded-lg text-outline hover:text-primary transition-colors cursor-pointer"
                                title="Chỉnh sửa hoạt động"
                              >
                                <span className="material-symbols-outlined text-[17px]">
                                  edit
                                </span>
                              </button>
                              <button
                                type="button"
                                onClick={() => handleDeleteItem(item.id)}
                                className="p-1.5 rounded-lg text-outline hover:text-rose-600 transition-colors cursor-pointer"
                                title="Xóa hoạt động"
                              >
                                <span className="material-symbols-outlined text-[17px]">
                                  delete
                                </span>
                              </button>
                            </div>
                          </div>
                        ))}
                      </div>
                    )}
                  </div>
                </div>
              ))}
            </div>
          )}
        </div>
      )}

      {/* Plan Create/Edit Modal */}
      <PracticePlanFormModal
        isOpen={isPlanModalOpen}
        onClose={() => {
          setIsPlanModalOpen(false);
          setPlanToEdit(null);
        }}
        planToEdit={planToEdit}
        onSave={handleSavePlan}
        isSubmitting={isOperating}
      />

      {/* Item Create/Edit Modal */}
      {activePlan && (
        <PracticePlanItemFormModal
          isOpen={isItemModalOpen}
          onClose={() => {
            setIsItemModalOpen(false);
            setItemToEdit(null);
          }}
          planId={activePlan.id}
          itemToEdit={itemToEdit}
          onSave={handleSaveItem}
          isSubmitting={isOperating}
        />
      )}

      {/* Session Link Dialog */}
      <PracticeSessionLinkDialog
        isOpen={linkDialogOccurrence !== null}
        onClose={() => setLinkDialogOccurrence(null)}
        occurrence={linkDialogOccurrence}
        onConfirmLink={handleConfirmLinkSession}
        isLinking={isOperating}
      />
    </div>
  );
}
