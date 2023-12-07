<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function updateTargetMacros(Request $request)
    {
        $data = $request->validate([
            'calories' => 'integer',
            'tfat' => 'integer',
            'sfat' => 'integer',
            'carbs' => 'integer',
            'sugar' => 'integer',
            'protein' => 'integer',
        ]);

        $user = User::find($request->user()->id);
        $user->update($data);

        return response([
            'user' => $user,
        ], 200);
    }
}
