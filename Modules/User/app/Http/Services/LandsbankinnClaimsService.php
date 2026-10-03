<?php

namespace Modules\User\app\Http\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LandsbankinnClaimsService
{
    protected $baseUrl;
    protected $consumerKey;
    protected $consumerSecret;
    
    public function __construct()
    {
        $this->baseUrl = env('LANDSBANKINN_API_URL', 'https://apisandbox.landsbankinn.is/api/Claims');
        $this->consumerKey = env('LANDSBANKINN_CONSUMER_KEY', env('LANDSBANKINN_API_KEY'));
        $this->consumerSecret = env('LANDSBANKINN_CONSUMER_SECRET', env('LANDSBANKINN_CLIENT_ID'));
    }

    /**
     * Get HTTP client with Basic Authentication
     * Landsbankinn uses Basic Auth with Consumer Key/Secret
     */
   protected function getHttpClient()
{
    return Http::withHeaders([
            'apikey' => $this->consumerKey, // Landsbankinn expects this header
            'secret' => $this->consumerSecret, // Optional; may not be required
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])
        ->timeout(30);
}


    /**
     * Get claimant accesses
     */
    public function getClaimantAccesses()
    {
        try {
            $response = $this->getHttpClient()
                ->get("{$this->baseUrl}/ClaimTemplates/v1/ClaimantAccesses");

            Log::info('Landsbankinn API - GetClaimantAccesses', [
                'url' => "{$this->baseUrl}/ClaimTemplates/v1/ClaimantAccesses",
                'status' => $response->status(),
                'response' => $response->json()
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            return [
                'success' => false,
                'message' => 'Failed to get claimant accesses',
                'status_code' => $response->status(),
                'error' => $response->json()
            ];
        } catch (\Exception $e) {
            Log::error('Landsbankinn API Error - GetClaimantAccesses', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Get claim templates
     */
    public function getClaimTemplates($claimantNationalId = null, $status = ['active'])
    {
        try {
            $query = [
                'status' => $status,
            ];

            if ($claimantNationalId) {
                $query['claimantNationalId'] = [$claimantNationalId];
            }

            $response = $this->getHttpClient()
                ->get("{$this->baseUrl}/ClaimTemplates/v1/ClaimTemplates", $query);

            Log::info('Landsbankinn API - GetClaimTemplates', [
                'url' => "{$this->baseUrl}/ClaimTemplates/v1/ClaimTemplates",
                'query' => $query,
                'status' => $response->status(),
                'response' => $response->json()
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            return [
                'success' => false,
                'message' => 'Failed to get claim templates',
                'status_code' => $response->status(),
                'error' => $response->json()
            ];
        } catch (\Exception $e) {
            Log::error('Landsbankinn API Error - GetClaimTemplates', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Get specific claim template by ID
     */
    public function getClaimTemplate($templateId)
    {
        try {
            $response = $this->getHttpClient()
                ->get("{$this->baseUrl}/ClaimTemplates/v1/ClaimTemplates/{$templateId}");

            Log::info('Landsbankinn API - GetClaimTemplate', [
                'template_id' => $templateId,
                'status' => $response->status(),
                'response' => $response->json()
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            return [
                'success' => false,
                'message' => 'Failed to get claim template',
                'status_code' => $response->status(),
                'error' => $response->json()
            ];
        } catch (\Exception $e) {
            Log::error('Landsbankinn API Error - GetClaimTemplate', [
                'template_id' => $templateId,
                'error' => $e->getMessage()
            ]);
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Create a new claim template
     */
    public function createClaimTemplate($data)
    {
        try {
            $response = $this->getHttpClient()
                ->post("{$this->baseUrl}/ClaimTemplates/v1/ClaimTemplates", $data);

            Log::info('Landsbankinn API - CreateClaimTemplate', [
                'data' => $data,
                'status' => $response->status(),
                'response' => $response->json()
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            return [
                'success' => false,
                'message' => 'Failed to create claim template',
                'status_code' => $response->status(),
                'error' => $response->json()
            ];
        } catch (\Exception $e) {
            Log::error('Landsbankinn API Error - CreateClaimTemplate', [
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Update an existing claim template
     */
    public function updateClaimTemplate($templateId, $data)
    {
        try {
            $response = $this->getHttpClient()
                ->put("{$this->baseUrl}/ClaimTemplates/v1/ClaimTemplates/{$templateId}", $data);

            Log::info('Landsbankinn API - UpdateClaimTemplate', [
                'template_id' => $templateId,
                'data' => $data,
                'status' => $response->status(),
                'response' => $response->json()
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            return [
                'success' => false,
                'message' => 'Failed to update claim template',
                'status_code' => $response->status(),
                'error' => $response->json()
            ];
        } catch (\Exception $e) {
            Log::error('Landsbankinn API Error - UpdateClaimTemplate', [
                'template_id' => $templateId,
                'error' => $e->getMessage()
            ]);
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Create a claim for an invoice
     */
    public function createClaimForInvoice($invoice, $claimTemplateId, $additionalData = [])
    {
        try {
            // Prepare claim data based on invoice
            $claimData = [
                'claimTemplateId' => $claimTemplateId,
                'debtorNationalId' => $invoice->ssn,
                'debtorName' => $invoice->customer_name ?? $invoice->customer->full_name ?? 'Unknown',
                'debtorEmail' => $invoice->customer_email,
                'principal' => (float) $invoice->total_amount_incl_vat,
                'currency' => $invoice->currency,
                'dueDate' => $invoice->due_date ? $invoice->due_date->format('Y-m-d') : now()->addDays(30)->format('Y-m-d'),
                'referenceNumber' => (string) $invoice->invoice_no,
                'description' => $invoice->regarding_job ?? "Invoice #{$invoice->invoice_no}",
                'claimKey' => "INV-{$invoice->id}-" . time(),
            ];

            // Add optional fields if they exist
            if ($invoice->deadline) {
                $claimData['deadline'] = $invoice->deadline->format('Y-m-d');
            }

            // Merge with additional data if provided
            $claimData = array_merge($claimData, $additionalData);

            Log::info('Landsbankinn API - Submitting Claim', [
                'invoice_id' => $invoice->id,
                'claim_data' => $claimData,
                'url' => "{$this->baseUrl}/Claims/v1/Claims"
            ]);

            $response = $this->getHttpClient()
                ->post("{$this->baseUrl}/Claims/v1/Claims", $claimData);

            Log::info('Landsbankinn API - Create Claim Response', [
                'invoice_id' => $invoice->id,
                'status' => $response->status(),
                'response_body' => $response->body(),
                'response_json' => $response->json()
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            return [
                'success' => false,
                'message' => 'Failed to create claim',
                'status_code' => $response->status(),
                'error' => $response->json(),
                'response_body' => $response->body()
            ];
        } catch (\Exception $e) {
            Log::error('Landsbankinn API Error - CreateClaim Exception', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Get claim by ID
     */
    public function getClaim($claimId)
    {
        try {
            $response = $this->getHttpClient()
                ->get("{$this->baseUrl}/Claims/v1/Claims/{$claimId}");

            Log::info('Landsbankinn API - GetClaim', [
                'claim_id' => $claimId,
                'status' => $response->status(),
                'response' => $response->json()
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            return [
                'success' => false,
                'message' => 'Failed to get claim',
                'status_code' => $response->status(),
                'error' => $response->json()
            ];
        } catch (\Exception $e) {
            Log::error('Landsbankinn API Error - GetClaim', [
                'claim_id' => $claimId,
                'error' => $e->getMessage()
            ]);
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Get claims list with filters
     */
    public function getClaims($filters = [])
    {
        try {
            $response = $this->getHttpClient()
                ->get("{$this->baseUrl}/Claims/v1/Claims", $filters);

            Log::info('Landsbankinn API - GetClaims', [
                'filters' => $filters,
                'status' => $response->status(),
                'response' => $response->json()
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            return [
                'success' => false,
                'message' => 'Failed to get claims',
                'status_code' => $response->status(),
                'error' => $response->json()
            ];
        } catch (\Exception $e) {
            Log::error('Landsbankinn API Error - GetClaims', [
                'filters' => $filters,
                'error' => $e->getMessage()
            ]);
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Cancel a claim
     */
    public function cancelClaim($claimId)
    {
        try {
            $response = $this->getHttpClient()
                ->delete("{$this->baseUrl}/Claims/v1/Claims/{$claimId}");

            Log::info('Landsbankinn API - CancelClaim', [
                'claim_id' => $claimId,
                'status' => $response->status(),
                'response' => $response->json()
            ]);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            return [
                'success' => false,
                'message' => 'Failed to cancel claim',
                'status_code' => $response->status(),
                'error' => $response->json()
            ];
        } catch (\Exception $e) {
            Log::error('Landsbankinn API Error - CancelClaim', [
                'claim_id' => $claimId,
                'error' => $e->getMessage()
            ]);
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
}