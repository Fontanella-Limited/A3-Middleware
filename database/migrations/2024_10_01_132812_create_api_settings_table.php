<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('api_settings', function (Blueprint $table) {
            $table->id();
            $table->json('settings')
            ->default('
                {
                    "globalSettings": {
                        "baseUrl": "https://api.example.com",
                        "timeoutDuration": 30,
                        "maxApiCallLimit": 1000,
                        "pagination": {
                        "defaultPageSize": 50
                        }
                    },
                    "authentication": {
                        "tokenExpiry": 24,
                        "keyRotation": "enabled",
                        "oauthProviders": ["Google", "Facebook"]
                    },
                    "security": {
                        "ipWhitelist": ["192.168.1.1", "192.168.1.2"],
                        "ipBlacklist": ["192.168.1.100"],
                        "cors": {
                        "allowedOrigins": ["https://www.example.com"],
                        "allowedMethods": ["get", "post", "put"],
                        "allowedHeaders": ["Content-Type", "Authorization"]
                        },
                        "rateLimiting": {
                        "global": 1000,
                        "perUser": 100
                        },
                        "encryption": {
                        "status": "enabled",
                        "algorithm": "AES"
                        }
                    },
                    "logging": {
                        "status": "enabled",
                        "retentionPeriod": 180,
                        "storageLocation": "cloud"
                    },
                    "performance": {
                        "caching": {
                        "status": "enabled",
                        "expiry": 60,
                        "storageLocation": "local"
                        },
                        "loadBalancing": {
                        "status": "enabled",
                        "healthChecks": "enabled"
                        }
                    },
                    "versionControl": {
                        "currentVersion": "v1",
                        "versioning": "enabled",
                        "deprecation": {
                        "deprecatedVersions": ["v1", "v0"],
                        "deprecationDate": "2025-01-01"
                        }
                    },
                    "errorHandling": {
                        "customErrors": "enabled",
                        "defaultErrorFormat": "json",
                        "errorCodes": {
                        "400": "Invalid Request",
                        "401": "Unauthorized",
                        "500": "Internal Server Error"
                        }
                    }
                }'
            );
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('api_settings');
    }
};
