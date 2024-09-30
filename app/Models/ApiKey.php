<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Str;

class ApiKey extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'key',
        'permissions',
        'ip_whitelisting',
        'expiry_date',
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
            'expiry_date' => 'datetime',
            'permissions' => 'json',
            'ip_whitelisting' => 'json',
        ];
    }

    public function getConcealedKey(): string
    {
        return Str::mask($this->key, '*', 6, -6);
    }

    public static function getActiveKeys()
    {
        return ApiKey::where('status', 'enabled');
    }

    public static function getInactiveKeys()
    {
        return ApiKey::where('status', 'disabled');
    }

    public static function getExpiredKeys()
    {
        return ApiKey::where('expiry_date', '<=', date('Y-m-d H:i:s'));
    }

    public static function getKeyByPermission( $permission )
    {
        return ApiKey::where('permissions', 'LIKE', "%$permission%");
    }
}
