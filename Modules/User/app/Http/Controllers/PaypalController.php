<?php

namespace Modules\User\app\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

use Modules\User\app\Models\Country;
use Modules\User\app\Models\IcelandPostalCodes;
use Modules\User\app\Models\User;
use Modules\User\app\Models\SubscriptionPlan;
use Modules\User\app\Models\Payment;

use Illuminate\Support\Facades\Mail;
	
use Illuminate\Support\Facades\Auth;

use Modules\User\app\Http\Requests\UserRequest;
use Modules\User\app\Http\Requests\LoginUserRequest;
use Modules\User\app\Http\Requests\UpdatePasswordRequest;
use Modules\User\app\Http\Requests\ChangePasswordRequest;
use Modules\User\app\Http\Services\UserService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str; 
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

use Modules\Clerk\Http\Controllers\SectionController;

use Modules\User\app\Http\Services\PayPalService;

class PaypalController extends Controller
{
    private UserService $userService;
	private PayPalService $payPalService;

    public function __construct(UserService $userService, PayPalService $payPalService)
    {
        $this->userService = $userService;
		$this->payPalService = $payPalService;
    }

    

    /**
     * registration
     *
     * @param UserRequest $request
     * @return JsonResponse
     */
	

	public function updatePayPalPlan(Request $request, PayPalService $paypalService)
	{
		//$paypalService = new Modules\User\app\Http\Services\PayPalService();
		
		$newPrice = $request->newPrice;
		$currency = $request->currency;
		$planId = $request->planId;
		
		$result = $paypalService->updatePayPalPlan(
			$planId,
			$newPrice,
			$currency
		);
		
		return response()->json([
				'status' => true,
				'data' => $result,
			]);

		//return redirect($result['approval_link']);
	}
	
	public function createPayPalPlan(Request $request, PayPalService $paypalService)
	{
		//$paypalService = new Modules\User\app\Http\Services\PayPalService();
		$productName = $request->product_name; 
		$productDescription = $request->product_description; 
		$planName = $request->plan_name;
		$price = $request->price;
		$currency = $request->currency;
		
		$result = $paypalService->createPayPalPlan(
			$productName,
			$productDescription,
			$planName,
			$price,
			$currency
		);
//		$result = $paypalService->createSubscription(
//			'My SaaS Product',
//			'Access to premium features',
//			'Monthly Plan',
//			'9.99',
//			'USD'
//		);
		
		return response()->json([
				'status' => true,
				'data' => $result,
			]);

		//return redirect($result['approval_link']);
	}
	
	public function createSubscription(Request $request, PayPalService $paypalService)
	{
		$language = $request->header('Accept-Language', 'en');
		  $validator = Validator::make($request->all(), [
				'plan_name' => 'required|string',
			], [
				'plan_name.required' => __t('plan_name_required', $language),
			]);

			if ($validator->fails()) {
				return response()->json([
					'status' => false,
					'errors' => $validator->errors()->first(),
				], 422);
			}
		 
		
		$getplan = SubscriptionPlan::select()->where('name', $request->plan_name)->get()->toArray();
		//echo "<pre>";print_r($getplan);
		//echo $getplan[0]['plan_id'];exit;
		if (!$getplan) {
			return response()->json([
				'status' => false,
				'message' => __t('plannotfound', $language),
			]);
		}

		try {
			$subscription = $paypalService->createSubscription($getplan[0]['plan_id']);
			return response()->json([
				'status' => true,
				'approval_url' => collect($subscription['links'])->firstWhere('rel', 'approve')['href'],
				'subscription_id' => $subscription['id'],
			]);
		} catch (\Exception $e) {
			return response()->json([
				'status' => false,
				'error' => $e->getMessage(),
			], 500);
		}
	}
	
	public function cancelSubscription(Request $request) 
	{
		$language = $request->header('Accept-Language', 'en');
		$user = auth()->user();
		$request->validate([
			'subscription_id' => 'required|string',
			'type' => 'nullable|string',
		]);

		try {
			// Step 1: Get subscription details
			$details = $this->payPalService->getSubscriptionDetails($request->subscription_id);

			$status = strtoupper($details['status']); // e.g. ACTIVE, SUSPENDED, CANCELLED

			if (!in_array($status, ['ACTIVE', 'SUSPENDED'])) {
				return response()->json([
					'status' => false,
					'error' => __t('subscription_not_cancellable', $language, ['status' => $status]),//"Subscription status is {$status}, cannot cancel."
				], 400);
			}

			// Step 2: Cancel if allowed
			$this->payPalService->cancelSubscription($request->subscription_id);

			// Step 3: Update user DB if "end"
			if($request->type =='end'){
				$users = User::find($user->id);
//				$users->active_plan_name = 'Free Plan';
//				$users->active_plan_price = NULL;
//				$users->subscription_id = NULL;
//				$users->active_plan_id = 1;
//				$users->plan_activate_date = NULL;
//				$users->plan_expiry_date = NULL;
				$users->is_cancel_plan = 'yes';
				$users->save();
			}

			return response()->json([
				'status' => true,
				'message' => __t('subscription_cancel_success', $language)
			]);

		} catch (\Exception $e) {
			return response()->json([
				'status' => false,
				'error' => $e->getMessage(),
			], 500);
		}
	}

	public function SelectPlan(Request $request): JsonResponse
    {
		$language = $request->header('Accept-Language', 'en');

        // Validate input
        $validator = Validator::make($request->all(), [
        	'active_plan_id' => 'required|string',
			'subscription_id' => 'nullable|string',
			'order_id' => 'nullable|string',
            'user_id'    => 'required|integer'
        ], [
			'active_plan_id.required' => __t('plan_id_required', $language),
			'active_plan_id.exists'   => __t('plan_not_found', $language),
			'user_id.required'        => __t('user_id_required', $language),
			'user_id.exists'          => __t('user_not_found', $language),
		]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()->first(),
            ]);
        }


        $user = User::find($request->user_id);

        if (!$user) {
            return response()->json([
                'status' => false,
                'errors' => __t('Invalid ID provided', $language),
            ], 404);
        }
		
		
		
		
		$user->active_plan_id = $request->active_plan_id;
			$user->subscription_id = $request->subscription_id;
		$user->plan_activate_date = date("Y-m-d");
		$user->plan_expiry_date = date("Y-m-d", strtotime("+30 days"));
		$user->status = 1;
        
		$getplan = SubscriptionPlan::select()->where('id', $request->active_plan_id)->get()->toArray();
		if($request->active_plan_id!=1){		
			
			
			
			
			$price = $getplan[0]['price']; // already VAT inclusive
			$qty = 1;
			$vatRate = 0.24; // 24%

			$total = $price * $qty; // already VAT included
			$subtotal = $total; // net price (without VAT)
			
			$price_excluding_vat = $total / (1 + $vatRate); // net price (without VAT)
			$vatAmount = $total - $price_excluding_vat; // VAT portion
			
			$country = Country::where('id', $user->country)->first();
			
			$payment_data = new Payment; 
			$payment_data->user_id = $request->user_id;
			$payment_data->active_plan_id = $request->active_plan_id;
			$payment_data->subscription_id = $request->subscription_id;
			$payment_data->order_id = $request->order_id;
			$payment_data->plan_name = $getplan[0]['name'];
			$payment_data->quantity = $qty;
			$payment_data->subtotal = $subtotal;
			$payment_data->vat_rate = $vatRate * 100; // store in %
			$payment_data->vat_amount = $vatAmount;
			$payment_data->price_excluding_vat = $price_excluding_vat;
			$payment_data->total = $total;
			$payment_data->service_start = now();
			$payment_data->service_end = now()->addDays(30); // 1 month plan
			$payment_data->status = 1;
			
			$payment_data->billed_name = $user->name;
			$payment_data->billed_address = $user->address ?? '';
			$payment_data->billed_city = $user->city ?? '';
			$payment_data->billed_country = $country?->name ?? '';
			$payment_data->billed_zip = $user->postal_code ?? '';

			$payment_data->save();
			
			
//			$payment_data = new Payment;
//			$payment_data->user_id = $request->user_id;
//			$payment_data->active_plan_id = $request->active_plan_id;
//			$payment_data->subscription_id = $request->subscription_id;
//			$payment_data->order_id = $request->order_id;
//			$payment_data->plan_name = $getplan[0]['name'];
//			$payment_data->amount = $getplan[0]['price'];
//			$payment_data->status = 1;
//			$payment_data->save();
			
			$user->active_plan_price = $getplan[0]['price'];
			$user->active_plan_name = $getplan[0]['name'];
		}else{
			$user->active_plan_price = $getplan[0]['price'];
			$user->active_plan_name = $getplan[0]['name'];
		}
		
		//if($request->active_plan_id==1){
			//$user->invoices_per_month= $getplan[0]['invoice_count'] ?? 0;
//		}elseif($request->active_plan_id==2){
//			$user->invoices_per_month= $getplan[0]['invoice_count'];
//		}elseif($request->active_plan_id==3){
//			$user->invoices_per_month= $getplan[0]['invoice_count'];
//		}
		$user->invoice_count = $getplan[0]['invoice_count'];
		$user->is_unlimited = $getplan[0]['is_unlimited'];
		$user->save();
		
		$token = $user->createToken('UserToken')->accessToken;
	
	
			$acceptUrl = env('APP_URL');
			$site_url = env('WEB_URL');
		
			// Prepare email
			$to = $user->email;
			$subject = "Welcome to X-US Invoice System! Your ".$getplan[0]['name']." Membership is Confirmed";
				//"Thank you for registering with us.";
			$message = view('email.thank_you_for_registering', [
				'companyName' => $user->company_name,
				'acceptUrl' => $acceptUrl,
				'site_url' => $site_url,
				'plan_name' => $getplan[0]['name']
			])->render();

			$headers = "MIME-Version: 1.0" . "\r\n";
			$headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
			$headers .= "From: X-US Invoice System <no-reply@yourapp.com>" . "\r\n";

			if (!mail($to, $subject, $message, $headers)) {
				return response()->json([
					'status' => false,
					'message' => __t('Employee created but email could not be sent', $language),
					'user' => $user
				], 500);
			}
		
		if ($user && $user->profile_logo) {
			$user->profile_logo = rtrim(env('WEB_URL'), '/') . '/public/uploads/profile_logos/' . $user->profile_logo;
		}
		
		

        return response()->json([
            'status' => true,
			'access_token' => $token,
            'token_type' => 'Bearer',
            'data' => $user, // Corrected response
            'message' => __t('Updated successfully', $language),
        ]);


    }	
	
	public function UpdatePlan(Request $request): JsonResponse
    {
		$language = $request->header('Accept-Language', 'en');
		$user = auth()->user();
        // Validate input
        $validator = Validator::make($request->all(), [
        	'active_plan_id' => 'required|string',
			'subscription_id' => 'nullable|string',
			'order_id' => 'nullable|string',
            //'user_id'    => 'required|integer'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()->first(),
            ]);
        }


        $users = User::find($user->id);

        if (!$users) {
            return response()->json([
                'status' => false,
                'errors' => __t('Invalid ID provided', $language),
            ], 404);
        }
		
		
		
		$users->subscription_id = $request->subscription_id;
		$users->active_plan_id = $request->active_plan_id;
		$users->plan_activate_date = date("Y-m-d");
		$users->plan_expiry_date = date("Y-m-d", strtotime("+30 days"));
		$users->status = 1;
        
		
//		if($request->active_plan_id!=1){		
//			$getplan = SubscriptionPlan::select()->where('id', $request->active_plan_id)->get()->toArray();
//			$payment_data = new Payment;
//			$payment_data->user_id = $user->id;
//			$payment_data->active_plan_id = $request->active_plan_id;
//			$payment_data->subscription_id = $request->subscription_id;
//			$payment_data->order_id = $request->order_id;
//			$payment_data->plan_name = $getplan[0]['name'];
//			$payment_data->amount = $getplan[0]['price'];
//			$payment_data->status = 1;
//			$payment_data->save();
//			
//			$users->active_plan_name = $getplan[0]['name'];
//			$users->active_plan_price = $getplan[0]['price'];
//		}
//		$users->save();
		
		
		
		
		
		
		$getplan = SubscriptionPlan::select()->where('id', $request->active_plan_id)->get()->toArray();
		if($request->active_plan_id!=1){		
			
			$price = $getplan[0]['price']; // already VAT inclusive
			$qty = 1;
			$vatRate = 0.24; // 24%

			$total = $price * $qty; // already VAT included
			$subtotal = $total; // net price (without VAT)
			
			$price_excluding_vat = $total / (1 + $vatRate); // net price (without VAT)
			$vatAmount = $total - $price_excluding_vat; // VAT portion
			
			$country = Country::where('id', $users->country)->first();
			
			$payment_data = new Payment; 
			$payment_data->user_id = $user->id;
			$payment_data->active_plan_id = $request->active_plan_id;
			$payment_data->subscription_id = $request->subscription_id;
			$payment_data->order_id = $request->order_id;
			$payment_data->plan_name = $getplan[0]['name'];
			$payment_data->quantity = $qty;
			$payment_data->subtotal = $subtotal;
			$payment_data->vat_rate = $vatRate * 100; // store in %
			$payment_data->vat_amount = $vatAmount;
			$payment_data->price_excluding_vat = $price_excluding_vat;
			$payment_data->total = $total;
			$payment_data->service_start = now();
			$payment_data->service_end = now()->addDays(30); // 1 month plan
			$payment_data->status = 1;
			
			$payment_data->billed_name = $user->name;
			$payment_data->billed_address = $user->address ?? '';
			$payment_data->billed_city = $user->city ?? '';
			$payment_data->billed_country = $country?->name ?? '';
			$payment_data->billed_zip = $user->postal_code ?? '';
			$payment_data->save();
			
			$users->active_plan_price = $getplan[0]['price'];
			$users->active_plan_name = $getplan[0]['name'];
		}else{
			$users->active_plan_price = $getplan[0]['price'];
			$users->active_plan_name = $getplan[0]['name'];
		}
		
		//$user->invoices_per_month= $getplan[0]['invoice_count'] ?? 0;
		
		$users->is_cancel_plan = null;
		$users->invoice_count = $getplan[0]['invoice_count'];
		$users->is_unlimited = $getplan[0]['is_unlimited'];
		$users->save();
		
		
		
		//$token = $user->createToken('UserToken')->accessToken;

        return response()->json([
            'status' => true,
			//'access_token' => $token,
            //'token_type' => 'Bearer',
            'data' => $users, // Corrected response
            'message' => __t('Updated successfully', $language),
        ]);


    }	
	
	
	
	//cron
	
	public function __cronUpdatePlans(): JsonResponse
	{
    $today = now()->format('Y-m-d');

    // Get users whose plan expired today or earlier
    $expiredUsers = User::whereDate('plan_expiry_date', '<=', $today)
        ->where('status', 1)
        ->get();

    foreach ($expiredUsers as $user) {
        // Skip if user cancelled the plan
        if ($user->is_cancel_plan === 'yes') {
            continue;
        }

        // Get current plan info
        $plan = SubscriptionPlan::find($user->active_plan_id);
        if (!$plan) continue;

        // Update plan dates (renew for 30 days)
        $user->plan_activate_date = now();
        $user->plan_expiry_date = now()->addDays(30);
        $user->status = 1;
        $user->is_cancel_plan = null;
        $user->invoice_count = $plan->invoice_count;
        $user->is_unlimited = $plan->is_unlimited;
        $user->save();

        // Create a new payment record
        $vatRate = 0.24;
        $price = $plan->price;
        $priceExclVat = $price / (1 + $vatRate);
        $vatAmount = $price - $priceExclVat;

        $country = Country::find($user->country);

        $payment = new Payment();
        $payment->user_id = $user->id;
        $payment->active_plan_id = $plan->id;
        $payment->plan_name = $plan->name;
        $payment->subtotal = $price;
        $payment->vat_rate = $vatRate * 100;
        $payment->vat_amount = $vatAmount;
        $payment->price_excluding_vat = $priceExclVat;
        $payment->total = $price;
        $payment->service_start = now();
        $payment->service_end = now()->addDays(30);
        $payment->status = 1;
        $payment->billed_name = $user->company_name;
        $payment->billed_address = $user->address ?? '';
        $payment->billed_city = $user->city ?? '';
        $payment->billed_country = $country?->name ?? '';
        $payment->billed_zip = $user->postal_code ?? '';
        $payment->save();

		
		
		
		$acceptUrl = env('APP_URL');
			$site_url = env('WEB_URL');
		
        // Optional: send email confirmation
        $to = $user->email;
        $subject = "Your {$plan->name} plan renewed successfully!";
        $message = view('email.plan_renewed', [
            'plan_name' => $plan->name,
			'acceptUrl' => $acceptUrl,
			'site_url' => $site_url,
            'expiry_date' => $user->plan_expiry_date->format('Y-m-d')
        ])->render();
			$headers = "MIME-Version: 1.0" . "\r\n";
			$headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
			$headers .= "From: X-US Invoice System <no-reply@yourapp.com>" . "\r\n";
        mail($to, $subject, $message, "Content-type:text/html;charset=UTF-8\r\nFrom: X-US Invoice System <no-reply@yourapp.com>\r\n");
    }

    return response()->json([
        'status' => true,
        'message' => 'Plan renewal cron executed successfully.'
    ]);
}
	
	
	
	public function __cronReminderEmail(): JsonResponse
	{
    $tomorrow = now()->addDay()->format('Y-m-d');

    $users = User::whereDate('plan_expiry_date', $tomorrow)
        ->where('status', 1)
        ->get();

    foreach ($users as $user) {
        $plan = SubscriptionPlan::find($user->active_plan_id);
        if (!$plan) continue;
		
		$acceptUrl = env('APP_URL');
			$site_url = env('WEB_URL');

        $to = $user->email;
        $subject = "Reminder: Your {$plan->name} plan will expire soon";
        $message = view('email.plan_expiry_reminder', [
            'plan_name' => $plan->name,			
			'acceptUrl' => $acceptUrl,
			'site_url' => $site_url,
            'expiry_date' => $user->plan_expiry_date,
        ])->render();
		$headers = "MIME-Version: 1.0" . "\r\n";
		$headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
		$headers .= "From: X-US Invoice System <no-reply@yourapp.com>" . "\r\n";
        mail($to, $subject, $message, "Content-type:text/html;charset=UTF-8\r\nFrom: X-US Invoice System <no-reply@yourapp.com>\r\n");
    }

    return response()->json([
        'status' => true,
        'message' => 'Reminder emails sent successfully.'
    ]);
}

	
	
	
	
	
	
	public function handle(Request $request)
	{//$message = view('email.invoice')->render();
	 //echo 125;exit;
    $event = $request->all();
    
    // Log the webhook event for debugging
    \Log::info('PayPal Webhook Received', ['event_type' => $event['event_type'] ?? 'unknown']);

    //try {
        // Handle subscription activation
        if ($event['event_type'] === 'BILLING.SUBSCRIPTION.ACTIVATED') {
            $subscriptionId = $event['resource']['id'];
            
            $user = User::where('subscription_id', $subscriptionId)->first();
            if ($user) {
                $user->status = 1;
                $user->is_cancel_plan = null;
                $user->save();
            }
        }

        // Handle successful payment completion
        if ($event['event_type'] === 'PAYMENT.SALE.COMPLETED') {
            $subscriptionId = $event['resource']['billing_agreement_id'] ?? null;
            
            if ($subscriptionId) {
                $user = User::where('subscription_id', $subscriptionId)->first();
                
                if ($user && $user->active_plan_id) {
                    // Get plan details
                    $plan = SubscriptionPlan::find($user->active_plan_id);
                    
                    if ($plan) {
                        // Update user subscription dates
                        $user->plan_activate_date = now();
                        $user->plan_expiry_date = now()->addDays(30);
                        $user->status = 1;
                        $user->is_cancel_plan = null;
                        $user->invoice_count = $plan->invoice_count;
                        $user->is_unlimited = $plan->is_unlimited;
                        $user->save();

                        // Calculate VAT and pricing
                        $vatRate = 0.24;
                        $price = $plan->price;
                        $priceExclVat = $price / (1 + $vatRate);
                        $vatAmount = $price - $priceExclVat;

                        $country = Country::find($user->country);

                        // Create payment record
                        $payment = new Payment();
                        $payment->user_id = $user->id;
                        $payment->active_plan_id = $plan->id;
                        $payment->subscription_id = $subscriptionId;
                        $payment->order_id = $event['resource']['id'] ?? null;
                        $payment->plan_name = $plan->name;
                        $payment->quantity = 1;
                        $payment->subtotal = $price;
                        $payment->vat_rate = $vatRate * 100;
                        $payment->vat_amount = $vatAmount;
                        $payment->price_excluding_vat = $priceExclVat;
                        $payment->total = $price;
                        $payment->service_start = now();
                        $payment->service_end = now()->addDays(30);
                        $payment->status = 1;
                        $payment->billed_name = $user->company_name ?? $user->name;
                        $payment->billed_address = $user->address ?? '';
                        $payment->billed_city = $user->city ?? '';
                        $payment->billed_country = $country?->name ?? '';
                        $payment->billed_zip = $user->postal_code ?? '';
                        $payment->save();

						
						$acceptUrl = env('APP_URL');
						$site_url = env('WEB_URL');

						$to = $user->email;//"sumons23up@gmail.com";//
						$subject = "Payment Successful - {$plan->name} Subscription Renewed";
						$message = view('email.payment_success', [
							'companyName' => $user->company_name,
							'plan_name' => $plan->name,
							'acceptUrl' => $acceptUrl,
							'site_url' => $site_url,
							'user_name' => $user->company_name,
							'expiry_date' => $user->plan_expiry_date->format('Y-m-d'),
							'payment_date' => date('Y-m-d')
						])->render();

						$headers = "MIME-Version: 1.0" . "\r\n";
						$headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
						$headers .= "From: X-US Invoice System <no-reply@yourapp.com>" . "\r\n";

						$emailSent = mail($to, $subject, $message, $headers);
						
						
                        // Send confirmation email
                        //$this->sendPaymentSuccessEmail($user, $plan);
                    }
                }
            }
        }

        // Handle subscription cancelled
        if ($event['event_type'] === 'BILLING.SUBSCRIPTION.CANCELLED') {
            $subscriptionId = $event['resource']['id'];
            
            $user = User::where('subscription_id', $subscriptionId)->first();
            if ($user) {
                $user->is_cancel_plan = 'yes';
                $user->save();
            }
        }

        // Handle subscription suspended (payment failure)
        if ($event['event_type'] === 'BILLING.SUBSCRIPTION.SUSPENDED') {
            $subscriptionId = $event['resource']['id'];
            
            $user = User::where('subscription_id', $subscriptionId)->first();
            if ($user) {
                // Optionally update status or send notification
                \Log::warning('Subscription suspended', ['user_id' => $user->id]);
            }
        }

        return response()->json(['message' => 'Webhook processed successfully'], 200);

//    } catch (\Exception $e) {
//        \Log::error('PayPal Webhook Error', [
//            'error' => $e->getMessage(),
//            'event' => $event
//        ]);
//        
//        return response()->json(['message' => 'Webhook processing failed'], 500);
//    }
}

	private function sendPaymentSuccessEmail($user, $plan)
	{
		
		try {
			$acceptUrl = env('APP_URL');
			$site_url = env('WEB_URL');

			$to = "sumons23up@gmail.com";//$user->email;
			$subject = "Payment Successful - {$plan->name} Subscription Renewed";
			$message = view('email.payment_success', [
				'user_name' => $user->company_name,
				'plan_name' => $plan->name,
				'acceptUrl' => $acceptUrl,
				'site_url' => $site_url,
				'expiry_date' => $user->plan_expiry_date->format('Y-m-d')
			])->render();

			$headers = "MIME-Version: 1.0" . "\r\n";
			$headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
			$headers .= "From: X-US Invoice System <no-reply@yourapp.com>" . "\r\n";

			$emailSent = mail($to, $subject, $message, $headers);
		} catch (\Exception $e) {
			\Log::error('Failed to send payment success email', [
				'user_id' => $user->id,
				'error' => $e->getMessage()
			]);
		}
	}
	
//	public function handle(Request $request)
//	{
//		$event = $request->all();
//
//		if ($event['event_type'] === 'BILLING.SUBSCRIPTION.ACTIVATED') {
//			$subscriptionId = $event['resource']['id'];
//			// Update user subscription status in DB
//		}
//
//		return response()->json(['message' => 'Webhook received']);
//	}
	
	

}
