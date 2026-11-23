import { test, describe } from "node:test";
import assert from "node:assert";
import { resolveMediaUrl, isCloudinaryUrl } from "../media.ts";

describe("Media Resolver and Cloudinary URL Tests", () => {
  test("preserves authentic Cloudinary URLs with .jpg.jpg suffix without stripping", () => {
    const authenticCloudinaryUrl =
      "https://res.cloudinary.com/dml5eptq2/image/upload/v1795404994/buddhist-figures/01M837M4DRK3WFS62J760M3PRJ.jpg.jpg";
    const resolved = resolveMediaUrl(authenticCloudinaryUrl);
    assert.strictEqual(resolved, authenticCloudinaryUrl);
  });

  test("preserves authentic Cloudinary URLs with single .jpg suffix", () => {
    const singleExtUrl =
      "https://res.cloudinary.com/dml5eptq2/image/upload/v1795407653/articles/covers/01M6ZTCVP04NNANE81DF81CEWV.jpg";
    const resolved = resolveMediaUrl(singleExtUrl);
    assert.strictEqual(resolved, singleExtUrl);
  });

  test("preserves Cloudinary audio URLs with .mp3", () => {
    const audioUrl =
      "https://res.cloudinary.com/dml5eptq2/video/upload/v1795407664/mantras/audio/01M784B2PWHN7YBFBCWXKBT5X2.mp3";
    const resolved = resolveMediaUrl(audioUrl);
    assert.strictEqual(resolved, audioUrl);
  });

  test("handles protocol-relative URLs by prefixing https:", () => {
    const protoRelative =
      "//res.cloudinary.com/dml5eptq2/image/upload/sample.jpg.jpg";
    const resolved = resolveMediaUrl(protoRelative);
    assert.strictEqual(
      resolved,
      "https://res.cloudinary.com/dml5eptq2/image/upload/sample.jpg.jpg"
    );
  });

  test("sanitizes duplicate extension only on relative local paths", () => {
    const dirtyRelative = "temples/covers/chua-mot-cot.jpg.jpg";
    const resolved = resolveMediaUrl(dirtyRelative);
    assert.ok(resolved?.includes("/storage/temples/covers/chua-mot-cot.jpg"));
    assert.ok(!resolved?.includes(".jpg.jpg"));
  });

  test("sanitizes duplicate extension on storage/ prefixed paths", () => {
    const dirtyStorage = "storage/products/statue.png.png";
    const resolved = resolveMediaUrl(dirtyStorage);
    assert.ok(resolved?.includes("/storage/products/statue.png"));
    assert.ok(!resolved?.includes(".png.png"));
  });

  test("returns null for falsy or invalid inputs", () => {
    assert.strictEqual(resolveMediaUrl(null), null);
    assert.strictEqual(resolveMediaUrl(undefined), null);
    assert.strictEqual(resolveMediaUrl(""), null);
    assert.strictEqual(resolveMediaUrl("   "), null);
    assert.strictEqual(resolveMediaUrl("null"), null);
    assert.strictEqual(resolveMediaUrl("undefined"), null);
  });

  test("isCloudinaryUrl accurately detects Cloudinary CDN assets", () => {
    assert.strictEqual(
      isCloudinaryUrl(
        "https://res.cloudinary.com/dml5eptq2/image/upload/v1795404994/buddhist-figures/01M837M4DRK3WFS62J760M3PRJ.jpg.jpg"
      ),
      true
    );
    assert.strictEqual(
      isCloudinaryUrl(
        "https://res.cloudinary.com/dml5eptq2/image/upload/v1795407653/articles/covers/cover.jpg"
      ),
      true
    );
    assert.strictEqual(
      isCloudinaryUrl("https://ompramani.onrender.com/storage/sample.jpg"),
      false
    );
    assert.strictEqual(isCloudinaryUrl("/images/placeholder.jpg"), false);
    assert.strictEqual(isCloudinaryUrl(null), false);
    assert.strictEqual(isCloudinaryUrl(undefined), false);
  });
});
