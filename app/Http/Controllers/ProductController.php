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
            $lineItems[] = [
                'price_data' => [
                  'currency' => 'usd',
                  'product_data' => [
                    'name' => $product->name,
                    'images' => [$product->image],
                  ],
                  'unit_amount' => $product->amount,
                ],
                'quantity' => $product->price,
            ];
        }
        $session = $stripe->checkout->sessions->create([
        'line_items' => $lineItems,
        'mode' => 'payment',
        'success_url' => 'http://localhost:4242/success',
        'cancel_url' => 'http://localhost:4242/cancel',
        ]);
        return redirect($session->url);
    }
}
