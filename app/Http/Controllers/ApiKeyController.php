<?php

namespace App\Http\Controllers;

use App\Http\Resources\ApiKeyResource;
use App\Models\ApiKey;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Validator;
use Str;

class ApiKeyController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return ApiKeyResource::collection(ApiKey::latest()->get());
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        if ( !$request->accepts(['application/json']) ) {
            return response()->json('Only JSON Format accepted',);
        }

        $validated = json_decode(json_encode($request->all()), true);

        $validator = Validator::make($validated, [
            'name' => 'required|string|max:255',
            'permissions' => 'required|array|min:1',
                'permissions.*' => 'required|in:read,write,delete',
            'ip_whitelisting' => 'sometimes|array',
                'ip_whitelisting.*' => 'sometimes|ip',
            'expiry_date' => 'sometimes|date',
            'status' => 'sometimes|in:enabled,disabled',
        ]);

        if ($validator->fails() ){
            return response()->json($validator->errors()->all());
        }

        $validated = $validator->validated();

        $validated['key'] = Str::random(36);

        $apiKey = ApiKey::create($validated);

        $data = [
            'message' => ($apiKey) ? 'API key created successfully':
                'Failed to create API key!',
            'apiKey' => ($apiKey) ? new ApiKeyResource($apiKey) : [],
        ];

        return response()->json($data);

    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            return new ApiKeyResource( ApiKey::findOrFail($id) );
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'API key not found!']);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        try {
            return new ApiKeyResource( ApiKey::findOrFail($id) );
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'API key not found!']);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        if ( !$request->accepts(['application/json']) ) {
            return response()->json('Only JSON Format accepted',);
        }

        try {
            $apiKey = ApiKey::findOrFail($id);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'API key not found!']);
        }

        $validated = json_decode(json_encode($request->all()), true);

        $validator = Validator::make($validated, [
            'name' => 'sometimes|string|max:255',
            'permissions' => 'sometimes|array|min:1',
                'permissions.*' => 'sometimes|in:read,write,delete',
            'ip_whitelisting' => 'sometimes|array',
                'ip_whitelisting.*' => 'sometimes|ip',
            'expiry_date' => 'sometimes|date',
            'status' => 'sometimes|in:enabled,disabled',
        ]);

        if ($validator->fails() ){
            return response()->json($validator->errors()->all());
        }

        $validated = $validator->validated();

        $updated = $apiKey->update($validated);

        $data = [
            'message' => ($updated) ? "API key updated successfully":
                'Failed to update API key',
            'apiKey' => new ApiKeyResource($apiKey),
        ];

        return response()->json($data);


    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $apiKey = ApiKey::findOrFail($id);

            $apiKey->delete($apiKey);

            return response()->json([
                "success" => true,
                "message" => "API key deleted successfully."
            ]);

        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'API key not found!']);
        }
    }

    /**
     * Search a listing of the resource.
     */
    public function search(Request $request)
    {
        if ( !$request->accepts(['application/json']) ) {
            return response()->json('Only JSON Format accepted',);
        }

        $validated = json_decode(json_encode($request->all()), true);

        $validator = Validator::make($validated, [
            'searchBy' => 'required|in:id,name,status',
            'searchQuery' => 'sometimes|string|max:255|nullable',
        ]);

        if ($validator->fails() ){
            return response()->json($validator->errors()->all());
        }

        $validated = $validator->validated();

        $apiKey = ApiKey::latest()
        ->where($validated['searchBy'], $validated['searchQuery'])
        ->get();

        return ApiKeyResource::collection($apiKey);

    }

    /**
     * Filter a listing of the resource.
     */
    public function filter(Request $request)
    {
        if ( !$request->accepts(['application/json']) ) {
            return response()->json('Only JSON Format accepted',);
        }

        $validated = json_decode(json_encode($request->all()), true);

        $validator = Validator::make($validated, [
            'filterStatus' => 'sometimes|string|max:255|nullable',
            'filterPermissions' => 'sometimes|in:read,write,delete|nullable',
            'filterCreationDateRange' => 'sometimes|array|min:0',
                'filterCreationDateRange.startDate' => 'sometimes|date|nullable',
                'filterCreationDateRange.endDate' => 'sometimes|date|nullable',
            'filterExpiryDate' => 'sometimes|date|nullable',
        ]);

        if ($validator->fails() ){
            return response()->json($validator->errors()->all());
        }

        $conditions = [];
        if (isset($validated['filterStatus']) && ($filterStatus = $validated['filterStatus'])) {
            $conditions[] = ['status', $filterStatus];
        }
        if (isset($validated['filterPermissions']) && ($filterPermissions = $validated['filterPermissions'])) {
            $conditions[] = ['permissions', 'LIKE', "%$filterPermissions%"];
        }
        if ( isset($validated['filterCreationDateRange']) && ($dateRange = $validated['filterCreationDateRange']) && $dateRange) {
            if ( isset($dateRange['startDate']) && ($startDate = $dateRange['startDate']) && $startDate) {
                // $conditions[] = ['created_at', 'LIKE', "%$startDate%"];
                $conditions[] = ['created_at', '>=', $startDate];
            }
            if ( isset($dateRange['endDate']) && ($endDate = $dateRange['endDate']) && $endDate) {
                // $conditions[] = ['created_at', 'LIKE', "%$endDate%"];
                $conditions[] = ['created_at', '<=', $endDate];
            }
        }
        if (isset($validated['filterExpiryDate']) && ($expiry_date = $validated['filterExpiryDate']) && $expiry_date) {
            $conditions[] = ['expiry_date', 'LIKE', "%$expiry_date%"];
            // $conditions[] = ['expiry_date', $expiry_date];
        }

        $apiKey = ApiKey::latest()->where($conditions)
        ->get();

        return ApiKeyResource::collection($apiKey);

    }

    /**
     * Regenerate API Key.
     */
    public function regenerate(Request $request, $id)
    {
        try {
            $apiKey = ApiKey::findOrfail($id);

            $apiKey->update(['key' => Str::random(36)]);

            return response()->json([
                "success" => true,
                "message" => "API key regenerated successfully.",
                "key" => $apiKey->getConcealedKey(),
            ]);

        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'API key not found!']);
        }
    }

    /**
     * Revoke API Key.
     */
    public function revoke(Request $request, $id)
    {
        try {
            $apiKey = ApiKey::findOrfail($id);

            $apiKey->update(['status' => 'disabled']);

            return response()->json([
                "success" => true,
                "message" => "API key revoked successfully.",
                "status" => $apiKey->status,
            ]);

        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'API key not found!']);
        }
    }

    /**
     * .
     */
    public function analytics()
    {
        $apiKeys = ApiKey::all();
        $activeApis = ApiKey::getActiveKeys();
        $expiredKeys = ApiKey::getExpiredKeys();

        $data = [
            "totalKeys" => $totalKeys = $apiKeys->count(),
            "activeKeys" => $activeCount = $activeApis->count(),
            "inactiveKeys" => $totalKeys - $activeApis->count(),
            "expiredKeys" => $expiredKeys->count(),
            "permissionDistribution" => [
                "read" => ApiKey::getKeyByPermission('read')->count(),
                "write" => ApiKey::getKeyByPermission('write')->count(),
                "delete" => ApiKey::getKeyByPermission('delete')->count()
            ],
        ];

        return response()->json($data, 200);

    }
}
