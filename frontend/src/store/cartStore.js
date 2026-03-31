import { create } from 'zustand';

const useCartStore = create((set, get) => ({
  items: [],
  total: 0,

  addItem: (product, quantity) => {
    set((state) => {
      const existingItem = state.items.find((item) => item.productId === product.id);
      let newItems;

      if (existingItem) {
        newItems = state.items.map((item) =>
          item.productId === product.id
            ? { ...item, quantity: item.quantity + quantity }
            : item
        );
      } else {
        newItems = [
          ...state.items,
          {
            productId: product.id,
            name: product.name,
            price: product.price,
            image: product.images?.[0],
            quantity,
          },
        ];
      }

      const newTotal = newItems.reduce((sum, item) => sum + item.price * item.quantity, 0);
      return { items: newItems, total: newTotal };
    });
  },

  removeItem: (productId) => {
    set((state) => {
      const newItems = state.items.filter((item) => item.productId !== productId);
      const newTotal = newItems.reduce((sum, item) => sum + item.price * item.quantity, 0);
      return { items: newItems, total: newTotal };
    });
  },

  updateQuantity: (productId, quantity) => {
    set((state) => {
      const newItems = state.items.map((item) =>
        item.productId === productId ? { ...item, quantity } : item
      );
      const newTotal = newItems.reduce((sum, item) => sum + item.price * item.quantity, 0);
      return { items: newItems, total: newTotal };
    });
  },

  clearCart: () => {
    set({ items: [], total: 0 });
  },
}));

export default useCartStore;
