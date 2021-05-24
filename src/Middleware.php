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
 * @copyright   2019 Copyright XEHub Corp. <https://www.xehub.io>
 * @license     http://www.gnu.org/licenses/lgpl-3.0-standalone.html LGPL
 * @link        https://xpressengine.io
 */

namespace Xpressengine\Plugins\SocialLogin;

use Closure;

/**
 * Middleware
 *
 * @category    SocialLogin
 * @package     Xpressengine\Plugins\SocialLogin
 * @author      XE Developers <developers@xpressengine.com>
 * @copyright   2019 Copyright XEHub Corp. <https://www.xehub.io>
 * @license     http://www.gnu.org/licenses/lgpl-3.0-standalone.html LGPL
 * @link        https://xpressengine.io
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
//            $request->get('by') !== 'email'
            $request->has('by') === false
        ) {
            return redirect()->route('social_login::login', $request->query());
        }

        return $next($request);
    }
}
