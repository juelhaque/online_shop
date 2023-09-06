<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class FrontController extends Controller
{
    public function index(){

        $products = Product::where('is_featured', 'Yes')
                            ->orderBy('id', 'ASC')
                            ->take(8)
                            ->where('status', 1)
                            ->get();

        $data['featuredProducts'] = $products;

        $latestProducts = Product::orderBy('id', 'ASC')
                                ->where('status', 1)
                                ->take(8)
                                ->get();

        $data['latestProducts'] = $latestProducts;

        return view('front.home', $data);
    }
}

