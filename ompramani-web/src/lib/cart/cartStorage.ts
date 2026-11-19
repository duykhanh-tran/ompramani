import { CartItemStorage, CartStoragePayload } from "@/types/shop";

export const CART_STORAGE_KEY = "ompramani_shop_cart_v1";
export const CART_SCHEMA_VERSION = 1;
export const MAX_CART_ITEMS = 50;
export const MAX_ITEM_QUANTITY = 99;
export const MIN_ITEM_QUANTITY = 1;

export function getInitialCartPayload(): CartStoragePayload {
  return {
    version: CART_SCHEMA_VERSION,
    items: [],
  };
}

/**
 * Safely parse and sanitize cart payload from localStorage.
 * Defends against manual edits, corrupted JSON, invalid versions, floats, and negative numbers.
 */
export function loadCartFromStorage(): CartItemStorage[] {
  if (typeof window === "undefined") {
    return [];
  }

  try {
    const raw = window.localStorage.getItem(CART_STORAGE_KEY);
    if (!raw) {
      return [];
    }

    const parsed = JSON.parse(raw) as Partial<CartStoragePayload>;

    if (!parsed || typeof parsed !== "object") {
      return [];
    }

    if (parsed.version !== CART_SCHEMA_VERSION || !Array.isArray(parsed.items)) {
      return [];
    }

    const sanitizedMap = new Map<string, number>();

    for (const rawItem of parsed.items) {
      if (!rawItem || typeof rawItem !== "object") continue;

      const rawId = (rawItem as { product_id?: unknown }).product_id;
      const rawQty = (rawItem as { quantity?: unknown }).quantity;

      if (typeof rawId !== "string" || !rawId.trim()) continue;
      const productId = rawId.trim();

      const numQty = typeof rawQty === "number" ? rawQty : parseInt(String(rawQty), 10);
      if (isNaN(numQty) || !isFinite(numQty)) continue;

      const integerQty = Math.floor(numQty);
      if (integerQty < MIN_ITEM_QUANTITY) continue;

      const current = sanitizedMap.get(productId) || 0;
      const combined = Math.min(MAX_ITEM_QUANTITY, current + integerQty);
      sanitizedMap.set(productId, combined);

      if (sanitizedMap.size >= MAX_CART_ITEMS) break;
    }

    const result: CartItemStorage[] = [];
    sanitizedMap.forEach((quantity, product_id) => {
      result.push({ product_id, quantity });
    });

    return result;
  } catch (error) {
    console.warn("Recovered from corrupted cart storage:", error);
    return [];
  }
}

/**
 * Persist cart items to localStorage with schema versioning.
 */
export function saveCartToStorage(items: CartItemStorage[]): void {
  if (typeof window === "undefined") {
    return;
  }

  try {
    const sanitizedMap = new Map<string, number>();

    for (const item of items) {
      if (!item.product_id || !item.product_id.trim()) continue;
      const id = item.product_id.trim();
      const qty = Math.max(
        MIN_ITEM_QUANTITY,
        Math.min(MAX_ITEM_QUANTITY, Math.floor(item.quantity || 1))
      );

      const current = sanitizedMap.get(id) || 0;
      sanitizedMap.set(id, Math.min(MAX_ITEM_QUANTITY, current + qty));
      if (sanitizedMap.size >= MAX_CART_ITEMS) break;
    }

    const cleanItems: CartItemStorage[] = [];
    sanitizedMap.forEach((quantity, product_id) => {
      cleanItems.push({ product_id, quantity });
    });

    const payload: CartStoragePayload = {
      version: CART_SCHEMA_VERSION,
      items: cleanItems,
    };

    window.localStorage.setItem(CART_STORAGE_KEY, JSON.stringify(payload));
  } catch (error) {
    console.error("Failed to save cart to storage:", error);
  }
}
