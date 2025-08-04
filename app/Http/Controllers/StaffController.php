<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;

class StaffController extends Controller
{

    public function manage(Request $request)
    {
        $staffs = User::orderBy('name', 'asc')
            ->where('id', '!=', 1)
            ->with('role')
            ->get();

        return response()->json($staffs, ResponseAlias::HTTP_OK);
    }

    public function addNewStaff(Request $request)
    {
        $user = User::where('name', $request->input('username'))->first();

        if ($user) {
            return response()->json([
                'message' => 'Account already exists.',
            ], ResponseAlias::HTTP_BAD_REQUEST);
        }

        User::create([
            'name' => $request->input('username'),
            'email' => $request->input('email'),
            'password' => $request->input('password'),
            'role_id' => $request->input('role', 4),
        ]);

        return response()->json([
            'message' => 'Account Created Successfully.',
        ], ResponseAlias::HTTP_CREATED);
    }

    public function deleteStaff(Request $request, string $id)
    {

        if ($id != 2) {
            User::destroy($id);
        }

        return response()->json([
            'message' => 'Account Deleted Successfully.',
        ]);
    }
}
