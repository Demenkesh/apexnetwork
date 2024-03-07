<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class ApiAdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            if (Auth::user()->role_as == '1') {
                if ($request->user()->tokenCan('server:admin')) {
                    return $next($request);
                }
            } else {
                return response()->json(['message' => 'Access Denied! as you are not as admin!', 'status' => Response::HTTP_FORBIDDEN], Response::HTTP_FORBIDDEN);
            }
        } else {
            return response()->json(['message' => 'Please login first!', 'status' => Response::HTTP_UNAUTHORIZED], Response::HTTP_UNAUTHORIZED);
        };
    }
}
