<?php
/**
 * Manager.php
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

use Illuminate\Support\Manager as AbstractManager;
use Laravel\Socialite\Contracts\Factory as Socialite;
use InvalidArgumentException;
use Xpressengine\Plugins\SocialLogin\Authenticators\GithubAuth;

class Manager extends AbstractManager
{
    protected function createGithubDriver()
    {
        $provider = $this->app[Socialite::class]->driver('github');
        return $this->buildAuthenticator(new GithubAuth($provider));
    }

    protected function buildAuthenticator($auth)
    {
        return new Authenticator($auth);
    }

    /**
     * Get the default driver name.
     *
     * @return string
     */
    public function getDefaultDriver()
    {
        throw new InvalidArgumentException('No Socialite driver was specified.');
    }
}
