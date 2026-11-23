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

  const email = typeof body.email === "string" ? body.email.trim() : "";
  const password = typeof body.password === "string" ? body.password : "";
  const remember = Boolean(body.remember);

  const errors: Record<string, string[]> = {};
  if (!email) {
    errors.email = ["Địa chỉ email không được để trống."];
  }
  if (!password) {
    errors.password = ["Mật khẩu không được để trống."];
  }

  if (Object.keys(errors).length > 0) {
    return NextResponse.json(
      {
        message: "Thông tin đăng nhập không hợp lệ.",
        errors,
      },
      { status: 422 }
    );
  }

  const backendUrl = getBackendApiUrl();

  try {
    const backendRes = await fetch(`${backendUrl}/api/v1/auth/login`, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        Accept: "application/json",
      },
      body: JSON.stringify({
        email,
        password,
        remember,
      }),
      cache: "no-store",
    });

    const data = await backendRes.json().catch(() => null);

    if (!backendRes.ok) {
      return NextResponse.json(
        data || { message: `Lỗi máy chủ (${backendRes.status})` },
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
      await setAuthTokenCookie(token, remember);
    }

    return NextResponse.json(
      {
        data: { user },
        user,
      },
      {
        status: 200,
        headers: {
          "Cache-Control": "no-store, private",
        },
      }
    );
  } catch (err) {
    console.error("Login BFF proxy error:", err);
    return NextResponse.json(
      { message: "Không thể kết nối đến máy chủ xác thực. Vui lòng thử lại sau." },
      { status: 502 }
    );
  }
}
