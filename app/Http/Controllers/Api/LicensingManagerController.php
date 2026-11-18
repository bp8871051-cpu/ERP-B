<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SoftwareLicense;
use App\Models\LicenseAllocation;
use App\Models\User;
use App\Models\Membership;
use App\Models\Customer;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class LicensingManagerController extends Controller
{
    /**
     * Get Licensing Manager Dashboard Overview Data
     */
    public function dashboard(Request $request)
    {
        $companyId = $request->user()->company_id ?: 1;
        $today = Carbon::today();
        $thirtyDaysFromNow = Carbon::today()->addDays(30);

        // Seed some demo records if table is completely empty
        if (SoftwareLicense::where('company_id', $companyId)->count() === 0) {
            $this->seedInitialData($companyId);
        }

        $allLicenses = SoftwareLicense::where('company_id', $companyId)->get();

        $totalLicenses = $allLicenses->count();
        $activeLicenses = $allLicenses->where('status', 'active')->count();
        $expiredLicenses = $allLicenses->where('status', 'expired')->count();
        $expiringSoon = $allLicenses->filter(function ($lic) use ($today, $thirtyDaysFromNow) {
            return $lic->status === 'active' && $lic->expiry_date && $lic->expiry_date >= $today && $lic->expiry_date <= $thirtyDaysFromNow;
        })->count();

        $totalSeats = $allLicenses->sum('seats_total');
        $allocatedSeats = $allLicenses->sum('seats_allocated');
        $utilizationRate = $totalSeats > 0 ? round(($allocatedSeats / $totalSeats) * 100, 1) : 0;

        $totalCost = $allLicenses->sum('cost');

        // Recent licenses with allocations
        $recentLicenses = SoftwareLicense::where('company_id', $companyId)
            ->with('allocations')
            ->orderBy('created_at', 'desc')
            ->take(15)
            ->get();

        // Expiring Soon List
        $expiringLicenses = SoftwareLicense::where('company_id', $companyId)
            ->where('status', 'active')
            ->whereNotNull('expiry_date')
            ->whereBetween('expiry_date', [$today, $thirtyDaysFromNow])
            ->orderBy('expiry_date', 'asc')
            ->take(8)
            ->get();

        // Customer & Membership insights
        $activeMemberships = Membership::where('status', 'active')->count();

        return response()->json([
            'status' => 'success',
            'data' => [
                'kpis' => [
                    'total_licenses' => $totalLicenses,
                    'active_licenses' => $activeLicenses,
                    'expiring_soon' => $expiringSoon,
                    'expired_licenses' => $expiredLicenses,
                    'total_seats' => $totalSeats,
                    'allocated_seats' => $allocatedSeats,
                    'available_seats' => max(0, $totalSeats - $allocatedSeats),
                    'utilization_rate' => $utilizationRate,
                    'total_annual_cost' => $totalCost,
                    'active_memberships' => $activeMemberships,
                ],
                'licenses' => $recentLicenses,
                'expiring_licenses' => $expiringLicenses,
            ],
        ]);
    }

    /**
     * Create a new software/client license
     */
    public function storeLicense(Request $request)
    {
        $request->validate([
            'software_name' => 'required|string|max:255',
            'license_type' => 'required|string|in:subscription,perpetual,trial,seat_based',
            'seats_total' => 'required|integer|min:1',
            'issued_date' => 'required|date',
            'expiry_date' => 'nullable|date',
            'cost' => 'nullable|numeric|min:0',
            'vendor' => 'nullable|string|max:255',
            'customer_name' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $companyId = $request->user()->company_id ?: 1;

        $key = 'LIC-' . strtoupper(Str::random(4)) . '-' . strtoupper(Str::random(4)) . '-' . strtoupper(Str::random(4));

        $license = SoftwareLicense::create([
            'company_id' => $companyId,
            'license_key' => $key,
            'software_name' => $request->software_name,
            'license_type' => $request->license_type,
            'customer_name' => $request->customer_name,
            'seats_total' => $request->seats_total,
            'seats_allocated' => 0,
            'issued_date' => $request->issued_date,
            'expiry_date' => $request->expiry_date,
            'status' => 'active',
            'cost' => $request->cost ?: 0,
            'vendor' => $request->vendor,
            'notes' => $request->notes,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Software license registered successfully',
            'data' => $license,
        ], 201);
    }

    /**
     * Allocate a seat/device to a license
     */
    public function allocateSeat(Request $request)
    {
        $request->validate([
            'license_id' => 'required|exists:software_licenses,id',
            'assigned_name' => 'required|string|max:255',
            'department' => 'nullable|string|max:255',
            'device_id' => 'nullable|string|max:255',
        ]);

        $license = SoftwareLicense::findOrFail($request->license_id);

        if ($license->seats_allocated >= $license->seats_total) {
            return response()->json([
                'status' => 'error',
                'message' => 'All available seats for this license have been allocated.',
            ], 422);
        }

        $allocation = LicenseAllocation::create([
            'license_id' => $license->id,
            'assigned_name' => $request->assigned_name,
            'department' => $request->department,
            'device_id' => $request->device_id ?: 'DEV-' . strtoupper(Str::random(6)),
            'ip_address' => $request->ip(),
            'assigned_date' => Carbon::today(),
            'status' => 'active',
        ]);

        $license->increment('seats_allocated');

        return response()->json([
            'status' => 'success',
            'message' => 'Seat allocated successfully',
            'data' => $allocation,
        ]);
    }

    /**
     * Renew an existing license
     */
    public function renewLicense(Request $request, $id)
    {
        $request->validate([
            'new_expiry_date' => 'required|date|after:today',
            'renewal_cost' => 'nullable|numeric|min:0',
        ]);

        $license = SoftwareLicense::findOrFail($id);
        $license->expiry_date = $request->new_expiry_date;
        $license->status = 'active';
        if ($request->renewal_cost) {
            $license->cost = $request->renewal_cost;
        }
        $license->save();

        return response()->json([
            'status' => 'success',
            'message' => 'License renewed successfully until ' . $request->new_expiry_date,
            'data' => $license,
        ]);
    }

    /**
     * Revoke or change license status
     */
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:active,expired,suspended,pending_renewal',
        ]);

        $license = SoftwareLicense::findOrFail($id);
        $license->status = $request->status;
        $license->save();

        return response()->json([
            'status' => 'success',
            'message' => 'License status updated to ' . $request->status,
            'data' => $license,
        ]);
    }

    private function seedInitialData($companyId)
    {
        $items = [
            [
                'company_id' => $companyId,
                'license_key' => 'FALCON-ENT-8849-XP91',
                'software_name' => 'Falcon ERP Enterprise Core',
                'license_type' => 'subscription',
                'customer_name' => 'Zocod Infotech Pvt Ltd',
                'seats_total' => 50,
                'seats_allocated' => 38,
                'issued_date' => Carbon::now()->subMonths(6),
                'expiry_date' => Carbon::now()->addMonths(6),
                'status' => 'active',
                'cost' => 150000.00,
                'vendor' => 'Falcon Softwares Ltd',
                'notes' => 'Primary ERP server license with high-availability clustering.',
            ],
            [
                'company_id' => $companyId,
                'license_key' => 'MS365-BUS-PREM-2026',
                'software_name' => 'Microsoft 365 Business Premium',
                'license_type' => 'seat_based',
                'customer_name' => 'Internal Workforce',
                'seats_total' => 60,
                'seats_allocated' => 54,
                'issued_date' => Carbon::now()->subMonths(11),
                'expiry_date' => Carbon::now()->addDays(14), // Expiring soon!
                'status' => 'active',
                'cost' => 84000.00,
                'vendor' => 'Microsoft Corporation',
                'notes' => 'Office apps, Exchange, Teams & Intune endpoint management.',
            ],
            [
                'company_id' => $companyId,
                'license_key' => 'TALLY-GOLD-M9283-IND',
                'software_name' => 'Tally Prime Server Multi-User',
                'license_type' => 'perpetual',
                'customer_name' => 'Finance & Accounts Department',
                'seats_total' => 10,
                'seats_allocated' => 8,
                'issued_date' => Carbon::now()->subYears(2),
                'expiry_date' => null,
                'status' => 'active',
                'cost' => 54000.00,
                'vendor' => 'Tally Solutions',
                'notes' => 'Perpetual unlimited license with active TSS subscription.',
            ],
            [
                'company_id' => $companyId,
                'license_key' => 'AUTODESK-CAD-ENG-552',
                'software_name' => 'AutoCAD Architecture & Plant 3D',
                'license_type' => 'subscription',
                'customer_name' => 'Engineering & Projects Dept',
                'seats_total' => 15,
                'seats_allocated' => 12,
                'issued_date' => Carbon::now()->subMonths(10),
                'expiry_date' => Carbon::now()->addDays(22), // Expiring soon!
                'status' => 'active',
                'cost' => 220000.00,
                'vendor' => 'Autodesk Inc',
                'notes' => 'Project engineering 3D modeling and layout designs.',
            ],
            [
                'company_id' => $companyId,
                'license_key' => 'SSL-WILDCARD-SECT-26',
                'software_name' => 'Sectigo Wildcard SSL Certificate',
                'license_type' => 'subscription',
                'customer_name' => 'Cloud IT Infrastructure',
                'seats_total' => 1,
                'seats_allocated' => 1,
                'issued_date' => Carbon::now()->subMonths(11)->subDays(20),
                'expiry_date' => Carbon::now()->addDays(10), // Expiring soon!
                'status' => 'active',
                'cost' => 12500.00,
                'vendor' => 'Sectigo CA',
                'notes' => 'HTTPS encryption certificate for *.falconerp.com domain.',
            ],
            [
                'company_id' => $companyId,
                'license_key' => 'AWS-DEV-SUPPORT-882',
                'software_name' => 'AWS Business Support Plan',
                'license_type' => 'subscription',
                'customer_name' => 'Cloud Hosting Team',
                'seats_total' => 5,
                'seats_allocated' => 5,
                'issued_date' => Carbon::now()->subYear(),
                'expiry_date' => Carbon::now()->subDays(5), // Expired
                'status' => 'expired',
                'cost' => 45000.00,
                'vendor' => 'Amazon Web Services',
                'notes' => 'Requires PO approval for renewal.',
            ],
        ];

        foreach ($items as $item) {
            $lic = SoftwareLicense::create($item);

            // Seed sample allocation
            if ($lic->seats_allocated > 0) {
                for ($i = 1; $i <= min(3, $lic->seats_allocated); $i++) {
                    LicenseAllocation::create([
                        'license_id' => $lic->id,
                        'assigned_name' => 'Staff Member #' . $i,
                        'department' => 'Operations & Tech',
                        'device_id' => 'WS-LAP-' . rand(100, 999),
                        'ip_address' => '192.168.1.' . rand(10, 200),
                        'assigned_date' => Carbon::now()->subDays(rand(10, 60)),
                        'status' => 'active',
                    ]);
                }
            }
        }
    }
}

