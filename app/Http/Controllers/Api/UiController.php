<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UiDemoRecord;
use App\Models\UiDragDropItem;
use App\Models\UiTablePreference;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;

class UiController extends Controller
{
    /**
     * Get paginated, filtered, sorted data table records
     */
    public function getDataTable(Request $request)
    {
        $query = UiDemoRecord::query();

        // Search
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('role', 'like', "%{$search}%")
                  ->orWhere('department', 'like', "%{$search}%");
            });
        }

        // Status Filter
        if ($status = $request->input('status')) {
            if ($status !== 'all') {
                $query->where('status', $status);
            }
        }

        // Department Filter
        if ($department = $request->input('department')) {
            if ($department !== 'all') {
                $query->where('department', $department);
            }
        }

        // Sorting
        $sortField = $request->input('sort', 'id');
        $sortDir = strtolower($request->input('direction', 'desc')) === 'asc' ? 'asc' : 'desc';
        $allowedSorts = ['id', 'name', 'email', 'role', 'department', 'status', 'salary', 'joined_date', 'created_at'];
        if (in_array($sortField, $allowedSorts)) {
            $query->orderBy($sortField, $sortDir);
        } else {
            $query->orderBy('id', 'desc');
        }

        // CSV / Excel Export Request
        if ($request->input('export') === 'csv') {
            $records = $query->limit(500)->get();
            $csvHeaders = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="data-table-export.csv"',
            ];

            $callback = function () use ($records) {
                $file = fopen('php://output', 'w');
                fputcsv($file, ['ID', 'Name', 'Email', 'Role', 'Department', 'Status', 'Salary', 'Joined Date']);
                foreach ($records as $r) {
                    fputcsv($file, [$r->id, $r->name, $r->email, $r->role, $r->department, $r->status, $r->salary, $r->joined_date]);
                }
                fclose($file);
            };

            return response()->stream($callback, 200, $csvHeaders);
        }

        // Pagination
        $perPage = (int) $request->input('per_page', 10);
        $perPage = max(5, min(100, $perPage));

        $data = $query->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'data' => $data->items(),
            'meta' => [
                'current_page' => $data->currentPage(),
                'last_page' => $data->lastPage(),
                'per_page' => $data->perPage(),
                'total' => $data->total(),
                'from' => $data->firstItem(),
                'to' => $data->lastItem(),
            ],
            'summary' => [
                'total_count' => UiDemoRecord::count(),
                'active_count' => UiDemoRecord::where('status', 'active')->count(),
                'pending_count' => UiDemoRecord::where('status', 'pending')->count(),
                'total_salary' => UiDemoRecord::sum('salary'),
            ]
        ]);
    }

    /**
     * Create new data table record
     */
    public function storeDataRecord(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:ui_demo_records,email',
            'role' => 'nullable|string|max:100',
            'department' => 'nullable|string|max:100',
            'status' => 'nullable|string|in:active,inactive,pending,suspended',
            'salary' => 'nullable|numeric|min:0',
            'joined_date' => 'nullable|date',
        ]);

        $record = UiDemoRecord::create($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Record created successfully',
            'record' => $record,
        ], 201);
    }

    /**
     * Update data table record
     */
    public function updateDataRecord(Request $request, $id)
    {
        $record = UiDemoRecord::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => "sometimes|required|email|unique:ui_demo_records,email,{$id}",
            'role' => 'nullable|string|max:100',
            'department' => 'nullable|string|max:100',
            'status' => 'nullable|string|in:active,inactive,pending,suspended',
            'salary' => 'nullable|numeric|min:0',
            'joined_date' => 'nullable|date',
        ]);

        $record->update($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Record updated successfully',
            'record' => $record,
        ]);
    }

    /**
     * Delete data table record
     */
    public function deleteDataRecord($id)
    {
        $record = UiDemoRecord::findOrFail($id);
        $record->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Record deleted successfully',
        ]);
    }

    /**
     * Bulk delete records
     */
    public function bulkDeleteDataRecords(Request $request)
    {
        $ids = $request->input('ids', []);
        if (!empty($ids)) {
            UiDemoRecord::whereIn('id', $ids)->delete();
        }

        return response()->json([
            'status' => 'success',
            'message' => count($ids) . ' records deleted successfully',
        ]);
    }

    /**
     * Get Dragula Kanban items
     */
    public function getDragItems(Request $request)
    {
        $items = UiDragDropItem::orderBy('order_index', 'asc')->get();

        return response()->json([
            'status' => 'success',
            'items' => $items,
        ]);
    }

    /**
     * Reorder Dragula items (Kanban move)
     */
    public function reorderDragItems(Request $request)
    {
        $items = $request->input('items', []);

        DB::transaction(function () use ($items) {
            foreach ($items as $itemData) {
                if (isset($itemData['id'])) {
                    UiDragDropItem::where('id', $itemData['id'])->update([
                        'status' => $itemData['status'] ?? 'todo',
                        'order_index' => $itemData['order_index'] ?? 0,
                    ]);
                }
            }
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Dragula item order updated successfully',
        ]);
    }

    /**
     * Get Table Preferences
     */
    public function getTablePreferences(Request $request)
    {
        $tableKey = $request->input('table_key', 'ui_data_table');
        $userId = auth()->id() ?? 1;

        $pref = UiTablePreference::firstOrCreate(
            ['user_id' => $userId, 'table_key' => $tableKey],
            [
                'visible_columns' => ['name', 'email', 'role', 'department', 'status', 'salary', 'actions'],
                'density' => 'comfortable',
                'per_page' => 10,
            ]
        );

        return response()->json([
            'status' => 'success',
            'preferences' => $pref,
        ]);
    }

    /**
     * Save Table Preferences
     */
    public function updateTablePreferences(Request $request)
    {
        $tableKey = $request->input('table_key', 'ui_data_table');
        $userId = auth()->id() ?? 1;

        $pref = UiTablePreference::updateOrCreate(
            ['user_id' => $userId, 'table_key' => $tableKey],
            [
                'visible_columns' => $request->input('visible_columns'),
                'density' => $request->input('density', 'comfortable'),
                'per_page' => (int) $request->input('per_page', 10),
            ]
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Table preferences saved',
            'preferences' => $pref,
        ]);
    }
}
