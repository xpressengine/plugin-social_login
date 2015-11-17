<?php
/**
 *  This file is part of the Xpressengine package.
 *
 * PHP version 5
 *
 * @category
 * @package     Xpressengine\
 * @author      XE Team (khongchi) <khongchi@xpressengine.com>
 * @copyright   2000-2014 Copyright (C) NAVER <http://www.navercorp.com>
 * @license     http://www.gnu.org/licenses/lgpl-3.0-standalone.html LGPL
 * @link        http://www.xpressengine.com
 */
namespace Xpressengine\Plugins\SocialLogin\Authenticators;

use Xpressengine\Plugins\SocialLogin\Providers\NaverProvider;

/**
 * @category
 * @package     Xpressengine\Plugins\SocialLogin\Authenticator
 * @author      XE Team (khongchi) <khongchi@xpressengine.com>
 * @license     http://www.gnu.org/licenses/lgpl-3.0-standalone.html LGPL
 * @link        http://www.xpressengine.com
 */
class NaverAuth extends AbstractAuth
{
    protected function extendProvider()
    {
        $config = $this->getConfig($this->provider);
        $this->socialite->extend(
            $this->provider,
            function ($app) use ($config) {
                return new NaverProvider(
                    $app['request'], $config['client_id'],
                    $config['client_secret'], $config['redirect']
                );
            }
        );
    }
}
