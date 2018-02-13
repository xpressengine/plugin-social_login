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
        if ($config === null) {
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

        // register user settings section
        $this->registerSection();

        // register route
        $this->routes();

        // set config for redirect
        foreach ($this->providers as $provider => $info) {
            array_set($info, 'redirect', route('social_login::connect', ['provider' => $provider]));
            config(['services.'.$provider => $info]);
        }

        app('router')->pushMiddlewareToGroup('web', Middleware::class);
    }

    public function getProviders()
    {
        return $this->providers;
    }

    public function getAuthenticator($provider)
    {
        $namespace = 'Xpressengine\\Plugins\\SocialLogin\\Authenticators\\';
        $className = $namespace.studly_case($provider).'Auth';

        $proxyClass = app('xe.interception')->proxy($className, 'SocialLoginAuth');

        return new $proxyClass($provider);
    }

    private function routes()
    {
        Route::group(['namespace' => 'Xpressengine\\Plugins\\SocialLogin\\Controllers'], function () {
            require __DIR__ . '/routes.php';
        });
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
}
