<?php

namespace App\Http\Controllers;

use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * Display a listing of the users.
     */
    public function index(): Response
    {
        $users = User::all();

        return Inertia::render('User/Index', [
            'users' => $users,
        ]);
    }

    // Fetch user details by ID
    public function getUserDetails($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'position' => $user->position,
            'card_id' => $user->card_id,
            'campus' => $user->campus,
            'division' => $user->division,
            'department' => $user->department,
        ]);
    }
}
