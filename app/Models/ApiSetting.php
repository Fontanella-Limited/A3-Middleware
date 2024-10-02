<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
        'settings',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'settings' => 'json',
        ];
    }

    public function getSettings()
    {
        $settings = $this->settings;
        return [
            "globalSettings" => $settings['globalSettings'] ?? [],
            "authentication" => $settings['authentication'] ?? [],
            "security" => $settings['security'] ?? [],
            "logging" => $settings['logging'] ?? [],
            "performance" => $settings['performance'] ?? [],
            "versionControl" => $settings['versionControl'] ?? [],
            "errorHandling" => $settings['errorHandling'] ?? [],
        ];
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
