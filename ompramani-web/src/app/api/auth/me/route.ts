import { NextResponse } from "next/server";
import {
  clearAuthTokenCookie,
  getAuthTokenFromCookies,
  getBackendApiUrl,
} from "@/lib/auth/bff";

export async function GET() {
  const token = await getAuthTokenFromCookies();

  if (!token) {
    return NextResponse.json(
      { message: "Chưa xác thực danh tính." },
      {
        status: 401,
        headers: {
          "Cache-Control": "private, no-cache, no-store, must-revalidate",
        },
      }
    );
  }

  const backendUrl = getBackendApiUrl();

  try {
    const backendRes = await fetch(`${backendUrl}/api/v1/auth/me`, {
      method: "GET",
      headers: {
        Authorization: `Bearer ${token}`,
        Accept: "application/json",
      },
      cache: "no-store",
    });

    const data = await backendRes.json().catch(() => null);

    if (!backendRes.ok) {
      if (backendRes.status === 401) {
        await clearAuthTokenCookie();
      }
      return NextResponse.json(
        data || { message: "Phiên đăng nhập đã hết hạn hoặc không hợp lệ." },
        {
          status: backendRes.status,
          headers: {
            "Cache-Control": "private, no-cache, no-store, must-revalidate",
          },
        }
      );
    }

    const user =
      data?.user || (data?.data && "user" in data.data ? data.data.user : data?.data);

    return NextResponse.json(
      {
        data: user,
        user,
      },
      {
        status: 200,
        headers: {
          "Cache-Control": "private, no-cache, no-store, must-revalidate",
        },
      }
    );
  } catch (err) {
    console.error("Auth me BFF proxy error:", err);
    return NextResponse.json(
      { message: "Lỗi kết nối máy chủ xác thực." },
      {
        status: 502,
        headers: {
          "Cache-Control": "private, no-cache, no-store, must-revalidate",
        },
      }
    );
  }
}
