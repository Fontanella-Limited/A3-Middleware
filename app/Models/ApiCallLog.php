<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class ApiCallLog extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        // 'user_id',
        'endpoint_id',
        'response',
        'response_time',
        'status',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'response' => 'json',
        ];
    }

    /**
     * Get the endpoint that owns the ApiCallLog
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function endpoint(): BelongsTo
    {
        return $this->belongsTo(Endpoint::class);
    }

    public function getEndpoint()
    {
        return $this->endpoint?->endpoint;
    }

    public function getMethod()
    {
        return $this->endpoint?->method;
    }

    public static function throughput( $callLog = null )
    {
        // returns default value, logic to be examined!
        return 2.5;
        if ( $callLog && ($callLog instanceof ApiCallLog)) {
            return ApiCallLog::where('endpoint_id', $callLog->endpoint_id)
            ->groupBy(DB::raw('MINUTE(created_at)'))
            ->select(DB::raw('MINUTE(created_at) as minute', 'COUNT(id) as count'))
            ->orderBy('minute', 'asc')
            ->avg('count');
        }

        return ApiCallLog::groupBy(DB::raw('MINUTE(created_at)'))
        ->select(DB::raw('MINUTE(created_at) as minute', 'COUNT(id) as count'))
        ->orderBy('minute', 'asc')
        ->avg('count');

    }

    public static function peakUsageTime( $callLog = null )
    {
        // returns default value, logic to be examined!
        return "3:00";
        if ( $callLog && ($callLog instanceof ApiCallLog)) {
            return ApiCallLog::where('endpoint_id', $callLog->endpoint_id)
            ->groupBy(DB::row('HOUR(created_at)'))
            ->select( DB::row('FORMAT(HOUR(created_at), "%l") as hour'),
             'COUNT(id) as count')
            ->orderBy('count', 'desc')
            ->limit(1)
            ->first();
        }


        return ApiCallLog::groupBy(DB::row('HOUR(created_at)'))
        ->select( DB::row('HOUR(created_at) as hour'),
         'COUNT(id) as count')
        ->orderBy('count', 'desc')
        ->limit(1)
        ->first();

    }

    public static function totalCalls( $callLog = null )
    {
        if ( $callLog && ($callLog instanceof ApiCallLog)) {
            return ApiCallLog::where('endpoint_id', $callLog->endpoint_id)
            ->get()->count();
        }

        return ApiCallLog::all()->count();

    }

    public static function averageReponseTime( $callLog = null )
    {
        if ( $callLog && ($callLog instanceof ApiCallLog)) {
            $averageReponseTime = ApiCallLog::where('endpoint_id', $callLog->endpoint_id)
            ->avg('response_time');
            return round($averageReponseTime, 2);
        }

        return round(ApiCallLog::avg('response_time'), 2);

    }

    public static function successRate( $callLog = null )
    {
        if ( $callLog && ($callLog instanceof ApiCallLog)) {
            $successCount = ApiCallLog::where('status', 'success')
            ->where('endpoint_id', $callLog->endpoint_id)
            ->get()->count();

            return ($totalCount = self::totalCalls( $callLog )) ?
            round(($successCount / $totalCount) * 100, 2)  : 0;
        }
        else{
            $successCount = ApiCallLog::where('status', 'success')
            ->get()->count();

            return ($totalCount = self::totalCalls()) ?
            round(($successCount / $totalCount) * 100, 2)  : 0;
        }
    }

    public static function errorRate( $callLog = null )
    {
        if ( $callLog && ($callLog instanceof ApiCallLog)) {
            $errorCount = ApiCallLog::where('status', 'failed')
            ->where('endpoint_id', $callLog->endpoint_id)
            ->get()->count();

            return ($totalCount = self::totalCalls( $callLog )) ?
            round(($errorCount / $totalCount) * 100, 2)  : 0;
        }
        else{
            $errorCount = ApiCallLog::where('status', 'failed')
            ->get()->count();

            return ($totalCount = self::totalCalls()) ?
            round(($errorCount / $totalCount) * 100, 2)  : 0;
        }
    }

    public static function getSuccessfulCalls( $callLog = null )
    {
        if ( $callLog && ($callLog instanceof ApiCallLog)) {
            return ApiCallLog::where('status', 'success')
            ->where('endpoint_id', $callLog->endpoint_id)->get();
        }

        return ApiCallLog::where('status', 'success')->get();

    }

    public static function getTotalResponseTime( $callLog = null )
    {
        if ( $callLog && ($callLog instanceof ApiCallLog)) {
            return ApiCallLog::where('endpoint_id', $callLog->endpoint_id)
            ->sum('response_time');
        }

        return ApiCallLog::sum('response_time');

    }

}
