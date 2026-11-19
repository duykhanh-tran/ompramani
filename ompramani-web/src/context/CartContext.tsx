"use client";

import React, {
  createContext,
  useContext,
  useState,
  useEffect,
  useCallback,
  useMemo,
} from "react";
import { CartItemStorage } from "@/types/shop";
import {
  CART_STORAGE_KEY,
  MAX_CART_ITEMS,
  MAX_ITEM_QUANTITY,
  MIN_ITEM_QUANTITY,
  loadCartFromStorage,
  saveCartToStorage,
} from "@/lib/cart/cartStorage";

interface AddItemResult {
  success: boolean;
  message: string;
}

interface CartContextValue {
  items: CartItemStorage[];
  totalQuantity: number;
  itemCount: number;
  isHydrated: boolean;
  addItem: (productId: string, quantity?: number) => AddItemResult;
  updateQuantity: (productId: string, quantity: number) => void;
  removeItem: (productId: string) => void;
  clearCart: () => void;
}

const CartContext = createContext<CartContextValue | null>(null);

export function CartProvider({ children }: { children: React.ReactNode }) {
  const [items, setItems] = useState<CartItemStorage[]>([]);
  const [isHydrated, setIsHydrated] = useState(false);

  // Initialize cart from localStorage after mount
  useEffect(() => {
    const stored = loadCartFromStorage();
    // eslint-disable-next-line react-hooks/set-state-in-effect
    setItems(stored);
    setIsHydrated(true);

    // Sync across browser tabs via storage events
    const handleStorageChange = (e: StorageEvent) => {
      if (e.key === CART_STORAGE_KEY) {
        setItems(loadCartFromStorage());
      }
    };

    window.addEventListener("storage", handleStorageChange);
    return () => {
      window.removeEventListener("storage", handleStorageChange);
    };
  }, []);

  const totalQuantity = useMemo(() => {
    return items.reduce((sum, item) => sum + item.quantity, 0);
  }, [items]);

  const itemCount = useMemo(() => {
    return items.length;
  }, [items]);

  const addItem = useCallback(
    (productId: string, quantity = 1): AddItemResult => {
      const cleanId = productId.trim();
      if (!cleanId) {
        return { success: false, message: "Mã sản phẩm không hợp lệ." };
      }

      const cleanQty = Math.max(
        MIN_ITEM_QUANTITY,
        Math.min(MAX_ITEM_QUANTITY, Math.floor(quantity))
      );

      let resultMsg = "Đã thêm vào giỏ hàng.";
      let wasAdded = true;

      setItems((prev) => {
        const existingIndex = prev.findIndex((i) => i.product_id === cleanId);

        if (existingIndex >= 0) {
          const currentQty = prev[existingIndex].quantity;
          const nextQty = Math.min(MAX_ITEM_QUANTITY, currentQty + cleanQty);

          if (currentQty >= MAX_ITEM_QUANTITY) {
            resultMsg = `Số lượng đã đạt mức tối đa (${MAX_ITEM_QUANTITY}) cho vật phẩm này.`;
            wasAdded = false;
            return prev;
          }

          const updated = [...prev];
          updated[existingIndex] = {
            product_id: cleanId,
            quantity: nextQty,
          };
          saveCartToStorage(updated);
          return updated;
        }

        if (prev.length >= MAX_CART_ITEMS) {
          resultMsg = `Giỏ hàng đã đầy (tối đa ${MAX_CART_ITEMS} loại vật phẩm).`;
          wasAdded = false;
          return prev;
        }

        const updated = [...prev, { product_id: cleanId, quantity: cleanQty }];
        saveCartToStorage(updated);
        return updated;
      });

      return { success: wasAdded, message: resultMsg };
    },
    []
  );

  const updateQuantity = useCallback((productId: string, quantity: number) => {
    const cleanId = productId.trim();
    if (!cleanId) return;

    setItems((prev) => {
      const intQty = Math.floor(quantity);

      if (intQty < MIN_ITEM_QUANTITY) {
        const filtered = prev.filter((i) => i.product_id !== cleanId);
        saveCartToStorage(filtered);
        return filtered;
      }

      const clampedQty = Math.min(MAX_ITEM_QUANTITY, intQty);
      const updated = prev.map((item) => {
        if (item.product_id === cleanId) {
          return { ...item, quantity: clampedQty };
        }
        return item;
      });

      saveCartToStorage(updated);
      return updated;
    });
  }, []);

  const removeItem = useCallback((productId: string) => {
    const cleanId = productId.trim();
    if (!cleanId) return;

    setItems((prev) => {
      const filtered = prev.filter((i) => i.product_id !== cleanId);
      saveCartToStorage(filtered);
      return filtered;
    });
  }, []);

  const clearCart = useCallback(() => {
    setItems([]);
    if (typeof window !== "undefined") {
      try {
        window.localStorage.removeItem(CART_STORAGE_KEY);
      } catch (e) {
        console.error("Failed to clear cart storage:", e);
      }
    }
  }, []);

  const value = useMemo(
    () => ({
      items,
      totalQuantity,
      itemCount,
      isHydrated,
      addItem,
      updateQuantity,
      removeItem,
      clearCart,
    }),
    [
      items,
      totalQuantity,
      itemCount,
      isHydrated,
      addItem,
      updateQuantity,
      removeItem,
      clearCart,
    ]
  );

  return <CartContext.Provider value={value}>{children}</CartContext.Provider>;
}

export function useCart(): CartContextValue {
  const context = useContext(CartContext);
  if (!context) {
    throw new Error("useCart must be used within a CartProvider");
  }
  return context;
}
