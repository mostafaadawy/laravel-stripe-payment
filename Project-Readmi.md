# Stripe Laravel Project for more details see the [ref](https://www.youtube.com/watch?v=J13Xe939Bh8)
- stripe process flow begins with client requiring buying somethings it sends to the server with its credentials and required product.
- server connects to stripe gate by a secret key and other object data we get this key from from stripe admin or testing key
- stripe server will create session object and returns to server
- server will make save unpaid order and associate session id 
- server will send session url comes from stripe to the client to redirect the client to payment page.
- or server will use the session url to redirect the user directly
- for both methods it redirects to stripe payment page that contains the user credentials visa or other methods and information
- whenever the user fills out the required data he will be redirected to success page or failure page
- server will send for validating the session from stripe itself 
- stripe will return session object for validation with success or failure to record as payed or pended or refused
# what will happen if user faced and obstacle after filling his card credentials before receiving or redirecting to the status page of success or failure?
- in this case the order will not be remarked as payed 
- to solve this problem we use webhooks 
- webhooks is a dashboard that is used to communicate with stripe, for payment status, we can configure there webhooks
- webhooks is a set of special urls on which we request when ever something happens
- in stripe webhooks we will configure whenever the checkout completes 
- we can make request from the server 
# Implementing this flow with laravel
- create laravel project 
```sh
laravel new projectName
```
- configure .env
```sh
DB_CONNECTION=sqlite
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=laravel_stripe_payment
# DB_USERNAME=root
# DB_PASSWORD=
```
- create new file `database.sqlite` in databse folder
- run migrate `php artisan migrate`
- make two models with its migrations and factory fro Product `php artisan make:model Product -mfsc`
- and Order `php artisan make:model Order -mfsc`

- edit product table
```sh
Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name',255);
            $table->decimal('price',2);
            $table->string('image',124)->nullable();
            $table->timestamps();
        });
```
- edit order table
```sh
Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('status');
            $table->decimal('total_price',6,2);
            $table->string('session_id');
            $table->timestamps();
        });
```
- migrate `php artisan migrate`
- create index in product controller
- create product faker note that it is deferent from laravel 7
```sh
 {
        return [
            'name' => $this->faker->name(),
            'price' => $this->faker->numberBetween(100,1000),
            'image' => $this->faker->imageUrl(),
        ];
    }
```
- edit product seeder
```sh
 public function run()
    {
        Product::factory(3)->create();
    }
```
- create the call to product seeder to be included in `db:seed`
```sh
  public function run()
    {
        // \App\Models\User::factory(10)->create();
        $this->call(ProductSeeder::class);
    }
```
- seed the database `php artisan db:seed`
- error debugging if you got rename namer in product table to name then run `php artisan migrate:fresh`
- run seed the database `php artisan db:seed` again
- create index for product controller
```sh
public function index(Request $request)
    {
        $products = Product::all();
        return view('product.index', compact('products'));
    }
```
- create blade for that index.blade.php and we can use copy from welcome page and edit it where in our application our main focus on the functionality not the styling
```sh
  <body class="antialiased">
        @foreach ($products as $product)
            <div>
                <h2>{{ $product->name }}</h2>
            </div>
        @endforeach
    </body>
```
- note that controller is already created when we make the model `mfcs`
- run the server `php artisan serve`
- edit the route to begins by our page in `web.php`
```sh
Route::get('/', [ProductController::class, 'index']);
```
- edit index to be inside `dev`flex gapped 3rem and draw one line
- add button image and price
```sh
    <body class="antialiased">
        <div style="display: flex; gap:3rem">
            @foreach ($products as $product)
            <div class="flex: 1">
                <img src="{{ $product->image }}" alt="nothing to show", style="max-width:100%">
                <h5>{{ $product->name }}</h5>
                <p>{{ $product->price }}</p>
            </div>
        @endforeach
        </div>

        <p>
            <button>
                CheckOut
            </button>
        </p>
    </body>
```
# begin stripe flow steps 
- step 1 when user press check out button we have to send to server the data of the required products to buy
- for that issue we create submit to checkout 
- with the post url `Route::post('/checkout', [ProductController::class, 'checkout'])->name('checkout');`
- edit index
```sh
            <form action="{{ route('checkout') }}">
                @csrf
                <button>
                    Checkout
                </button>
            </form>
```
- install stripe `composer require stripe/stripe-php`
- to use stripe we have to check the Doc for [stripe accept payment](https://stripe.com/docs/payments/accept-a-payment) 
- begging with installation then adding some code snippets from the documentation for stripe session
- we have to set API key for stripe 
- then we have to create our session
- then redirect to session url
- after creating account on stripe go to dashboard select test key replace the session key
- it is better to add this key in `.env` and call it
- between key and session creation we have to provide the product that we have to buy
- where we have to fill or replace lineItems with our product items 
- the session it self is created from stripe checkout create based our test key public key that we obtain from logged in dashboard from stripe website after we create our stripe account
- check the code
```sh
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
```
- then create to new links with function for success session call and cancel one
- use the links for session success and cancel call back
```sh
    'success_url' => route('checkout.success',[],true),
    'cancel_url' => route('checkout.cancel',[],true),
```
- where `true` is required for sending absolute url where it is link outside the project to stripe
- in success we need to create an order
- so from stripe session creation we need stripe session id to join it with the order
- we create order object ort recode with unpaid status
- debugging needs `method="POST"` in the form in index
- check the code
```sh
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
                  'unit_amount' => $product->amount,
                ],
                'quantity' => $product->price *100,
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
        $order->session_id= $session->id;
        $order->save();

        return redirect($session->url);
    }
```
- error Invalid integer: `Invalid integer: ` when creating session callback error
- install sqlite viewer for vscode tio check tables `SQLite Viewer`
- error comes from two reasons first price `'unit_amount' => $product->price * 100,` must be multiplied by 100 cause stripe not allow float but show cent instead of dollar, second because we were calling `product->amount` column which is not exist, and solution is to create that column and fill it and consider that in price or to fix the amount to `1`
- when we try checkout now it works well
- filling any fake data in card credentials
- for successful test card number use `4242424242424242`
- then it will redirect to the success get link that we specify in session
- but as we can guess here success url is global not specific for certain session id and to avoid this we have to send back session id with success as in [doc for custom success page](https://stripe.com/docs/payments/checkout/custom-success-page) 
- by inserting `'?session_id={CHECKOUT_SESSION_ID}'` to success url in checkout in product controller `'success_url' => route('checkout.success'.'?session_id={CHECKOUT_SESSION_ID}',[],true),`
- and in success callback function extract session id from request
```sh
$session_id= $request->get('session_id');
```
- or we can use the [custom success page doc](https://stripe.com/docs/payments/checkout/custom-success-page) to get info about the custoar  check the code
```sh
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
```
- and in page we can show `$customer->name`
- try the checkout and pay by fake testing any credentials and check the success
# Resource ID Error or stripe error customer is null
- actually resource id is not the problem, but the error is stripe error customer is null
- by tracing the error we found that retrieved session is customer is null
- to solve this error in session creation we have to add `customer_creation: 'always'` as solved in this [link](https://stackoverflow.com/questions/73939920/stripe-payment-session-has-null-customer-field)
- edit the code to be 
```sh
  $session = $stripe->checkout->sessions->create([
            'line_items' => $lineItems,
            'mode' => 'payment',
            'customer_creation'=> 'always',
            'success_url' => route('checkout.success',[],true).'?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('checkout.cancel',[],true),
            ]);
```
- instead of 
```sh
  $session = $stripe->checkout->sessions->create([
            'line_items' => $lineItems,
            'mode' => 'payment',
            'success_url' => route('checkout.success',[],true).'?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => route('checkout.cancel',[],true),
            ]);
```
- at that moment the customer will be created with its info that we can retrieve if we miss this line the session or payment will be registered as guest which cant retrieve a customer although of this we can get its email or som other info check the returned session json in the two cases
- actually i also created manually a customer but it doesn't solve the problem but this line solved it `'customer_creation'=> 'always',`
- note that `424242424242` card number is for test and it always returns success for other testing status check this [link](https://stripe.com/docs/payments/accept-a-payment?platform=web) for deferent card numbers for testing
- to make errors more user friendly we use try and catch
# Now it is a time to change order status from unpaid to paid when receiving accepted payment
- note `return dd($order)` as same as 
```sh
    echo '<pre>';
    var_dump($order);
    echo '</pre>';
```
- deference between dd() and var_dump us var_dump return both normal page pluse dumped data while dd return only dumped 
- increase where for unpaid status
- check for the order
- if ok change order status and save it
- know after this change if we refresh the page it give not found 
# BUT and WebHooks
- from the previous methodology if the link according to any problem is not redirected back it will save that it is successfully paid in stripe while here in the order status it remains unpaid
- here we solve by [webHooks](https://dashboard.stripe.com/test/webhooks)
- webhook will interact with the session then save it and then will return to webhook url link on your site not to customer then our site will notify customer
- first create webhook  post function to receive data fromm stripe 
- create url post route for the hook
- as this is post from other site som `csrf` middleware will refuse this connection so we have to allow this hook by adding it in the exception urls in `VerifyCsrfToken.php` middleware `'webhook'` this will allows us to receive from this webhook
- if we are in production we can link our hook [here](https://dashboard.stripe.com/test/webhooks/create)
- but as we are in localHosting we will do as explained [here](https://dashboard.stripe.com/test/webhooks/create?endpoint_location=local)
- according to this documentation we have to install cli [link](https://github.com/stripe/stripe-cli/releases/tag/v1.13.8)
# Note Stripe credential in private repo StartingUp
- as in documentation in the downloaded and extracted stripe.exe from cmd terminal `./stripe.exe login`
- a link will be generated with to verify its you then you allow access so we link the cli
- now we authorized stripe cli
- we will get this massage in the console `Please note: this key will expire after 90 days, at which point you'll need to re-authenticate.` and that means our token will need this action after 90 day
- now we will start lessening forward to our local
- as we are locale hosted so our stripe webhooks cannot listen  where no ip link is assigned but our stripe cli can listen to our webhooks and forward that to our local project
- now in the root of extracted stripe.exe in command cmd `./stripe listen --forward-to localhost:8000/webhook`to allow stripe cli listen
- do not forget `./` for execution
- let that terminal window open and we will need the secret key that will be generated for that session
- tell this moment we downloaded the cli authentic it then listen and finally we can check it is working using this command `./stripe trigger payment_intent.succeeded` in other terminal in the same stripe place and watch what will be printed in the main listener terminal 
- then we get that replay massage `[200] POST http://localhost:8000/webhook [evt_3MZbptCItBkDkz3L1FquSlT1]` that means that we are doing well
- lets implement the webhook code 
- from the same [link](https://dashboard.stripe.com/test/webhooks/create?endpoint_location=local) we can use the code in the right side but change te language to php or your required language
- after copying the code remove `require autoload` and change the endpoint secret key with your session key in the remained opened terminal `whsec_9bd02e531f9f202bd73cac39a9902725782d452f429651371f14974ed185e04e`
