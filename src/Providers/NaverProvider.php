<?php
/**
 * @author      XE Developers <developers@xpressengine.com>
 * @copyright   2015 Copyright (C) NAVER Corp. <http://www.navercorp.com>
 * @license     LGPL-2.1
 * @license     http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html
 * @link        https://xpressengine.io
 */

namespace Xpressengine\Plugins\SocialLogin\Providers;

use Exception;
use GuzzleHttp\ClientInterface;
use Laravel\Socialite\Two\AbstractProvider;
use Laravel\Socialite\Two\ProviderInterface;
use Laravel\Socialite\Two\User;

class NaverProvider extends AbstractProvider implements ProviderInterface
{

    /**
     * {@inheritdoc}
     */
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase('https://nid.naver.com/oauth2.0/authorize', $state);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return 'https://nid.naver.com/oauth2.0/token';
    }

    /**
     * Get the access token response for the given code.
     *
     * @param  string  $code
     * @return array
     */
    public function getAccessTokenResponse($code)
    {
        $query               = $this->getTokenFields($code);
        $query['grant_type'] = 'authorization_code';

        $response = $this->getHttpClient()->get(
            $this->getTokenUrl(), [
                                    'headers' => ['Accept' => 'application/json'],
                                    'query' => $query,
                                ]
        );

        return json_decode($response->getBody(), true);
    }

    /**
     * Get the raw user for the given access token.
     *
     * @param  string  $token
     * @return array
     */
    protected function getUserByToken($token)
    {
        $response = $this->getHttpClient()->get(
            'https://apis.naver.com/nidlogin/nid/getUserProfile.xml',
            [
                'headers' => [
                    'Accept' => '*/*',
                    'Authorization' => 'Bearer '.$token,
                ]
            ]
        );

        $user = (array) simplexml_load_string($response->getBody())->response;
        return array_map(
            function ($field) {
                return (string) $field;
            }, $user
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function mapUserToObject(array $user)
    {
        return (new User)->setRaw($user)->map(
            [
                'id' => $user['id'],
                'nickname' => $user['nickname'],
                'name' => null,
                'email' => array_get($user, 'email'),
                'avatar' => $user['profile_image'],
            ]
        );
    }

}
