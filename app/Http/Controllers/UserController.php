<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Validator;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return UserResource::collection(User::latest()->get());
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        return new UserResource(User::findOrFail($id));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => ['required','email','unique:'.User::class],
            'password' => 'required|min:6',
            'type' => 'required|in:admin,developer,viewer',
        ]);

        $user = User::create($validated);

        return new UserResource($user);

    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        return new UserResource(User::findOrFail($id));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'first_name' => 'sometimes|string|max:255',
            'last_name' => 'sometimes|string|max:255',
            'email' => ['sometimes','email',Rule::unique(User::class)->ignore($user->id)],
            'password' => 'sometimes|min:6',
            'type' => 'sometimes|in:admin,developer,viewer',
        ]);

        $user->update($validated);

        return new UserResource($user);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $user = User::findOrFail($id);

        $user->delete($user);

        return response()->json([
            "success" => true,
            "message" => "User deleted successfully."
        ]);

    }

    /**
     * Enable/Disable User.
     */
    public function status(Request $request, $id)
    {
        $user = User::findOrFail($id);

        if ( !$request->accepts(['application/json']) ) {
            return response()->json('Only JSON Format accepted',);
        }

        $validated = json_decode(json_encode($request->all()), true);

        $validator = Validator::make($validated, [
            'status' => 'required|in:active,inactive',
        ]);

        if ($validator->passes() ){

            $user->update(['status' => $validated['status']]);

            $action = ($user->status == 'active') ?
            'activated' : 'deactivated';

            return response()->json([
                "success" => true,
                "message" => "User $action successfully.",
                "status" => $user->status,
            ], 200);

        }else {
            return response()->json($validator->errors()->all(),);
        }
    }

    /**
     * Filter a listing of the resource.
     */
    public function filter(Request $request)
    {
        if ( !$request->accepts(['application/json']) ) {
            return response()->json('Only JSON Format accepted',);
        }

        $validated = json_decode(json_encode($request->all()), true);

        $validator = Validator::make($validated, [
            'filterName' => 'sometimes|string|max:255|nullable',
            'filterStatus' => 'sometimes|in:active,inactive|nullable',
            'filterEmail' => 'sometimes|email|nullable',
            'filterType' => 'sometimes|in:admin,developer,viewer|nullable',
        ]);

        if ($validator->passes() ){

            $conditions = [];
            if (isset($validated['filterName']) && ($filterName = $validated['filterName']) && $filterName) {
                $conditions[] = ['first_name', 'LIKE', "%$filterName%"];
            }
            if (isset($validated['filterStatus']) && ($filterStatus = $validated['filterStatus']) && $filterStatus) {
                $conditions[] = ['status', $filterStatus];
            }
            if (isset($validated['filterEmail']) && ($filterEmail = $validated['filterEmail']) && $filterEmail) {
                $conditions[] = ['email', "%$filterEmail%"];
            }
            if (isset($validated['filterType']) && ($filterType = $validated['filterType']) && $filterType) {
                $conditions[] = ['type', $filterType];
            }

            $user = User::latest()->where($conditions)
            ->get();

            return new UserResource($user);

        }else {
            return response()->json($validator->errors()->all(),);
        }

    }
}
