'use client';

import Link from 'next/link';
import Image from 'next/image';
import { FiShoppingCart, FiHeart } from 'react-icons/fi';
import useCartStore from '@/store/cartStore';
import toast from 'react-hot-toast';
import { useState } from 'react';

export default function ProductCard({ product }) {
  const [isFavorite, setIsFavorite] = useState(false);
  const addItem = useCartStore((state) => state.addItem);

  const handleAddToCart = (e) => {
    e.preventDefault();
    addItem(product, 1);
    toast.success('Added to cart!');
  };

  const handleFavorite = (e) => {
    e.preventDefault();
    setIsFavorite(!isFavorite);
    toast.success(isFavorite ? 'Removed from favorites' : 'Added to favorites');
  };

  return (
    <Link href={`/products/${product.id}`}>
      <div className="bg-white rounded-lg shadow-md hover:shadow-xl transition-shadow overflow-hidden cursor-pointer h-full">
        {/* Image */}
        <div className="relative h-48 w-full bg-gray-200">
          {product.images && product.images.length > 0 ? (
            <Image
              src={product.images[0]}
              alt={product.name}
              fill
              className="object-cover"
            />
          ) : (
            <div className="w-full h-full flex items-center justify-center bg-gray-300 text-gray-500">
              No Image
            </div>
          )}
          <div className="absolute top-2 right-2 bg-red-500 text-white px-2 py-1 rounded text-xs font-bold">
            {product.stock > 0 ? 'In Stock' : 'Out of Stock'}
          </div>
        </div>

        {/* Content */}
        <div className="p-4">
          <h3 className="text-lg font-semibold text-gray-800 truncate">{product.name}</h3>
          <p className="text-gray-600 text-sm mt-1 line-clamp-2">{product.description}</p>

          {/* Rating */}
          <div className="flex items-center mt-2">
            <div className="flex text-yellow-400">
              {'★'.repeat(Math.floor(product.rating || 4))}
              {'☆'.repeat(5 - Math.floor(product.rating || 4))}
            </div>
            <span className="text-gray-600 text-sm ml-2">({product.rating || 4}/5)</span>
          </div>

          {/* Price */}
          <div className="mt-4 flex items-center justify-between">
            <div className="text-2xl font-bold text-primary">${product.price.toFixed(2)}</div>
          </div>

          {/* Actions */}
          <div className="mt-4 flex gap-2">
            <button
              onClick={handleAddToCart}
              disabled={product.stock <= 0}
              className="flex-1 flex items-center justify-center gap-2 bg-primary text-white py-2 rounded-lg hover:bg-blue-600 transition disabled:bg-gray-300 disabled:cursor-not-allowed"
            >
              <FiShoppingCart />
              Add
            </button>
            <button
              onClick={handleFavorite}
              className="px-3 py-2 border border-gray-300 rounded-lg hover:bg-gray-100 transition"
            >
              <FiHeart fill={isFavorite ? 'red' : 'none'} color={isFavorite ? 'red' : 'black'} />
            </button>
          </div>
        </div>
      </div>
    </Link>
  );
}
