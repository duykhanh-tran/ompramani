"use client";

import React, { useState } from "react";
import Link from "next/link";
import { SutraSectionTreeNode } from "@/types/api";

interface SutraTableOfContentsProps {
  sutraSlug: string;
  versionId?: number;
  sections: SutraSectionTreeNode[];
  activeSectionId?: number;
}

export function SutraTableOfContents({
  sutraSlug,
  versionId,
  sections,
  activeSectionId,
}: SutraTableOfContentsProps) {
  const [mobileDrawerOpen, setMobileDrawerOpen] = useState(false);

  const renderSectionNode = (
    node: SutraSectionTreeNode,
    depth = 0
  ): React.ReactNode => {
    const isActive = node.id === activeSectionId;
    const hasChildren = node.children && node.children.length > 0;
    const sectionHref = versionId
      ? `/kinh/${sutraSlug}?version=${versionId}&section=${node.id}`
      : `/kinh/${sutraSlug}?section=${node.id}`;

    return (
      <li key={node.id} className="space-y-1">
        <Link
          href={sectionHref}
          onClick={() => setMobileDrawerOpen(false)}
          className={`group flex items-center justify-between py-2 px-3 rounded-xl transition-all font-body-sm text-[14px] ${
            depth > 0 ? "ml-4 border-l-2 border-outline-variant/40 pl-3" : ""
          } ${
            isActive
              ? "bg-primary text-on-primary font-semibold shadow-xs"
              : "text-on-surface-variant hover:bg-surface-container hover:text-primary"
          }`}
        >
          <div className="flex items-center gap-2 min-w-0">
            <span
              className={`material-symbols-outlined text-[16px] shrink-0 ${
                isActive
                  ? "text-on-primary"
                  : depth === 0
                  ? "text-primary"
                  : "text-outline group-hover:text-primary"
              }`}
            >
              {depth === 0 ? "bookmark" : "subdirectory_arrow_right"}
            </span>
            <span className="truncate">{node.title}</span>
          </div>

          {isActive && (
            <span className="w-1.5 h-1.5 rounded-full bg-secondary-fixed shrink-0" />
          )}
        </Link>

        {hasChildren && (
          <ul className="space-y-1">
            {node.children.map((child) => renderSectionNode(child, depth + 1))}
          </ul>
        )}
      </li>
    );
  };

  return (
    <>
      {/* Mobile Floating TOC Toggle Button */}
      <div className="lg:hidden sticky top-20 z-30 mb-4">
        <button
          type="button"
          suppressHydrationWarning
          onClick={() => setMobileDrawerOpen(true)}
          className="w-full py-2.5 px-4 bg-surface-container-lowest/95 backdrop-blur-md border border-outline-variant/60 rounded-2xl shadow-sm text-on-surface font-label-md text-label-md flex items-center justify-between cursor-pointer"
        >
          <div className="flex items-center gap-2">
            <span className="material-symbols-outlined text-primary text-[20px]">
              menu_book
            </span>
            <span>Mục lục kinh văn</span>
          </div>
          <span className="material-symbols-outlined text-outline text-[18px]">
            format_list_bulleted
          </span>
        </button>
      </div>

      {/* Mobile Drawer Backdrop & Panel */}
      {mobileDrawerOpen && (
        <div className="lg:hidden fixed inset-0 z-50 flex">
          {/* Backdrop */}
          <div
            className="fixed inset-0 bg-on-surface/40 backdrop-blur-sm transition-opacity"
            onClick={() => setMobileDrawerOpen(false)}
          />

          {/* Drawer Sheet */}
          <div className="relative w-4/5 max-w-sm bg-surface-container-lowest h-full shadow-2xl p-6 flex flex-col z-10 overflow-y-auto">
            <div className="flex items-center justify-between pb-4 border-b border-outline-variant/30 mb-4">
              <div className="flex items-center gap-2">
                <span className="material-symbols-outlined text-primary text-[22px]">
                  auto_stories
                </span>
                <h3 className="font-headline-md text-headline-md font-semibold text-on-surface">
                  Mục lục Kinh
                </h3>
              </div>
              <button
                type="button"
                suppressHydrationWarning
                onClick={() => setMobileDrawerOpen(false)}
                className="w-8 h-8 rounded-full flex items-center justify-center text-outline hover:text-on-surface"
              >
                <span className="material-symbols-outlined text-[20px]">
                  close
                </span>
              </button>
            </div>

            <nav aria-label="Mục lục kinh di động" className="flex-1">
              <ul className="space-y-1">
                {sections.map((node) => renderSectionNode(node))}
              </ul>
            </nav>
          </div>
        </div>
      )}

      {/* Desktop Sticky Rail */}
      <aside className="hidden lg:block w-72 xl:w-80 shrink-0">
        <div className="sticky top-24 bg-surface-container-lowest rounded-3xl p-5 border border-outline-variant/30 shadow-sm max-h-[calc(100vh-8rem)] flex flex-col">
          <div className="flex items-center gap-2 pb-3 mb-3 border-b border-outline-variant/30">
            <span className="material-symbols-outlined text-primary text-[20px]">
              format_list_bulleted
            </span>
            <h3 className="font-headline-md text-[17px] font-semibold text-on-surface">
              Mục Lục Kinh Văn
            </h3>
          </div>

          <nav
            aria-label="Mục lục kinh văn trên máy tính"
            className="overflow-y-auto pr-1 flex-1 space-y-1"
          >
            <ul className="space-y-1">
              {sections.map((node) => renderSectionNode(node))}
            </ul>
          </nav>
        </div>
      </aside>
    </>
  );
}
