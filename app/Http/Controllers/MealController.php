<?php

namespace App\Http\Controllers;

use App\Models\Meal;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MealController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // $meals = Meal::where('user_id', $request->user()->id)->get();
        $meals = Meal::whereBelongsTo($request->user())->get();
        return response([
            'meals' => $meals,
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
            'unit' => 'required|string',
            'calories' => 'required|integer',
            'category' => 'string',
            'tfat' => 'integer',
            'sfat' => 'integer',
            'carbs' => 'integer',
            'sugar' => 'integer',
            'protein' => 'integer',
        ]);
        $data['user_id'] = $request->user()->id;

        $meal = Meal::create($data);
        // Try $request->user()->meals->create($data)

        return response([
            'meal' => $meal,
        ], 201);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        // Collection of meals belonging to current user
        $meals = Meal::whereBelongsTo($request->user())->get();
        // Meal with the specified id
        $meal = $meals->where('id', $id)->first();

        if (!$meal) {
            return response([
                'message' => 'No such meal in your library.'
            ], 404);
        }

        $data = $request->validate([
            'unit' => 'string',
            'calories' => 'integer',
            'tfat' => 'integer',
            'sfat' => 'integer',
            'carbs' => 'integer',
            'sugar' => 'integer',
            'protein' => 'integer',
        ]);

        $meal->update($data);

        return response([
            'meal' => $meal
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, string $id)
    {
        $meals = Meal::whereBelongsTo($request->user())->get();
        $meal = $meals->where('id', $id)->first();

        if (!$meal) {
            return response([
                'message' => 'No such meal in your library.'
            ], 404);
        }

        $meal->delete();

        // Alternatively return nothing with status 204: no content
        return response([
            'meal' => $meal,
        ], 200);
    }
}
