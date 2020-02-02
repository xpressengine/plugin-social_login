<?php
/**
 * NaverProvider.php
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

namespace Xpressengine\Plugins\SocialLogin\Providers;

use Exception;
use GuzzleHttp\ClientInterface;
use Laravel\Socialite\Two\AbstractProvider;
use Laravel\Socialite\Two\ProviderInterface;
use Laravel\Socialite\Two\User;

/**
 * NaverProvider
 *
 * @category    SocialLogin
 * @package     Xpressengine\Plugins\SocialLogin
 * @author      XE Developers <developers@xpressengine.com>
 * @copyright   2019 Copyright XEHub Corp. <https://www.xehub.io>
 * @license     http://www.gnu.org/licenses/lgpl-3.0-standalone.html LGPL
 * @link        https://xpressengine.io
 */
class NaverProvider extends AbstractProvider implements ProviderInterface
{
    /**
     * get auth url
     *
     * @param string $state state
     *
     * @return mixed
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase('https://kauth.kakao.com/oauth/authorize', $state);
    }

    /**
     * get token url
     * {@inheritdoc}
     * @return string
     */
    protected function getTokenUrl()
    {
        return 'https://kauth.kakao.com/oauth/token';
    }

    /**
     * Get the access token response for the given code.
     *
     * @param  string $code code
     *
     * @return array
     */
    public function getAccessTokenResponse($code)
    {
        $query = $this->getTokenFields($code);
        $query['grant_type'] = 'authorization_code';

        $response = $this->getHttpClient()->get($this->getTokenUrl(), [
            'headers' => ['Accept' => 'application/json'],
            'query' => $query,
        ]);

        return json_decode($response->getBody(), true);
    }

    /**
     * Get the raw user for the given access token.
     *
     * @param  string $token token
     *
     * @return array
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get(
            'https://kapi.kakao.com//v1/user/update_profile',
            [
                'headers' => [
                    'Accept' => '*/*',
                    'Authorization' => 'Bearer ' . $token,
                ]
            ]
        );

        $user = (array)simplexml_load_string($response->getBody())->response;

        return array_map(function ($field) {
            return (string)$field;
        }, $user);
    }

    /**
     * map user to object
     *
     * @param array $user user
     *                    {@inheritdoc}
     *
     * @return mixed
     */
    protected function mapUserToObject(array $user)
    {
        return (new User)->setRaw($user)->map([
            'id' => $user['id'],
            'nickname' => array_get($user, 'nickname'),
            'name' => array_get($user, 'name'),
            'email' => array_get($user, 'email'),
            'avatar' => array_get($user, 'profile_image'),
        ]);
    }
}
