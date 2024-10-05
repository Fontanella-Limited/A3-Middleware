<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Resources\ApiCallLogResource;
use App\Models\ApiSetting;
use App\Models\ApiCallLog;
use App\Models\Endpoint;
use Validator;
use Str;

class PerformanceMonitoringController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $callLogs = ApiCallLog::get()->unique('endpoint_id');

        $usageByEndpoints = $callLogs
        ->map( function( $log ) {
            return [
                "endpoint" => $log->getEndpoint(),
                "method" => $log->getMethod(),
                "totalCalls" => ApiCallLog::totalCalls( $log ),
                "averageResponseTime" => ApiCallLog::averageReponseTime( $log ),
                "successRate" => ApiCallLog::successRate( $log ),
                "errorRate" => ApiCallLog::errorRate( $log ),
            ];
        });

        $data = [
            "totalCalls" => ApiCallLog::totalCalls(),
            "averageResponseTime" => ApiCallLog::averageReponseTime(),
            "successRate" => ApiCallLog::successRate(),
            "errorRate" => ApiCallLog::errorRate(),
            "throughput" => ApiCallLog::throughput(),
            "peakUsageTime" => ApiCallLog::peakUsageTime(),
            "apiUsageByEndpoint" => $usageByEndpoints,
        ];

        return response()->json($data);
    }

    /**
     * .
     */
    public function logs()
    {
        $apiCallLogs = ApiCallLog::latest()->get()
        ->map( function( $apiCallLog ){
            return [
                "timestamp" => $apiCallLog->created_at,
                "endpoint" => $apiCallLog->endpoint->endpoint,
                "method" => $apiCallLog->endpoint->method,
                "responseTime" => $apiCallLog->response_time,
                "statusCode" => $apiCallLog->status == 'success' ? 200 : 500,
                "success" => $apiCallLog->status == 'success' ? true : false,
                "callerIp" => null,
                "payloadSize" => null
            ];
        });

        return response()->json($apiCallLogs);

    }

    /**
     * .
     */
    public function filter(Request $request)
    {
        if ( !$request->accepts(['application/json']) ) {
            return response()->json('Only JSON Format accepted',);
        }

        $validated = json_decode(json_encode($request->all()), true);

        $validator = Validator::make($validated, [
            'filterCreationDateRange' => 'sometimes|array|min:0',
                'filterCreationDateRange.startDate' => 'sometimes|date|nullable',
                'filterCreationDateRange.endDate' => 'sometimes|date|nullable',
            'filterStatus' => 'sometimes|in:success,failed|nullable',
            'filterResponseTime' => 'sometimes|numeric|nullable',
            'filterEndpoint' => 'sometimes|string|max:255|nullable',
            'filterMethod' => 'sometimes|in:get,post,put,patch,head,delete|nullable',
        ]);

        if ($validator->fails() ){
            return response()->json($validator->errors()->all());
        }

        $validated = $validator->validated();

        $conditions = [];
        if (isset($validated['filterStatus']) && ($filterStatus = $validated['filterStatus'])) {
            $conditions[] = ['status', $filterStatus];
        }
        if (isset($validated['filterResponseTime']) && ($filterResponseTime = $validated['filterResponseTime'])) {
            $conditions[] = ['responseTime', '>=', $filterResponseTime];
        }
        if (isset($validated['filterCreationDateRange']) && ($dateRange = $validated['filterCreationDateRange']) && $dateRange) {
            if ( isset($dateRange['startDate']) && ($startDate = $dateRange['startDate']) && $startDate) {
                // $conditions[] = ['created_at', 'LIKE', "%$startDate%"];
                $conditions[] = ['created_at', '>=', $startDate];
            }
            if ( isset($dateRange['endDate']) && ($endDate = $dateRange['endDate']) && $endDate) {
                // $conditions[] = ['created_at', 'LIKE', "%$endDate%"];
                $conditions[] = ['created_at', '<=', $endDate];
            }
        }

        $apiCallLogs = ApiCallLog::latest()->where($conditions)->get()
        ->filter( function( $apiCallLog ) use ($validated){
            $filterEndpoint = isset($validated['filterEndpoint']) ? $validated['filterEndpoint'] : '';
            return $filterEndpoint && $apiCallLog->endpoint->endpoint == $filterEndpoint;
        })
        ->filter( function( $apiCallLog ) use ($validated){
            $filterMethod = isset($validated['filterMethod']) ? $validated['filterMethod'] : '';
            return $filterMethod && $apiCallLog->endpoint->method == $filterMethod;
        });

        return ApiCallLogResource::collection($apiCallLogs);

    }
}
