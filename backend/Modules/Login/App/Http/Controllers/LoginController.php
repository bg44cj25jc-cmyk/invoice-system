<?php

namespace Modules\Login\App\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Login\App\Http\Services\LoginService;
use DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    protected $loginService;

    public function __construct(LoginService $loginService)
    {
        $this->loginService = $loginService;
    }

    /**
     * Display Login View
     * @return Renderable
     */
    public function index()
    {



        return view('login::index');
    }

    /**
     * @param Request $request
     * @return RedirectResponse
     */
    public function submitLogin(Request $request): RedirectResponse
    {
        $credentials = $request->only('email', 'password');
        $loggedIn = $this->loginService->attemptLogin($credentials);
        if ($loggedIn) {
            return redirect()->intended('/dashboard');
        } else {
            return redirect()->back()->withErrors(['error' => 'Invalid credentials.']);
        }
    }

    /**
     * Logout
     *
     * @return RedirectResponse
     */
    public function logOut(): RedirectResponse
    {
        $this->loginService->logOut();
        return redirect('/login');
    }
	
	
	
	
	
	
	
	
	
	
	
	
	/**
     * Change Password
     */
	
	public function showChangePasswordForm()
    { //echo 11;exit;
        $user = Auth::user();
        if (empty($user)) {
            return redirect('/login');
        }
        return view('login::change-password');
    }

    /**
     * Update password
     */
    public function updatePassword(Request $request)
    {
        $user = Auth::user();
        if (empty($user)) {
            return redirect('/login');
        }

        // Validation
        $request->validate([
            'current_password' => 'required|string',
            'new_password' => [
                'required',
                'string',
                'min:8',
                'confirmed',
                'different:current_password',
                'regex:/[a-z]/',      // must contain at least one lowercase letter
                'regex:/[A-Z]/',      // must contain at least one uppercase letter
                'regex:/[0-9]/',      // must contain at least one digit
                'regex:/[@$!%*#?&]/', // must contain a special character
            ],
            'new_password_confirmation' => 'required|string',
        ], [
            'current_password.required' => 'Current password is required',
            'new_password.required' => 'New password is required',
            'new_password.min' => 'Password must be at least 8 characters',
            'new_password.confirmed' => 'Password confirmation does not match',
            'new_password.different' => 'New password must be different from current password',
            'new_password.regex' => 'Password must contain uppercase, lowercase, number and special character',
            'new_password_confirmation.required' => 'Password confirmation is required',
        ]);

        // Change password
        $result = $this->loginService->changePassword(
            $user,
            $request->current_password,
            $request->new_password
        );

        if ($result['success']) {
            return redirect()->back()->with('success', $result['message']);
        }

        return redirect()->back()
            ->withErrors(['current_password' => $result['message']])
            ->withInput();
    }

    /**
     * Validate password strength (AJAX)
     */
    public function validatePassword(Request $request)
    {
        $password = $request->input('password');
        $errors = [];

        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters long';
        }
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Password must contain at least one uppercase letter';
        }
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'Password must contain at least one lowercase letter';
        }
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'Password must contain at least one number';
        }
        if (!preg_match('/[@$!%*#?&]/', $password)) {
            $errors[] = 'Password must contain at least one special character';
        }

        return response()->json([
            'valid' => empty($errors),
            'errors' => $errors
        ]);
    }
	
	
    

	
	
	
	
	
}
