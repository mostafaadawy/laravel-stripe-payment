<?php

namespace App\Http\Controllers;

use App\Models\Order;
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
        $totalPrice=0;
        foreach($products as $product){
            $totalPrice += $product->price;
            $lineItems[] = [
                'price_data' => [
                  'currency' => 'usd',
                  'product_data' => [
                    'name' => $product->name,
                    'images' => [$product->image],
                  ],
                  'unit_amount' => $product->amount *100,
                ],
                'quantity' => $product->price,
            ];
        }
        $session = $stripe->checkout->sessions->create([
        'line_items' => $lineItems,
        'mode' => 'payment',
        'success_url' => route('checkout.success',[],true),
        'cancel_url' => route('checkout.cancel',[],true),
        ]);
        $order = new Order();
        $order->status='unpaid';
        $order->total_price= $totalPrice;
        $order->session_id= $totalPrice;
        $order->status='unpaid';
        $order->save();

        return redirect($session->url);
    }
    public function success()
    {

    }
    public function Cancel()
    {

    }

}
