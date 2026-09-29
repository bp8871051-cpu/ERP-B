<?php

namespace App\Http\Controllers\Api\Crm;

use App\Http\Controllers\Controller;
use App\Models\CrmCampaign;
use App\Models\CrmContact;
use App\Models\CrmDeal;
use App\Models\CrmLead;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CrmSearchController extends Controller
{
    public function search(Request $request): JsonResponse
    {
        $query = trim($request->input('q', ''));
        if (strlen($query) < 2) {
            return response()->json([
                'status' => 'success',
                'data' => [
                    'contacts' => [],
                    'leads' => [],
                    'deals' => [],
                    'customers' => [],
                    'campaigns' => [],
                ],
            ]);
        }

        $companyId = $request->header('X-Company-ID') ? (int) $request->header('X-Company-ID') : ($request->user()?->company_id ?? 1);

        // Contacts
        $contacts = CrmContact::where('company_id', $companyId)
            ->where(function ($q) use ($query) {
                $q->where('first_name', 'like', "%{$query}%")
                  ->orWhere('last_name', 'like', "%{$query}%")
                  ->orWhere('email', 'like', "%{$query}%")
                  ->orWhere('company_name', 'like', "%{$query}%");
            })
            ->take(5)
            ->get(['id', 'first_name', 'last_name', 'company_name', 'email', 'phone']);

        // Leads
        $leads = CrmLead::where('company_id', $companyId)
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('company_name', 'like', "%{$query}%")
                  ->orWhere('email', 'like', "%{$query}%");
            })
            ->take(5)
            ->get(['id', 'name', 'company_name', 'status', 'score', 'score_category']);

        // Deals
        $deals = CrmDeal::where('company_id', $companyId)
            ->where('name', 'like', "%{$query}%")
            ->with('stage')
            ->take(5)
            ->get(['id', 'name', 'value', 'currency', 'status', 'stage_id']);

        // Customers
        $customers = Customer::where('company_id', $companyId)
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('company_name', 'like', "%{$query}%")
                  ->orWhere('email', 'like', "%{$query}%");
            })
            ->take(5)
            ->get(['id', 'name', 'company_name', 'email', 'phone', 'status']);

        // Campaigns
        $campaigns = CrmCampaign::where('company_id', $companyId)
            ->where('name', 'like', "%{$query}%")
            ->take(5)
            ->get(['id', 'name', 'type', 'status', 'roi']);

        return response()->json([
            'status' => 'success',
            'data' => [
                'contacts' => $contacts,
                'leads' => $leads,
                'deals' => $deals,
                'customers' => $customers,
                'campaigns' => $campaigns,
            ],
        ]);
    }
}
