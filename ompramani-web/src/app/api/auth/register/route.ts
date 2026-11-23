import { NextRequest, NextResponse } from "next/server";
import {
  getBackendApiUrl,
  isOriginAllowed,
  setAuthTokenCookie,
} from "@/lib/auth/bff";

export async function POST(request: NextRequest) {
  if (!isOriginAllowed(request)) {
    return NextResponse.json(
      { message: "Yêu cầu bị từ chối do nguồn gốc không hợp lệ (CSRF)." },
      { status: 403 }
    );
  }

  let body: Record<string, unknown>;
  try {
    body = await request.json();
  } catch {
    return NextResponse.json(
      { message: "Dữ liệu yêu cầu không hợp lệ." },
      { status: 400 }
    );
  }

  const backendUrl = getBackendApiUrl();

  try {
    const backendRes = await fetch(`${backendUrl}/api/v1/auth/register`, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        Accept: "application/json",
      },
      body: JSON.stringify(body),
      cache: "no-store",
    });

    const data = await backendRes.json().catch(() => null);

    if (!backendRes.ok) {
      return NextResponse.json(
        data || { message: `Lỗi đăng ký tài khoản (${backendRes.status})` },
        {
          status: backendRes.status,
          headers: {
            "Cache-Control": "no-store, private",
          },
        }
      );
    }

    const token = data?.data?.token || data?.token;
    const user =
      data?.user || (data?.data && "user" in data.data ? data.data.user : data?.data);

    if (token) {
      await setAuthTokenCookie(token, false);
    }

    return NextResponse.json(
      {
        data: { user },
        user,
      },
      {
        status: 201,
        headers: {
          "Cache-Control": "no-store, private",
        },
      }
    );
  } catch (err) {
    console.error("Register BFF proxy error:", err);
    return NextResponse.json(
      { message: "Không thể kết nối đến máy chủ xác thực. Vui lòng thử lại sau." },
      { status: 502 }
    );
  }
}
