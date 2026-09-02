<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AuthSecret
{
    public function handle(Request $request, Closure $next)
    {
        $secret = env('IMPORT_SECRET', 'musicarchive2024');

        if ($request->has('key') && hash_equals($secret, $request->input('key'))) {
            return $next($request);
        }

        abort(4003);
    }
}