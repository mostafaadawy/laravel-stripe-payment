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
            $order = Order::where('session_id', $session->id)
            ->where('status', 'unpaid')
            ->first();
            if(!$order){
                throw new NotFoundHttpException();
            }
            $order->status='paid';
            $order->save();
            // echo '<pre>';
            // var_dump($order);
            // echo '</pre>';
            return view('product.checkout-success', compact('customer'));
        }
       catch(Expression $e){
            throw new NotFoundHttpException();
       }
    }
    public function Cancel()
    {

    }
    public function webhook()
    {
        // webhook.php
        //
        // Use this sample code to handle webhook events in your integration.
        //
        // 1) Paste this code into a new file (webhook.php)
        //
        // 2) Install dependencies
        //   composer require stripe/stripe-php
        //
        // 3) Run the server on http://localhost:8000
        //   php -S localhost:8000
        // This is your Stripe CLI webhook secret for testing your endpoint locally.
        $endpoint_secret = env('STRIPE_WEBHOOK_SECRET');
        $payload = @file_get_contents('php://input');
        $sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];
        $event = null;
        try {
          $event = \Stripe\Webhook::constructEvent(
            $payload, $sig_header, $endpoint_secret
          );
        } catch(\UnexpectedValueException $e) {
            // Invalid payload
            //http_response_code(400);
            //exit();
            return response('', 400);
        } catch(\Stripe\Exception\SignatureVerificationException $e) {
            // Invalid signature
            //http_response_code(400);
            //exit();
            return response('', 400);
        }

        // Handle the event
        switch ($event->type) {
          case 'customer.created':
            $paymentIntent = $event->data->object;
          // ... handle other event types
          case 'charge.succeeded':
            $paymentIntent = $event->data->object;
          // ... handle other event types
          case 'payment_intent.created':
            $paymentIntent = $event->data->object;
          // ... handle other event types
          case 'payment_intent.canceled':
            $paymentIntent = $event->data->object;
          // ... handle other event types
          case 'payment_intent.succeeded':
            $paymentIntent = $event->data->object;
          // ... handle other event types
          case 'checkout.session.completed':
            $paymentIntent = $event->data->object;
          // ... handle other event types
          default:
            echo 'Received unknown event type ' . $event->type;
        }

        return response('');
    }

}
