import { NextRequest, NextResponse } from "next/server";
import { getBackendApiUrl, isOriginAllowed } from "@/lib/auth/bff";

export async function POST(request: NextRequest) {
  if (!isOriginAllowed(request)) {
    return NextResponse.json(
      { message: "Yêu cầu bị từ chối do nguồn gốc không hợp lệ (CSRF)." },
      { status: 403 }
    );
  }

  const backendUrl = getBackendApiUrl();
  const body = await request.json().catch(() => ({}));

  try {
    const res = await fetch(`${backendUrl}/api/v1/auth/reset-password`, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        Accept: "application/json",
      },
      body: JSON.stringify(body),
      cache: "no-store",
    });

    const data = await res.json().catch(() => null);

    return NextResponse.json(
      data || { message: res.ok ? "Thành công" : "Lỗi đặt lại mật khẩu" },
      { status: res.status }
    );
  } catch (err) {
    console.error("Reset password BFF proxy error:", err);
    return NextResponse.json(
      { message: "Không thể kết nối đến máy chủ. Vui lòng thử lại sau." },
      { status: 502 }
    );
  }
}
