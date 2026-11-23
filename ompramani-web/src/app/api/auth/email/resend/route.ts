import { NextRequest, NextResponse } from "next/server";
import {
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
  if (!token) {
    return NextResponse.json(
      { message: "Chưa xác thực danh tính." },
      { status: 401 }
    );
  }

  const backendUrl = getBackendApiUrl();

  try {
    const res = await fetch(`${backendUrl}/api/v1/auth/email/resend`, {
      method: "POST",
      headers: {
        Authorization: `Bearer ${token}`,
        Accept: "application/json",
      },
      cache: "no-store",
    });

    const data = await res.json().catch(() => null);

    return NextResponse.json(
      data || { message: "Đã gửi lại liên kết xác thực email." },
      { status: res.status }
    );
  } catch (err) {
    console.error("Resend email verification BFF proxy error:", err);
    return NextResponse.json(
      { message: "Không thể kết nối đến máy chủ. Vui lòng thử lại sau." },
      { status: 502 }
    );
  }
}
