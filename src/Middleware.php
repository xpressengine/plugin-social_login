<?php
/**
 * Middleware.php
 *
 * This file is part of the Xpressengine package.
 *
 * PHP version 7
 *
 * @category    SocialLogin
 * @package     Xpressengine\Plugins\SocialLogin
 * @author      XE Developers <developers@xpressengine.com>
 * @copyright   2015 Copyright (C) NAVER <http://www.navercorp.com>
 * @license     http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html LGPL-2.1
 * @link        http://www.xpressengine.com
 */

namespace Xpressengine\Plugins\SocialLogin;

use Closure;

/**
 * Middleware
 *
 * @category    SocialLogin
 * @package     Xpressengine\Plugins\SocialLogin
 * @author      XE Developers <developers@xpressengine.com>
 * @copyright   2015 Copyright (C) NAVER <http://www.navercorp.com>
 * @license     http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html LGPL-2.1
 * @link        http://www.xpressengine.com
 */
class Middleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request $request request
     * @param  \Closure                 $next    next middleware
     *
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if ($request->routeIs('login') &&
            in_array('GET', $request->route()->methods()) &&
            $request->get('by') !== 'email'
        ) {
            return redirect()->route('social_login::login', $request->query());
        }

        return $next($request);
    }
}
