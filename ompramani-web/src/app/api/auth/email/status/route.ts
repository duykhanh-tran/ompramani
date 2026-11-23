import { NextResponse } from "next/server";
import {
  getAuthTokenFromCookies,
  getBackendApiUrl,
} from "@/lib/auth/bff";

export async function GET() {
  const token = await getAuthTokenFromCookies();
  if (!token) {
    return NextResponse.json(
      { message: "Chưa xác thực danh tính." },
      { status: 401 }
    );
  }

  const backendUrl = getBackendApiUrl();

  try {
    const res = await fetch(`${backendUrl}/api/v1/auth/email/status`, {
      method: "GET",
      headers: {
        Authorization: `Bearer ${token}`,
        Accept: "application/json",
      },
      cache: "no-store",
    });

    const data = await res.json().catch(() => null);

    return NextResponse.json(
      data || { message: "Không thể lấy trạng thái xác thực email." },
      {
        status: res.status,
        headers: {
          "Cache-Control": "private, no-cache, no-store, must-revalidate",
        },
      }
    );
  } catch (err) {
    console.error("Email status BFF proxy error:", err);
    return NextResponse.json(
      { message: "Không thể kết nối đến máy chủ. Vui lòng thử lại sau." },
      { status: 502 }
    );
  }
}
