<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use Exception;
use Illuminate\Database\Query\Expression;
use Illuminate\Http\Request;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Throw_;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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
            'customer_creation'=> 'always',
            'success_url' => route('checkout.success',[],true).'?session_id={CHECKOUT_SESSION_ID}',
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
        try{
            $session_id= $request->get('session_id');
            $stripe = new \Stripe\StripeClient(env('STRIPE_SECRET_KEY'));
            $session = $stripe->checkout->sessions->retrieve($session_id);
            if(!$session) throw new NotFoundHttpException();
            $customer = $stripe->customers->retrieve($session->customer);
            $order = Order::where('session_id', $session->id)->first();
            echo '<pre>';
            var_dump($order);
            echo '</pre>';
        }
       catch(Expression $e){
            throw new NotFoundHttpException();
       }




        return view('product.checkout-success', compact('customer'));

    }
    public function Cancel()
    {

    }

}
