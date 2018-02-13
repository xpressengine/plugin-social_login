<?php

/**
 * Middleware.php
 *
 * PHP version 5
 *
 * @category
 * @package
 * @author      XE Developers <developers@xpressengine.com>
 * @copyright   2015 Copyright (C) NAVER Corp. <http://www.navercorp.com>
 * @license     http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html LGPL-2.1
 * @link        https://xpressengine.io
 */

namespace Xpressengine\Plugins\SocialLogin;

use Closure;

class Middleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (
            $request->routeIs('auth.register') &&
            $request->get('by') !== 'email' &&
            !$request->get('token')
        ) {
            return redirect()->route('social_login::register');
        }

        if (
            $request->routeIs('login') &&
            in_array('GET', $request->route()->methods()) &&
            $request->get('by') !== 'email'
        ) {
            return redirect()->route('social_login::login', $request->query());
        }

        return $next($request);
    }
}
