<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $products = Product::all();
        return view('product.index', compact('products'));
    }

    public function checkout()
    {
        $stripe = new \Stripe\StripeClient(env('STRIPE_SECRET_KEY'));
        $products = Product::all();
        $lineItems = [];
        foreach($products as $product){
            $lineItems[]=[
                'price_data'=> 'usd',
                'product_data'=>[
                    'name' => $product->name,
                    'images' => $product->image
                ],
                'unit_amount'=>$product->price *100,
            ],
                'quantity'=>1,
        }
        $session = $stripe->checkout->sessions->create([
        'line_items' => [[
            'price_data' => [
            'currency' => 'usd',
            'product_data' => [
                'name' => 'T-shirt',
            ],
            'unit_amount' => 2000,
            ],
            'quantity' => 1,
        ]],
        'mode' => 'payment',
        'success_url' => 'http://localhost:4242/success',
        'cancel_url' => 'http://localhost:4242/cancel',
        ]);
        return redirect($session->url);
    }
}
