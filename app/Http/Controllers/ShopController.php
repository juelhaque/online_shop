<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductRating;
use App\Models\SubCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ShopController extends Controller
{
    public function index(Request $request, $categorySlug = null, $subCategorySlug = null){
        $categorySelected = '';
        $subCategorySelected = '';
        $brandsArray = [];

        $categories = Category::orderBy('name', 'DESC')->with('sub_category')->where('status', 1)->get();
        $brands = Brand::orderBy('name', 'DESC')->where('status', 1)->get();
        $products = Product::where('status', 1);

        //Apply filter here
        if (!empty($categorySlug)) {
            $category = Category::where('slug', $categorySlug)->first();
            $products = $products->where('category_id', $category->id);
            $categorySelected = $category->id;
        }

        if (!empty($subCategorySlug)) {
            $subCategory = SubCategory::where('slug', $subCategorySlug)->first();
            $products = $products->where('sub_category_id', $subCategory->id);
            $subCategorySelected = $subCategory->id;
        }

        if (!empty($request->get('brand'))) {
            $brandsArray = explode(',', $request->get('brand'));
            $products = $products->whereIn('brand_id', $brandsArray);
        }

        if ($request->get('price_max') != '' && $request->get('price_min') != '') {
            if ($request->get('price_max') == 1000) {
                $products = $products->whereBetween('price', [intval($request->get('price_min')), 10000000]);
            }else{
                $products = $products->whereBetween('price', [intval($request->get('price_min')), intval($request->get('price_max'))]);
            }

        }

        if ($request->get('sort') != '') {
            if ($request->get('sort') == 'latest') {
                $products = $products->orderBy('id', 'ASC');
            }else if($request->get('sort') == 'price_asc'){
                $products = $products->orderBy('price', 'ASC');
            }else{
                $products = $products->orderBy('price', 'ASC');
            }

        }else{
            $products = $products->orderBy('id', 'ASC');
        }

        $products = $products->paginate(6);

        $data['categories'] = $categories;
        $data['brands'] = $brands;
        $data['products'] = $products;
        $data['categorySelected'] = $categorySelected;
        $data['subCategorySelected'] = $subCategorySelected;
        $data['brandsArray'] = $brandsArray;
        $data['priceMax'] = (intval($request->get('price_max')) == 0) ? 1000 : $request->get('price_max');
        $data['priceMin'] = intval($request->get('price_min'));
        $data['sort'] = $request->get('sort');

        return view('front.shop', $data);
    }


    public function product($slug){
        $product = Product::where('slug', $slug)
                    ->withCount('product_ratings')
                    ->withSum('product_ratings', 'rating')
                    ->with(['product_images', 'product_ratings'])->first();
// dd($product);
        if ($product == null) {
            abort(404);
        }

        //Fetch related Products
        $relatedProducts = [];
        if ($product->related_products != '') {
            $productArray = explode(',', $product->related_products);
            $relatedProducts = Product::whereIn('id', $productArray)->get();
        }

        $data['product'] = $product;
        $data['relatedProducts'] = $relatedProducts;

        // Rating Calculation
        $avgRating = '0.00';
        $avgRatingPercent = 0;
        if ($product->product_ratings_count > 0) {
            $avgRating = number_format(($product->product_ratings_sum_rating/$product->product_ratings_count), 2);
            $avgRatingPercent = ($avgRating*100)/5;
        }

        $data['avgRating'] = $avgRating;
        $data['avgRatingPercent'] = $avgRatingPercent;

        return view('front.product', $data);
    }


    public function saveRating($id, Request $request){

        $validator = Validator::make($request->all(), [
            'name' => 'required|min:3',
            'email' => 'required|email',
            'comment' => 'required',
            'rating' => 'required',
        ]);

        if ($validator->fails()) {

            return response()->json([
                // 'message' => 'Please fill the required feild',
                'status' => false,
                'errors' => $validator->errors()
            ]);
        }

        $productRating = new ProductRating;
        $productRating->product_id = $id;
        $productRating->username = $request->name;
        $productRating->email = $request->email;
        $productRating->comment = $request->comment;
        $productRating->rating = $request->rating;
        $productRating->status = 0;
        $productRating->save();

        session()->flash('success', 'Thanks for your rating');
            return response()->json([
                'status' => true,
                'message' => 'Thanks for your rating.'
            ]);
    }
}

