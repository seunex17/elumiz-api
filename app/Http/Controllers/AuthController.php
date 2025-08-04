<?php

namespace App\Http\Controllers;

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
            ->withCount('role')
            ->first();
        if (! $user || ! Hash::check($input['password'], $user->password)) {
            return response()->json([
                'message' => 'Username or password is invalid',
            ], ResponseAlias::HTTP_BAD_REQUEST);
        }

        //$user->tokens()->where('name', 'desktop')->delete();

        return response()->json([
            'user' => $user,
            'token' => $user->createToken('desktop')->plainTextToken,
        ], ResponseAlias::HTTP_OK);
    }
}
