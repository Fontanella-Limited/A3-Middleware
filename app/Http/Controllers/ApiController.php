<?php

namespace App\Http\Controllers;

use App\Http\Resources\EndpointResource;
use App\Models\Endpoint;
use App\Models\ApiCallLog;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Validator;
use Str;

class ApiController extends Controller
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
            'endpoint' => ['required','string','max:255','unique:'.Endpoint::class],
            'method' => 'required|in:post,get,put,patch,head,delete',
            'description' => 'string|max:255',
            'status' => 'in:enabled,disabled',
            'headers' => 'array',
            'payload' => 'array|required_if:method,post,put,patch',
            'parameters' => 'array',
        ]);

        if ($validator->passes() ){

            $endpoint = Endpoint::create($validated);

            return new EndpointResource($endpoint);

        }else {
            return response()->json($validator->errors()->all(),);
        }

    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        return new EndpointResource(Endpoint::findOrFail($id));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        return new EndpointResource(Endpoint::findOrFail($id));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $endpoint = Endpoint::findOrFail($id);

        if ( !$request->accepts(['application/json']) ) {
            return response()->json('Only JSON Format accepted',);
        }

        $validated = json_decode(json_encode($request->all()), true);

        $validator = Validator::make($validated, [
            'endpoint' => ['required','string','max:255',Rule::unique(Endpoint::class)->ignore($endpoint->id)],
            'method' => 'required|in:post,get,put,patch,head,delete',
            'description' => 'string|max:255',
            'status' => 'in:enabled,disabled',
            'headers' => 'array',
            'payload' => 'array|required_if:method,post,put',
            'parameters' => 'array',
        ]);

        if ($validator->passes() ){

            $endpoint->update($validated);

            return new EndpointResource($endpoint);

        }else {
            return response()->json($validator->errors()->all(),);
        }

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

        if ($validator->passes() ){

            $apiKeys = Endpoint::latest()
            ->where($validated['searchBy'], 'LIKE', "%".$validated['searchQuery']."%")
            ->get();

            return EndpointResource::collection($apiKeys);

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
            'filterMethod' => 'sometimes|in:post,get,put,patch,head,delete|nullable',
            'filterStatus' => 'sometimes|in:enabled,disabled|nullable',
            'filterCreationDateRange' => 'sometimes|array|min:0',
                'filterCreationDateRange.startDate' => 'sometimes|date|nullable',
                'filterCreationDateRange.endDate' => 'sometimes|date|nullable',
            'filterExpiryDate' => 'sometimes|date|nullable',
        ]);

        if ($validator->passes() ){

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

        }else {
            return response()->json($validator->errors()->all(),);
        }
    }

    /**
     * Enable/Disable API Endpoint.
     */
    public function status(Request $request, $id)
    {
        $endpoint = Endpoint::findOrfail($id);

        if ( !$request->accepts(['application/json']) ) {
            return response()->json('Only JSON Format accepted',);
        }

        $validated = json_decode(json_encode($request->all()), true);

        $validator = Validator::make($validated, [
            'status' => 'required|in:enabled,disabled',
        ]);

        if ($validator->passes() ){

            $endpoint->update(['status' => $validated['status']]);

            return response()->json([
                "success" => true,
                "message" => "API endpoint status updated successfully.",
                "status" => $endpoint->status,
            ], 200);

        }else {
            return response()->json($validator->errors()->all(),);
        }
    }

    /**
     * .
     */
    public function analytics()
    {
        $endpoints = Endpoint::all();

        $activeApis = Endpoint::getActiveApis();

        $successfulCalls = ApiCallLog::getSuccessfulCalls();
        $totalResponseTime = ApiCallLog::getTotalResponseTime();

        $data = [
            "totalApiEndpoints" => $totalCount = $endpoints->count(),
            "activeApiEndpoints" => $activeCount = $activeApis->count(),
            "inactiveApiEndpoints" => $totalCount - $activeCount,
            "apiCallStatistics" => [
                "totalCalls" => $totalCallsCount = ApiCallLog::all()->count(),
                "successfulCalls" => $successCount = $successfulCalls->count(),
                "failedCalls" => $totalCallsCount - $successCount,
                "averageResponseTime" => ($totalCallsCount) ? ($totalResponseTime / $totalCallsCount) : 0,
            ]
        ];

        return response()->json($data, 200);

    }

    /**
     * Displays a log of API calls made.
     */
    public function history()
    {
        $endpoints = Endpoint::all()
        ->map( function ($endpoint) {

            $callLogs = $endpoint->api_call_log;

            return [
                "apiName" => $endpoint->endpoint,
                "method" => $endpoint->method,
                "callsMade" => $callsMade = $callLogs->count(),
                "averageResponseTime" => ($callsMade) ?
                number_format($callLogs->reduce(function($sum,$log){
                    return $sum += intVal($log->response_time);
                }, 0) / $callsMade, 2) : 0,
                "status" => $endpoint->status,
            ];
        });

        return response()->json($endpoints, 200);

    }


}
