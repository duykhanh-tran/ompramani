import { NextRequest, NextResponse } from "next/server";
import {
  clearAuthTokenCookie,
  getAuthTokenFromCookies,
  getBackendApiUrl,
  isOriginAllowed,
} from "@/lib/auth/bff";

type RouteParams = {
  params: Promise<{
    path: string[];
  }>;
};

async function handleProxy(
  request: NextRequest,
  { params }: RouteParams
) {
  const method = request.method.toUpperCase();

  // Validate Origin / Referer for CSRF protection on mutating requests
  if (!["GET", "HEAD", "OPTIONS"].includes(method) && !isOriginAllowed(request)) {
    return NextResponse.json(
      { message: "Yêu cầu bị từ chối do nguồn gốc không hợp lệ (CSRF)." },
      { status: 403 }
    );
  }

  const { path } = await params;
  const backendBaseUrl = getBackendApiUrl();
  const search = request.nextUrl.search; // preserves ?query=params
  const targetPath = path.join("/");
  const targetUrl = `${backendBaseUrl}/${targetPath}${search}`;

  const token = await getAuthTokenFromCookies();

  const forwardHeaders = new Headers();
  forwardHeaders.set("Accept", "application/json");

  // Pass through relevant incoming headers
  const incomingContentType = request.headers.get("content-type");
  if (incomingContentType) {
    forwardHeaders.set("Content-Type", incomingContentType);
  }

  // Forward authorization token if user has HttpOnly cookie session
  if (token) {
    forwardHeaders.set("Authorization", `Bearer ${token}`);
  }

  // Forward client IP/proxies if available
  const xForwardedFor = request.headers.get("x-forwarded-for");
  if (xForwardedFor) {
    forwardHeaders.set("X-Forwarded-For", xForwardedFor);
  }

  let body: BodyInit | null = null;
  if (!["GET", "HEAD"].includes(method)) {
    try {
      body = await request.arrayBuffer();
    } catch {
      body = null;
    }
  }

  try {
    const backendRes = await fetch(targetUrl, {
      method,
      headers: forwardHeaders,
      body,
      cache: "no-store",
    });

    if (backendRes.status === 401 && token) {
      // If token expired or revoked on backend, clear cookie
      await clearAuthTokenCookie();
    }

    const responseHeaders = new Headers();
    const contentType = backendRes.headers.get("content-type");
    if (contentType) {
      responseHeaders.set("Content-Type", contentType);
    }

    // Never cache private/financial/authenticated data
    responseHeaders.set(
      "Cache-Control",
      token
        ? "private, no-cache, no-store, must-revalidate"
        : backendRes.headers.get("cache-control") || "no-store"
    );

    const responseBody = await backendRes.arrayBuffer();

    return new NextResponse(responseBody, {
      status: backendRes.status,
      headers: responseHeaders,
    });
  } catch (error) {
    console.error(`BFF Proxy error to ${targetUrl}:`, error);
    return NextResponse.json(
      { message: "Không thể kết nối đến máy chủ API backend." },
      { status: 502 }
    );
  }
}

export const GET = handleProxy;
export const POST = handleProxy;
export const PUT = handleProxy;
export const PATCH = handleProxy;
export const DELETE = handleProxy;
export const HEAD = handleProxy;
