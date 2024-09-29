<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

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
        'base_api_id',
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
     * Get the base_api that owns the ApiCallLog
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function base_api(): BelongsTo
    {
        return $this->belongsTo(BaseApi::class);
    }

    public static function getSuccessfulCalls()
    {
        return ApiCallLog::where('status', 'success')->get();
    }

    public static function getTotalResponseTime()
    {
        return $totalResponseTime = ApiCallLog::all()
        ->reduce(function($totalResponseTime, $log) {
            return $totalResponseTime += intVal($log->response_time);
        }, 0);
    }

}
