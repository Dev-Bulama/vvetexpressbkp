<?php

namespace App\Http\Controllers\API;

use App\Models\Category;
use App\Models\Favorite;
use App\Models\FlashSale;
use App\Models\CategoryAttribute;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\ReviewRequest;
use App\Http\Resources\BrandResource;
use App\Http\Resources\ReviewResource;
use App\Repositories\ReviewRepository;
use App\Http\Resources\ProductResource;
use App\Repositories\ProductRepository;
use App\Http\Requests\AddFavoriteRequest;
use App\Http\Resources\ProductDetailsResource;

class ProductController extends Controller
{
    /**
     * Retrieve a paginated list of products based on the provided request parameters.
     *
     * @param  Request  $request  The request object containing page, per_page, and search parameters
     * @return Some_Return_Value The JSON response containing total and products data
     */
    public function index(Request $request)
    {
        $request->validate([
            'search' => 'nullable|string|max:200',
        ]);

        $page = $request->page;
        $perPage = $request->per_page;
        $skip = ($page * $perPage) - $perPage;

        $search = $request->search;
        $shopID = $request->shop_id;
        $categoryID = $request->category_id;
        $attributeID = $request->attribute_id;

        // Selecting a (parent) category / attribute should also match products
        // tagged only to its descendants, since products are attached to the
        // specific leaf category / attribute they belong to.
        $categoryIDs = $categoryID ? $this->withDescendantIds(Category::class, $categoryID) : null;
        $attributeIDs = $attributeID ? $this->withDescendantIds(CategoryAttribute::class, $attributeID) : null;

        $rating = $request->rating; // 4.0
        $sortType = $request->sort_type;
        $minPrice = $request->min_price;
        $maxPrice = $request->max_price;
        $brandID = $request->brand_id;

        $generaleSetting = generaleSetting('setting');
        $shop = null;
        if ($generaleSetting?->shop_type == 'single') {
            $shop = generaleSetting('rootShop');
        }

        // get data for
        $rootShop = $shop ?? generaleSetting('rootShop');
        $productQuery = ProductRepository::query()->when($shop, function ($query) use ($shop) {
            return $query->where('shop_id', $shop->id);
        })->isActive();

        $flashSale = FlashSale::isActive()->first();
        $flashSaleMinPrice = $flashSale ? $flashSale->products->min('pivot.price') : null;

        $productMinPrice = $productQuery->min('price');
        if ($flashSaleMinPrice && $flashSaleMinPrice < $productMinPrice) {
            $productMinPrice = $flashSaleMinPrice;
        }

        $productMaxPrice = $productQuery->max('price');
        $brands = $rootShop?->brands()->isActive()->get();

        if ($search && $search != '' && $search != null && strlen($search) >= 3) {
            $this->getSearchLog($search);
        }

        // filter query
        $products = ProductRepository::query()
            ->withSum('orders as orders_count', 'order_products.quantity')
            ->withAvg('reviews as average_rating', 'rating')
            ->isActive()
            ->when($shop, function ($query) use ($shop) {
                return $query->where('shop_id', $shop->id);
            })->when($shopID && ! $shop, function ($query) use ($shopID) {
                return $query->where('shop_id', $shopID);
            })
            ->when($search, function ($query) use ($search) {
                return $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', '%' . $search . '%')
                        ->orWhere('short_description', 'like', '%' . $search . '%')
                        ->orWhere('code', 'like', '%' . $search . '%');
                });
            })->when($brandID, function ($query) use ($brandID) {
                return $query->where('brand_id', $brandID);
            })->when($categoryIDs, function ($query) use ($categoryIDs) {
                return $query->whereHas('categories', function ($query) use ($categoryIDs) {
                    return $query->whereIn('id', $categoryIDs);
                });
            })->when($attributeIDs, function ($query) use ($attributeIDs) {
                return $query->whereHas('categoryAttributes', function ($query) use ($attributeIDs) {
                    return $query->whereIn('category_attributes.id', $attributeIDs);
                });
            })->when($rating, function ($query) use ($rating) {
                $ratingValue = floatval($rating);
                $upperBound = $ratingValue + 1;

                return $query->havingRaw('average_rating >= ' . $rating . ' AND average_rating < ' . $upperBound);
            })->when($sortType == 'top_selling', function ($query) {
                return $query->orderByDesc('orders_count');
            })->when($sortType == 'popular_product', function ($query) {
                return $query->orderByDesc('orders_count')->orderByDesc('average_rating');
            })->when($sortType == 'newest' || $sortType == 'just_for_you', function ($query) {
                return $query->orderBy('id', 'desc');
            })->when($minPrice || $maxPrice, function ($query) use ($minPrice, $maxPrice) {
                $query->whereRaw('
                    COALESCE(
                        (SELECT flash_sale_products.price
                         FROM flash_sale_products
                         INNER JOIN flash_sales ON flash_sales.id = flash_sale_products.flash_sale_id
                         WHERE flash_sale_products.product_id = products.id
                         AND flash_sale_products.quantity > 0
                         AND flash_sales.status = 1
                         AND flash_sales.start_date <= CURDATE()
                         AND flash_sales.end_date >= CURDATE()
                         AND (flash_sales.start_time <= CURTIME() OR flash_sales.end_time >= CURTIME())
                         ORDER BY flash_sale_products.price ASC LIMIT 1
                        ),
                        IF(discount_price > 0, discount_price, price)
                    ) BETWEEN ? AND ?
                ', [$minPrice ?? 0, $maxPrice ?? PHP_INT_MAX]);
            })
            ->when(in_array($sortType, ['high_to_low', 'low_to_high']), function ($query) use ($sortType) {
                $order = $sortType === 'high_to_low' ? 'DESC' : 'ASC';

                return $query->orderByRaw("
                    COALESCE(
                        (SELECT flash_sale_products.price
                         FROM flash_sale_products
                         INNER JOIN flash_sales ON flash_sales.id = flash_sale_products.flash_sale_id
                         WHERE flash_sale_products.product_id = products.id
                         AND flash_sale_products.quantity > 0
                         AND flash_sales.status = 1
                         AND flash_sales.start_date <= CURDATE()
                         AND flash_sales.end_date >= CURDATE()
                         AND (flash_sales.start_time <= CURTIME() OR flash_sales.end_time >= CURTIME())
                         ORDER BY flash_sale_products.price $order LIMIT 1
                        ),
                        IF(discount_price > 0, discount_price, price)
                    ) $order
                ")->orderByDesc('id');
            });

        $total = $products->count();
        $products = $products->when($perPage && $page, function ($query) use ($perPage, $skip) {
            return $query->skip($skip)->take($perPage);
        })->get();

        return $this->json('products', [
            'total' => $total,
            'products' => ProductResource::collection($products),
            'filters' => [
                'brands' => $brands ? BrandResource::collection($brands) : [],
                'min_price' => (int) intval($productMinPrice),
                'max_price' => (int) intval($productMaxPrice),
            ],
        ]);
    }

    /**
     * Expand the given id(s) to include every descendant id of a self-referencing
     * model (children linked via `parent_id`). Used so filtering by a parent
     * category / attribute also matches products tagged to its descendants.
     *
     * @param  class-string  $model
     * @param  int|array  $ids
     */
    private function withDescendantIds(string $model, $ids): array
    {
        $all = collect((array) $ids)
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $parents = $all;
        while (! empty($parents)) {
            $children = $model::whereIn('parent_id', $parents)->pluck('id')->all();
            $children = array_values(array_diff($children, $all));

            if (empty($children)) {
                break;
            }

            $all = array_merge($all, $children);
            $parents = $children;
        }

        return $all;
    }

    private function getSearchLog($search)
    {
        if (!module_exists('report')) {
            return true;
        }
        \Modules\Report\App\Repositories\ProductSearchLogRepository::updateOrCreateByRequest($search);
    }

    /**
     * Show the product details.
     *
     * @param  datatype  $id  description
     * @return response
     */
    public function show(Request $request)
    {
        $request->validate([
            'product_id' => 'required_without:slug|nullable|integer|exists:products,id',
            'slug' => 'required_without:product_id|nullable|string|exists:products,slug',
        ]);

        $product = ProductRepository::query()->when(is_numeric($request->product_id), function ($query) use ($request) {
            return $query->where('id', $request->product_id);
        }, function ($query) use ($request) {
            return $query->where('slug', $request->slug);
        })
            ->isActive()
            ->first();
        ProductRepository::recentView($product);

        $relatedProducts = ProductRepository::query()->whereHas('categories', function ($query) use ($product) {
            $query->whereIn('categories.id', $product->categories->pluck('id'));
        })->where('id', '!=', $product->id)
            ->isActive()
            ->inRandomOrder()
            ->limit(6)->get();

        $shop = $product->shop;

        $popularProducts = $shop->products()->isActive()->where('id', '!=', $product->id)->withCount('orders')->withAvg('reviews as average_rating', 'rating')->orderByDesc('average_rating')->orderByDesc('orders_count')->take(6)->get();

        return $this->json('product details', [
            'product' => ProductDetailsResource::make($product),
            'related_products' => ProductResource::collection($relatedProducts),
            'popular_products' => ProductResource::collection($popularProducts),
        ]);
    }

    /**
     * Add or remove favorite product for the user.
     *
     * @param  AddFavoriteRequest  $request  The request for adding a favorite.
     * @return json Response with favorite updated successfully
     */
    public function addFavorite(AddFavoriteRequest $request)
    {
        $product = ProductRepository::find($request->product_id);

        $favorite = Favorite::where('customer_id', auth()->user()->customer->id)
            ->where('product_id', $product->id)
            ->first();

        if ($favorite) {
            $favorite->delete();
        } else {
            Favorite::create([
                'customer_id' => auth()->user()->customer->id,
                'product_id'  => $product->id,
            ]);
        }
        // auth()->user()?->customer->favorites()->toggle($product->id);

        return $this->json('favorite updated successfully', [
            'product' => ProductResource::make($product),
        ]);
    }

    /**
     * get list of favorite products.
     *
     * @return json Response
     */
    public function favoriteProducts()
    {
        $products = auth()->user()->customer->favorites;

        return $this->json('favorite products', [
            'products' => ProductResource::collection($products),
        ]);
    }

    /**
     * Store a new review.
     *
     * @param  ReviewRequest  $request  The review request
     * @return json Response
     */
    public function storeReview(ReviewRequest $request)
    {
        $product = ProductRepository::find($request->product_id);

        $hasReview = $product->reviews()->where('customer_id', auth()->user()->customer->id)->where('order_id', $request->order_id)->first();

        if ($hasReview) {
            return $this->json('review already exists', [
                'review' => ReviewResource::make($hasReview),
            ]);
        }

        $review = ReviewRepository::storeByRequest($request, $product);

        return $this->json('review added successfully', [
            'review' => ReviewResource::make($review),
        ]);
    }
}
