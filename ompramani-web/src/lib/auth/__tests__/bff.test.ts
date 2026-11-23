import { describe, it } from "node:test";
import assert from "node:assert/strict";
import {
  AUTH_COOKIE_NAME,
  getBackendApiUrl,
  getCookieOptions,
  isOriginAllowed,
} from "../bff.ts";

describe("Next.js BFF Authentication & Security Tests", () => {
  it("cookie options strictly enforce HttpOnly, lax sameSite, and appropriate maxAge", () => {
    assert.strictEqual(AUTH_COOKIE_NAME, "auth_token");

    const sessionOptions = getCookieOptions(false);
    assert.strictEqual(sessionOptions.name, "auth_token");
    assert.strictEqual(sessionOptions.httpOnly, true);
    assert.strictEqual(sessionOptions.sameSite, "lax");
    assert.strictEqual(sessionOptions.path, "/");
    assert.strictEqual(sessionOptions.maxAge, 7 * 24 * 60 * 60);

    const rememberOptions = getCookieOptions(true);
    assert.strictEqual(rememberOptions.maxAge, 30 * 24 * 60 * 60);
  });

  it("backend URL resolver returns sanitized URL without trailing slashes", () => {
    const url = getBackendApiUrl();
    assert.ok(url.startsWith("http"));
    assert.ok(!url.endsWith("/"));
  });

  it("origin validation allows requests from authorized frontend domains and localhost", () => {
    const vercelReq = new Request("https://ompramani.vercel.app/api/auth/login", {
      method: "POST",
      headers: {
        origin: "https://ompramani.vercel.app",
        host: "ompramani.vercel.app",
      },
    });
    assert.strictEqual(isOriginAllowed(vercelReq), true);

    const localhostReq = new Request("http://localhost:3000/api/auth/login", {
      method: "POST",
      headers: {
        origin: "http://localhost:3000",
        host: "localhost:3000",
      },
    });
    assert.strictEqual(isOriginAllowed(localhostReq), true);

    const loopbackReq = new Request("http://127.0.0.1:3000/api/auth/login", {
      method: "POST",
      headers: {
        origin: "http://127.0.0.1:3000",
        host: "127.0.0.1:3000",
      },
    });
    assert.strictEqual(isOriginAllowed(loopbackReq), true);

    const previewReq = new Request("https://ompramani-preview-git.vercel.app/api/auth/login", {
      method: "POST",
      headers: {
        origin: "https://ompramani-preview-git.vercel.app",
        host: "ompramani-preview-git.vercel.app",
      },
    });
    assert.strictEqual(isOriginAllowed(previewReq), true);
  });

  it("origin validation strictly rejects unauthorized cross-origin requests", () => {
    const attackerReq = new Request("https://ompramani.vercel.app/api/auth/login", {
      method: "POST",
      headers: {
        origin: "https://evil-phishing-site.com",
        host: "ompramani.vercel.app",
      },
    });
    assert.strictEqual(isOriginAllowed(attackerReq), false);

    const attackerReferer = new Request("https://ompramani.vercel.app/api/auth/login", {
      method: "POST",
      headers: {
        referer: "https://evil-phishing-site.com/exploit",
        host: "ompramani.vercel.app",
      },
    });
    assert.strictEqual(isOriginAllowed(attackerReferer), false);
  });
});
