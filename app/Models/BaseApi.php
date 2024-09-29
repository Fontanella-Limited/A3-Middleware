<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class BaseApi extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'endpoint',
        'method',
        'description',
        'status',
        'headers',
        'payload',
        'parameters',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'headers' => 'json',
            'payload' => 'json',
            'parameters' => 'json',
        ];
    }

    /**
     * Get the api_call_log associated with the BaseApi
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function api_call_log(): HasOne
    {
        return $this->hasOne(ApiCallLog::class);
    }

    public function getEndPoint() {
        return $this->endpoint;
    }

    public function getMethod() {
        return $this->method;
    }

    public function getHeaders() {
        return json_decode( json_encode($this->headers), true);
    }

    public function getPayload() {
        return json_decode( json_encode($this->payload), true);
    }

    public function getParameters() {
        return json_decode( json_encode($this->parameter), true);
    }

    public static function getActiveApis()
    {
        return BaseApi::where('status', 'enabled')->get();
    }

}
