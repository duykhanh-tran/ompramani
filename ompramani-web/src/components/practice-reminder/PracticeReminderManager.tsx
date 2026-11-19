'use client';

import React, { useState, useEffect } from 'react';
import Link from 'next/link';
import {
  PracticeReminder,
  PracticeReminderSettings,
  CreatePracticeReminderInput,
  UpdatePracticeReminderInput,
  UpdatePracticeReminderSettingsInput,
} from '@/types/practice-reminder';
import {
  getPracticeReminders,
  getPracticeReminderSettings,
  createPracticeReminder,
  updatePracticeReminder,
  deletePracticeReminder,
  pausePracticeReminder,
  resumePracticeReminder,
  updatePracticeReminderSettings,
} from '@/lib/api/practice-reminders';
import { PracticeReminderCard } from './PracticeReminderCard';
import { PracticeReminderFormModal } from './PracticeReminderFormModal';
import { PracticeReminderSettingsModal } from './PracticeReminderSettingsModal';

export function PracticeReminderManager() {
  const [reminders, setReminders] = useState<PracticeReminder[]>([]);
  const [settings, setSettings] = useState<PracticeReminderSettings | null>(null);
  const [isLoading, setIsLoading] = useState(true);

  const [isFormOpen, setIsFormOpen] = useState(false);
  const [isSettingsOpen, setIsSettingsOpen] = useState(false);
  const [reminderToEdit, setReminderToEdit] = useState<PracticeReminder | null>(null);
  const [isSubmittingForm, setIsSubmittingForm] = useState(false);
  const [isSavingSettings, setIsSavingSettings] = useState(false);
  const [togglingId, setTogglingId] = useState<number | null>(null);
  const [deleteConfirmId, setDeleteConfirmId] = useState<number | null>(null);
  const [isDeleting, setIsDeleting] = useState(false);

  const [feedback, setFeedback] = useState<{
    type: 'success' | 'error';
    message: string;
  } | null>(null);

  useEffect(() => {
    let active = true;

    async function init() {
      try {
        const [remindersData, settingsData] = await Promise.all([
          getPracticeReminders(),
          getPracticeReminderSettings(),
        ]);
        if (active) {
          setReminders(remindersData);
          setSettings(settingsData);
        }
      } catch (err: unknown) {
        if (active) {
          console.error('Lỗi tải dữ liệu nhắc nhở tu tập:', err);
          setFeedback({
            type: 'error',
            message: 'Không thể tải danh sách lời nhắc. Vui lòng tải lại trang.',
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

  // Handle Create / Edit save
  const handleSaveReminder = async (
    data: CreatePracticeReminderInput | UpdatePracticeReminderInput
  ) => {
    setIsSubmittingForm(true);
    try {
      if (reminderToEdit) {
        const updated = await updatePracticeReminder(reminderToEdit.id, data);
        setReminders((prev) =>
          prev.map((r) => (r.id === updated.id ? updated : r))
        );
        setFeedback({
          type: 'success',
          message: 'Đã cập nhật lời nhắc tu tập thành công.',
        });
      } else {
        const created = await createPracticeReminder(
          data as CreatePracticeReminderInput
        );
        setReminders((prev) => [created, ...prev]);
        setFeedback({
          type: 'success',
          message: 'Đã tạo lời nhắc tu tập thành công.',
        });
      }
    } finally {
      setIsSubmittingForm(false);
    }
  };

  // Handle Pause / Resume toggle
  const handleTogglePause = async (reminder: PracticeReminder) => {
    setTogglingId(reminder.id);
    try {
      if (reminder.is_enabled) {
        const updated = await pausePracticeReminder(reminder.id);
        setReminders((prev) =>
          prev.map((r) => (r.id === updated.id ? updated : r))
        );
        setFeedback({
          type: 'success',
          message: 'Đã tạm dừng lời nhắc tu tập.',
        });
      } else {
        const updated = await resumePracticeReminder(reminder.id);
        setReminders((prev) =>
          prev.map((r) => (r.id === updated.id ? updated : r))
        );
        setFeedback({
          type: 'success',
          message: 'Đã kích hoạt lại lời nhắc tu tập.',
        });
      }
    } catch (err: unknown) {
      console.error('Lỗi đổi trạng thái lời nhắc:', err);
      setFeedback({
        type: 'error',
        message: 'Không thể thay đổi trạng thái lời nhắc. Vui lòng thử lại.',
      });
    } finally {
      setTogglingId(null);
    }
  };

  // Handle Delete confirm
  const handleExecuteDelete = async () => {
    if (!deleteConfirmId) return;
    setIsDeleting(true);
    try {
      await deletePracticeReminder(deleteConfirmId);
      setReminders((prev) => prev.filter((r) => r.id !== deleteConfirmId));
      setDeleteConfirmId(null);
      setFeedback({
        type: 'success',
        message: 'Đã xóa lời nhắc tu tập.',
      });
    } catch (err: unknown) {
      console.error('Lỗi xóa lời nhắc:', err);
      setFeedback({
        type: 'error',
        message: 'Không thể xóa lời nhắc. Vui lòng thử lại.',
      });
    } finally {
      setIsDeleting(false);
    }
  };

  // Handle Save Settings
  const handleSaveSettings = async (
    data: UpdatePracticeReminderSettingsInput
  ) => {
    setIsSavingSettings(true);
    try {
      const updated = await updatePracticeReminderSettings(data);
      setSettings(updated);
      setFeedback({
        type: 'success',
        message: 'Đã cập nhật cài đặt thông báo & giờ yên tĩnh.',
      });
    } finally {
      setIsSavingSettings(false);
    }
  };

  const activeReminders = reminders.filter((r) => r.is_enabled);
  const pausedReminders = reminders.filter((r) => !r.is_enabled);

  return (
    <div className="w-full max-w-4xl mx-auto space-y-6">
      {/* Breadcrumb back to account */}
      <div className="flex items-center gap-2 text-xs font-label-md text-outline">
        <Link
          href="/tai-khoan"
          className="hover:text-primary transition-colors flex items-center gap-1"
        >
          <span className="material-symbols-outlined text-[16px]">arrow_back</span>
          <span>Tài khoản tu tập</span>
        </Link>
        <span>/</span>
        <span className="text-on-surface font-semibold">Nhắc nhở tu tập</span>
      </div>

      {/* Header Banner */}
      <div className="p-6 sm:p-8 rounded-3xl bg-gradient-to-br from-surface-container-lowest to-surface-container-low border border-outline-variant/30 shadow-2xs relative overflow-hidden">
        <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
          <div className="space-y-1.5 max-w-xl">
            <div className="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-primary/10 text-primary text-xs font-label-md font-bold mb-1">
              <span className="material-symbols-outlined text-[16px]">
                self_improvement
              </span>
              <span>Thời Khóa Tu Học</span>
            </div>
            <h1 className="font-serif text-2xl sm:text-3xl font-bold text-on-surface">
              Nhắc Nhở Tu Tập Cá Nhân
            </h1>
            <p className="font-body-md text-xs sm:text-sm text-on-surface-variant leading-relaxed">
              Thiết lập lời nhắc thanh tịnh cho các thời khóa thiền định, niệm Phật, trì chú, tụng kinh. Thông báo gửi an tĩnh vào Hộp thư ứng dụng, đồng hành cùng tinh tấn mỗi ngày.
            </p>
          </div>

          <div className="flex items-center gap-2.5 flex-wrap shrink-0">
            <button
              type="button"
              onClick={() => setIsSettingsOpen(true)}
              className="flex items-center gap-1.5 px-4 py-2.5 rounded-2xl border border-outline-variant/40 bg-surface text-on-surface text-xs font-label-md font-semibold hover:bg-surface-container transition-colors cursor-pointer"
            >
              <span className="material-symbols-outlined text-[18px]">bedtime</span>
              <span>Giờ yên tĩnh</span>
            </button>

            <button
              type="button"
              onClick={() => {
                setReminderToEdit(null);
                setIsFormOpen(true);
              }}
              className="flex items-center gap-1.5 px-5 py-2.5 rounded-2xl bg-primary text-on-primary text-xs font-label-md font-bold shadow-xs hover:bg-primary-variant transition-colors cursor-pointer"
            >
              <span className="material-symbols-outlined text-[18px]">add</span>
              <span>Tạo lời nhắc mới</span>
            </button>
          </div>
        </div>

        {/* Global Preference Disabled Warning */}
        {settings && !settings.practice_reminders_enabled && (
          <div className="mt-4 p-3.5 rounded-2xl bg-amber-500/10 border border-amber-500/30 text-amber-800 text-xs font-label-md flex items-center justify-between gap-3">
            <div className="flex items-center gap-2">
              <span className="material-symbols-outlined text-[18px] text-amber-600">
                notifications_off
              </span>
              <span>
                Thông báo nhắc tu tập hiện đang <strong>tắt</strong>. Hệ thống sẽ tạm dừng gửi thông báo cho tới khi bạn bật lại.
              </span>
            </div>
            <button
              type="button"
              onClick={() => setIsSettingsOpen(true)}
              className="px-3 py-1 rounded-xl bg-amber-600 text-white text-xs font-bold shrink-0 hover:bg-amber-700 transition-colors cursor-pointer"
            >
              Bật lại
            </button>
          </div>
        )}
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

      {/* Loading Skeleton */}
      {isLoading ? (
        <div className="space-y-4 animate-pulse">
          <div className="h-28 rounded-3xl bg-surface-container-low" />
          <div className="h-28 rounded-3xl bg-surface-container-low" />
          <div className="h-28 rounded-3xl bg-surface-container-low" />
        </div>
      ) : reminders.length === 0 ? (
        /* Empty State */
        <div className="p-10 sm:p-14 rounded-3xl bg-surface-container-lowest border border-outline-variant/30 text-center space-y-4">
          <div className="w-16 h-16 rounded-full bg-primary/10 text-primary mx-auto flex items-center justify-center">
            <span className="material-symbols-outlined text-[32px]">
              alarm_on
            </span>
          </div>
          <div className="max-w-md mx-auto space-y-1.5">
            <h3 className="font-serif text-lg font-bold text-on-surface">
              Chưa có lời nhắc tu tập nào
            </h3>
            <p className="text-xs sm:text-sm font-body-md text-outline leading-relaxed">
              Hãy tạo lời nhắc cho thời khóa sáng hoặc tối để duy trì tinh tấn tu học mỗi ngày trong bình an.
            </p>
          </div>
          <button
            type="button"
            onClick={() => {
              setReminderToEdit(null);
              setIsFormOpen(true);
            }}
            className="inline-flex items-center gap-2 px-5 py-2.5 rounded-2xl bg-primary text-on-primary text-xs font-label-md font-bold shadow-xs hover:bg-primary-variant transition-colors cursor-pointer"
          >
            <span className="material-symbols-outlined text-[18px]">add</span>
            <span>Thiết lập lời nhắc đầu tiên</span>
          </button>
        </div>
      ) : (
        /* Reminder Lists */
        <div className="space-y-8">
          {/* Active Reminders */}
          {activeReminders.length > 0 && (
            <div className="space-y-3.5">
              <h2 className="text-xs font-label-md font-bold text-outline uppercase tracking-wider px-1 flex items-center gap-2">
                <span>Lời nhắc đang hoạt động ({activeReminders.length})</span>
              </h2>
              <div className="grid grid-cols-1 gap-3.5">
                {activeReminders.map((reminder) => (
                  <PracticeReminderCard
                    key={reminder.id}
                    reminder={reminder}
                    onEdit={(r) => {
                      setReminderToEdit(r);
                      setIsFormOpen(true);
                    }}
                    onDelete={(id) => setDeleteConfirmId(id)}
                    onTogglePause={handleTogglePause}
                    isToggling={togglingId === reminder.id}
                  />
                ))}
              </div>
            </div>
          )}

          {/* Paused Reminders */}
          {pausedReminders.length > 0 && (
            <div className="space-y-3.5">
              <h2 className="text-xs font-label-md font-bold text-outline uppercase tracking-wider px-1 flex items-center gap-2">
                <span>Lời nhắc tạm dừng ({pausedReminders.length})</span>
              </h2>
              <div className="grid grid-cols-1 gap-3.5">
                {pausedReminders.map((reminder) => (
                  <PracticeReminderCard
                    key={reminder.id}
                    reminder={reminder}
                    onEdit={(r) => {
                      setReminderToEdit(r);
                      setIsFormOpen(true);
                    }}
                    onDelete={(id) => setDeleteConfirmId(id)}
                    onTogglePause={handleTogglePause}
                    isToggling={togglingId === reminder.id}
                  />
                ))}
              </div>
            </div>
          )}
        </div>
      )}

      {/* Delete Confirmation Dialog */}
      {deleteConfirmId !== null && (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs animate-in fade-in duration-150">
          <div className="w-full max-w-sm rounded-3xl bg-surface-container-lowest border border-outline-variant/30 p-6 space-y-4 shadow-2xl">
            <div className="w-11 h-11 rounded-2xl bg-rose-500/10 text-rose-600 flex items-center justify-center">
              <span className="material-symbols-outlined text-[24px]">delete</span>
            </div>
            <div>
              <h3 className="font-serif text-base font-semibold text-on-surface">
                Xóa lời nhắc tu tập?
              </h3>
              <p className="text-xs text-outline font-body-sm mt-1 leading-relaxed">
                Hành động này sẽ xóa hoàn toàn lịch nhắc đã chọn. Bạn có thể tạo lại lịch mới bất cứ lúc nào.
              </p>
            </div>
            <div className="flex items-center justify-end gap-2.5 pt-2">
              <button
                type="button"
                onClick={() => setDeleteConfirmId(null)}
                disabled={isDeleting}
                className="px-4 py-2 rounded-2xl border border-outline-variant/40 text-xs font-label-md font-semibold text-on-surface hover:bg-surface-container transition-colors cursor-pointer"
              >
                Hủy
              </button>
              <button
                type="button"
                onClick={handleExecuteDelete}
                disabled={isDeleting}
                className="px-4 py-2 rounded-2xl bg-rose-600 text-white text-xs font-label-md font-bold shadow-xs hover:bg-rose-700 transition-colors flex items-center gap-1.5 cursor-pointer disabled:opacity-50"
              >
                {isDeleting ? 'Đang xóa...' : 'Xóa lời nhắc'}
              </button>
            </div>
          </div>
        </div>
      )}

      {/* Form Modal (Create / Edit) */}
      <PracticeReminderFormModal
        isOpen={isFormOpen}
        onClose={() => {
          setIsFormOpen(false);
          setReminderToEdit(null);
        }}
        reminderToEdit={reminderToEdit}
        onSave={handleSaveReminder}
        isSubmitting={isSubmittingForm}
      />

      {/* Settings Modal (Quiet Hours) */}
      {settings && (
        <PracticeReminderSettingsModal
          isOpen={isSettingsOpen}
          onClose={() => setIsSettingsOpen(false)}
          settings={settings}
          onSave={handleSaveSettings}
          isSaving={isSavingSettings}
        />
      )}
    </div>
  );
}
