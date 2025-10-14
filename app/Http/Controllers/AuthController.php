<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;

class AuthController extends Controller
{
    public function authenticate(Request $request)
    {
        $input = $request->all();

        $user = User::where('name', $input['name'])
            ->with('role')
            ->first();
        if (! $user || ! Hash::check($input['password'], $user->password)) {
            return response()->json([
                'message' => 'Username or password is invalid',
            ], ResponseAlias::HTTP_BAD_REQUEST);
        }

        // $user->tokens()->where('name', 'desktop')->delete();

        return response()->json([
            'user' => $user,
            'token' => $user->createToken('desktop')->plainTextToken,
        ], ResponseAlias::HTTP_OK);
    }

    public function checkLogin(Request $request)
    {
        $user = User::where('name', $request->user()->name)
            ->with('role')
            ->first();

        return response()->json($user, ResponseAlias::HTTP_OK);
    }

    public function logout(Request $request)
    {
        $userId = $request->user()->id;
        Device::query()->where('user_id', $userId)
            ->delete();

        return response()->json([
            'message' => 'Logout Successful',
        ], ResponseAlias::HTTP_OK);
    }

    public function authenticates(Request $request)
    {
        $user = User::find($request->user()->id);
        if (! $user || ! Hash::check($request->input('password'), $user->password)) {
            return response()->json([
                'message' => 'Username or password is invalid',
            ], ResponseAlias::HTTP_BAD_REQUEST);
        }

        return response()->json([
            'message' => 'Login Successful',
        ], ResponseAlias::HTTP_OK);
    }

    public function addDevice(Request $request)
    {
        $input = $request->input();
        Device::create($input);

        return response()->json([
            'message' => 'Device added',
        ], ResponseAlias::HTTP_OK);
    }
}
