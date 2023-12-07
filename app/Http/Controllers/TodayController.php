<?php

namespace App\Http\Controllers;

use App\Models\Meal;
use App\Models\Today;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TodayController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        /**
         * SELECT * FROM meals WHERE id IN (
         *    SELECT meal_id FROM todays WHERE user_id = $request->user()->id
         * )
         */

        $meal_IDs_query = DB::table('todays')->select('meal_id')->where('user_id', $request->user()->id);
        $todayList = DB::table('meals')->whereIn('id', $meal_IDs_query)->get();

        // Append amount to the response
        $todayList = $todayList->map(function ($item) {
            $item->amount = Today::where('meal_id', $item->id)->first()->amount;
            return $item;
        });

        return response([
            'todayList' => $todayList,
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, string $meal_id)
    {
        $meal = Meal::find($meal_id);

        if (!$meal || $meal->user_id != $request->user()->id) {
            return response([
                'message' => 'No such meal in your library.'
            ], 404);
        }

        // Retrieve today entry by meal_id or create it with the meal_id, user_id and amount
        $today = Today::firstOrCreate(
            ['meal_id' => $meal->id],
            [
                'user_id' => $meal->user_id,
                'amount' => 0,
            ]
        );

        $today->update(['amount' => $today->amount + 1]);

        return response([
            'meal' => $today
        ], 201);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {

        $today = Today::where('user_id', $request->user()->id)->find($id);

        if (!$today) {
            return response([
                'message' => 'No such meal on your TodayList.'
            ], 404);
        }

        $data = $request->validate([
            'amount' => 'required|numeric'
        ]);

        $today->update($data);

        return response([
            'meal' => $today,
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, string $id)
    {
        $today = Today::where('user_id', $request->user()->id)->find($id);

        if (!$today) {
            return response([
                'message' => 'No such meal on your TodayList.'
            ], 404);
        }

        $today->delete();

        return response([
            'meal' => $today,
        ], 200);
    }

    /**
     * Delete every entry from storage that belongs to currently authenticaed user.
     */
    public function drop(Request $request)
    {
        Today::where('user_id', $request->user()->id)->delete();

        return response(200);
    }
}
