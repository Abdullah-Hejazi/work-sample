<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class IpRestrictionMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if ($request->user() && $request->user()->ip_restricted) {
            $ips = $request->user()->ips()->pluck('ip')->toArray();

            if (!in_array($request->ip(), $ips)) {
                return response()->json([
                    'title'     =>      'لا يمكنك دخول هذا الحساب من هذا الجهاز',
                    'text'   =>      'يبدو أن هذا الحساب مرتبط بمعرف (أي بي) خاص . ولذلك لا يمكنك دخول هذا الحساب إلا بإستخدام من خلال جهاز يستخدم معرف الأي بي المخصص. إذا كنت تظن أن هناك خطأ ما , برجاء التواصل مع فريق الدعم الفني.',
                    'icon'      =>      'error'
                ], 450);
            }
        }

        return $next($request);
    }
}
