import { NextRequest, NextResponse } from "next/server";
import {
  clearAuthTokenCookie,
  getAuthTokenFromCookies,
  getBackendApiUrl,
  isOriginAllowed,
} from "@/lib/auth/bff";

export async function POST(request: NextRequest) {
  if (!isOriginAllowed(request)) {
    return NextResponse.json(
      { message: "Yêu cầu bị từ chối do nguồn gốc không hợp lệ (CSRF)." },
      { status: 403 }
    );
  }

  const token = await getAuthTokenFromCookies();
  const backendUrl = getBackendApiUrl();

  if (token) {
    try {
      await fetch(`${backendUrl}/api/v1/auth/logout`, {
        method: "POST",
        headers: {
          Authorization: `Bearer ${token}`,
          Accept: "application/json",
        },
        cache: "no-store",
      });
    } catch (err) {
      console.warn("Could not notify backend of logout:", err);
    }
  }

  await clearAuthTokenCookie();

  return NextResponse.json(
    { message: "Đã đăng xuất thành công." },
    {
      status: 200,
      headers: {
        "Cache-Control": "no-store, private",
      },
    }
  );
}
