<?php

namespace App\Services;

use App\Models\BaseApi;
use App\Models\ApiCallLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use App\Http\Resources\ApiCallLogResource;

class ApiCall
{
    /**
     * Create a new ApiCall instance.
     *
     * @return void
     */
    public function __construct(protected BaseApi $baseApi)
    {
        // $this->middleware('auth');
    }

    /**
     *
     */
    public function makeCall(): ApiCallLogResource
    {

        $endpoint = $this->baseApi->getEndPoint();
        $method = $this->baseApi->getMethod();
        $headers = $this->baseApi->getHeaders();
        $payload = $this->baseApi->getPayload();
        $parameters = $this->baseApi->getParameters();

        $start_time = microtime(true);
        if ( $method == 'post' || $method == 'put' || $method == 'patch' ) {
            $response = Http::withHeaders($headers)
            ->withQueryParameters($parameters??[])
            ->$method($endpoint, $payload);

        }else{
            $response = Http::withHeaders($headers)
            ->withQueryParameters($parameters??[])
            ->$method($endpoint, $payload);
        }
        $data['response_time'] = microtime(true) - $start_time;

        $data['base_api_id'] = $this->baseApi->id;
        $data['status'] = $response->ok() ? 'success':'failed';
        $data['response'] = [
            'headers' => $response->headers(),
            'body' => $response->body(),
        ];

        $callLog = ApiCallLog::create($data);

        return new ApiCallLogResource($callLog);

    }
}
