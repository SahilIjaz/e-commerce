'use client';

import { useEffect, useState } from 'react';
import { useParams } from 'next/navigation';
import Image from 'next/image';
import api from '@/lib/api';
import useCartStore from '@/store/cartStore';
import useAuthStore from '@/store/authStore';
import toast from 'react-hot-toast';
import { FiShoppingCart, FiHeart } from 'react-icons/fi';

export default function ProductDetailPage() {
  const params = useParams();
  const productId = params.id;
  const { user } = useAuthStore();
  const addItem = useCartStore((state) => state.addItem);

  const [product, setProduct] = useState(null);
  const [loading, setLoading] = useState(true);
  const [quantity, setQuantity] = useState(1);
  const [isFavorite, setIsFavorite] = useState(false);
  const [mainImage, setMainImage] = useState(0);

  useEffect(() => {
    const fetchProduct = async () => {
      try {
        const response = await api.get(`/products/${productId}`);
        setProduct(response.data.product);
        setMainImage(0);
      } catch (error) {
        console.error('Error fetching product:', error);
        toast.error('Product not found');
      } finally {
        setLoading(false);
      }
    };

    if (productId) {
      fetchProduct();
    }
  }, [productId]);

  const handleAddToCart = () => {
    if (!user || user.role !== 'buyer') {
      toast.error('Please login as a buyer to add items to cart');
      return;
    }

    addItem(product, quantity);
    toast.success('Added to cart!');
    setQuantity(1);
  };

  const handleFavorite = () => {
    setIsFavorite(!isFavorite);
    toast.success(isFavorite ? 'Removed from favorites' : 'Added to favorites');
  };

  if (loading) {
    return <div className="text-center py-12">Loading...</div>;
  }

  if (!product) {
    return <div className="text-center py-12">Product not found</div>;
  }

  return (
    <div>
      <div className="grid grid-cols-1 md:grid-cols-2 gap-8 bg-white p-8 rounded-lg">
        {/* Images */}
        <div>
          <div className="relative h-96 mb-4 bg-gray-100 rounded-lg overflow-hidden">
            {product.images && product.images.length > 0 ? (
              <Image
                src={product.images[mainImage]}
                alt={product.name}
                fill
                className="object-cover"
              />
            ) : (
              <div className="w-full h-full flex items-center justify-center text-gray-500">
                No Image Available
              </div>
            )}
          </div>

          {/* Image Thumbnails */}
          {product.images && product.images.length > 1 && (
            <div className="flex gap-2">
              {product.images.map((image, index) => (
                <button
                  key={index}
                  onClick={() => setMainImage(index)}
                  className={`relative h-20 w-20 rounded-lg overflow-hidden border-2 ${
                    mainImage === index ? 'border-primary' : 'border-gray-300'
                  }`}
                >
                  <Image
                    src={image}
                    alt={`${product.name}-${index}`}
                    fill
                    className="object-cover"
                  />
                </button>
              ))}
            </div>
          )}
        </div>

        {/* Product Details */}
        <div>
          <h1 className="text-4xl font-bold mb-4">{product.name}</h1>

          {/* Rating */}
          <div className="flex items-center mb-4">
            <div className="flex text-yellow-400 text-2xl">
              {'★'.repeat(Math.floor(product.rating || 4))}
              {'☆'.repeat(5 - Math.floor(product.rating || 4))}
            </div>
            <span className="text-gray-600 ml-2">({product.rating || 4}/5)</span>
          </div>

          {/* Price */}
          <div className="mb-6">
            <p className="text-4xl font-bold text-primary">${product.price.toFixed(2)}</p>
          </div>

          {/* Description */}
          <p className="text-gray-700 mb-6 text-lg">{product.description}</p>

          {/* Stock */}
          <div className="mb-6">
            <p className="text-lg font-semibold">
              Stock:{' '}
              <span className={product.stock > 0 ? 'text-green-600' : 'text-red-600'}>
                {product.stock} available
              </span>
            </p>
          </div>

          {/* Category */}
          <div className="mb-6">
            <p className="text-gray-700">
              <span className="font-semibold">Category:</span> {product.category}
            </p>
          </div>

          {/* Quantity and Actions */}
          <div className="flex gap-4 mb-8">
            <div className="flex items-center border border-gray-300 rounded-lg">
              <button
                onClick={() => setQuantity(Math.max(1, quantity - 1))}
                className="px-4 py-2 text-gray-600 hover:bg-gray-100"
              >
                −
              </button>
              <input
                type="number"
                min="1"
                max={product.stock}
                value={quantity}
                onChange={(e) => setQuantity(Math.max(1, Math.min(product.stock, parseInt(e.target.value))))}
                className="w-16 text-center border-l border-r border-gray-300"
              />
              <button
                onClick={() => setQuantity(Math.min(product.stock, quantity + 1))}
                className="px-4 py-2 text-gray-600 hover:bg-gray-100"
              >
                +
              </button>
            </div>

            <button
              onClick={handleAddToCart}
              disabled={product.stock <= 0}
              className="flex-1 flex items-center justify-center gap-2 bg-primary text-white py-3 rounded-lg hover:bg-blue-600 transition disabled:bg-gray-300 disabled:cursor-not-allowed font-semibold"
            >
              <FiShoppingCart size={20} />
              Add to Cart
            </button>

            <button
              onClick={handleFavorite}
              className="px-6 py-3 border-2 border-gray-300 rounded-lg hover:bg-gray-100 transition"
            >
              <FiHeart size={20} fill={isFavorite ? 'red' : 'none'} color={isFavorite ? 'red' : 'black'} />
            </button>
          </div>

          {/* Additional Info */}
          <div className="bg-gray-50 p-6 rounded-lg space-y-3">
            <p className="flex justify-between">
              <span>Seller ID:</span>
              <span className="font-semibold">{product.seller}</span>
            </p>
            <p className="flex justify-between">
              <span>Product ID:</span>
              <span className="font-semibold">{product.id}</span>
            </p>
          </div>
        </div>
      </div>
    </div>
  );
}
