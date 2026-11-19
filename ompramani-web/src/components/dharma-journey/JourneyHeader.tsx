/* eslint-disable @next/next/no-img-element */
import React from "react";
import { AuthUser } from "@/types/auth";

interface JourneyHeaderProps {
  user: AuthUser;
}

export function JourneyHeader({ user }: JourneyHeaderProps) {
  return (
    <div className="relative overflow-hidden rounded-3xl bg-surface-container-lowest border border-outline-variant/50 p-6 sm:p-8 shadow-[0_4px_24px_rgba(140,109,45,0.06)]">
      <div className="absolute top-0 right-0 w-72 h-72 bg-primary/5 rounded-full blur-3xl pointer-events-none" />

      <div className="flex flex-col sm:flex-row items-center sm:items-start gap-6 text-center sm:text-left">
        {/* Avatar frame */}
        <div className="relative shrink-0">
          <div className="w-20 h-20 rounded-full bg-gradient-to-br from-primary via-primary-container to-secondary-container text-on-primary flex items-center justify-center font-bold text-2xl shadow-md ring-4 ring-primary-fixed/30 overflow-hidden">
            {user.profile?.avatar_url ? (
              <img
                src={user.profile.avatar_url}
                alt={user.name}
                className="w-full h-full object-cover"
              />
            ) : (
              <span>{user.name.charAt(0).toUpperCase()}</span>
            )}
          </div>
        </div>

        {/* User Journey Info */}
        <div className="flex-1 min-w-0 space-y-2">
          <div className="flex flex-wrap items-center justify-center sm:justify-start gap-2.5">
            <h1 className="font-headline-xl text-2xl sm:text-3xl font-bold text-on-surface">
              Hành Trình Phật Pháp
            </h1>
            {user.profile?.dharma_name && (
              <span className="inline-flex items-center gap-1 px-3 py-1 rounded-full bg-primary-fixed text-on-primary-fixed text-xs font-semibold font-label-md">
                <span className="material-symbols-outlined text-[14px]">spa</span>
                <span>Pháp danh: {user.profile.dharma_name}</span>
              </span>
            )}
          </div>

          <p className="font-body-md text-sm text-on-surface-variant max-w-2xl leading-relaxed">
            Nhìn lại những thời khóa đã thực hành và tiếp tục hành trình của riêng bạn.
            Từng phút tĩnh lặng, từng câu niệm, từng phẩm kinh đều là hạt giống lành
            nuôi dưỡng tâm bồ đề.
          </p>

          <div className="flex flex-wrap items-center justify-center sm:justify-start gap-4 pt-1 text-xs text-outline font-label-md">
            <span className="flex items-center gap-1">
              <span className="material-symbols-outlined text-[15px]">person</span>
              <span>{user.name}</span>
            </span>
            {user.profile?.preferred_tradition && (
              <span className="flex items-center gap-1">
                <span className="material-symbols-outlined text-[15px]">
                  account_balance
                </span>
                <span>{user.profile.preferred_tradition.name}</span>
              </span>
            )}
          </div>
        </div>
      </div>
    </div>
  );
}
