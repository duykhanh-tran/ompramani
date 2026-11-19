'use client';

import React, { useState, useEffect } from 'react';
import {
  PracticePlanOccurrence,
  CandidatePracticeSession,
} from '@/types/practice-plan';
import { getCandidateSessions } from '@/lib/api/practice-plans';

interface PracticeSessionLinkDialogProps {
  isOpen: boolean;
  onClose: () => void;
  occurrence: PracticePlanOccurrence | null;
  onConfirmLink: (
    planItemId: number,
    localDate: string,
    sessionId: number
  ) => Promise<void>;
  isLinking: boolean;
}

function PracticeSessionLinkDialogContent({
  onClose,
  occurrence,
  onConfirmLink,
  isLinking,
}: {
  onClose: () => void;
  occurrence: PracticePlanOccurrence;
  onConfirmLink: (
    planItemId: number,
    localDate: string,
    sessionId: number
  ) => Promise<void>;
  isLinking: boolean;
}) {
  const [candidates, setCandidates] = useState<CandidatePracticeSession[]>([]);
  const [selectedSessionId, setSelectedSessionId] = useState<number | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  const [errorMsg, setErrorMsg] = useState<string | null>(null);

  useEffect(() => {
    let active = true;

    getCandidateSessions(occurrence.plan_item_id, occurrence.local_date)
      .then((data) => {
        if (active) {
          setCandidates(data);
          if (data.length > 0) {
            setSelectedSessionId(data[0].id);
          }
        }
      })
      .catch((err: unknown) => {
        if (active) {
          console.error('Lỗi tải danh sách buổi tu tập phù hợp:', err);
          setErrorMsg('Không thể tải danh sách buổi tu tập. Vui lòng thử lại.');
        }
      })
      .finally(() => {
        if (active) {
          setIsLoading(false);
        }
      });

    return () => {
      active = false;
    };
  }, [occurrence.plan_item_id, occurrence.local_date]);

  const formatDuration = (seconds: number): string => {
    const mins = Math.floor(seconds / 60);
    if (mins < 60) return `${mins} phút`;
    const hours = Math.floor(mins / 60);
    const remMins = mins % 60;
    return remMins > 0 ? `${hours} giờ ${remMins} phút` : `${hours} giờ`;
  };

  const handleConfirm = async () => {
    if (!selectedSessionId) {
      setErrorMsg('Vui lòng chọn một buổi thực hành.');
      return;
    }

    try {
      await onConfirmLink(
        occurrence.plan_item_id,
        occurrence.local_date,
        selectedSessionId
      );
      onClose();
    } catch (err: unknown) {
      if (err instanceof Error) {
        setErrorMsg(err.message);
      } else {
        setErrorMsg('Lỗi liên kết buổi thực hành. Vui lòng thử lại.');
      }
    }
  };

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-xs animate-in fade-in duration-200">
      <div
        className="w-full max-w-lg rounded-3xl bg-surface-container-lowest border border-outline-variant/30 shadow-2xl overflow-hidden flex flex-col max-h-[90vh]"
        role="dialog"
        aria-modal="true"
        aria-labelledby="session-link-dialog-title"
      >
        {/* Header */}
        <div className="flex items-center justify-between px-6 py-4 border-b border-outline-variant/20 bg-surface-container-low/50">
          <div className="flex items-center gap-2.5">
            <span className="material-symbols-outlined text-[22px] text-primary">
              link
            </span>
            <h2
              id="session-link-dialog-title"
              className="font-serif text-lg font-semibold text-on-surface"
            >
              Liên kết buổi thực hành đã hoàn thành
            </h2>
          </div>
          <button
            type="button"
            onClick={onClose}
            className="p-1 rounded-full text-outline hover:text-on-surface hover:bg-surface-container transition-colors cursor-pointer"
            aria-label="Đóng"
          >
            <span className="material-symbols-outlined text-[20px]">close</span>
          </button>
        </div>

        {/* Body */}
        <div className="flex-1 overflow-y-auto p-6 space-y-4">
          <div className="p-3.5 rounded-2xl bg-surface-container-low border border-outline-variant/20 text-xs font-body-sm leading-relaxed space-y-1">
            <p className="font-semibold text-on-surface">
              Hoạt động: {occurrence.title} ({occurrence.practice_label})
            </p>
            <p className="text-outline">
              Ngày dự kiến: {occurrence.local_date} • {occurrence.local_time || 'Giờ linh hoạt'}
            </p>
          </div>

          {errorMsg && (
            <div className="p-3.5 rounded-2xl bg-rose-500/10 border border-rose-500/30 text-rose-700 text-xs font-label-md flex items-center gap-2">
              <span className="material-symbols-outlined text-[18px]">error</span>
              <span>{errorMsg}</span>
            </div>
          )}

          {isLoading ? (
            <div className="space-y-2.5 animate-pulse">
              <div className="h-16 rounded-2xl bg-surface-container-low" />
              <div className="h-16 rounded-2xl bg-surface-container-low" />
            </div>
          ) : candidates.length === 0 ? (
            <div className="py-8 px-4 text-center space-y-3">
              <span className="material-symbols-outlined text-[36px] text-outline">
                event_available
              </span>
              <p className="text-xs sm:text-sm font-body-md text-outline leading-relaxed max-w-sm mx-auto">
                Chưa tìm thấy buổi thực hành <strong>{occurrence.practice_label}</strong> nào đã hoàn tất vào ngày {occurrence.local_date}.
              </p>
              <div className="pt-2">
                <a
                  href={occurrence.destination_url}
                  className="inline-flex items-center gap-1.5 px-4 py-2 rounded-2xl bg-primary text-on-primary text-xs font-label-md font-semibold shadow-2xs hover:bg-primary-variant transition-colors"
                >
                  <span>Mở công cụ thực hành ngay</span>
                  <span className="material-symbols-outlined text-[15px]">
                    arrow_forward
                  </span>
                </a>
              </div>
            </div>
          ) : (
            <div className="space-y-2">
              <p className="text-xs font-label-md font-semibold text-on-surface">
                Chọn buổi thực hành để ghi nhận:
              </p>

              <div className="space-y-2">
                {candidates.map((session) => {
                  const isSelected = selectedSessionId === session.id;
                  const endedTime = session.ended_at
                    ? new Date(session.ended_at).toLocaleTimeString('vi-VN', {
                        hour: '2-digit',
                        minute: '2-digit',
                      })
                    : '';

                  return (
                    <label
                      key={session.id}
                      className={`flex items-center justify-between p-3.5 rounded-2xl border transition-all cursor-pointer ${
                        isSelected
                          ? 'border-primary bg-primary/10 text-on-surface'
                          : 'border-outline-variant/30 bg-surface-container-low hover:bg-surface-container'
                      }`}
                    >
                      <div className="flex items-center gap-3">
                        <input
                          type="radio"
                          name="candidate-session"
                          checked={isSelected}
                          onChange={() => setSelectedSessionId(session.id)}
                          className="w-4 h-4 text-primary focus:ring-primary"
                        />
                        <div>
                          <p className="text-xs font-label-md font-semibold text-on-surface">
                            {formatDuration(session.active_duration_seconds)}
                            {session.repetition_count !== null &&
                              ` • ${session.repetition_count.toLocaleString('vi-VN')} biến`}
                          </p>
                          <p className="text-[11px] text-outline font-body-sm mt-0.5">
                            Hoàn tất lúc {endedTime} • {session.status_label}
                          </p>
                        </div>
                      </div>

                      <span className="material-symbols-outlined text-[18px] text-emerald-700">
                        check_circle
                      </span>
                    </label>
                  );
                })}
              </div>
            </div>
          )}
        </div>

        {/* Footer */}
        <div className="px-6 py-3.5 border-t border-outline-variant/20 flex items-center justify-end gap-3 bg-surface-container-low/30">
          <button
            type="button"
            onClick={onClose}
            disabled={isLinking}
            className="px-4 py-2 rounded-2xl border border-outline-variant/40 text-xs font-label-md font-semibold text-on-surface hover:bg-surface-container transition-colors cursor-pointer"
          >
            Hủy
          </button>
          <button
            type="button"
            onClick={handleConfirm}
            disabled={isLinking || candidates.length === 0 || !selectedSessionId}
            className="px-5 py-2 rounded-2xl bg-primary text-on-primary text-xs font-label-md font-bold shadow-xs hover:bg-primary-variant transition-colors flex items-center gap-1.5 cursor-pointer disabled:opacity-50"
          >
            {isLinking ? (
              <>
                <span className="w-3.5 h-3.5 border-2 border-on-primary border-t-transparent rounded-full animate-spin" />
                <span>Đang liên kết...</span>
              </>
            ) : (
              <>
                <span className="material-symbols-outlined text-[16px]">link</span>
                <span>Xác nhận liên kết</span>
              </>
            )}
          </button>
        </div>
      </div>
    </div>
  );
}

export function PracticeSessionLinkDialog(
  props: PracticeSessionLinkDialogProps
) {
  if (!props.isOpen || !props.occurrence) return null;

  return (
    <PracticeSessionLinkDialogContent
      key={`${props.occurrence.plan_item_id}-${props.occurrence.local_date}`}
      onClose={props.onClose}
      occurrence={props.occurrence}
      onConfirmLink={props.onConfirmLink}
      isLinking={props.isLinking}
    />
  );
}
