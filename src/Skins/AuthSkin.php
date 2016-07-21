<?php
/**
 *  This file is part of the Xpressengine package.
 *
 * @category    SocialLogin
 * @package     Xpressengine\Plugins\SocialLogin
 * @author      XE Developers (khongchi) <khongchi@xpressengine.com>
 * @copyright   2015 Copyright (C) NAVER Crop. <http://www.navercorp.com>
 * @license     LGPL-2.1
 * @license     http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html
 * @link        https://xpressengine.io
 */

namespace Xpressengine\Plugins\SocialLogin\Skins;

use Illuminate\Contracts\Support\Renderable;
use App\Skins\Member\AuthSkin as CoreSkin;
use Xpressengine\Plugins\SocialLogin\Plugin;

/**
 * @category
 * @package     Xpressengine\Plugins\SocialLogin\Skins
 */
class AuthSkin extends CoreSkin
{
    protected static $id;
    protected static $componentInfo = [];

    /**
     * Html을 생성하여 반환한다.
     *
     * @return Renderable|string
     */
    protected function login()
    {
        /** @var Plugin $plugin */
        $plugin = app(Plugin::class);
        $providers = $plugin->getProviders();

        return view($plugin->getIdWith('views.login'), compact('providers'));
    }

    protected function register()
    {
        /** @var Plugin $plugin */
        $plugin = app(Plugin::class);
        $providers = $plugin->getProviders();

        $use_email = app('request')->get('use_email', false);

        if ($use_email !== false) {
            return parent::register();
        }

        return view($plugin->getIdWith('views.register'), compact('providers'));
    }

}
