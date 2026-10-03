<?php

namespace Modules\User\App\Http\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Modules\User\App\Models\User;

class UserService
{
    /**
     * Get all users
     */
    public function getAllUsers()
    {
        return User::orderBy('id', 'desc')->get();
    }

    /**
     * Get single user by ID
     */
    public function getUserById($id)
    {
        return User::find($id);
    }

    /**
     * Create a new user
     */
    public function createUser($data)
    {
        return User::create([
            'name'       => $data['name'],
            'email'      => $data['email'],
            'phone'      => $data['phone'] ?? null,
            'password'   => Hash::make($data['password']),
            'status'     => $data['status'] ?? 1,
        ]);
    }

    /**
     * Update user details
     */
    public function updateUser($id, $data)
    {
        $user = User::find($id);
        if (!$user) {
            return false;
        }

        $user->update([
            'name'     => $data['name'] ?? $user->name,
            'email'    => $data['email'] ?? $user->email,
            'phone'    => $data['phone'] ?? $user->phone,
            'status'   => $data['status'] ?? $user->status,
        ]);

        return $user;
    }

    /**
     * Delete a user
     */
    public function deleteUser($id)
    {
        $user = User::find($id);
        if (!$user) {
            return false;
        }

        return $user->delete();
    }

    /**
     * Update user password
     */
    public function updatePassword($id, $newPassword)
    {
        $user = User::find($id);
        if (!$user) {
            return false;
        }

        $user->password = Hash::make($newPassword);
        return $user->save();
    }

    /**
     * Authenticate a user login
     */
    public function authenticate($credentials)
    {
        if (Auth::attempt($credentials)) {
            return Auth::user();
        }

        return false;
    }
}
