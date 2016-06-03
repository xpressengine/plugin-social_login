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
use Laravel\Socialite\Two\AbstractProvider;
use Laravel\Socialite\Two\ProviderInterface;
use Laravel\Socialite\Two\User;

class NaverProvider extends AbstractProvider implements ProviderInterface
{

    /**
     * The scopes being requested.
     *
     * @var array
     */
    protected $scopes = [];

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

    public function getAccessToken($code)
    {
        $query               = $this->getTokenFields($code);
        $query['grant_type'] = 'authorization_code';

        $response = $this->getHttpClient()->get(
            $this->getTokenUrl(), [
            'headers' => ['Accept' => 'application/json'],
            'query' => $query,
        ]
        );

        return $this->parseAccessToken($response->getBody());
    }

    /**
     * {@inheritdoc}
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

    /**
     * Get the default options for an HTTP request.
     *
     * @return array
     */
    protected function getRequestOptions($token)
    {
        return [
            'headers' => [
                'Accept' => 'application/json',
                'Authorization' => 'Bearer '.$token,
            ],
        ];
    }
}
