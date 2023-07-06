# Przelewy24 Laravel library

Laravel wrapper for [mnastalski/przelewy24-php](https://github.com/mnastalski/przelewy24-php/).

## Requirements

- PHP >=8.1
- Laravel >=9.0

## Installation

```shell
composer require mnastalski/przelewy24-laravel
```

## Configuration

Add the following to your `.env` file:

```dotenv
PRZELEWY24_MERCHANT_ID=12345
PRZELEWY24_REPORTS_KEY=f0ae...
PRZELEWY24_CRC=aef0...
PRZELEWY24_LIVE=false
```

Setting `PRZELEWY24_LIVE` to `false` will use the [sandbox environment](https://sandbox.przelewy24.pl/panel/). Set it to `true` to use production/live mode.

Pos ID may also be set if necessary:

```dotenv
PRZELEWY24_POS_ID=...
```

## Usage

Here is a simple example of how the package can be used to create a transaction, listen for Przelewy24's webhook and verify the transaction. Dependency injection is used to get an instance of `Przelewy24`:

```php
<?php

use App\Models\Order;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Przelewy24\Enums\Currency;
use Przelewy24\Enums\Language;
use Przelewy24\Exceptions\Przelewy24Exception;
use Przelewy24\Przelewy24;

class MyController
{
    public function __construct(
        private readonly Przelewy24 $przelewy24,
    ) {}

    public function pay(Order $order): RedirectResponse
    {
        // Create a new transaction
        $register = $this->przelewy24->transactions()->register(
            sessionId: $order->id,
            amount: $order->amount,
            description: "Order #{$order->id}",
            email: $order->email,
            urlReturn: route('orders.success'),
            urlStatus: route('orders.webhook'),
            // client: 'Mateusz Nastalski',
            // currency: Currency::EUR,
            // language: Language::ENGLISH,
            // ...
        );

        $order->payment_id = $register->orderId();

        // Redirect to Przelewy24's payment gateway
        return redirect(
            $register->gatewayUrl()
        );
    }

    /**
     * Method for route "orders.success". 
     */
    public function paymentSuccessful(): Response
    {
        return response('Payment successful!');
    }

    /**
     * Method for route "orders.webhook".
     * Must be POST and excluded from CSRF protection.
     */
    public function webhook(Request $request): Response
    {
        // Handle Przelewy24's webhook
        $webhook = $this->przelewy24->handleWebhook(
            $request->post()
        );

        // Find the order by the order ID from the webhook
        $order = Order::find(
            $webhook->orderId()
        );

        // If you would like to verify that the webhook's and its
        // signature are legitimate you may use the following method:
        $isSignValid = $webhook->isSignValid(
            sessionId: $order->id,
            amount: $order->amount,
            originAmount: $order->amount,
            orderId: $webhook->orderId(),
            methodId: $webhook->methodId(),
            statement: "Order #{$order->id}",
            // currency: Currency::EUR,
        );

        if (!$isSignValid) {
            // Handle error ...

            abort(Response::HTTP_BAD_REQUEST);
        }

        // Verify the transaction / claim the payment
        try {
            $this->przelewy24->transactions()->verify(
                $webhook->sessionId(),
                $order->id,
                $order->amount,
            );

            $order->status = 'paid';
            $order->save();
        } catch (Przelewy24Exception) {
            // Handle error ...
        }

        return response()->noContent();
    }
}

```

As this package wraps the [mnastalski/przelewy24-php](https://github.com/mnastalski/przelewy24-php/) package, all methods are the same. For a more in-depth documentation, check its README.
