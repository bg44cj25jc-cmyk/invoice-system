<?php

namespace Modules\User\app\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Auth\AuthenticationException;

class Authenticate extends Middleware
{
    /**
     * Prevent Laravel from redirecting to login page.
     */
    protected function redirectTo($request)
    {
        return null; // ✅ no redirect, avoids Route [login] error
    }

    /**
     * Return custom JSON response instead of redirect.
     */
    protected function unauthenticated($request, array $guards)
    {
        throw new AuthenticationException(
            'Unauthenticated.',
            $guards,
            $this->redirectTo($request)
        );
    }
}


//app/Http/Middleware/Authenticate.php

//protected function redirectTo($request)
//{
//    if (!$request->expectsJson()) {
//        abort(response()->json([
//            'status'  => false,
//            'message' => 'Unauthorized. Please login to continue.',
//            'errors'  => [
//                'auth' => ['User not authenticated']
//            ]
//        ], 401));
//    }
//}




