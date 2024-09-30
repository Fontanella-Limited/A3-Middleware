<?php

namespace App\Http\Controllers;

use App\Http\Resources\BaseApiResource;
use App\Models\BaseApi;
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
        return BaseApiResource::collection(BaseApi::latest()->get());
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
            'endpoint' => ['required','string','max:255','unique:'.BaseApi::class],
            'method' => 'required|in:post,get,put,patch,head,delete',
            'description' => 'string|max:255',
            'status' => 'in:enabled,disabled',
            'headers' => 'array',
            'payload' => 'array|required_if:method,post,put,patch',
            'parameters' => 'array',
        ]);

        if ($validator->passes() ){

            $baseApi = BaseApi::create($validated);

            return new BaseApiResource($baseApi);

        }else {
            return response()->json($validator->errors()->all(),);
        }

    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        return new BaseApiResource(BaseApi::findOrFail($id));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        return new BaseApiResource(BaseApi::findOrFail($id));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $baseApi = BaseApi::findOrFail($id);

        if ( !$request->accepts(['application/json']) ) {
            return response()->json('Only JSON Format accepted',);
        }

        $validated = json_decode(json_encode($request->all()), true);

        $validator = Validator::make($validated, [
            'endpoint' => ['required','string','max:255',Rule::unique(BaseApi::class)->ignore($baseApi->id)],
            'method' => 'required|in:post,get,put,patch,head,delete',
            'description' => 'string|max:255',
            'status' => 'in:enabled,disabled',
            'headers' => 'array',
            'payload' => 'array|required_if:method,post,put',
            'parameters' => 'array',
        ]);

        if ($validator->passes() ){

            $baseApi->update($validated);

            return new BaseApiResource($baseApi);

        }else {
            return response()->json($validator->errors()->all(),);
        }

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $baseApi = BaseApi::findOrFail($id);

        $baseApi->delete($baseApi);

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

            $apiKeys = BaseApi::latest()
            ->where($validated['searchBy'], 'LIKE', "%".$validated['searchQuery']."%")
            ->get();

            return BaseApiResource::collection($apiKeys);

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

            $baseApi = BaseApi::latest()->where($conditions)
            ->get();

            return BaseApiResource::collection($baseApi);

        }else {
            return response()->json($validator->errors()->all(),);
        }
    }

    /**
     * Enable/Disable API Endpoint.
     */
    public function status(Request $request, $id)
    {
        $baseApi = BaseApi::findOrfail($id);

        if ( !$request->accepts(['application/json']) ) {
            return response()->json('Only JSON Format accepted',);
        }

        $validated = json_decode(json_encode($request->all()), true);

        $validator = Validator::make($validated, [
            'status' => 'required|in:enabled,disabled',
        ]);

        if ($validator->passes() ){

            $baseApi->update(['status' => $validated['status']]);

            return response()->json([
                "success" => true,
                "message" => "API endpoint status updated successfully.",
                "status" => $baseApi->status,
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
        $baseApis = BaseApi::all();

        $activeApis = BaseApi::getActiveApis();

        $successfulCalls = ApiCallLog::getSuccessfulCalls();
        $totalResponseTime = ApiCallLog::getTotalResponseTime();

        $data = [
            "totalApiEndpoints" => $totalCount = $baseApis->count(),
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
        $baseApis = BaseApi::all()
        ->map( function ($base_api) {

            $callLogs = $base_api->api_call_log;

            return [
                "apiName" => $base_api->endpoint,
                "method" => $base_api->method,
                "callsMade" => $callsMade = $callLogs->count(),
                "averageResponseTime" => ($callsMade) ?
                number_format($callLogs->reduce(function($sum,$log){
                    return $sum += intVal($log->response_time);
                }, 0) / $callsMade, 2) : 0,
                "status" => $base_api->status,
            ];
        });

        return response()->json($baseApis, 200);

    }


}
