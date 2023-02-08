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
