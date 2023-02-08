<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use Exception;
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
                  'unit_amount' => $product->price * 100,
                ],
                'quantity' => 1,
            ];
        }

        $session = $stripe->checkout->sessions->create([
            'line_items' => $lineItems,
            'mode' => 'payment',
            'success_url' => route('checkout.success'.'?session_id={CHECKOUT_SESSION_ID}',[],true),
            'cancel_url' => route('checkout.cancel',[],true),
            ]);

        $order = new Order();
        $order->status='unpaid';
        $order->total_price= $totalPrice;
        $order->session_id= $session->id;
        $order->save();

        return redirect($session->url);
    }
    public function success(Request $request)
    {
        $session_id= $request->get('session_id');
        $stripe = new \Stripe\StripeClient(env('STRIPE_SECRET_KEY'));
        try {
            $session = $stripe->checkout->sessions->retrieve($_GET['session_id']);
            $customer = $stripe->customers->retrieve($session->customer);
            echo "<h1>Thanks for your order, $customer->name!</h1>";
            http_response_code(200);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
        }
        $session = $stripe->checkout->sessions->retrieve($_GET['session_id']);
        $customer = $stripe->customers->retrieve($session->customer);


        return view('product.checkout-success', compact('customer'));

    }
    public function Cancel()
    {

    }

}
