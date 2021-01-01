<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Shop\Category;
use App\Models\Shop\Product;
use App\Models\Shop\ProductVariant;
use Illuminate\Support\Facades\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ShopController extends Controller
{
    public function __construct()
    {
        $this->middleware([
            'auth',
            'member',
        ]);
    }

    public function index()
    {
        $categories = Category::query()
            ->where('visible', 1)
            ->orderBy('name')
            ->get();

        // Add to CSP
        $images = $categories
            ->map(static fn (Category $category) => $category->products()->first())
            ->pluck('image_url');
        $this->addImageUrlsToCspPolicy($images);

        return Response::view('shop.index', [
            'categories' => $categories,
        ]);
    }

    public function showCategory(Category $category)
    {
        if (!$category->visible) {
            throw new NotFoundHttpException();
        }

        $products = $category->products()
            ->where('visible', '1')
            ->orderBy('name')
            ->with('variants')
            ->withCount('variants')
            ->get();

        // Add to CSP
        $this->addImageUrlsToCspPolicy($products->pluck('image_url'));

        // TODO: show category
        return Response::view('shop.category', [
            'category' => $category,
            'products' => $products,
        ]);
    }

    public function showProduct(Product $product)
    {
        if (!$product->visible) {
            throw new NotFoundHttpException();
        }

        // Find first variant
        $variant = ProductVariant::query()
            ->where('product_id', $product->id)
            ->firstOrFail();

        return Response::redirectToRoute('shop.product-variant', [
            'product' => $product,
            'variant' => $variant->slug,
        ]);
    }

    public function showProductVariant(Product $product, string $variant)
    {
        if (!$product->visible) {
            throw new NotFoundHttpException();
        }

        $variant = ProductVariant::query()
            ->where('product_id', $product->id)
            ->where('slug', $variant)
            ->firstOrFail();

        // Add to CSP
        $this->addImageUrlsToCspPolicy([
            $product->image_url,
            $variant->image_url,
        ]);

        // Show product
        return Response::view('shop.product', [
            'category' => $product->category,
            'product' => $product,
            'variant' => $variant,
            'variants' => $product->variants,
        ]);
    }
}