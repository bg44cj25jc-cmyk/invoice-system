<?php

namespace Modules\Login\App\Http\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Modules\Login\App\Models\User;

class LoginService
{
    /**
     *  Sign In a user
     *
     * @param array $requestData
     * @return bool
     */
    public function attemptLogin(array $requestData): bool
    {
        if (Auth::attempt(["email" => $requestData['email'], "password" => $requestData['password']])) {
            $user = Auth::user();
            return true;
        } else {
            return false;
        }
    }

    /**
     * Logout
     *
     * @return void
     */
    public function logOut(): void
    {
        Auth::logout();
    }
	
	public function changePassword($user, $currentPassword, $newPassword)
    {
        // Verify current password
        if (!Hash::check($currentPassword, $user->password)) {
            return [
                'success' => false,
                'message' => 'Current password is incorrect'
            ];
        }

        // Check if new password is same as current
        if (Hash::check($newPassword, $user->password)) {
            return [
                'success' => false,
                'message' => 'New password must be different from current password'
            ];
        }

        // Update password
        $user->password = $newPassword; // Will be hashed automatically
        $user->save();

        return [
            'success' => true,
            'message' => 'Password changed successfully'
        ];
    }

}
