<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TestMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, $data): Response
    {
        return response()->json(['sdf'=>$data],200);
        info($data);
        if (2<4) {
            return redirect('/api/redirect-url');
        }
        $request->merge(['test' => "data from middleware"]);
        return $next($request);
    }
}
