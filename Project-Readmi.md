# Stripe Laravel Project
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
- 

