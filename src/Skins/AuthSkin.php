<?php
/**
 *  This file is part of the Xpressengine package.
 *
 * PHP version 5
 *
 * @category    SocialLogin
 * @package     Xpressengine\Plugins\SocialLogin
 * @author      XE Team (khongchi) <khongchi@xpressengine.com>
 * @copyright   2000-2014 Copyright (C) NAVER <http://www.navercorp.com>
 * @license     http://www.gnu.org/licenses/lgpl-3.0-standalone.html LGPL
 * @link        http://www.xpressengine.com
 */
namespace Xpressengine\Plugins\SocialLogin\Skins;

use Illuminate\Contracts\Support\Renderable;
use Xpressengine\Skins\Member\AuthSkin as CoreSkin;
use Xpressengine\Plugins\SocialLogin\Plugin;

/**
 * @category
 * @package     Xpressengine\Plugins\SocialLogin\Skins
 * @author      XE Team (khongchi) <khongchi@xpressengine.com>
 * @license     http://www.gnu.org/licenses/lgpl-3.0-standalone.html LGPL
 * @link        http://www.xpressengine.com
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
            return $this->renderBlade();
        }

        return view($plugin->getIdWith('views.register'), compact('providers'));
    }

}
