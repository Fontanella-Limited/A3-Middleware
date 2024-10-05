<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApiSetting extends Model
{
    use HasFactory;

    public const CATEGORIES = [
        'globalSettings',
        'authentication',
        'security',
        'logging',
        'performance',
        'versionControl',
        'errorHandling'
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'api_name',
        'globalSettings',
        'authentication',
        'security',
        'logging',
        'performance',
        'versionControl',
        'errorHandling'
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'globalSettings' => 'json',
            'authentication' => 'json',
            'security' => 'json',
            'logging' => 'json',
            'performance' => 'json',
            'versionControl' => 'json',
            'errorHandling' => 'json'
        ];
    }

    /**
     * Get all of the endpoints for the ApiSetting
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function endpoints(): HasMany
    {
        return $this->hasMany(Endpoint::class, 'base_api_id');
    }

    public function getApiName( )
    {
        return $this->api_name;
    }

    public function getBaseUrl( )
    {
        return ($globalSettings = $this->globalSettings) ?
        $globalSettings['baseUrl'] : '';
    }

    public function getSettingsCategory( $category )
    {
        return $this->settings[$category];
    }

    public static function isValidCategory( $category )
    {
        return in_array($category, self::CATEGORIES);
    }
}
