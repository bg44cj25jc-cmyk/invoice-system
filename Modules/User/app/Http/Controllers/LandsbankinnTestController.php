<?php

namespace Modules\User\app\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Modules\User\app\Http\Services\LandsbankinnClaimsService;
class LandsbankinnTestController extends Controller
{
    protected $claimsService;

    public function __construct(LandsbankinnClaimsService $claimsService)
    {
        $this->claimsService = $claimsService;
    }

    /**
     * Test API Connection
     */
    public function testConnection(): JsonResponse
    {
        $tests = [];

        // Test 1: Get Claimant Accesses
        $tests['claimant_accesses'] = $this->claimsService->getClaimantAccesses();

        // Test 2: Get Claim Templates
        $claimantId = env('LANDSBANKINN_CLAIMANT_NATIONAL_ID');
        $tests['claim_templates'] = $this->claimsService->getClaimTemplates($claimantId);

        // Test 3: Configuration Check
        $tests['configuration'] = [
            'api_url' => env('LANDSBANKINN_API_URL'),
            'consumer_key_set' => !empty(env('LANDSBANKINN_CONSUMER_KEY')),
            'consumer_secret_set' => !empty(env('LANDSBANKINN_CONSUMER_SECRET')),
            'claimant_id' => $claimantId,
        ];

        return response()->json([
            'status' => true,
            'message' => 'Landsbankinn API Connection Test',
            'tests' => $tests
        ]);
    }

    /**
     * Test Create Claim Template
     */
    public function testCreateTemplate(): JsonResponse
    {
        $templateData = [
            'name' => 'Test Invoice Collection Template - ' . now()->format('Y-m-d H:i:s'),
            'type' => 'primaryCollection',
            'claimantNationalId' => env('LANDSBANKINN_CLAIMANT_NATIONAL_ID', '4710080280'),
            'categoryCode' => '37',
            'depositingAccountBban' => '013326123456',
            'withdrawalAccountBban' => '013326123456',
            'newClaimPresets' => [
                'isPartialPaymentAllowed' => false,
                'paymentSequenceType' => 'claimKey',
                'notifications' => [
                    'sendLatePaymentNotification' => false,
                    'daysUntilLatePaymentNotification' => 10
                ],
                'useCentralBankRateForDefaultInterest' => true
            ]
        ];

        $result = $this->claimsService->createClaimTemplate($templateData);

        return response()->json([
            'status' => $result['success'],
            'message' => $result['success'] ? 'Template created successfully' : 'Failed to create template',
            'data' => $result
        ]);
    }
}