<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Resources\ApiSettingResource;
use App\Models\ApiSetting;
use Illuminate\Validation\Rule;
use Validator;
use Str;

class ApiSettingsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return new ApiSettingResource(ApiSetting::first());
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
    public function store(Request $request, $category)
    {
        if ( !$request->accepts(['application/json']) ) {
            return response()->json('Only JSON Format accepted');
        }

        if ( ! ($isValidCategory = ApiSetting::isValidCategory($category)) ) {
            return response()->json("Invalid settings category '{$category}'");
        }

        $oldSetting = json_decode(json_encode(($apiSettings = ApiSetting::first())->settings), true);

        if ( isset($oldSetting[$category]) && $oldSetting[$category] ) {
            return response()->json(["messsage"=> "'{$category}' settings configured already!"]);
        }

        $data = json_decode(json_encode($request->all()), true);

        $validator = Validator::make($data, [
            'globalSettings' => ['array', Rule::prohibitedIf($category != 'globalSettings')],
                'globalSettings.baseUrl' => 'required_with:globalSettings|url',
                'globalSettings.timeoutDuration' => 'required_with:globalSettings|numeric',
                'globalSettings.maxApiCallLimit' => 'required_with:globalSettings|numeric',
                'globalSettings.pagination' => 'required_with:globalSettings|array',
                'globalSettings.pagination.defaultPageSize' => 'required_with:globalSettings|numeric',
            'authentication' => ['array', Rule::prohibitedIf($category != 'authentication')],
                'authentication.tokenExpiry' => 'required_with:authentication|in:24,12,6',
                'authentication.keyRotation' => 'required_with:authentication|in:enabled,disabled',
                'authentication.oauthProviders' => 'required_with:authentication|array',
            'security' => ['array', Rule::prohibitedIf($category != 'security')],
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
            'logging' => ['array', Rule::prohibitedIf($category != 'logging')],
                'logging.status' => 'required_with:logging|in:enabled,disabled',
                'logging.retentionPeriod' => 'required_with:logging|numeric',
                'logging.storageLocation' => 'required_with:logging|string|max:255',
            'performance' => ['array', Rule::prohibitedIf($category != 'performance')],
                'performance.caching' => 'required_with:performance|array',
                'performance.caching.status' => 'required_with:performance.caching|in:enabled,disabled',
                'performance.caching.expiry' => 'required_with:performance.caching|numeric',
                'performance.caching.storageLocation' => 'required_with:performance.caching|string|max:255',
                'performance.loadBalancing' => 'required_with:performance|array',
                'performance.loadBalancing.status' => 'required_with:performance.loadBalancing|in:enabled,disabled',
                'performance.loadBalancing.healthChecks' => 'required_with:performance.loadBalancing|in:enabled,disabled',
            'versionControl' => ['array', Rule::prohibitedIf($category != 'versionControl')],
                'versionControl.currentVersion' => 'required_with:versionControl|string|max:255',
                'versionControl.versioning' => 'required_with:versionControl|in:enabled,disabled',
                'versionControl.deprecation' => 'required_with:versionControl|array',
                'versionControl.deprecation.deprecatedVersions' => 'required_with:versionControl.deprecation|array',
                'versionControl.deprecation.deprecationDate' => 'required_with:versionControl.deprecation|date',
            'errorHandling' => ['array', Rule::prohibitedIf($category != 'errorHandling')],
                'errorHandling.customErrors' => 'required_with:errorHandling|in:enabled,disabled',
                'errorHandling.defaultErrorFormat' => 'required_with:errorHandling|in:json,xml',
                'errorHandling.errorCodes' => 'required_with:errorHandling|array',
                'errorHandling.errorCodes.*' => 'required_with:errorHandling|string|max:255',
        ]);

        if ($validator->fails() ){
            return response()->json($validator->errors()->all(),);
        }

        $oldSetting[$category] = $validator->validated();
        $apiSettings->settings = $oldSetting;

        $data = [
            'message' => ($apiSettings->save()) ? 'Settings saved successfully':
                'Failed to save settings',
            'settings' => $apiSettings->settings[$category]
        ];

        return response()->json($data);

    }

    /**
     * Display the specified resource.
     */
    public function show(string $category)
    {
        return (ApiSetting::isValidCategory($category)) ?
        response()->json([$category => ApiSetting::first()->settings[$category] ?? []]):
        response()->json("Invalid settings category '{$category}'");
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $category)
    {
        return (ApiSetting::isValidCategory($category)) ?
        response()->json([$category => ApiSetting::first()->settings[$category] ?? []]):
        response()->json("Invalid settings category '{$category}'");
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $category)
    {
        if ( !$request->accepts(['application/json']) ) {
            return response()->json('Only JSON Format accepted');
        }

        if ( ! ($isValidCategory = in_array($category, ApiSetting::CATEGORIES)) ) {
            return response()->json("Invalid settings category '{$category}'");
        }

        $data = json_decode(json_encode($request->all()), true);

        $validator = Validator::make($data, [
            'globalSettings' => ['array', Rule::prohibitedIf($category != 'globalSettings')],
                'globalSettings.baseUrl' => 'required_with:globalSettings|url',
                'globalSettings.timeoutDuration' => 'required_with:globalSettings|numeric',
                'globalSettings.maxApiCallLimit' => 'required_with:globalSettings|numeric',
                'globalSettings.pagination' => 'required_with:globalSettings|array',
                'globalSettings.pagination.defaultPageSize' => 'required_with:globalSettings|numeric',
            'authentication' => ['array', Rule::prohibitedIf($category != 'authentication')],
                'authentication.tokenExpiry' => 'required_with:authentication|in:24,12,6',
                'authentication.keyRotation' => 'required_with:authentication|in:enabled,disabled',
                'authentication.oauthProviders' => 'required_with:authentication|array',
            'security' => ['array', Rule::prohibitedIf($category != 'security')],
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
            'logging' => ['array', Rule::prohibitedIf($category != 'logging')],
                'logging.status' => 'required_with:logging|in:enabled,disabled',
                'logging.retentionPeriod' => 'required_with:logging|numeric',
                'logging.storageLocation' => 'required_with:logging|string|max:255',
            'performance' => ['array', Rule::prohibitedIf($category != 'performance')],
                'performance.caching' => 'required_with:performance|array',
                'performance.caching.status' => 'required_with:performance.caching|in:enabled,disabled',
                'performance.caching.expiry' => 'required_with:performance.caching|numeric',
                'performance.caching.storageLocation' => 'required_with:performance.caching|string|max:255',
                'performance.loadBalancing' => 'required_with:performance|array',
                'performance.loadBalancing.status' => 'required_with:performance.loadBalancing|in:enabled,disabled',
                'performance.loadBalancing.healthChecks' => 'required_with:performance.loadBalancing|in:enabled,disabled',
            'versionControl' => ['array', Rule::prohibitedIf($category != 'versionControl')],
                'versionControl.currentVersion' => 'required_with:versionControl|string|max:255',
                'versionControl.versioning' => 'required_with:versionControl|in:enabled,disabled',
                'versionControl.deprecation' => 'required_with:versionControl|array',
                'versionControl.deprecation.deprecatedVersions' => 'required_with:versionControl.deprecation|array',
                'versionControl.deprecation.deprecationDate' => 'required_with:versionControl.deprecation|date',
            'errorHandling' => ['array', Rule::prohibitedIf($category != 'errorHandling')],
                'errorHandling.customErrors' => 'required_with:errorHandling|in:enabled,disabled',
                'errorHandling.defaultErrorFormat' => 'required_with:errorHandling|in:json,xml',
                'errorHandling.errorCodes' => 'required_with:errorHandling|array',
                'errorHandling.errorCodes.*' => 'required_with:errorHandling|string|max:255',
        ]);

        if ($validator->fails() ){
            return response()->json($validator->errors()->all(),);
        }

        $oldSetting = json_decode(json_encode(($apiSettings = ApiSetting::first())->settings), true);

        $oldSetting[$category] = $validator->validated();
        $apiSettings->settings = $oldSetting;

        $data = [
            'message' => ($apiSettings->save()) ? "'{$category}' settings updated successfully":
                'Failed to update settings',
            'settings' => $apiSettings->settings[$category]
        ];

        return response()->json($data);

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, string $category)
    {
        if ( !$request->accepts(['application/json']) ) {
            return response()->json('Only JSON Format accepted');
        }

        if ( ! ($isValidCategory = in_array($category, ApiSetting::CATEGORIES)) ) {
            return response()->json("Invalid settings category '{$category}'");
        }

        $data = json_decode(json_encode($request->all()), true);

        $validator = Validator::make($data, [
            'globalSettings' => ['array', Rule::prohibitedIf($category != 'globalSettings')],
                'globalSettings.baseUrl' => 'exclude_without:globalSettings|url',
                'globalSettings.timeoutDuration' => 'exclude_without:globalSettings|numeric',
                'globalSettings.maxApiCallLimit' => 'exclude_without:globalSettings|numeric',
                'globalSettings.pagination' => 'exclude_without:globalSettings|array',
                'globalSettings.pagination.defaultPageSize' => 'exclude_without:globalSettings|numeric',
            'authentication' => ['array', Rule::prohibitedIf($category != 'authentication')],
                'authentication.tokenExpiry' => 'exclude_without:authentication|in:24,12,6',
                'authentication.keyRotation' => 'exclude_without:authentication|in:enabled,disabled',
                'authentication.oauthProviders' => 'exclude_without:authentication|array',
            'security' => ['array', Rule::prohibitedIf($category != 'security')],
                'security.ipWhitelist' => 'exclude_without:security|array',
                    'security.ipWhitelist.*' => 'exclude_without:security.ipWhitelist|ip',
                'security.ipBlacklist' => 'exclude_without:security|array',
                    'security.ipBlacklist.*' => 'exclude_without:security.ipBlacklist|ip',
                'security.cors' => 'exclude_without:security|array',
                    'security.cors.allowedOrigins' => 'exclude_without:security.cors|array',
                        'security.cors.allowedOrigins.*' => 'exclude_without:security.cors.allowedOrigins|url',
                    'security.cors.allowedMethods' => 'exclude_without:security.cors|array',
                        'security.cors.allowedMethods.*' => 'exclude_without:security.cors.allowedMethods|in:get,post,put,patch,head,delete',
                    'security.cors.allowedHeaders' => 'exclude_without:security.cors|array',
                        'security.cors.allowedHeaders.*' => 'exclude_without:security.cors.allowedHeaders|string|max:255',
                'security.rateLimiting' => 'exclude_without:security|array',
                    'security.rateLimiting.global' => 'exclude_without:security.rateLimiting|numeric',
                    'security.rateLimiting.perUser' => 'exclude_without:security.rateLimiting|numeric',
                'security.encryption' => 'exclude_without:security|array',
                    'security.encryption.status' => 'exclude_without:security.encryption|in:enabled,disabled',
                    'security.encryption.algorithm' => 'exclude_without:security.encryption|string|max:255',
            'logging' => ['array', Rule::prohibitedIf($category != 'logging')],
                'logging.status' => 'exclude_without:logging|in:enabled,disabled',
                'logging.retentionPeriod' => 'exclude_without:logging|numeric',
                'logging.storageLocation' => 'exclude_without:logging|string|max:255',
            'performance' => ['array', Rule::prohibitedIf($category != 'performance')],
                'performance.caching' => 'exclude_without:performance|array',
                'performance.caching.status' => 'exclude_without:performance.caching|in:enabled,disabled',
                'performance.caching.expiry' => 'exclude_without:performance.caching|numeric',
                'performance.caching.storageLocation' => 'exclude_without:performance.caching|string|max:255',
                'performance.loadBalancing' => 'exclude_without:performance|array',
                'performance.loadBalancing.status' => 'exclude_without:performance.loadBalancing|in:enabled,disabled',
                'performance.loadBalancing.healthChecks' => 'exclude_without:performance.loadBalancing|in:enabled,disabled',
            'versionControl' => ['array', Rule::prohibitedIf($category != 'versionControl')],
                'versionControl.currentVersion' => 'exclude_without:versionControl|string|max:255',
                'versionControl.versioning' => 'exclude_without:versionControl|in:enabled,disabled',
                'versionControl.deprecation' => 'exclude_without:versionControl|array',
                'versionControl.deprecation.deprecatedVersions' => 'exclude_without:versionControl.deprecation|array',
                'versionControl.deprecation.deprecationDate' => 'exclude_without:versionControl.deprecation|date',
            'errorHandling' => ['array', Rule::prohibitedIf($category != 'errorHandling')],
                'errorHandling.customErrors' => 'exclude_without:errorHandling|in:enabled,disabled',
                'errorHandling.defaultErrorFormat' => 'exclude_without:errorHandling|in:json,xml',
                'errorHandling.errorCodes' => 'exclude_without:errorHandling|array',
                'errorHandling.errorCodes.*' => 'exclude_without:errorHandling|string|max:255',
        ]);

        if ($validator->fails() ){
            return response()->json($validator->errors()->all(),);
        }

        $oldSetting = json_decode(json_encode(( $apiSettings = ApiSetting::first() )->settings), true);

        $oldSetting[$category] = $validator->validated()[$category];
        $apiSettings->settings = $oldSetting;

        $data = [
            'message' => ($apiSettings->save()) ? "'{$category}' settings deleted successfully":
                'Failed to delete settings',
            'settings' => $apiSettings->settings[$category]
        ];

        return response()->json($data);
    }
}
