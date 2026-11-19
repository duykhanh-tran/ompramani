'use client';

import React, { useState } from 'react';
import { createPrayerResponse } from '@/lib/api/prayer-responses';
import { MyPrayerResponse } from '@/types/prayer-response';

interface SubmitPrayerResponseFormProps {
  publicId: string;
  onResponseSubmitted?: (newResponse: MyPrayerResponse) => void;
}

export function SubmitPrayerResponseForm({
  publicId,
  onResponseSubmitted,
}: SubmitPrayerResponseFormProps) {
  const [body, setBody] = useState('');
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [errorMessage, setErrorMessage] = useState<string | null>(null);
  const [successMessage, setSuccessMessage] = useState<string | null>(null);

  const charCount = body.trim().length;
  const isValidLength = charCount >= 3 && charCount <= 500;

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();

    if (!isValidLength) {
      setErrorMessage('Nội dung lời nhắn cần từ 3 đến 500 ký tự.');
      return;
    }

    setIsSubmitting(true);
    setErrorMessage(null);
    setSuccessMessage(null);

    try {
      const res = await createPrayerResponse(publicId, {
        body: body.trim(),
      });

      setSuccessMessage(
        'Lời nhắn của bạn đã được gửi thành công và đang ở trạng thái chờ kiểm duyệt. Lời nhắn sẽ hiển thị công khai sau khi được duyệt.'
      );
      setBody('');
      if (onResponseSubmitted && res.data) {
        onResponseSubmitted(res.data);
      }
    } catch (err: unknown) {
      const msg =
        err instanceof Error
          ? err.message
          : 'Không thể gửi lời nhắn lúc này. Vui lòng thử lại sau.';
      setErrorMessage(msg);
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <form
      onSubmit={handleSubmit}
      className="rounded-3xl bg-surface-container-lowest border border-outline-variant/40 p-5 sm:p-6 shadow-xs space-y-4"
    >
      <div className="flex items-center gap-2">
        <span className="material-symbols-outlined text-primary text-[20px]">
          edit_note
        </span>
        <h4 className="font-headline-md text-base font-semibold text-on-surface">
          Gửi Lời Động Viên Thiện Lành
        </h4>
      </div>

      {successMessage && (
        <div className="p-3.5 rounded-2xl bg-secondary-container/60 border border-secondary/30 text-on-secondary-container font-body-sm text-xs flex items-start gap-2.5">
          <span className="material-symbols-outlined text-[18px] text-primary shrink-0 mt-0.5">
            check_circle
          </span>
          <span className="leading-relaxed">{successMessage}</span>
        </div>
      )}

      {errorMessage && (
        <div className="p-3 rounded-2xl bg-error-container/40 border border-error/20 text-error font-body-sm text-xs flex items-center gap-2">
          <span className="material-symbols-outlined text-[16px] shrink-0">
            error
          </span>
          <span>{errorMessage}</span>
        </div>
      )}

      <div>
        <label htmlFor="response-body" className="sr-only">
          Nội dung lời nhắn đồng hành
        </label>
        <textarea
          id="response-body"
          rows={3}
          maxLength={500}
          value={body}
          onChange={(e) => setBody(e.target.value)}
          placeholder="Gửi đôi dòng chia sẻ thiện lành, chúc bình an và động viên đến người gửi lời nguyện..."
          className="w-full px-4 py-3 rounded-2xl bg-surface-container-low border border-outline-variant/50 font-body-md text-sm text-on-surface placeholder:text-outline/60 focus:outline-hidden focus:border-primary focus:ring-1 focus:ring-primary transition-colors resize-none leading-relaxed"
        />
        <div className="mt-1 flex items-center justify-between font-body-sm text-[11px] text-outline px-1">
          <span>Tối thiểu 3 ký tự</span>
          <span className={charCount > 500 ? 'text-error font-semibold' : ''}>
            {charCount}/500
          </span>
        </div>
      </div>

      {/* Privacy Notice Callout */}
      <div className="p-3 rounded-2xl bg-surface-container/60 border border-outline-variant/30 text-outline font-body-sm text-xs flex items-start gap-2">
        <span className="material-symbols-outlined text-[16px] text-primary shrink-0 mt-0.5">
          privacy_tip
        </span>
        <span className="leading-relaxed text-[11px]">
          Lời nhắn sẽ được hiển thị công khai ẩn danh sau khi kiểm duyệt. Xin
          quý vị không đưa họ tên đầy đủ, số điện thoại, thông tin liên lạc cá
          nhân hoặc quảng cáo.
        </span>
      </div>

      <div className="flex items-center justify-end">
        <button
          type="submit"
          disabled={isSubmitting || !isValidLength}
          className="px-5 py-2.5 rounded-xl bg-primary text-on-primary font-label-md text-xs font-semibold hover:bg-primary-hover transition-colors shadow-xs flex items-center gap-2 disabled:opacity-40 disabled:cursor-not-allowed"
        >
          {isSubmitting ? (
            <>
              <span className="w-3.5 h-3.5 rounded-full border-2 border-on-primary border-t-transparent animate-spin" />
              <span>Đang gửi kiểm duyệt...</span>
            </>
          ) : (
            <>
              <span className="material-symbols-outlined text-[16px]">send</span>
              <span>Gửi lời nhắn để kiểm duyệt</span>
            </>
          )}
        </button>
      </div>
    </form>
  );
}
