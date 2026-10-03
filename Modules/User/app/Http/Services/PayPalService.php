<?php

namespace Modules\User\app\Http\Services;
use Illuminate\Support\Facades\Http;
use GuzzleHttp\Client;

use Modules\User\app\Models\User;
use Modules\User\app\Models\SubscriptionPlan;

class PayPalService
{
    protected $client;
    protected $baseUri;
    protected $clientId;
    protected $secret;

    public function __construct()
    {
        $this->clientId = 'AddRPbxWpS6RkmpYG-PTY8myweXO8Ya8XAu7aiwm3qd2-3bE3L-s9GeAtnvp-XPmewpKI9lV5sXSneT0';//config('paypal.client_id');
        $this->secret = 'EAKeMpoY-GILRc-x9CqFV-xxYnyuq7cMnYHw1cpYlq211h9KQXPi0ulNSKoQujfOaq0IF0yvizbbr5rc'; //config('paypal.secret');
        $this->baseUrl = config('paypal.mode') === 'live'
            ? 'https://api.paypal.com'
            : 'https://api-m.sandbox.paypal.com';

        $this->client = new Client();
    }

    private function getAccessToken()
    {
        $response = Http::asForm()
            ->withBasicAuth($this->clientId, $this->secret)
            ->post($this->baseUrl . '/v1/oauth2/token', [
                'grant_type' => 'client_credentials',
            ]);

        if (!$response->successful()) {
            throw new \Exception('Failed to get PayPal access token: ' . $response->body());
        }

        return $response->json()['access_token'];
    }
	
	
	public function updatePayPalPlan($planId, $newPrice, $currency = 'USD')
	{
		$token = $this->getAccessToken();

		$response = Http::withToken($token)
			->patch($this->baseUrl . "/v1/billing/plans/{$planId}", [
				[
					'op' => 'replace',
					'path' => '/billing_cycles/0/pricing_scheme/fixed_price',
					'value' => [
						'value' => $newPrice,
						'currency_code' => $currency,
					],
				]
			]);

		if (!$response->successful()) {
			throw new \Exception('Failed to update plan: ' . $response->body());
		}

		return $response->json();
	}


	public function createPayPalPlan($productName, $productDescription, $planName, $price, $currency)
    {
        $token = $this->getAccessToken();

        // 1. Create Product
        $productResponse = Http::withToken($token)
            ->post($this->baseUrl . '/v1/catalogs/products', [
                'name' => $productName,
                'description' => $productDescription,
                'type' => 'SERVICE',
                'category' => 'SOFTWARE',
            ]);

        if (!$productResponse->successful()) {
            throw new \Exception('Failed to create product: ' . $productResponse->body());
        }

        $productId = $productResponse->json()['id'];

        // 2. Create Plan
        $planResponse = Http::withToken($token)
            ->post($this->baseUrl . '/v1/billing/plans', [
                'product_id' => $productId,
                'name' => $planName,
                'status' => 'ACTIVE',
                'billing_cycles' => [
                    [
                        'frequency' => [
                            'interval_unit' => 'MONTH',
                            'interval_count' => 1
                        ],
                        'tenure_type' => 'REGULAR',
                        'sequence' => 1,
                        'total_cycles' => 0, // 0 = infinite
                        'pricing_scheme' => [
                            'fixed_price' => [
                                'value' => $price,
                                'currency_code' => $currency
                            ]
                        ]
                    ]
                ],
                'payment_preferences' => [
                    'auto_bill_outstanding' => true,
                    'setup_fee_failure_action' => 'CONTINUE',
                    'payment_failure_threshold' => 3
                ]
            ]);

        if (!$planResponse->successful()) {
            throw new \Exception('Failed to create plan: ' . $planResponse->body());
        }

        $planId = $planResponse->json()['id'];

        // 3. Create Subscription to get Approval Link
        $subscriptionResponse = Http::withToken($token)
            ->post($this->baseUrl . '/v1/billing/subscriptions', [
                'plan_id' => $planId,
                'application_context' => [
                    'brand_name' => 'Your Company Name',
                    'locale' => 'en-US',
                    'return_url' => route('user.paypal.success'),
                    'cancel_url' => route('user.paypal.cancel'),
                ]
            ]);

        if (!$subscriptionResponse->successful()) {
            throw new \Exception('Failed to create subscription: ' . $subscriptionResponse->body());
        }

        $subscriptionData = $subscriptionResponse->json();

        return [
            'product_id' => $productId,
            'plan_id' => $planId,
            'subscription_id' => $subscriptionData['id'],
            'approval_link' => collect($subscriptionData['links'])
                ->firstWhere('rel', 'approve')['href']
        ];
    }
	
    public function createSubscription($planId)
	{
		$token = $this->getAccessToken();

		$response = Http::withToken($token)->post($this->baseUrl . '/v1/billing/subscriptions', [
			'plan_id' => $planId,
			'application_context' => [
				'brand_name' => 'YourCompany',
				'locale' => 'en-US',
				'return_url' => route('user.paypal.success'),
				'cancel_url' => route('user.paypal.cancel'),
			]
		]);

		if (!$response->successful()) {
			throw new \Exception('Failed to create subscription: ' . $response->body());
		}

		return $response->json();
	}
	
	public function getSubscriptionDetails($subscriptionId)
	{
		$token = $this->getAccessToken();

		$response = Http::withToken($token)
			->get("{$this->baseUrl}/v1/billing/subscriptions/{$subscriptionId}");

		if (!$response->successful()) {
			throw new \Exception('Failed to get subscription details: ' . $response->body());
		}

		return $response->json();
	}

	
	public function cancelSubscription($subscriptionId, $reason = 'User requested cancellation.')
	{
		$token = $this->getAccessToken();

		$response = Http::withToken($token)->post("{$this->baseUrl}/v1/billing/subscriptions/{$subscriptionId}/cancel", [
			'reason' => $reason
		]);

		if ($response->status() === 204) {
			// 204 No Content means successful cancellation
			return true;
		}

		throw new \Exception('Failed to cancel subscription: ' . $response->body());
	}

	
	
	public function createProduct($name, $description, $type, $category)
	{
		$token = $this->getAccessToken();

		$response = $this->client->post($this->baseUri . '/v1/catalogs/products', [
			'headers' => [
				'Authorization' => "Bearer {$token}",
				'Content-Type'  => 'application/json',
			],
			'json' => [
				'name'        => $name,
				'description' => $description,
				'type'        => $type,
				'category'    => $category,
			],
		]);

		return json_decode($response->getBody(), true);
	}
	

}
