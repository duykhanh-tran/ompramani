/* eslint-disable @next/next/no-img-element */
"use client";

import React, { useState, useEffect, useRef } from "react";
import Link from "next/link";
import { useRouter } from "next/navigation";
import { useAuth } from "@/context/AuthContext";
import { resendVerificationEmail } from "@/lib/api/auth";
import {
  updateProfile,
  uploadAvatar,
  updateEmail,
  changePassword,
} from "@/lib/api/profile";
import { getTraditions } from "@/lib/api/sutras";
import { Tradition } from "@/types/api";
import { ApiError } from "@/lib/api/client";

type ActiveTab = "profile" | "security";

export default function AccountOverview() {
  const router = useRouter();
  const { user, isAuthenticated, isLoading, logout, setUser, refreshUser } =
    useAuth();

  const [activeTab, setActiveTab] = useState<ActiveTab>("profile");
  const [traditions, setTraditions] = useState<Tradition[]>([]);

  // Avatar states
  const fileInputRef = useRef<HTMLInputElement>(null);
  const [isUploadingAvatar, setIsUploadingAvatar] = useState(false);
  const [avatarMessage, setAvatarMessage] = useState<string | null>(null);
  const [avatarError, setAvatarError] = useState<string | null>(null);

  // Profile form state
  const [name, setName] = useState("");
  const [dharmaName, setDharmaName] = useState("");
  const [dateOfBirth, setDateOfBirth] = useState("");
  const [gender, setGender] = useState("");
  const [preferredTraditionId, setPreferredTraditionId] = useState("");
  const [location, setLocation] = useState("");
  const [dharmaJourneyStartedAt, setDharmaJourneyStartedAt] = useState("");
  const [bio, setBio] = useState("");

  const [isSavingProfile, setIsSavingProfile] = useState(false);
  const [profileSuccess, setProfileSuccess] = useState<string | null>(null);
  const [profileErrors, setProfileErrors] = useState<Record<string, string[]>>({});

  // Security - Email change state
  const [newEmail, setNewEmail] = useState("");
  const [currentPasswordForEmail, setCurrentPasswordForEmail] = useState("");
  const [isUpdatingEmail, setIsUpdatingEmail] = useState(false);
  const [emailSuccess, setEmailSuccess] = useState<string | null>(null);
  const [emailErrors, setEmailErrors] = useState<Record<string, string[]>>({});

  // Security - Password change state
  const [currentPassword, setCurrentPassword] = useState("");
  const [password, setPassword] = useState("");
  const [passwordConfirmation, setPasswordConfirmation] = useState("");
  const [isChangingPassword, setIsChangingPassword] = useState(false);
  const [passwordSuccess, setPasswordSuccess] = useState<string | null>(null);
  const [passwordErrors, setPasswordErrors] = useState<Record<string, string[]>>({});

  // Security - Verification resend state
  const [isResending, setIsResending] = useState(false);

  // Redirect if not authenticated
  useEffect(() => {
    if (!isLoading && !isAuthenticated) {
      router.replace("/dang-nhap?next=/tai-khoan");
    }
  }, [isLoading, isAuthenticated, router]);

  // Load traditions
  useEffect(() => {
    let isMounted = true;
    getTraditions()
      .then((items) => {
        if (isMounted) setTraditions(items);
      })
      .catch(() => {
        // silently fallback to empty
      });
    return () => {
      isMounted = false;
    };
  }, []);

  // Sync profile data from user state when user ID changes
  const [syncedUserId, setSyncedUserId] = useState<number | null>(null);
  if (user && user.id !== syncedUserId) {
    setSyncedUserId(user.id);
    setName(user.name || "");
    setDharmaName(user.profile?.dharma_name || "");
    setDateOfBirth(user.profile?.date_of_birth || "");
    setGender(user.profile?.gender || "");
    setPreferredTraditionId(
      user.profile?.preferred_tradition?.id
        ? String(user.profile.preferred_tradition.id)
        : user.profile?.preferred_tradition_id
        ? String(user.profile.preferred_tradition_id)
        : ""
    );
    setLocation(user.profile?.location || "");
    setDharmaJourneyStartedAt(
      user.profile?.dharma_journey_started_at || ""
    );
    setBio(user.profile?.bio || "");
  }

  const handleResetProfile = () => {
    if (!user) return;
    setName(user.name || "");
    setDharmaName(user.profile?.dharma_name || "");
    setDateOfBirth(user.profile?.date_of_birth || "");
    setGender(user.profile?.gender || "");
    setPreferredTraditionId(
      user.profile?.preferred_tradition?.id
        ? String(user.profile.preferred_tradition.id)
        : user.profile?.preferred_tradition_id
        ? String(user.profile.preferred_tradition_id)
        : ""
    );
    setLocation(user.profile?.location || "");
    setDharmaJourneyStartedAt(user.profile?.dharma_journey_started_at || "");
    setBio(user.profile?.bio || "");
    setProfileSuccess(null);
    setProfileErrors({});
  };

  if (isLoading || !user) {
    return (
      <div className="w-full max-w-6xl mx-auto p-6 space-y-6 animate-pulse">
        <div className="h-10 rounded-2xl bg-surface-container-low w-1/3" />
        <div className="grid grid-cols-1 lg:grid-cols-12 gap-6">
          <div className="lg:col-span-4 h-96 rounded-3xl bg-surface-container-low" />
          <div className="lg:col-span-8 h-96 rounded-3xl bg-surface-container-low" />
        </div>
      </div>
    );
  }

  // --- Avatar Handlers ---
  const handleAvatarSelect = async (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    if (!file) return;

    if (file.size > 5 * 1024 * 1024) {
      setAvatarError("Dung lượng ảnh không được vượt quá 5MB.");
      return;
    }

    const validTypes = ["image/jpeg", "image/png", "image/webp", "image/jpg"];
    if (!validTypes.includes(file.type)) {
      setAvatarError("Chỉ chấp nhận định dạng ảnh JPG, PNG hoặc WebP.");
      return;
    }

    setIsUploadingAvatar(true);
    setAvatarMessage(null);
    setAvatarError(null);

    try {
      const res = await uploadAvatar(file);
      setUser(res.user);
      setAvatarMessage("Cập nhật ảnh đại diện thành công.");
    } catch (err) {
      if (err instanceof ApiError) {
        setAvatarError(err.message);
      } else {
        setAvatarError("Không thể tải ảnh lên. Vui lòng thử lại.");
      }
    } finally {
      setIsUploadingAvatar(false);
      if (fileInputRef.current) {
        fileInputRef.current.value = "";
      }
    }
  };

  // --- Profile Save Handler ---
  const handleSaveProfile = async (e: React.FormEvent) => {
    e.preventDefault();
    setIsSavingProfile(true);
    setProfileSuccess(null);
    setProfileErrors({});

    try {
      const payload = {
        name: name.trim(),
        dharma_name: dharmaName.trim() || null,
        date_of_birth: dateOfBirth || null,
        gender: gender || null,
        preferred_tradition_id: preferredTraditionId
          ? Number(preferredTraditionId)
          : null,
        location: location.trim() || null,
        bio: bio.trim() || null,
        dharma_journey_started_at: dharmaJourneyStartedAt || null,
      };

      const res = await updateProfile(payload);
      setUser(res.user);
      setProfileSuccess(res.message || "Cập nhật hồ sơ Phật tử thành công.");
      await refreshUser();
    } catch (err) {
      if (err instanceof ApiError) {
        if (err.errors) {
          setProfileErrors(err.errors as Record<string, string[]>);
        } else {
          setProfileErrors({
            general: [err.message || "Không thể cập nhật hồ sơ."],
          });
        }
      } else {
        setProfileErrors({
          general: ["Đã có lỗi xảy ra. Vui lòng kiểm tra lại kết nối."],
        });
      }
    } finally {
      setIsSavingProfile(false);
    }
  };

  // --- Resend Verification Email ---
  const handleResendVerification = async () => {
    setIsResending(true);
    try {
      const res = await resendVerificationEmail();
      setAvatarMessage(
        res.message || "Đã gửi liên kết xác thực đến hòm thư của bạn."
      );
    } catch (err) {
      if (err instanceof ApiError) {
        setAvatarError(err.message);
      } else {
        setAvatarError("Không thể gửi email xác thực lúc này.");
      }
    } finally {
      setIsResending(false);
    }
  };

  // --- Email Change Handler ---
  const handleUpdateEmail = async (e: React.FormEvent) => {
    e.preventDefault();
    setIsUpdatingEmail(true);
    setEmailSuccess(null);
    setEmailErrors({});

    try {
      const res = await updateEmail({
        email: newEmail.trim(),
        current_password: currentPasswordForEmail,
      });
      setUser(res.user);
      setEmailSuccess(res.message);
      setNewEmail("");
      setCurrentPasswordForEmail("");
    } catch (err) {
      if (err instanceof ApiError) {
        if (err.errors) {
          setEmailErrors(err.errors as Record<string, string[]>);
        } else {
          setEmailErrors({
            general: [err.message || "Không thể cập nhật địa chỉ email."],
          });
        }
      } else {
        setEmailErrors({
          general: ["Đã có lỗi xảy ra. Vui lòng thử lại sau."],
        });
      }
    } finally {
      setIsUpdatingEmail(false);
    }
  };

  // --- Password Change Handler ---
  const handleChangePassword = async (e: React.FormEvent) => {
    e.preventDefault();
    setIsChangingPassword(true);
    setPasswordSuccess(null);
    setPasswordErrors({});

    try {
      const res = await changePassword({
        current_password: currentPassword,
        password: password,
        password_confirmation: passwordConfirmation,
      });
      setPasswordSuccess(res.message || "Đã đổi mật khẩu thành công.");
      setCurrentPassword("");
      setPassword("");
      setPasswordConfirmation("");
    } catch (err) {
      if (err instanceof ApiError) {
        if (err.errors) {
          setPasswordErrors(err.errors as Record<string, string[]>);
        } else {
          setPasswordErrors({
            general: [err.message || "Không thể thay đổi mật khẩu."],
          });
        }
      } else {
        setPasswordErrors({
          general: ["Đã có lỗi xảy ra. Vui lòng thử lại sau."],
        });
      }
    } finally {
      setIsChangingPassword(false);
    }
  };

  // Determine current tradition label
  const currentTraditionLabel =
    user.profile?.preferred_tradition?.name ||
    traditions.find((t) => String(t.id) === preferredTraditionId)?.name ||
    "Bắc Truyền (Đại Thừa)";

  return (
    <div className="w-full max-w-6xl mx-auto space-y-6">
      {/* Hidden File Input for Avatar Upload */}
      <input
        ref={fileInputRef}
        type="file"
        accept="image/png,image/jpeg,image/jpg,image/webp"
        onChange={handleAvatarSelect}
        className="hidden"
        aria-label="Tải lên ảnh đại diện"
      />

      {/* TOP HEADER BAR */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pb-1">
        <div className="flex flex-wrap items-center gap-3">
          <h1 className="font-serif text-2xl sm:text-3xl font-bold text-[#2d1b0c] tracking-tight">
            Hồ Sơ Cá Nhân & Tu Học
          </h1>
          <span className="inline-flex items-center px-3 py-1 rounded-full bg-[#f6ede2] text-[#8a572a] text-xs font-semibold border border-[#ebdccb]">
            {user.profile?.preferred_tradition?.name || "Niệm Phật - Tịnh Độ"}
          </span>
        </div>

        <div className="flex items-center gap-1.5 text-xs font-medium text-stone-500">
          <span className="material-symbols-outlined text-[18px] text-teal-600">
            verified_user
          </span>
          <span>Tài khoản chính chủ đã định danh tu học</span>
        </div>
      </div>

      {/* 2-COLUMN MAIN LAYOUT */}
      <div className="grid grid-cols-1 lg:grid-cols-12 gap-6 lg:gap-7 items-start">
        {/* ========================================================
            LEFT COLUMN: PROFILE SUMMARY & NAVIGATION MENU
        ======================================================== */}
        <div className="lg:col-span-4 space-y-5">
          {/* CARD 1: PROFILE SUMMARY */}
          <div className="rounded-3xl bg-white border border-[#ebdccb]/80 shadow-[0_2px_16px_rgba(140,109,45,0.06)] p-6 text-center flex flex-col items-center">
            {/* Centered Avatar with Golden Border Ring and Camera Button */}
            <div className="relative group shrink-0">
              <div className="w-24 h-24 rounded-full bg-gradient-to-br from-[#d4a359]/20 via-[#fcfaf7] to-[#ebdccb] ring-4 ring-[#eadccb] overflow-hidden flex items-center justify-center shadow-inner">
                {user.profile?.avatar_url ? (
                  <img
                    src={user.profile.avatar_url}
                    alt={user.name}
                    className="w-full h-full object-cover"
                  />
                ) : (
                  <span className="font-serif text-3xl font-bold text-[#785915]">
                    {user.name.charAt(0).toUpperCase()}
                  </span>
                )}
              </div>

              {/* Floating Camera Button */}
              <button
                type="button"
                onClick={() => fileInputRef.current?.click()}
                disabled={isUploadingAvatar}
                className="absolute bottom-0 right-0 w-8 h-8 rounded-full bg-[#3d2613] text-white flex items-center justify-center shadow-md hover:bg-[#27170a] transition-all cursor-pointer ring-2 ring-white disabled:opacity-50"
                title="Thay đổi ảnh đại diện"
                aria-label="Thay đổi ảnh đại diện"
              >
                {isUploadingAvatar ? (
                  <span className="w-4 h-4 border-2 border-white border-t-transparent rounded-full animate-spin" />
                ) : (
                  <span className="material-symbols-outlined text-[16px]">
                    photo_camera
                  </span>
                )}
              </button>
            </div>

            {/* Name */}
            <h2 className="font-serif text-xl font-bold text-[#2d1b0c] mt-3">
              {user.name}
            </h2>

            {/* Dharma Name with Lotus Icon */}
            <div className="flex items-center gap-1.5 text-xs font-medium text-[#99652c] mt-1">
              <span className="material-symbols-outlined text-[15px] text-[#b8782a]">
                spa
              </span>
              <span>
                Pháp danh: {user.profile?.dharma_name || dharmaName || "Diệu Âm Trí Duy"}
              </span>
            </div>

            {/* Role & Tradition Pills */}
            <div className="flex flex-wrap items-center justify-center gap-2 mt-3">
              <span className="px-3 py-1 rounded-full bg-[#fbf6f0] text-[#5c4a3a] text-[11px] font-semibold border border-[#ecd9c3] uppercase tracking-wider">
                {user.role === "admin" ? "Quản Trị Viên" : "Phật Tử / Bạn Đọc"}
              </span>
              <span className="inline-flex items-center gap-1 px-3 py-1 rounded-full bg-[#fbf6f0] text-[#5c4a3a] text-[11px] font-semibold border border-[#ecd9c3]">
                <span className="material-symbols-outlined text-[13px] text-[#99652c]">
                  account_balance
                </span>
                <span>{currentTraditionLabel.split(" ")[0] || "Bắc Truyền"}</span>
              </span>
            </div>

            {/* Email with Verified Badge */}
            <div className="w-full mt-4 pt-4 border-t border-[#f2e6d8] flex items-center justify-between text-xs px-1">
              <div className="flex items-center gap-1.5 text-stone-600 min-w-0">
                <span className="material-symbols-outlined text-[16px] text-stone-400 shrink-0">
                  mail
                </span>
                <span className="truncate">{user.email}</span>
              </div>

              {user.email_verified ? (
                <span className="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-emerald-50 text-emerald-700 text-[11px] font-medium border border-emerald-200 shrink-0">
                  <span className="material-symbols-outlined text-[13px]">check</span>
                  <span>Đã xác thực</span>
                </span>
              ) : (
                <button
                  type="button"
                  onClick={handleResendVerification}
                  disabled={isResending}
                  className="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-amber-50 text-amber-700 text-[11px] font-medium border border-amber-200 hover:bg-amber-100 shrink-0 cursor-pointer"
                >
                  <span>{isResending ? "Đang gửi..." : "Gửi lại"}</span>
                </button>
              )}
            </div>

            {/* Format Hint */}
            <p className="text-[11px] text-stone-400 mt-2 text-center">
              Hỗ trợ định dạng: JPG, PNG, WebP (Tối đa 5MB)
            </p>

            {avatarMessage && (
              <p className="text-xs text-emerald-700 bg-emerald-50 p-2 rounded-xl mt-2 w-full animate-in fade-in">
                {avatarMessage}
              </p>
            )}
            {avatarError && (
              <p className="text-xs text-red-600 bg-red-50 p-2 rounded-xl mt-2 w-full animate-in fade-in">
                {avatarError}
              </p>
            )}

            {/* Logout Button */}
            <button
              type="button"
              onClick={async () => {
                await logout();
                router.replace("/dang-nhap");
              }}
              className="w-full mt-4 py-2.5 px-4 rounded-xl border border-red-200 bg-red-50/50 hover:bg-red-100/70 text-red-600 text-xs font-semibold flex items-center justify-center gap-2 transition-colors cursor-pointer"
            >
              <span className="material-symbols-outlined text-[16px]">logout</span>
              <span>Đăng xuất khỏi hệ thống</span>
            </button>
          </div>

          {/* CARD 2: VERTICAL NAVIGATION MENU */}
          <div className="rounded-3xl bg-white border border-[#ebdccb]/80 shadow-[0_2px_16px_rgba(140,109,45,0.06)] p-3 space-y-1">
            {/* 1. Hồ Sơ Phật Tử */}
            <button
              type="button"
              onClick={() => setActiveTab("profile")}
              className={`w-full flex items-center justify-between px-3.5 py-3 rounded-2xl text-xs font-semibold transition-all cursor-pointer ${
                activeTab === "profile"
                  ? "bg-[#452b14] text-white shadow-sm"
                  : "text-stone-700 hover:bg-[#faf5ef] hover:text-[#452b14]"
              }`}
            >
              <div className="flex items-center gap-3">
                <span
                  className={`material-symbols-outlined text-[18px] ${
                    activeTab === "profile" ? "text-[#d4a359]" : "text-stone-400"
                  }`}
                >
                  assignment_ind
                </span>
                <span>Hồ Sơ Phật Tử</span>
              </div>
              <span
                className={`material-symbols-outlined text-[16px] ${
                  activeTab === "profile" ? "text-[#d4a359]" : "text-stone-400"
                }`}
              >
                chevron_right
              </span>
            </button>

            {/* 2. Bảo Mật Tài Khoản */}
            <button
              type="button"
              onClick={() => setActiveTab("security")}
              className={`w-full flex items-center justify-between px-3.5 py-3 rounded-2xl text-xs font-semibold transition-all cursor-pointer ${
                activeTab === "security"
                  ? "bg-[#452b14] text-white shadow-sm"
                  : "text-stone-700 hover:bg-[#faf5ef] hover:text-[#452b14]"
              }`}
            >
              <div className="flex items-center gap-3">
                <span
                  className={`material-symbols-outlined text-[18px] ${
                    activeTab === "security" ? "text-[#d4a359]" : "text-stone-400"
                  }`}
                >
                  shield
                </span>
                <span>Bảo Mật Tài Khoản</span>
              </div>
              <span
                className={`material-symbols-outlined text-[16px] ${
                  activeTab === "security" ? "text-[#d4a359]" : "text-stone-400"
                }`}
              >
                chevron_right
              </span>
            </button>

            {/* 3. Hành Trình Tu Học */}
            <Link
              href="/tai-khoan/hanh-trinh"
              className="w-full flex items-center justify-between px-3.5 py-3 rounded-2xl text-xs font-semibold text-stone-700 hover:bg-[#faf5ef] hover:text-[#452b14] transition-all"
            >
              <div className="flex items-center gap-3">
                <span className="material-symbols-outlined text-[18px] text-stone-400">
                  self_improvement
                </span>
                <span>Hành Trình Tu Học</span>
              </div>
              <span className="material-symbols-outlined text-[16px] text-stone-400">
                chevron_right
              </span>
            </Link>

            {/* 4. Đơn Hàng Pháp Khí */}
            <Link
              href="/tai-khoan/don-hang"
              className="w-full flex items-center justify-between px-3.5 py-3 rounded-2xl text-xs font-semibold text-stone-700 hover:bg-[#faf5ef] hover:text-[#452b14] transition-all"
            >
              <div className="flex items-center gap-3">
                <span className="material-symbols-outlined text-[18px] text-stone-400">
                  shopping_bag
                </span>
                <span>Đơn Hàng Pháp Khí</span>
              </div>
              <span className="material-symbols-outlined text-[16px] text-stone-400">
                chevron_right
              </span>
            </Link>

            {/* 5. Yêu Thích */}
            <Link
              href="/tai-khoan/yeu-thich"
              className="w-full flex items-center justify-between px-3.5 py-3 rounded-2xl text-xs font-semibold text-stone-700 hover:bg-[#faf5ef] hover:text-[#452b14] transition-all"
            >
              <div className="flex items-center gap-3">
                <span className="material-symbols-outlined text-[18px] text-stone-400">
                  favorite
                </span>
                <span>Yêu Thích</span>
              </div>
              <span className="material-symbols-outlined text-[16px] text-stone-400">
                chevron_right
              </span>
            </Link>

            {/* 6. Tủ Kinh Pháp */}
            <Link
              href="/tai-khoan/thu-vien-kinh"
              className="w-full flex items-center justify-between px-3.5 py-3 rounded-2xl text-xs font-semibold text-stone-700 hover:bg-[#faf5ef] hover:text-[#452b14] transition-all"
            >
              <div className="flex items-center gap-3">
                <span className="material-symbols-outlined text-[18px] text-stone-400">
                  auto_stories
                </span>
                <span>Tủ Kinh Pháp</span>
              </div>
              <span className="material-symbols-outlined text-[16px] text-stone-400">
                chevron_right
              </span>
            </Link>

            {/* 7. Dashboard Hành Trình */}
            <Link
              href="/tai-khoan/hanh-trinh"
              className="w-full flex items-center justify-between px-3.5 py-3 rounded-2xl text-xs font-semibold text-stone-700 hover:bg-[#faf5ef] hover:text-[#452b14] transition-all"
            >
              <div className="flex items-center gap-3">
                <span className="material-symbols-outlined text-[18px] text-stone-400">
                  query_stats
                </span>
                <span>Dashboard Hành Trình</span>
              </div>
              <span className="material-symbols-outlined text-[16px] text-stone-400">
                chevron_right
              </span>
            </Link>

            {/* 8. Kế Hoạch Tu Tập */}
            <Link
              href="/tai-khoan/ke-hoach-tu-tap"
              className="w-full flex items-center justify-between px-3.5 py-3 rounded-2xl text-xs font-semibold text-stone-700 hover:bg-[#faf5ef] hover:text-[#452b14] transition-all"
            >
              <div className="flex items-center gap-3">
                <span className="material-symbols-outlined text-[18px] text-stone-400">
                  calendar_month
                </span>
                <span>Kế Hoạch Tu Tập</span>
              </div>
              <span className="material-symbols-outlined text-[16px] text-stone-400">
                chevron_right
              </span>
            </Link>

            {/* 9. Nhắc Nhở Tu Tập */}
            <Link
              href="/tai-khoan/nhac-nho-tu-tap"
              className="w-full flex items-center justify-between px-3.5 py-3 rounded-2xl text-xs font-semibold text-stone-700 hover:bg-[#faf5ef] hover:text-[#452b14] transition-all"
            >
              <div className="flex items-center gap-3">
                <span className="material-symbols-outlined text-[18px] text-stone-400">
                  alarm
                </span>
                <span>Nhắc Nhở Tu Tập</span>
              </div>
              <span className="material-symbols-outlined text-[16px] text-stone-400">
                chevron_right
              </span>
            </Link>

            {/* 10. Thông Báo */}
            <Link
              href="/tai-khoan/thong-bao"
              className="w-full flex items-center justify-between px-3.5 py-3 rounded-2xl text-xs font-semibold text-stone-700 hover:bg-[#faf5ef] hover:text-[#452b14] transition-all"
            >
              <div className="flex items-center gap-3">
                <span className="material-symbols-outlined text-[18px] text-stone-400">
                  notifications
                </span>
                <span>Thông Báo</span>
              </div>
              <span className="w-5 h-5 rounded-full bg-[#d4a359] text-white text-[10px] font-bold flex items-center justify-center">
                3
              </span>
            </Link>
          </div>
        </div>

        {/* ========================================================
            RIGHT COLUMN: MAIN CONTENT (PROFILE / SECURITY)
        ======================================================== */}
        <div className="lg:col-span-8">
          {/* TAB 1: THÔNG TIN CÁ NHÂN & TU HỌC */}
          {activeTab === "profile" && (
            <div className="rounded-3xl bg-white border border-[#ebdccb]/80 shadow-[0_2px_16px_rgba(140,109,45,0.06)] p-6 sm:p-8 space-y-6">
              {/* Card Header */}
              <div>
                <div className="flex items-center gap-2.5">
                  <span className="material-symbols-outlined text-[24px] text-[#452b14]">
                    person
                  </span>
                  <h2 className="font-serif text-xl sm:text-2xl font-bold text-[#2d1b0c]">
                    Thông Tin Cá Nhân & Tu Học
                  </h2>
                </div>
                <p className="font-body-sm text-xs text-stone-500 mt-1.5 leading-relaxed">
                  Ghi nhận các thông tin tu tập để cá nhân hóa trải nghiệm đọc kinh, trì chú, lịch sám hối và phát bồ đề tâm...
                </p>
                <div className="h-px bg-[#f2e6d8] mt-4" />
              </div>

              {profileSuccess && (
                <div className="p-3.5 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-body-sm flex items-center gap-2 animate-in fade-in">
                  <span className="material-symbols-outlined text-[18px] text-emerald-600">
                    check_circle
                  </span>
                  <span>{profileSuccess}</span>
                </div>
              )}

              {profileErrors.general && (
                <div className="p-3.5 rounded-2xl bg-red-50 border border-red-200 text-red-700 text-xs font-body-sm flex items-center gap-2 animate-in fade-in">
                  <span className="material-symbols-outlined text-[18px]">
                    error
                  </span>
                  <span>{profileErrors.general.join(", ")}</span>
                </div>
              )}

              <form onSubmit={handleSaveProfile} className="space-y-6">
                {/* ---------------- 1. THÔNG TIN CĂN BẢN ---------------- */}
                <div className="space-y-3.5">
                  <h3 className="font-label-md text-xs font-bold uppercase tracking-wider text-stone-600 flex items-center gap-1.5">
                    <span className="text-[#99652c]">•</span> 1. THÔNG TIN CĂN BẢN
                  </h3>

                  <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    {/* Họ và tên */}
                    <div className="space-y-1.5">
                      <label
                        htmlFor="profile-name"
                        className="block font-label-md text-xs font-medium text-stone-700"
                      >
                        Họ và tên <span className="text-red-500">*</span>
                      </label>
                      <div className="relative">
                        <span className="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-[18px] text-stone-400 pointer-events-none">
                          person
                        </span>
                        <input
                          id="profile-name"
                          type="text"
                          required
                          maxLength={100}
                          value={name}
                          onChange={(e) => setName(e.target.value)}
                          className="w-full pl-10 pr-3.5 py-2.5 rounded-xl border border-stone-200 bg-stone-50/50 focus:bg-white focus:border-[#99652c] focus:ring-1 focus:ring-[#99652c] text-sm text-stone-800 transition-all outline-none"
                          placeholder="Họ và tên..."
                        />
                      </div>
                      {profileErrors.name && (
                        <p className="font-body-sm text-xs text-red-600">
                          {profileErrors.name[0]}
                        </p>
                      )}
                    </div>

                    {/* Pháp danh */}
                    <div className="space-y-1.5">
                      <div className="flex items-center justify-between">
                        <label
                          htmlFor="profile-dharma-name"
                          className="block font-label-md text-xs font-medium text-stone-700"
                        >
                          Pháp danh
                        </label>
                        <span className="text-stone-400 text-xs font-normal">
                          (Tùy chọn)
                        </span>
                      </div>
                      <div className="relative">
                        <span className="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-[18px] text-[#b8782a] pointer-events-none">
                          spa
                        </span>
                        <input
                          id="profile-dharma-name"
                          type="text"
                          maxLength={100}
                          value={dharmaName}
                          onChange={(e) => setDharmaName(e.target.value)}
                          className="w-full pl-10 pr-3.5 py-2.5 rounded-xl border border-stone-200 bg-stone-50/50 focus:bg-white focus:border-[#99652c] focus:ring-1 focus:ring-[#99652c] text-sm text-stone-800 transition-all outline-none"
                          placeholder="ví dụ: Diệu Âm Trí Duy"
                        />
                      </div>
                      {profileErrors.dharma_name && (
                        <p className="font-body-sm text-xs text-red-600">
                          {profileErrors.dharma_name[0]}
                        </p>
                      )}
                    </div>

                    {/* Ngày sinh */}
                    <div className="space-y-1.5">
                      <label
                        htmlFor="profile-dob"
                        className="block font-label-md text-xs font-medium text-stone-700"
                      >
                        Ngày sinh
                      </label>
                      <div className="relative">
                        <span className="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-[18px] text-stone-400 pointer-events-none">
                          calendar_today
                        </span>
                        <input
                          id="profile-dob"
                          type="date"
                          value={dateOfBirth}
                          onChange={(e) => setDateOfBirth(e.target.value)}
                          className="w-full pl-10 pr-3.5 py-2.5 rounded-xl border border-stone-200 bg-stone-50/50 focus:bg-white focus:border-[#99652c] focus:ring-1 focus:ring-[#99652c] text-sm text-stone-800 transition-all outline-none"
                        />
                      </div>
                      {profileErrors.date_of_birth && (
                        <p className="font-body-sm text-xs text-red-600">
                          {profileErrors.date_of_birth[0]}
                        </p>
                      )}
                    </div>

                    {/* Giới tính */}
                    <div className="space-y-1.5">
                      <label
                        htmlFor="profile-gender"
                        className="block font-label-md text-xs font-medium text-stone-700"
                      >
                        Giới tính
                      </label>
                      <div className="relative">
                        <span className="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-[18px] text-stone-400 pointer-events-none">
                          wc
                        </span>
                        <select
                          id="profile-gender"
                          value={gender}
                          onChange={(e) => setGender(e.target.value)}
                          className="w-full pl-10 pr-9 py-2.5 rounded-xl border border-stone-200 bg-stone-50/50 focus:bg-white focus:border-[#99652c] focus:ring-1 focus:ring-[#99652c] text-sm text-stone-800 transition-all outline-none appearance-none cursor-pointer"
                        >
                          <option value="">Chưa chọn</option>
                          <option value="male">Nam</option>
                          <option value="female">Nữ</option>
                          <option value="other">Khác</option>
                          <option value="prefer_not_to_say">Không chia sẻ</option>
                        </select>
                        <span className="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 text-[18px] text-stone-400 pointer-events-none">
                          expand_more
                        </span>
                      </div>
                      {profileErrors.gender && (
                        <p className="font-body-sm text-xs text-red-600">
                          {profileErrors.gender[0]}
                        </p>
                      )}
                    </div>
                  </div>
                </div>

                {/* ---------------- 2. ĐỊNH HƯỚNG TU HỌC & ĐỊA PHƯƠNG ---------------- */}
                <div className="space-y-3.5 pt-2">
                  <h3 className="font-label-md text-xs font-bold uppercase tracking-wider text-stone-600 flex items-center gap-1.5">
                    <span className="text-[#99652c]">•</span> 2. ĐỊNH HƯỚNG TU HỌC & ĐỊA PHƯƠNG
                  </h3>

                  <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    {/* Truyền thống tu học ưu tiên */}
                    <div className="space-y-1.5">
                      <label
                        htmlFor="profile-tradition"
                        className="block font-label-md text-xs font-medium text-stone-700"
                      >
                        Truyền thống tu học ưu tiên
                      </label>
                      <div className="relative">
                        <span className="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-[18px] text-[#b8782a] pointer-events-none">
                          temple_buddhist
                        </span>
                        <select
                          id="profile-tradition"
                          value={preferredTraditionId}
                          onChange={(e) => setPreferredTraditionId(e.target.value)}
                          className="w-full pl-10 pr-9 py-2.5 rounded-xl border border-stone-200 bg-stone-50/50 focus:bg-white focus:border-[#99652c] focus:ring-1 focus:ring-[#99652c] text-sm text-stone-800 transition-all outline-none appearance-none cursor-pointer"
                        >
                          <option value="">Chưa chọn truyền thống</option>
                          {traditions.map((t) => (
                            <option key={t.id} value={t.id}>
                              {t.name}
                            </option>
                          ))}
                        </select>
                        <span className="material-symbols-outlined absolute right-3 top-1/2 -translate-y-1/2 text-[18px] text-stone-400 pointer-events-none">
                          expand_more
                        </span>
                      </div>
                      {profileErrors.preferred_tradition_id && (
                        <p className="font-body-sm text-xs text-red-600">
                          {profileErrors.preferred_tradition_id[0]}
                        </p>
                      )}
                    </div>

                    {/* Địa phương sinh sống */}
                    <div className="space-y-1.5">
                      <label
                        htmlFor="profile-location"
                        className="block font-label-md text-xs font-medium text-stone-700"
                      >
                        Địa phương sinh sống
                      </label>
                      <div className="relative">
                        <span className="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-[18px] text-stone-400 pointer-events-none">
                          location_on
                        </span>
                        <input
                          id="profile-location"
                          type="text"
                          maxLength={150}
                          value={location}
                          onChange={(e) => setLocation(e.target.value)}
                          className="w-full pl-10 pr-3.5 py-2.5 rounded-xl border border-stone-200 bg-stone-50/50 focus:bg-white focus:border-[#99652c] focus:ring-1 focus:ring-[#99652c] text-sm text-stone-800 transition-all outline-none"
                          placeholder="ví dụ: Hà Nội, TP. Hồ Chí Minh..."
                        />
                      </div>
                      {profileErrors.location && (
                        <p className="font-body-sm text-xs text-red-600">
                          {profileErrors.location[0]}
                        </p>
                      )}
                    </div>

                    {/* Ngày bắt đầu hành trình Phật pháp */}
                    <div className="space-y-1.5 sm:col-span-2">
                      <div className="flex items-center justify-between">
                        <label
                          htmlFor="profile-journey-start"
                          className="block font-label-md text-xs font-medium text-stone-700"
                        >
                          Ngày bắt đầu hành trình Phật pháp / Ngày Quy Y Tam Bảo
                        </label>
                        <span className="text-[11px] font-medium text-[#99652c] italic">
                          Cột mốc bồ đề tâm
                        </span>
                      </div>
                      <div className="relative sm:w-1/2">
                        <span className="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-[18px] text-stone-400 pointer-events-none">
                          calendar_month
                        </span>
                        <input
                          id="profile-journey-start"
                          type="date"
                          value={dharmaJourneyStartedAt}
                          onChange={(e) => setDharmaJourneyStartedAt(e.target.value)}
                          className="w-full pl-10 pr-3.5 py-2.5 rounded-xl border border-stone-200 bg-stone-50/50 focus:bg-white focus:border-[#99652c] focus:ring-1 focus:ring-[#99652c] text-sm text-stone-800 transition-all outline-none"
                        />
                      </div>
                      {profileErrors.dharma_journey_started_at && (
                        <p className="font-body-sm text-xs text-red-600">
                          {profileErrors.dharma_journey_started_at[0]}
                        </p>
                      )}
                    </div>
                  </div>
                </div>

                {/* ---------------- 3. TIỂU SỬ & LỜI NGUYỆN TU HỌC ---------------- */}
                <div className="space-y-3.5 pt-2">
                  <div className="flex items-center justify-between">
                    <h3 className="font-label-md text-xs font-bold uppercase tracking-wider text-stone-600 flex items-center gap-1.5">
                      <span className="text-[#99652c]">•</span> 3. TIỂU SỬ & LỜI NGUYỆN TU HỌC
                    </h3>
                    <span className="font-body-sm text-xs text-stone-400">
                      {bio.length}/1000 ký tự
                    </span>
                  </div>

                  <div className="relative">
                    <textarea
                      id="profile-bio"
                      rows={4}
                      maxLength={1000}
                      value={bio}
                      onChange={(e) => setBio(e.target.value)}
                      className="w-full p-4 pb-10 rounded-2xl border border-stone-200 bg-stone-50/50 focus:bg-white focus:border-[#99652c] focus:ring-1 focus:ring-[#99652c] text-sm text-stone-800 transition-all outline-none resize-y min-h-[120px] leading-relaxed"
                      placeholder="Nguyện tu học và thực hành giáo lý của Đức Phật. Mong muốn Vãng sinh về nước Cực Lạc của Đức Phật A Di Đà..."
                    />
                    {/* Sacred Stamp Watermark */}
                    <div className="absolute right-3.5 bottom-3 flex items-center gap-1 text-[#b8782a] text-xs font-serif italic pointer-events-none select-none opacity-85">
                      <span className="material-symbols-outlined text-[15px]">spa</span>
                      <span>Nam Mô A Di Đà Phật</span>
                    </div>
                  </div>
                  {profileErrors.bio && (
                    <p className="font-body-sm text-xs text-red-600">
                      {profileErrors.bio[0]}
                    </p>
                  )}

                  <p className="text-stone-400 italic text-[11px] leading-normal">
                    *Lời nguyện này sẽ được hiển thị trong sổ tay tu tập và lịch tụng niệm hằng ngày của quý Phật tử.
                  </p>
                </div>

                {/* Action Buttons */}
                <div className="pt-4 flex items-center justify-end gap-3 border-t border-[#f2e6d8]">
                  <button
                    type="button"
                    onClick={handleResetProfile}
                    className="px-5 py-2.5 rounded-xl border border-stone-200 bg-white hover:bg-stone-50 text-stone-700 text-xs font-semibold transition-all cursor-pointer shadow-xs"
                  >
                    Hủy thay đổi
                  </button>
                  <button
                    type="submit"
                    disabled={isSavingProfile}
                    className="px-6 py-2.5 rounded-xl bg-[#452b14] hover:bg-[#34200e] text-white font-label-md text-xs font-semibold shadow-xs flex items-center gap-2 transition-all cursor-pointer disabled:opacity-50"
                  >
                    {isSavingProfile ? (
                      <>
                        <span className="w-3.5 h-3.5 border-2 border-white border-t-transparent rounded-full animate-spin" />
                        <span>Đang lưu...</span>
                      </>
                    ) : (
                      <>
                        <span className="material-symbols-outlined text-[16px]">
                          save
                        </span>
                        <span>Lưu thay đổi</span>
                      </>
                    )}
                  </button>
                </div>
              </form>
            </div>
          )}

          {/* TAB 2: BẢO MẬT TÀI KHOẢN */}
          {activeTab === "security" && (
            <div className="rounded-3xl bg-white border border-[#ebdccb]/80 shadow-[0_2px_16px_rgba(140,109,45,0.06)] p-6 sm:p-8 space-y-6">
              <div>
                <div className="flex items-center gap-2.5">
                  <span className="material-symbols-outlined text-[24px] text-[#452b14]">
                    shield
                  </span>
                  <h2 className="font-serif text-xl sm:text-2xl font-bold text-[#2d1b0c]">
                    Bảo Mật Tài Khoản
                  </h2>
                </div>
                <p className="font-body-sm text-xs text-stone-500 mt-1.5">
                  Quản lý an toàn địa chỉ email, mật khẩu đăng nhập và thông tin chứng thực.
                </p>
                <div className="h-px bg-[#f2e6d8] mt-4" />
              </div>

              {/* Form 1: Thay Đổi Email */}
              <form onSubmit={handleUpdateEmail} className="space-y-4 pt-2">
                <h3 className="font-label-md text-xs font-bold uppercase tracking-wider text-stone-600 flex items-center gap-1.5">
                  <span className="text-[#99652c]">•</span> 1. THAY ĐỔI ĐỊA CHỈ EMAIL
                </h3>
                <p className="text-xs text-stone-500">
                  Email hiện tại: <strong>{user.email}</strong>
                </p>

                {emailSuccess && (
                  <div className="p-3 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs flex items-center gap-2 animate-in fade-in">
                    <span className="material-symbols-outlined text-[18px] text-emerald-600">
                      check_circle
                    </span>
                    <span>{emailSuccess}</span>
                  </div>
                )}
                {emailErrors.general && (
                  <div className="p-3 rounded-2xl bg-red-50 border border-red-200 text-red-700 text-xs flex items-center gap-2 animate-in fade-in">
                    <span className="material-symbols-outlined text-[18px]">
                      error
                    </span>
                    <span>{emailErrors.general.join(", ")}</span>
                  </div>
                )}

                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                  <div className="space-y-1.5">
                    <label
                      htmlFor="new-email"
                      className="block font-label-md text-xs font-medium text-stone-700"
                    >
                      Địa chỉ email mới <span className="text-red-500">*</span>
                    </label>
                    <div className="relative">
                      <span className="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-[18px] text-stone-400 pointer-events-none">
                        mail
                      </span>
                      <input
                        id="new-email"
                        type="email"
                        required
                        value={newEmail}
                        onChange={(e) => setNewEmail(e.target.value)}
                        className="w-full pl-10 pr-3.5 py-2.5 rounded-xl border border-stone-200 bg-stone-50/50 focus:bg-white focus:border-[#99652c] text-sm text-stone-800 transition-all outline-none"
                        placeholder="nhap-email-moi@example.com"
                      />
                    </div>
                    {emailErrors.email && (
                      <p className="font-body-sm text-xs text-red-600">
                        {emailErrors.email[0]}
                      </p>
                    )}
                  </div>

                  <div className="space-y-1.5">
                    <label
                      htmlFor="email-confirm-pw"
                      className="block font-label-md text-xs font-medium text-stone-700"
                    >
                      Mật khẩu hiện tại để xác nhận <span className="text-red-500">*</span>
                    </label>
                    <div className="relative">
                      <span className="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-[18px] text-stone-400 pointer-events-none">
                        lock
                      </span>
                      <input
                        id="email-confirm-pw"
                        type="password"
                        required
                        value={currentPasswordForEmail}
                        onChange={(e) => setCurrentPasswordForEmail(e.target.value)}
                        className="w-full pl-10 pr-3.5 py-2.5 rounded-xl border border-stone-200 bg-stone-50/50 focus:bg-white focus:border-[#99652c] text-sm text-stone-800 transition-all outline-none"
                        placeholder="••••••••"
                      />
                    </div>
                    {emailErrors.current_password && (
                      <p className="font-body-sm text-xs text-red-600">
                        {emailErrors.current_password[0]}
                      </p>
                    )}
                  </div>
                </div>

                <div className="flex justify-end pt-1">
                  <button
                    type="submit"
                    disabled={isUpdatingEmail}
                    className="px-5 py-2.5 rounded-xl bg-[#452b14] hover:bg-[#34200e] text-white text-xs font-semibold flex items-center gap-2 cursor-pointer disabled:opacity-50"
                  >
                    {isUpdatingEmail ? (
                      <span className="w-3.5 h-3.5 border-2 border-white border-t-transparent rounded-full animate-spin" />
                    ) : (
                      <span className="material-symbols-outlined text-[16px]">
                        mark_email_read
                      </span>
                    )}
                    <span>Cập nhật Email</span>
                  </button>
                </div>
              </form>

              <div className="h-px bg-[#f2e6d8]" />

              {/* Form 2: Thay Đổi Mật Khẩu */}
              <form onSubmit={handleChangePassword} className="space-y-4">
                <h3 className="font-label-md text-xs font-bold uppercase tracking-wider text-stone-600 flex items-center gap-1.5">
                  <span className="text-[#99652c]">•</span> 2. THAY ĐỔI MẬT KHẨU
                </h3>

                {passwordSuccess && (
                  <div className="p-3 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs flex items-center gap-2 animate-in fade-in">
                    <span className="material-symbols-outlined text-[18px] text-emerald-600">
                      check_circle
                    </span>
                    <span>{passwordSuccess}</span>
                  </div>
                )}
                {passwordErrors.general && (
                  <div className="p-3 rounded-2xl bg-red-50 border border-red-200 text-red-700 text-xs flex items-center gap-2 animate-in fade-in">
                    <span className="material-symbols-outlined text-[18px]">
                      error
                    </span>
                    <span>{passwordErrors.general.join(", ")}</span>
                  </div>
                )}

                <div className="space-y-4">
                  <div className="space-y-1.5">
                    <label
                      htmlFor="current-pw"
                      className="block font-label-md text-xs font-medium text-stone-700"
                    >
                      Mật khẩu hiện tại <span className="text-red-500">*</span>
                    </label>
                    <div className="relative sm:w-2/3">
                      <span className="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-[18px] text-stone-400 pointer-events-none">
                        lock
                      </span>
                      <input
                        id="current-pw"
                        type="password"
                        required
                        value={currentPassword}
                        onChange={(e) => setCurrentPassword(e.target.value)}
                        className="w-full pl-10 pr-3.5 py-2.5 rounded-xl border border-stone-200 bg-stone-50/50 focus:bg-white focus:border-[#99652c] text-sm text-stone-800 transition-all outline-none"
                        placeholder="••••••••"
                      />
                    </div>
                    {passwordErrors.current_password && (
                      <p className="font-body-sm text-xs text-red-600">
                        {passwordErrors.current_password[0]}
                      </p>
                    )}
                  </div>

                  <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div className="space-y-1.5">
                      <label
                        htmlFor="new-pw"
                        className="block font-label-md text-xs font-medium text-stone-700"
                      >
                        Mật khẩu mới <span className="text-red-500">*</span>
                      </label>
                      <div className="relative">
                        <span className="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-[18px] text-stone-400 pointer-events-none">
                          key
                        </span>
                        <input
                          id="new-pw"
                          type="password"
                          required
                          value={password}
                          onChange={(e) => setPassword(e.target.value)}
                          className="w-full pl-10 pr-3.5 py-2.5 rounded-xl border border-stone-200 bg-stone-50/50 focus:bg-white focus:border-[#99652c] text-sm text-stone-800 transition-all outline-none"
                          placeholder="Tối thiểu 8 ký tự..."
                        />
                      </div>
                      {passwordErrors.password && (
                        <p className="font-body-sm text-xs text-red-600">
                          {passwordErrors.password[0]}
                        </p>
                      )}
                    </div>

                    <div className="space-y-1.5">
                      <label
                        htmlFor="confirm-new-pw"
                        className="block font-label-md text-xs font-medium text-stone-700"
                      >
                        Xác nhận mật khẩu mới <span className="text-red-500">*</span>
                      </label>
                      <div className="relative">
                        <span className="material-symbols-outlined absolute left-3.5 top-1/2 -translate-y-1/2 text-[18px] text-stone-400 pointer-events-none">
                          lock_reset
                        </span>
                        <input
                          id="confirm-new-pw"
                          type="password"
                          required
                          value={passwordConfirmation}
                          onChange={(e) => setPasswordConfirmation(e.target.value)}
                          className="w-full pl-10 pr-3.5 py-2.5 rounded-xl border border-stone-200 bg-stone-50/50 focus:bg-white focus:border-[#99652c] text-sm text-stone-800 transition-all outline-none"
                          placeholder="Nhập lại mật khẩu mới..."
                        />
                      </div>
                      {passwordErrors.password_confirmation && (
                        <p className="font-body-sm text-xs text-red-600">
                          {passwordErrors.password_confirmation[0]}
                        </p>
                      )}
                    </div>
                  </div>
                </div>

                <div className="flex justify-end pt-1">
                  <button
                    type="submit"
                    disabled={isChangingPassword}
                    className="px-5 py-2.5 rounded-xl bg-[#452b14] hover:bg-[#34200e] text-white text-xs font-semibold flex items-center gap-2 cursor-pointer disabled:opacity-50"
                  >
                    {isChangingPassword ? (
                      <span className="w-3.5 h-3.5 border-2 border-white border-t-transparent rounded-full animate-spin" />
                    ) : (
                      <span className="material-symbols-outlined text-[16px]">
                        key
                      </span>
                    )}
                    <span>Đổi mật khẩu</span>
                  </button>
                </div>
              </form>
            </div>
          )}
        </div>
      </div>
    </div>
  );
}
