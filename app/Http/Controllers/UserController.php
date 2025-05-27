<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class UserController extends Controller
{
    /**
     * Upload the user's signature.
     */
    public function uploadSignature(Request $request, $id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $validated = $request->validate([
            'signature' => 'required|image|max:2048',
        ]);

        if ($user->signature) {
            Storage::disk('public')->delete($user->signature);
        }

        $path = $request->file('signature')->store('signature', 'public');
        $user->signature = $path;
        $user->save();

        return response()->json(['message' => 'Signature uploaded successfully', 'path' => $path]);
    }

    /**
     * Upload the user's profile picture.
     */
    public function uploadProfile(Request $request, $id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $validated = $request->validate([
            'profile' => 'required|image|max:2048',
        ]);

        if ($user->profile) {
            Storage::disk('public')->delete($user->profile);
        }

        $path = $request->file('profile')->store('profile', 'public');
        $user->profile = $path;
        $user->save();

        return response()->json(['message' => 'Profile picture uploaded successfully', 'path' => $path]);
    }

    /**
     * Delete the user's profile and signature.
     */
    public function deleteProfileAndSignature($id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        if ($user->profile) {
            Storage::delete($user->profile);
            $user->profile = null;
        }

        if ($user->signature) {
            Storage::delete($user->signature);
            $user->signature = null;
        }

        $user->save();

        return response()->json(['message' => 'Profile and signature deleted successfully']);
    }

    /**
     * Replace the user's profile or signature.
     */
    public function replaceFile(Request $request, $id, $type)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $validated = $request->validate([
            'file' => 'required|image|max:2048',
        ]);

        if ($type === 'signature') {
            if ($user->signature) {
                Storage::delete($user->signature);
            }
            $path = $request->file('file')->store('signatures');
            $user->signature = $path;
        } elseif ($type === 'profile') {
            if ($user->profile) {
                Storage::delete($user->profile);
            }
            $path = $request->file('file')->store('profiles');
            $user->profile = $path;
        } else {
            return response()->json(['message' => 'Invalid file type'], 400);
        }

        $user->save();

        return response()->json(['message' => ucfirst($type) . ' replaced successfully', 'path' => $path]);
    }

    /**
     * Get the user's signature or profile URL.
     */
    public function getFileUrl($id, $type)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        if ($type === 'signature' && $user->signature) {
            return response()->json(['url' => asset('storage/' . $user->signature)]);
        } elseif ($type === 'profile' && $user->profile) {
            return response()->json(['url' => asset('storage/' . $user->profile)]);
        } else {
            return response()->json(['message' => 'File not found'], 404);
        }
    }

    /**
     * Display a listing of the users.
     */
    public function index()
    {
        $users = User::select('name', 'email', 'card_id', 'position', 'campus', 'division', 'department', 'phone', 'extension')->get();
        return Inertia::render('User/Index', [
            'users' => $users,
        ]);
    }
}
