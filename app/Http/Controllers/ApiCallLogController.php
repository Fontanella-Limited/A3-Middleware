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
     * Filter a listing of the resource.
     */
    public function filter(Request $request)
    {
        if ( !$request->accepts(['application/json']) ) {
            return response()->json('Only JSON Format accepted',);
        }

        $validated = json_decode(json_encode($request->all()), true);

        $validator = Validator::make($validated, [
            'searchQuery' => 'sometimes|string|max:255|nullable',
            'filterMethod' => 'sometimes|in:post,get,put,patch,head,delete|nullable',
            'filterStatus' => 'sometimes|in:enabled,disabled|nullable',
        ]);

        if ($validator->passes() ){

            $conditions = [];
            if ($validated['searchQuery']) {
                $conditions[] = ['endpoint', $validated['searchQuery']];
            }
            if ($validated['filterMethod']) {
                $conditions[] = ['method', $validated['filterMethod']];
            }
            if ($validated['filterStatus']) {
                $conditions[] = ['status', $validated['filterStatus']];
            }

            $baseApi = ApiCallLog::latest()
            ->where($conditions)
            ->get();

            return new ApiCallLogResource($baseApi);

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
