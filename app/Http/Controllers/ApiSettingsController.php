<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Resources\ApiSettingResource;
use App\Models\ApiSetting;
use Illuminate\Validation\Rule;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Validator;
use Str;

class ApiSettingsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return ApiSettingResource::collection(ApiSetting::all());
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
            return response()->json('Only JSON Format accepted');
        }

        $data = json_decode(json_encode($request->all()), true);

        $validator = Validator::make($data, [
            'api_name' => ['required', 'string', 'max:255'],
            'globalSettings' => ['required', 'array'],
                'globalSettings.baseUrl' => 'required_with:globalSettings|url',
                'globalSettings.timeoutDuration' => 'required_with:globalSettings|numeric',
                'globalSettings.maxApiCallLimit' => 'required_with:globalSettings|numeric',
                'globalSettings.pagination' => 'required_with:globalSettings|array',
                'globalSettings.pagination.defaultPageSize' => 'required_with:globalSettings|numeric',
            'authentication' => ['required', 'array'],
                'authentication.tokenExpiry' => 'required_with:authentication|in:24,12,6',
                'authentication.keyRotation' => 'required_with:authentication|in:enabled,disabled',
                'authentication.oauthProviders' => 'required_with:authentication|array',
            'security' => ['required', 'array'],
                'security.ipWhitelist' => 'required_with:security|array',
                    'security.ipWhitelist.*' => 'required_with:security.ipWhitelist|ip',
                'security.ipBlacklist' => 'required_with:security|array',
                    'security.ipBlacklist.*' => 'required_with:security.ipBlacklist|ip',
                'security.cors' => 'required_with:security|array',
                    'security.cors.allowedOrigins' => 'required_with:security.cors|array',
                        'security.cors.allowedOrigins.*' => 'required_with:security.cors.allowedOrigins|url',
                    'security.cors.allowedMethods' => 'required_with:security.cors|array',
                        'security.cors.allowedMethods.*' => 'required_with:security.cors.allowedMethods|in:get,post,put,patch,head,delete',
                    'security.cors.allowedHeaders' => 'required_with:security.cors|array',
                        'security.cors.allowedHeaders.*' => 'required_with:security.cors.allowedHeaders|string|max:255',
                'security.rateLimiting' => 'required_with:security|array',
                    'security.rateLimiting.global' => 'required_with:security.rateLimiting|numeric',
                    'security.rateLimiting.perUser' => 'required_with:security.rateLimiting|numeric',
                'security.encryption' => 'required_with:security|array',
                    'security.encryption.status' => 'required_with:security.encryption|in:enabled,disabled',
                    'security.encryption.algorithm' => 'required_with:security.encryption|string|max:255',
            'logging' => ['required', 'array'],
                'logging.status' => 'required_with:logging|in:enabled,disabled',
                'logging.retentionPeriod' => 'required_with:logging|numeric',
                'logging.storageLocation' => 'required_with:logging|string|max:255',
            'performance' => ['required', 'array'],
                'performance.caching' => 'required_with:performance|array',
                'performance.caching.status' => 'required_with:performance.caching|in:enabled,disabled',
                'performance.caching.expiry' => 'required_with:performance.caching|numeric',
                'performance.caching.storageLocation' => 'required_with:performance.caching|string|max:255',
                'performance.loadBalancing' => 'required_with:performance|array',
                'performance.loadBalancing.status' => 'required_with:performance.loadBalancing|in:enabled,disabled',
                'performance.loadBalancing.healthChecks' => 'required_with:performance.loadBalancing|in:enabled,disabled',
            'versionControl' => ['required', 'array'],
                'versionControl.currentVersion' => 'required_with:versionControl|string|max:255',
                'versionControl.versioning' => 'required_with:versionControl|in:enabled,disabled',
                'versionControl.deprecation' => 'required_with:versionControl|array',
                'versionControl.deprecation.deprecatedVersions' => 'required_with:versionControl.deprecation|array',
                'versionControl.deprecation.deprecationDate' => 'required_with:versionControl.deprecation|date',
            'errorHandling' => ['required', 'array'],
                'errorHandling.customErrors' => 'required_with:errorHandling|in:enabled,disabled',
                'errorHandling.defaultErrorFormat' => 'required_with:errorHandling|in:json,xml',
                'errorHandling.errorCodes' => 'required_with:errorHandling|array',
                'errorHandling.errorCodes.*' => 'required_with:errorHandling|string|max:255',
        ]);

        if ($validator->fails() ){
            return response()->json($validator->errors()->all());
        }

        $api_setting = ApiSetting::create( $validator->validated() );

        $data = [
            'message' => ($api_setting) ? 'API saved and configured successfully':
                'Failed to save and configure API!',
            'settings' => ($api_setting) ? new ApiSettingResource( $api_setting ) : [],
        ];

        return response()->json($data);

    }


    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $api = ApiSetting::findOrFail($id);
            return new ApiSettingResource($api);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'API not found!']);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        try {
            $api = ApiSetting::findOrFail($id);
            return new ApiSettingResource($api);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'API not found!']);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        if ( !$request->accepts(['application/json']) ) {
            return response()->json('Only JSON Format accepted');
        }

        try {
            $api_setting = ApiSetting::findOrFail($id);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'API not found!']);
        }

        $data = json_decode(json_encode($request->all()), true);

        $validator = Validator::make($data, [
            'api_name' => ['required', 'string', 'max:255',],
            'globalSettings' => ['required', 'array'],
                'globalSettings.baseUrl' => 'required_with:globalSettings|url',
                'globalSettings.timeoutDuration' => 'required_with:globalSettings|numeric',
                'globalSettings.maxApiCallLimit' => 'required_with:globalSettings|numeric',
                'globalSettings.pagination' => 'required_with:globalSettings|array',
                'globalSettings.pagination.defaultPageSize' => 'required_with:globalSettings|numeric',
            'authentication' => ['required', 'array'],
                'authentication.tokenExpiry' => 'required_with:authentication|in:24,12,6',
                'authentication.keyRotation' => 'required_with:authentication|in:enabled,disabled',
                'authentication.oauthProviders' => 'required_with:authentication|array',
            'security' => ['required', 'array'],
                'security.ipWhitelist' => 'required_with:security|array',
                    'security.ipWhitelist.*' => 'required_with:security.ipWhitelist|ip',
                'security.ipBlacklist' => 'required_with:security|array',
                    'security.ipBlacklist.*' => 'required_with:security.ipBlacklist|ip',
                'security.cors' => 'required_with:security|array',
                    'security.cors.allowedOrigins' => 'required_with:security.cors|array',
                        'security.cors.allowedOrigins.*' => 'required_with:security.cors.allowedOrigins|url',
                    'security.cors.allowedMethods' => 'required_with:security.cors|array',
                        'security.cors.allowedMethods.*' => 'required_with:security.cors.allowedMethods|in:get,post,put,patch,head,delete',
                    'security.cors.allowedHeaders' => 'required_with:security.cors|array',
                        'security.cors.allowedHeaders.*' => 'required_with:security.cors.allowedHeaders|string|max:255',
                'security.rateLimiting' => 'required_with:security|array',
                    'security.rateLimiting.global' => 'required_with:security.rateLimiting|numeric',
                    'security.rateLimiting.perUser' => 'required_with:security.rateLimiting|numeric',
                'security.encryption' => 'required_with:security|array',
                    'security.encryption.status' => 'required_with:security.encryption|in:enabled,disabled',
                    'security.encryption.algorithm' => 'required_with:security.encryption|string|max:255',
            'logging' => ['required', 'array'],
                'logging.status' => 'required_with:logging|in:enabled,disabled',
                'logging.retentionPeriod' => 'required_with:logging|numeric',
                'logging.storageLocation' => 'required_with:logging|string|max:255',
            'performance' => ['required', 'array'],
                'performance.caching' => 'required_with:performance|array',
                'performance.caching.status' => 'required_with:performance.caching|in:enabled,disabled',
                'performance.caching.expiry' => 'required_with:performance.caching|numeric',
                'performance.caching.storageLocation' => 'required_with:performance.caching|string|max:255',
                'performance.loadBalancing' => 'required_with:performance|array',
                'performance.loadBalancing.status' => 'required_with:performance.loadBalancing|in:enabled,disabled',
                'performance.loadBalancing.healthChecks' => 'required_with:performance.loadBalancing|in:enabled,disabled',
            'versionControl' => ['required', 'array'],
                'versionControl.currentVersion' => 'required_with:versionControl|string|max:255',
                'versionControl.versioning' => 'required_with:versionControl|in:enabled,disabled',
                'versionControl.deprecation' => 'required_with:versionControl|array',
                'versionControl.deprecation.deprecatedVersions' => 'required_with:versionControl.deprecation|array',
                'versionControl.deprecation.deprecationDate' => 'required_with:versionControl.deprecation|date',
            'errorHandling' => ['required', 'array'],
                'errorHandling.customErrors' => 'required_with:errorHandling|in:enabled,disabled',
                'errorHandling.defaultErrorFormat' => 'required_with:errorHandling|in:json,xml',
                'errorHandling.errorCodes' => 'required_with:errorHandling|array',
                'errorHandling.errorCodes.*' => 'required_with:errorHandling|string|max:255',
        ]);

        if ($validator->fails() ){
            return response()->json($validator->errors()->all(),);
        }

        $validated = $validator->validated();

        $updated = $api_setting->update($validated);

        $data = [
            'message' => ($updated) ? "API settings updated successfully":
                'Failed to update API settings',
            'settings' => new ApiSettingResource($api_setting),
        ];

        return response()->json($data);

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, string $id)
    {
        try {
            $api_setting = ApiSetting::findOrFail($id);

            $api_setting->delete();

            return response()->json([
                "success" => true,
                "message" => "API deleted successfully."
            ]);

        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'API not found!']);
        }
    }
}
