<?php

namespace App\Http\Controllers;

use App\Http\Resources\ApiCallLogResource;
use App\Models\BaseApi;
use App\Models\ApiCallLog;
use App\Services\ApiCall;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
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

        if ($validator->passes() ){

            $baseApi = BaseApi::findOrFail( $validated['id'] );

            return (new ApiCall($baseApi))->makeCall();

        }else {
            return response()->json($validator->errors()->all(),);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        return new ApiCallLogResource(ApiCallLog::findOrfail($id));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        return new ApiCallLogResource(ApiCallLog::findOrfail($id));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        return response()->json([
            "success" => true,
            "message" => "Action not supported!"
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $callLog = ApiCallLog::findOrFail($id);

        $callLog->delete($callLog);

        return response()->json([
            "success" => true,
            "message" => "API call log deleted successfully."
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
            'searchBy' => 'required|in:endpoint,method,status',
            'searchQuery' => 'sometimes|string|max:255|nullable',
        ]);

        if ($validator->passes() ){

            if ( $validated['searchBy'] == 'status') {
                $apiKeys = ApiCallLog::where('status', $validated['searchQuery']);
            }else {
                $apiKeys = BaseApi::latest()
                ->where($validated['searchBy'], 'LIKE', "%".$validated['searchQuery']."%")
                ->get()->filter(function($key){
                    return $key->api_call_log->count();
                })->map(function($key){
                    return $key->api_call_log;
                })->collapse();
            }

            return ApiCallLogResource::collection($apiKeys);

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
            'filterStatus' => 'sometimes|in:success,failed|nullable',
            'filterCreationDateRange' => 'sometimes|array|min:0',
                'filterCreationDateRange.startDate' => 'sometimes|date|nullable',
                'filterCreationDateRange.endDate' => 'sometimes|date|nullable',
            'filterExpiryDate' => 'sometimes|date|nullable',
        ]);

        if ($validator->passes() ){

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

        }else {
            return response()->json($validator->errors()->all(),);
        }

    }

    /**
     *
     */
    public function analytics(Request $request)
    {
        $callLogs = ApiCallLog::all();

        $successfulCalls = ApiCallLog::getSuccessfulCalls();
        $totalResponseTime = ApiCallLog::getTotalResponseTime();

        $data = [
            'totalCalls' => $totalCalls = $callLogs->count(),
            'successfulCalls' => $successfulCallsCount = $successfulCalls->count(),
            'failedCalls' => $totalCalls - $successfulCallsCount,
            'averageResponseTime' => ($totalCalls) ? ($totalResponseTime / $totalCalls) : 0,
        ];

        return response()->json($data, 200);

    }
}
