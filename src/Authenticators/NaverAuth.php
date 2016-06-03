<?php
/**
 *  This file is part of the Xpressengine package.
 *
 * @category
 * @package     Xpressengine\
 * @author      XE Developers (khongchi) <khongchi@xpressengine.com>
 * @copyright   2015 Copyright (C) NAVER Crop. <http://www.navercorp.com>
 * @license     LGPL-2.1
 * @license     http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html
 * @link        https://xpressengine.io
 */

namespace Xpressengine\Plugins\SocialLogin\Authenticators;

use Xpressengine\Plugins\SocialLogin\Providers\NaverProvider;

/**
 * @category
 * @package     Xpressengine\Plugins\SocialLogin\Authenticator
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
