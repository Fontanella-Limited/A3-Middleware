<?php

namespace App\Http\Controllers;

use App\Http\Resources\ApiKeyResource;
use App\Models\ApiKey;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
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
            'status' => 'in:enabled,disabled',
        ]);

        if ($validator->passes() ){

            $validated['key'] = Str::random(36);

            $apiKey = ApiKey::create($validated);

            return new ApiKeyResource($apiKey);

        }else {
            return response()->json($validator->errors()->all(),);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        return new ApiKeyResource( ApiKey::findOrFail($id) );
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        return new ApiKeyResource( ApiKey::findOrFail($id) );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $apiKey = ApiKey::findOrFail($id);

        if ( !$request->accepts(['application/json']) ) {
            return response()->json('Only JSON Format accepted',);
        }

        $validated = json_decode(json_encode($request->all()), true);

        $validator = Validator::make($validated, [
            'name' => 'sometimes|string|max:255',
            'permissions' => 'sometimes|array|min:1',
                'permissions.*' => 'sometimes|in:read,write,delete',
            'ip_whitelisting' => 'sometimes|array',
                'ip_whitelisting.*' => 'sometimes|ip',
            'expiry_date' => 'sometimes|date',
            // 'status' => 'sometimes|in:enabled,disabled',
        ]);

        if ($validator->passes() ){

            $apiKey->update($validated);

            return new ApiKeyResource($apiKey);

        }else {
            return response()->json($validator->errors()->all(),);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $apiKey = ApiKey::findOrFail($id);

        $apiKey->delete($apiKey);

        return response()->json([
            "success" => true,
            "message" => "API key deleted successfully."
        ]);
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
            'searchQuery' => 'required|string|max:255',
        ]);

        if ($validator->passes() ){

            $apiKey = ApiKey::latest()
            ->where($validated['searchBy'], $validated['searchQuery'])
            ->get();

            return ApiKeyResource::collection($apiKey);

        }else {
            return response()->json($validator->errors()->all(),);
        }
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

        if ($validator->passes() ){

            $conditions = [];
            if (isset($validated['filterStatus']) && $validated['filterStatus']) {
                $conditions[] = ['status', $validated['filterStatus']];
            }
            if (isset($validated['filterPermissions']) && $validated['filterPermissions']) {
                $conditions[] = ['permissions', 'LIKE', '%'.$validated['filterPermissions'].'%'];
            }
            if (isset($validated['filterCreationDateRange']) && $validated['filterCreationDateRange']) {
                if (null !== ($startDate = $validated['filterCreationDateRange']['startDate']) && $startDate) {
                    // $conditions[] = ['created_at', 'LIKE', "%$startDate%"];
                    $conditions[] = ['created_at', '>=', $startDate];
                }
                if (null !== ($endDate = $validated['filterCreationDateRange']['endDate']) && $endDate) {
                    // $conditions[] = ['created_at', 'LIKE', "%$endDate%"];
                    $conditions[] = ['created_at', '<=', $endDate];
                }
            }
            if (null !== ($expiry_date = $validated['filterExpiryDate']) && $expiry_date) {
                $conditions[] = ['expiry_date', 'LIKE', "%$expiry_date%"];
                // $conditions[] = ['expiry_date', $expiry_date];
            }

            $apiKey = ApiKey::latest()->where($conditions)
            ->get();

            return ApiKeyResource::collection($apiKey);

        }else {
            return response()->json($validator->errors()->all(),);
        }
    }

    /**
     * Regenerate API Key.
     */
    public function regenerate(Request $request, $id)
    {
        $apiKey = ApiKey::findOrfail($id);

        $apiKey->update(['key' => Str::random(36)]);

        return response()->json([
            "success" => true,
            "message" => "API key regenerated successfully.",
            "key" => $apiKey->getConcealedKey(),
        ], 200);

    }

    /**
     * Revoke API Key.
     */
    public function revoke(Request $request, $id)
    {
        $apiKey = ApiKey::findOrfail($id);

        $apiKey->update(['status' => 'disabled']);

        return response()->json([
            "success" => true,
            "message" => "API key revoked successfully.",
            "status" => $apiKey->status,
        ], 200);

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
