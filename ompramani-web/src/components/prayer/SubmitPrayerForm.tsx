'use client';

import React, { useState } from 'react';
import Link from 'next/link';
import { useAuth } from '@/context/AuthContext';
import { createPrayerIntention } from '@/lib/api/prayers';
import { PrayerCategoryKey, PrayerVisibility } from '@/types/prayer';
import { ApiError } from '@/lib/api/client';

const CATEGORIES: { key: PrayerCategoryKey; label: string }[] = [
  { key: 'binh_an', label: 'Bình an & Cầu an' },
  { key: 'gia_dinh', label: 'Gia đình & Thân quyến' },
  { key: 'tri_an', label: 'Tri ân & Báo ân' },
  { key: 'hoi_huong', label: 'Hồi hướng công đức' },
  { key: 'thien_lanh', label: 'Lời nguyện thiện lành' },
  { key: 'khac', label: 'Khác' },
];

export function SubmitPrayerForm() {
  const { isAuthenticated, isLoading: isAuthLoading } = useAuth();

  const [title, setTitle] = useState('');
  const [category, setCategory] = useState<PrayerCategoryKey | ''>('binh_an');
  const [body, setBody] = useState('');
  // PRIVACY IS DEFAULT (Section 4): Private by default!
  const [visibility, setVisibility] = useState<PrayerVisibility>('private');
  // Consent is unchecked by default!
  const [consentGiven, setConsentGiven] = useState(false);

  const [isSubmitting, setIsSubmitting] = useState(false);
  const [errors, setErrors] = useState<Record<string, string[]>>({});
  const [generalError, setGeneralError] = useState<string | null>(null);
  const [successInfo, setSuccessInfo] = useState<{
    id: number;
    visibility: PrayerVisibility;
  } | null>(null);

  if (isAuthLoading) {
    return (
      <div className="max-w-2xl mx-auto rounded-3xl bg-surface-container-lowest p-8 border border-outline-variant/40 animate-pulse space-y-6">
        <div className="h-8 w-48 bg-surface-container-low rounded-xl" />
        <div className="h-40 bg-surface-container-low rounded-2xl" />
      </div>
    );
  }

  if (!isAuthenticated) {
    return (
      <div className="max-w-xl mx-auto rounded-3xl bg-surface-container-lowest border border-outline-variant/40 p-8 sm:p-10 text-center space-y-5 shadow-xs">
        <div className="w-16 h-16 rounded-full bg-primary-fixed/30 text-primary mx-auto flex items-center justify-center">
          <span className="material-symbols-outlined text-[32px]">
            lock
          </span>
        </div>
        <div className="space-y-2">
          <h2 className="font-headline-md text-2xl font-semibold text-primary">
            Đăng nhập để viết lời nguyện
          </h2>
          <p className="font-body-md text-sm sm:text-base text-on-surface-variant max-w-sm mx-auto">
            Quý Phật tử vui lòng đăng nhập tài khoản tu tập để lưu giữ lời nguyện riêng tư hoặc gửi chia sẻ cùng đại chúng.
          </p>
        </div>
        <div className="pt-2 flex items-center justify-center gap-3">
          <Link
            href="/dang-nhap?redirect=/cau-nguyen/gui-loi-nguyen"
            className="px-6 py-2.5 rounded-full bg-primary text-on-primary hover:bg-on-primary-container font-label-md text-sm font-semibold transition-colors"
          >
            Đăng nhập
          </Link>
          <Link
            href="/dang-ky"
            className="px-6 py-2.5 rounded-full border border-outline-variant/60 bg-surface text-on-surface hover:bg-surface-container-low font-label-md text-sm font-semibold transition-colors"
          >
            Tạo tài khoản mới
          </Link>
        </div>
      </div>
    );
  }

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setErrors({});
    setGeneralError(null);

    // Frontend validations
    const trimmedBody = body.trim();
    if (trimmedBody.length < 20) {
      setErrors({
        body: ['Nội dung lời nguyện cần tối thiểu 20 ký tự để bày tỏ tâm nguyện trọn vẹn.'],
      });
      return;
    }
    if (trimmedBody.length > 2000) {
      setErrors({
        body: ['Nội dung lời nguyện tối đa 2000 ký tự.'],
      });
      return;
    }

    if (visibility === 'public' && !consentGiven) {
      setErrors({
        consent_public_sharing: [
          'Bạn cần xác nhận đồng ý với lưu ý quyền riêng tư trước khi gửi chia sẻ cộng đồng.',
        ],
      });
      return;
    }

    setIsSubmitting(true);
    try {
      const res = await createPrayerIntention({
        title: title.trim() || undefined,
        category: category || undefined,
        body: trimmedBody,
        visibility,
        consent_public_sharing: visibility === 'public' ? consentGiven : undefined,
      });

      setSuccessInfo({
        id: res.data.id,
        visibility: res.data.visibility,
      });
    } catch (err) {
      if (err instanceof ApiError && err.errors) {
        setErrors(err.errors);
      } else {
        setGeneralError(
          err instanceof Error
            ? err.message
            : 'Đã xảy ra lỗi kết nối. Vui lòng thử lại sau.'
        );
      }
    } finally {
      setIsSubmitting(false);
    }
  };

  // Success view
  if (successInfo) {
    return (
      <div className="max-w-xl mx-auto rounded-3xl bg-surface-container-lowest border border-outline-variant/40 p-8 sm:p-10 text-center space-y-6 shadow-xs animate-in fade-in duration-200">
        <div className="w-16 h-16 rounded-full bg-secondary-container text-on-secondary-container mx-auto flex items-center justify-center">
          <span className="material-symbols-outlined text-[32px]">
            check_circle
          </span>
        </div>

        <div className="space-y-2">
          <h2 className="font-headline-md text-2xl font-semibold text-primary">
            {successInfo.visibility === 'public'
              ? 'Đã gửi lời nguyện để kiểm duyệt'
              : 'Đã lưu lời nguyện riêng tư'}
          </h2>
          <p className="font-body-md text-sm sm:text-base text-on-surface-variant max-w-md mx-auto leading-relaxed">
            {successInfo.visibility === 'public'
              ? 'Tâm nguyện của bạn đã được tiếp nhận. Ban Quản Trị sẽ thẩm định và xuất bản lên không gian Cầu Nguyện Cộng Đồng sớm nhất.'
              : 'Tâm nguyện của bạn đã được bảo lưu an toàn trong sổ tay tu học cá nhân. Bạn có thể mở lại xem bất cứ lúc nào.'}
          </p>
        </div>

        <div className="pt-2 flex items-center justify-center gap-3 flex-wrap">
          <Link
            href="/tai-khoan/loi-nguyen"
            className="px-6 py-2.5 rounded-full bg-primary text-on-primary hover:bg-on-primary-container font-label-md text-sm font-semibold transition-colors"
          >
            Quản lý lời nguyện của tôi
          </Link>
          <button
            type="button"
            onClick={() => {
              setTitle('');
              setBody('');
              setVisibility('private');
              setConsentGiven(false);
              setSuccessInfo(null);
            }}
            className="px-6 py-2.5 rounded-full border border-outline-variant/60 bg-surface text-on-surface hover:bg-surface-container-low font-label-md text-sm font-semibold transition-colors"
          >
            Viết lời nguyện khác
          </button>
        </div>
      </div>
    );
  }

  return (
    <form
      onSubmit={handleSubmit}
      className="max-w-2xl mx-auto rounded-3xl bg-surface-container-lowest border border-outline-variant/40 p-6 sm:p-10 shadow-xs space-y-6"
    >
      <div className="space-y-1.5 pb-4 border-b border-outline-variant/30">
        <h2 className="font-headline-md text-2xl font-semibold text-primary">
          Bày Tỏ Tâm Nguyện
        </h2>
        <p className="font-body-md text-sm text-on-surface-variant">
          Ghi lại tâm nguyện thiện lành trong không gian an tịnh và tôn trọng.
        </p>
      </div>

      {generalError && (
        <div className="p-4 rounded-2xl bg-error/10 border border-error/30 text-error text-sm flex items-start gap-2.5">
          <span className="material-symbols-outlined text-[20px] shrink-0">
            error
          </span>
          <span>{generalError}</span>
        </div>
      )}

      {/* Title (Optional) */}
      <div className="space-y-1.5">
        <label
          htmlFor="prayer-title"
          className="font-label-md text-sm font-semibold text-on-surface flex items-center justify-between"
        >
          <span>Tiêu đề tâm nguyện (tùy chọn)</span>
          <span className="text-xs text-outline font-normal">Tối đa 255 ký tự</span>
        </label>
        <input
          id="prayer-title"
          type="text"
          value={title}
          onChange={(e) => setTitle(e.target.value)}
          maxLength={255}
          placeholder="Ví dụ: Cầu bình an cho phụ mẫu, Hồi hướng công đức..."
          className="w-full px-4 py-3 rounded-2xl bg-surface-container-low/60 border border-outline-variant/60 text-on-surface placeholder:text-outline focus:outline-none focus:ring-2 focus:ring-primary/40 focus:bg-surface-container-lowest text-sm transition-all"
        />
        {errors.title && (
          <p className="text-xs text-error mt-1">{errors.title[0]}</p>
        )}
      </div>

      {/* Category (Optional) */}
      <div className="space-y-1.5">
        <label
          htmlFor="prayer-category"
          className="font-label-md text-sm font-semibold text-on-surface block"
        >
          Chủ đề lời nguyện
        </label>
        <select
          id="prayer-category"
          value={category}
          onChange={(e) => setCategory(e.target.value as PrayerCategoryKey)}
          className="w-full px-4 py-3 rounded-2xl bg-surface-container-low/60 border border-outline-variant/60 text-on-surface focus:outline-none focus:ring-2 focus:ring-primary/40 focus:bg-surface-container-lowest text-sm transition-all"
        >
          {CATEGORIES.map((cat) => (
            <option key={cat.key} value={cat.key}>
              {cat.label}
            </option>
          ))}
        </select>
        {errors.category && (
          <p className="text-xs text-error mt-1">{errors.category[0]}</p>
        )}
      </div>

      {/* Body (Required) */}
      <div className="space-y-1.5">
        <label
          htmlFor="prayer-body"
          className="font-label-md text-sm font-semibold text-on-surface flex items-center justify-between"
        >
          <span>Nội dung lời nguyện <span className="text-error">*</span></span>
          <span className="text-xs text-outline font-normal">
            {body.length} / 2000 ký tự (tối thiểu 20)
          </span>
        </label>
        <textarea
          id="prayer-body"
          rows={6}
          value={body}
          onChange={(e) => setBody(e.target.value)}
          maxLength={2000}
          required
          placeholder="Viết lời nguyện chân thành của bạn tại đây... (tối thiểu 20 ký tự)"
          className="w-full px-4 py-3 rounded-2xl bg-surface-container-low/60 border border-outline-variant/60 text-on-surface placeholder:text-outline focus:outline-none focus:ring-2 focus:ring-primary/40 focus:bg-surface-container-lowest text-sm leading-relaxed transition-all resize-y"
        />
        {errors.body && (
          <p className="text-xs text-error mt-1">{errors.body[0]}</p>
        )}
      </div>

      {/* Visibility Options: Privacy by default! */}
      <div className="space-y-3 pt-2">
        <label className="font-label-md text-sm font-semibold text-on-surface block">
          Chế độ lưu giữ <span className="text-xs font-normal text-outline">(Mặc định là riêng tư)</span>
        </label>

        <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
          {/* Option A: Private (Default) */}
          <div
            onClick={() => setVisibility('private')}
            className={`p-4 rounded-2xl border cursor-pointer transition-all flex items-start gap-3 ${
              visibility === 'private'
                ? 'bg-primary-fixed/20 border-primary shadow-2xs'
                : 'bg-surface-container-low/50 border-outline-variant/40 hover:bg-surface-container-low'
            }`}
          >
            <input
              type="radio"
              name="visibility"
              id="visibility-private"
              checked={visibility === 'private'}
              onChange={() => setVisibility('private')}
              className="mt-0.5 text-primary focus:ring-primary"
            />
            <label htmlFor="visibility-private" className="cursor-pointer space-y-0.5">
              <span className="font-label-md text-sm font-semibold text-on-surface block">
                Lưu riêng tư
              </span>
              <span className="text-xs text-on-surface-variant block leading-relaxed">
                Chỉ một mình bạn có thể đọc và quản lý trong sổ tay tu học.
              </span>
            </label>
          </div>

          {/* Option B: Public */}
          <div
            onClick={() => setVisibility('public')}
            className={`p-4 rounded-2xl border cursor-pointer transition-all flex items-start gap-3 ${
              visibility === 'public'
                ? 'bg-primary-fixed/20 border-primary shadow-2xs'
                : 'bg-surface-container-low/50 border-outline-variant/40 hover:bg-surface-container-low'
            }`}
          >
            <input
              type="radio"
              name="visibility"
              id="visibility-public"
              checked={visibility === 'public'}
              onChange={() => setVisibility('public')}
              className="mt-0.5 text-primary focus:ring-primary"
            />
            <label htmlFor="visibility-public" className="cursor-pointer space-y-0.5">
              <span className="font-label-md text-sm font-semibold text-on-surface block">
                Chia sẻ với cộng đồng
              </span>
              <span className="text-xs text-on-surface-variant block leading-relaxed">
                Gửi để Ban Kiểm Duyệt thẩm định trước khi hiển thị công khai.
              </span>
            </label>
          </div>
        </div>
      </div>

      {/* Sensitive Information Warning & Consent Checkbox (Only if public selected) */}
      {visibility === 'public' && (
        <div className="p-4 sm:p-5 rounded-2xl bg-amber-500/10 border border-amber-500/30 space-y-3 animate-in fade-in duration-150">
          <div className="flex items-start gap-2.5 text-amber-900 dark:text-amber-200">
            <span className="material-symbols-outlined text-[20px] shrink-0 text-amber-600 dark:text-amber-400">
              privacy_tip
            </span>
            <div className="text-xs sm:text-sm leading-relaxed">
              <strong className="font-semibold block mb-0.5">Lưu ý bảo vệ quyền riêng tư:</strong>
              Nội dung được duyệt có thể được mọi người trên Internet nhìn thấy. Xin tránh đưa thông tin cá nhân (họ tên đầy đủ, số điện thoại, địa chỉ, số tài khoản) hoặc thông tin riêng tư của người khác.
            </div>
          </div>

          <label className="flex items-start gap-3 cursor-pointer pt-2 border-t border-amber-500/20">
            <input
              type="checkbox"
              checked={consentGiven}
              onChange={(e) => setConsentGiven(e.target.checked)}
              className="mt-1 rounded text-primary focus:ring-primary"
            />
            <span className="text-xs sm:text-sm text-on-surface leading-snug">
              Tôi xác nhận nội dung không chứa thông tin định danh cá nhân và đồng ý gửi Ban Kiểm Duyệt để chia sẻ công khai.
            </span>
          </label>
          {errors.consent_public_sharing && (
            <p className="text-xs text-error">{errors.consent_public_sharing[0]}</p>
          )}
        </div>
      )}

      {/* Action Buttons */}
      <div className="pt-4 border-t border-outline-variant/30 flex items-center justify-between gap-4">
        <Link
          href="/cau-nguyen"
          className="text-outline hover:text-on-surface font-label-md text-sm transition-colors"
        >
          Hủy bỏ
        </Link>

        <button
          type="submit"
          disabled={isSubmitting}
          className="px-7 py-3 rounded-full bg-primary text-on-primary hover:bg-on-primary-container disabled:opacity-50 font-label-md text-sm font-semibold shadow-xs hover:shadow-sm transition-all flex items-center gap-2"
        >
          {isSubmitting && (
            <span className="w-4 h-4 border-2 border-on-primary border-t-transparent rounded-full animate-spin" />
          )}
          <span>
            {visibility === 'public' ? 'Gửi để kiểm duyệt' : 'Lưu riêng tư'}
          </span>
        </button>
      </div>
    </form>
  );
}
