<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DepartmentMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$departments): Response
    {
        $user = $request->attributes->get('user') ?? $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        $userRole = strtolower($user->role?->name ?? $user->roles?->name ?? '');

        // Admin and superadmin bypass
        if ($userRole === 'admin' || $userRole === 'superadmin') {
            return $next($request);
        }

        $userDeptName = strtolower($user->department?->name ?? $user->departments?->name ?? '');
        $userDeptId = (string)($user->department_id ?? $user->departments_id ?? '');

        $allowedDepts = array_map('strtolower', $departments);

        $hasAccess = in_array($userDeptName, $allowedDepts) || in_array($userDeptId, $allowedDepts);

        if (!$hasAccess) {
            return response()->json([
                'success' => false,
                'message' => 'Department Access Denied'
            ], 403);
        }

        return $next($request);
    }
}
