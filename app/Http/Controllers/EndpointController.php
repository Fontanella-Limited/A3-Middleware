<?php

namespace App\Http\Controllers;

use App\Http\Resources\EndpointResource;
use App\Models\Endpoint;
use App\Models\ApiCallLog;
use App\Models\ApiSetting;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\UniqueConstraintViolationException;
use Validator;
use Str;

class EndpointController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return EndpointResource::collection(Endpoint::latest()->get());
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
            'base_api_id' => ['required','numeric'],
            'endpoint' => ['required','string','max:255'],
            'method' => 'required|in:post,get,put,patch,head,delete',
            'description' => 'string|max:255',
            'status' => 'in:enabled,disabled',
            'headers' => 'array',
            'payload' => 'array|required_if:method,post,put,patch',
            'parameters' => 'array',
        ]);

        if ($validator->fails() ){
            return response()->json($validator->errors()->all(),);
        }

        try {
            $api_setting = ApiSetting::findOrFail($validator->validated()['base_api_id']);

            $endpoint = $api_setting->endpoints()
            ->createMany( [$validator->validated()] )->first();

        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Base API not found!']);
        }
        catch (UniqueConstraintViolationException $e) {
            return response()->json(['message' => 'The endpoint already exist!']);
        }

        $data = [
            'message' => ($endpoint) ? 'Endpoint created successfully':
                'Failed to create endpoint!',
            'endpoint' => ($endpoint) ? new EndpointResource( $endpoint ) : [],
        ];

        return response()->json($data);

    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            return new EndpointResource(Endpoint::findOrFail($id));
        } catch (ModelNotFoundException $th) {
            return response()->json(['message' => 'Endpoint not found!']);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        try {
            return new EndpointResource(Endpoint::findOrFail($id));
        } catch (ModelNotFoundException $th) {
            return response()->json(['message' => 'Endpoint not found!']);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        try {
            $endpoint = Endpoint::findOrFail($id);
        } catch (ModelNotFoundException $th) {
            return response()->json(['message' => 'Endpoint not found!']);
        }

        if ( !$request->accepts(['application/json']) ) {
            return response()->json('Only JSON Format accepted',);
        }

        $validated = json_decode(json_encode($request->all()), true);

        $validator = Validator::make($validated, [
            'endpoint' => ['sometimes','string','max:255'],
            'method' => 'sometimes|in:post,get,put,patch,head,delete',
            'description' => 'string|max:255',
            'status' => 'in:enabled,disabled',
            'headers' => 'array',
            'payload' => 'array|required_if:method,post,put',
            'parameters' => 'array',
        ]);

        if ($validator->fails() ){
            return response()->json($validator->errors()->all(),);
        }

        $validated = $validator->validated();

        $updated = $endpoint->update($validated);

        $data = [
            'message' => ($updated) ? "Endpoint updated successfully":
                'Failed to update endpoint',
            'endpoint' => new EndpointResource($endpoint),
        ];

        return response()->json($data);

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $endpoint = Endpoint::findOrFail($id);

        $endpoint->delete($endpoint);

        return response()->json([
            "success" => true,
            "message" => "API endpoint deleted successfully."
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
            'searchBy' => 'required|in:endpoint,method,description',
            'searchQuery' => 'sometimes|string|max:255|nullable',
        ]);

        if ($validator->fails() ){
            return response()->json($validator->errors()->all(),);
        }

        $validated = $validator->validated();

        $apiKeys = Endpoint::latest()
        ->where($validated['searchBy'], 'LIKE', "%".$validated['searchQuery']."%")
        ->get();

        return EndpointResource::collection($apiKeys);

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
            'filterMethod' => 'sometimes|in:post,get,put,patch,head,delete|nullable',
            'filterStatus' => 'sometimes|in:enabled,disabled|nullable',
            'filterCreationDateRange' => 'sometimes|array|min:0',
                'filterCreationDateRange.startDate' => 'sometimes|date|nullable',
                'filterCreationDateRange.endDate' => 'sometimes|date|nullable',
            'filterExpiryDate' => 'sometimes|date|nullable',
        ]);


        if ($validator->fails() ){
            return response()->json($validator->errors()->all(),);
        }

        $validated = $validator->validated();

        $conditions = [];
        if (isset($validated['filterMethod']) && ($filterMethod = $validated['filterMethod'])) {
            $conditions[] = ['method', $filterMethod];
        }
        if (isset($validated['filterStatus']) && ($filterStatus = $validated['filterStatus'])) {
            $conditions[] = ['status', $filterStatus];
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

        $endpoint = Endpoint::latest()->where($conditions)
        ->get();

        return EndpointResource::collection($endpoint);

    }

    /**
     * Enable/Disable API Endpoint.
     */
    public function status(Request $request, $id)
    {
        try {
            $endpoint = Endpoint::findOrFail($id);
        } catch (ModelNotFoundException $th) {
            return response()->json(['message' => 'Endpoint not found!']);
        }

        if ( !$request->accepts(['application/json']) ) {
            return response()->json('Only JSON Format accepted',);
        }

        $validated = json_decode(json_encode($request->all()), true);

        $validator = Validator::make($validated, [
            'status' => 'required|in:enabled,disabled',
        ]);


        if ($validator->fails() ){
            return response()->json($validator->errors()->all(),);
        }

        $validated = $validator->validated();

        $endpoint->update($validated);

        return response()->json([
            "success" => true,
            "message" => "API endpoint status updated successfully.",
            "status" => $endpoint->status,
        ]);
    }

    /**
     * .
     */
    public function analytics()
    {
        $endpoints = Endpoint::all();

        $data = [
            "totalApiEndpoints" => $totalCount = $endpoints->count(),
            "activeApiEndpoints" => $activeCount = Endpoint::getActiveApis()->count(),
            "inactiveApiEndpoints" => $totalCount - $activeCount,
            "apiCallStatistics" => [
                "totalCalls" => $totalCallsCount = ApiCallLog::totalCalls(),
                "successfulCalls" => $successCount = ApiCallLog::getSuccessfulCalls(),
                "failedCalls" => $totalCallsCount - $successCount,
                "averageResponseTime" => ApiCallLog::averageReponseTime(),
            ]
        ];

        return response()->json($data);

    }

    /**
     * Displays a log of API calls made.
     */
    public function history()
    {
        $endpoints = ApiCallLog::latest()->distinct('endpoint_id')
        ->map( function ($log) {
            return [
                "apiName" => $log->endpoint->base_api->getBaseUrl(),
                "method" => $log->endpoint->method,
                "callsMade" => ApiCallLog::totalCalls( $log ),
                "averageResponseTime" => ApiCallLog::averageReponseTime( $log ),
                "status" => $endpoint->status,
            ];
        });

        return response()->json($endpoints);

    }
}
