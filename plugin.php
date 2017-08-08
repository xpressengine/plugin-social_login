<?php
/**
 * @author      XE Developers <developers@xpressengine.com>
 * @copyright   2015 Copyright (C) NAVER Corp. <http://www.navercorp.com>
 * @license     LGPL-2.1
 * @license     http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html
 * @link        https://xpressengine.io
 */

namespace Xpressengine\Plugins\SocialLogin;

use Route;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Xpressengine\Plugin\AbstractPlugin;
use Xpressengine\Plugins\SocialLogin\Authenticators\AbstractAuth;
use Xpressengine\User\UserInterface;

class Plugin extends AbstractPlugin
{
    protected $providers = [];

    public function install()
    {
        $providers = require __DIR__.'/option.php';

        foreach ($providers as $provider => $info) {
            if ($info['client_id']) {
                $providers[$provider]['activate'] = true;
            } else {
                $providers[$provider]['activate'] = false;
            }
        }

        app('xe.config')->set('social_login', [
            'providers' => $providers
        ]);
    }
    public function checkUpdated()
    {
        $config = app('xe.config')->get('social_login');
        if($config === null) {
            return false;
        }

        return true;

    }

    public function update()
    {
        $config = app('xe.config')->get('social_login');
        $providers = require __DIR__.'/option.php';

        foreach ($providers as $provider => $info) {
            if ($info['client_id'] !== '') {
                $providers[$provider]['activate'] = true;
            } else {
                $providers[$provider]['activate'] = false;
            }
        }

        if($config === null) {
            app('xe.config')->set('social_login', [
                'providers' => $providers
            ]);
        }
    }

    public function boot()
    {
        $this->providers = $this->resolveProviders();

        // register settings menu
        $this->registerSettingsMenu();

        // register member settings section
        $this->registerSection();

        $this->registerForUserRegister();

        // register route
        $this->route($this->providers);

        // set config for redirect

        foreach ($this->providers as $provider => $info) {
            array_set($info, 'redirect', route('social_login::connect', ['provider' => $provider]));
            config(['services.'.$provider => $info]);
        }
    }

    public function getProviders()
    {
        return $this->providers;
    }

    public function getAuthenticator($provider)
    {
        $providers = $this->getProviders();
        $namespace = 'Xpressengine\\Plugins\\SocialLogin\\Authenticators\\';
        $className = $namespace.studly_case($provider).'Auth';

        $proxyClass = app('xe.interception')->proxy($className, 'SocialLoginAuth');

        return new $proxyClass($provider);
    }

    private function route($providers)
    {
        $this->routeSettings($providers);
        $this->routeConnect();
        $this->routeDisconnect();
    }

    private function registerSettingsMenu()
    {
        app('xe.register')->push(
            'settings/menu',
            'user.social_login@default',
            [
                'title' => '소셜로그인',
                'description' => '소셜로그인을 설정하는 방법을 안내합니다.',
                'display' => true,
                'ordering' => 350
            ]
        );
    }

    private function registerSection()
    {
        $plugin = $this;
        app('xe.register')->push(
            'user/settings/section',
            'social_login@section',
            [
                'title' => '소셜 로그인 설정',
                'content' => function ($member) use ($plugin) {
                    return $plugin->getMemberSettingSection($member);
                }
            ]
        );
    }

    protected function registerForUserRegister()
    {
        app('xe.register')->push(
            'user/register/guard',
            'social_login',
            function () {
                $providers = $this->getProviders();
                return view($this->view('views.register'), compact('providers'))->render();
            }
        );

        app('xe.register')->push(
            'user/register/form',
            'social_login',
            function ($registerToken) {
                if ($registerToken->guard !== 'social_login') {
                    return null;
                }

                $provider = $registerToken->provider;
                $email = $registerToken->email;
                $displayName = $registerToken->displayName;

                app('xe.frontend')->html('social_login.register')->content("
                    <script>
                        $('input[name=email]').attr('readonly','readonly').val('{$email}');
                        $('input[name=displayName]').val('{$displayName}');
                        $('input[name=password]').parent().remove();
                        $('input[name=password_confirmation]').parent().remove();
                    </script>
                    ")->load();

                $providers = $this->getProviders();

                return view($this->view('views.form'), compact('provider', 'providers'))->render();
            }
        );

        intercept('XeUser@create', 'social_login@create', function($target, $data, $registerToken = null) {

            if ($registerToken !== null) {
                if ($registerToken->guard !== 'social_login') {
                    return $target($data, $registerToken);
                }

                $provider = $registerToken->provider;
                $token = $registerToken->token;
                $tokenSecret = data_get($registerToken, 'tokenSecret');
                $email = $registerToken->email;

                if ($email && $email !== $data['email']) {
                    throw new HttpException(400, '잘못된 이메일 정보입니다.');
                }

                /** @var AbstractAuth $auth */
                $auth = $this->getAuthenticator($provider);
                $userInfo = $auth->getAuthenticatedUser($token, $tokenSecret);

                $userData = $auth->resolveUserInfo($userInfo);
                $data['account'] = $userData['account'];
            }

            $user = $target($data, $registerToken);
            return $user;
        });


    }

    protected function getMemberSettingSection(UserInterface $member)
    {
        $providers = $this->providers;
        $accountList = data_get($member, 'accounts', []);

        $accounts = [];

        foreach ($accountList as $account) {
            $accounts[$account->provider] = $account;
        }

        app('xe.frontend')->html('social_login::addlink')->content(
            "
            <script>
            $(function () {
                $('.__xe_socialConnect').click(function(){
                    window.open($(this).data('link'), 'social_login_connect',\"width=600,height=400,scrollbars=no\");
                });
                $('.__xe_socialDisconnect').click(function(){
                    location.href = $(this).data('link');
                })
            });
            </script>
        "
        )->load();

        return \View::make('social_login::tpl.member_setting', compact('member', 'accounts', 'providers'));
    }

    private function resolveProviders()
    {
        // set config
        $config = app('xe.config')->get('social_login');
        if($config === null) {
            return [];
        }
        $providers = $config->get('providers');

        foreach ($providers as $provider => $info) {
            if(isset($info['use'])) {
                $info['activate'] = $info['use'];
                unset($info['use']);
                $providers[$provider] = $info;
            }
        }

        app('xe.config')->setVal('social_login.providers', $providers);

        return $providers;
    }

    /**
     * routeSettings
     *
     * @param $providers
     */
    private function routeSettings($providers)
    {
        // register setting page
        Route::settings(
            static::getId(),
            function () use ($providers) {
                Route::get(
                    '/',
                    [
                        'as' => 'social_login::settings',
                        'uses' => 'Xpressengine\Plugins\SocialLogin\Controllers\SettingsController@index',
                        'permission' => 'user.setting',
                        'settings_menu' => 'user.social_login@default'
                    ]
                );
                Route::group(['prefix'=>'providers', 'namespace'=> 'Xpressengine\Plugins\SocialLogin\Controllers'], function(){
                    Route::get(
                        '{provider}',
                        [
                            'as' => 'social_login::settings.provider.show',
                            'uses' => 'SettingsController@show',
                            'permission' => 'user.setting'
                        ]
                    );
                    Route::get(
                        '{provider}/edit',
                        [
                            'as' => 'social_login::settings.provider.edit',
                            'uses' => 'SettingsController@edit',
                            'permission' => 'user.setting'
                        ]
                    );
                    Route::put(
                        '{provider}',
                        [
                            'as' => 'social_login::settings.provider.update',
                            'uses' => 'SettingsController@update',
                            'permission' => 'user.setting'
                        ]
                    );
                });
            }
        );
    }

    /**
     * routeLogin
     *
     * @return void
     */
    private function routeConnect()
    {
        Route::fixed(
            static::getId(),
            function () {
                Route::group(
                    ['prefix' => 'login', 'namespace'=> 'Xpressengine\Plugins\SocialLogin\Controllers'],
                    function () {

                        Route::get(
                            '{provider}',
                            [
                                'as' => 'social_login::connect',
                                'uses' => 'ConnectController@connect',
                            ]
                        );
                    }
                );
            }
        );
    }

    /**
     * routeLogin
     *
     * @return void
     */
    private function routeDisconnect()
    {
        Route::fixed(
            static::getId(),
            function () {
                // register each provider's connect page
                Route::group(
                    ['prefix' => 'disconnect', 'middleware' => 'auth', 'namespace'=> 'Xpressengine\Plugins\SocialLogin\Controllers'],
                    function () {
                        Route::get(
                            '{provider}',
                            [
                                'as' => 'social_login::disconnect',
                                'uses' => 'ConnectController@disconnect',
                            ]
                        );
                    }
                );
            }
        );
    }
}
