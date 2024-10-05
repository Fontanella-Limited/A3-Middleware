<?php

namespace App\Services;

use App\Models\Endpoint;
use App\Models\ApiCallLog;
use App\Models\ApiSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;
use App\Http\Resources\ApiCallLogResource;
use Illuminate\Http\Client\ConnectionException;

class ApiCall
{
    /**
     * Create a new ApiCall instance.
     *
     * @return void
     */
    public function __construct(protected Endpoint $endpoint,)
    {
        // $this->middleware('auth');
    }

    /**
     *
     */
    public function makeCall(): mixed
    {
        $baseApi = $this->endpoint->base_api->getBaseUrl();
        $endpoint = $this->endpoint->getEndpoint();
        $endpoint = "$baseApi$endpoint";
        $method = $this->endpoint->getMethod();
        $headers = $this->endpoint->getHeaders();
        $payload = $this->endpoint->getPayload();
        $parameters = $this->endpoint->getParameters();

        try {
            $start_time = microtime(true);  // start of response
            if ( $method == 'post' || $method == 'put' || $method == 'patch' ) {
                $response = Http::withHeaders($headers)
                ->withQueryParameters( $parameters ?? [] )
                ->$method($endpoint, $payload);

            }else{
                $response = Http::withHeaders($headers)
                ->withQueryParameters( $parameters ?? [] )
                ->$method($endpoint);
            }
            $data['response_time'] =  round(microtime(true) - $start_time, 2); // end of response

            $data['endpoint_id'] = $this->endpoint->id;
            $data['status'] = $response->successful() ? 'success':'failed';
            $data['response'] = [
                'headers' => $response->headers(),
                'body' => $response->body(),
                'status_code' => $response->status(),
                'callerIp' => request()->ip(),
            ];

            $callLog = ApiCallLog::create($data);

            return new ApiCallLogResource($callLog);

        } catch (ConnectionException $e) {
            return response()->json(['message' => $e->getMessage()]);
        }
        catch (\Throwable $th) {
            return response()->json(['message' => $th->getMessage()]);
        }


    }
}
