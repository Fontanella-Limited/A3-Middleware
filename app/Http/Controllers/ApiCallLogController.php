<?php

namespace App\Http\Controllers;

use App\Http\Resources\ApiCallLogResource;
use App\Models\Endpoint;
use App\Models\ApiCallLog;
use App\Models\ApiSetting;
use App\Services\ApiCall;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Validator;

class ApiCallLogController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return ApiCallLogResource::collection(ApiCallLog::latest()->get());
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
            'id' => 'required|numeric',
        ]);

        if ($validator->fails() ){
            return response()->json($validator->errors()->all());
        }

        try {
            $endpoint = Endpoint::findOrFail( $validator->validated()['id'] );
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Endpoint not found!']);
        }

        return (new ApiCall($endpoint))->makeCall();

    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            return new ApiCallLogResource(ApiCallLog::findOrfail($id));
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'API call log not found!']);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        try {
            return new ApiCallLogResource(ApiCallLog::findOrfail($id));
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'API call log not found!']);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        return response()->json([
            "success" => false,
            "message" => "Action not supported!"
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $callLog = ApiCallLog::findOrFail($id);

            $callLog->delete();

            return response()->json([
                "success" => true,
                "message" => "API call log deleted successfully."
            ]);

        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'API call log not found!']);
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
            'searchBy' => 'required|in:endpoint,method,status',
            'searchQuery' => 'sometimes|string|max:255|nullable',
        ]);

        if ($validator->fails() ){
            return response()->json($validator->errors()->all());
        }

        $validated = $validator->validated();

        if ( $validated['searchBy'] == 'status') {
            $apiKeys = ApiCallLog::where('status', $validated['searchQuery']);
        }else {
            $apiKeys = Endpoint::latest()
            ->where($validated['searchBy'], 'LIKE', "%".$validated['searchQuery']."%")
            ->get()->filter(function($key){
                return $key->api_call_log->count();
            })->map(function($key){
                return $key->api_call_log;
            })->collapse();
        }

        return ApiCallLogResource::collection($apiKeys);

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
            'filterStatus' => 'sometimes|in:success,failed|nullable',
            'filterCreationDateRange' => 'sometimes|array|min:0',
                'filterCreationDateRange.startDate' => 'sometimes|date|nullable',
                'filterCreationDateRange.endDate' => 'sometimes|date|nullable',
            'filterExpiryDate' => 'sometimes|date|nullable',
        ]);

        if ($validator->fails() ){
            return response()->json($validator->errors()->all());
        }

        $validated = $validator->validated();

        $conditions = [];
        if ( isset($validated['filterMethod']) && $filterMethod = $validated['filterMethod'] ) {
            $conditions[] = ['method', $filterMethod];
        }
        if ( isset($validated['filterStatus']) && ($filterStatus = $validated['filterStatus']) ) {
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

        $apiCallLogs = ApiCallLog::latest() ->where($conditions)
        ->get();

        return new ApiCallLogResource($apiCallLogs);

    }

    /**
     *
     */
    public function analytics(Request $request)
    {
        $data = [
            'totalCalls' => $totalCalls = ApiCallLog::totalCalls(),
            'successfulCalls' => $successfulCallsCount = ApiCallLog::getSuccessfulCalls(),
            'failedCalls' => $totalCalls - $successfulCallsCount,
            'averageResponseTime' => ApiCallLog::averageReponseTime(),
        ];

        return response()->json($data);

    }
}
