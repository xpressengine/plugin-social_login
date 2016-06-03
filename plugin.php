<?php
/**
 * @author      XE Developers <developers@xpressengine.com>
 * @copyright   2015 Copyright (C) NAVER Corp. <http://www.navercorp.com>
 * @license     LGPL-2.1
 * @license     http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html
 * @link        https://xpressengine.io
 */

namespace Xpressengine\Plugins\SocialLogin;

use Illuminate\Http\Request;
use Route;
use Xpressengine\Plugin\AbstractPlugin;
use Xpressengine\User\UserInterface;

class Plugin extends AbstractPlugin
{
    protected $providers = [];

    public function boot()
    {

        $this->providers = $this->resolveProviders();

        // register settings menu
        $this->registerSettingsMenu();

        // register member settings section
        $this->registerSection();

        // register route
        $this->route($this->providers);

        // set config for redirect
        config(['services' => $this->providers]);

        foreach ($this->providers as $provider => $info) {
            config(['services.'.$provider.'.redirect' => route('social_login::connect.'.$provider)]);
        }
    }

    public function getProviders()
    {
        return $this->providers;
    }

    private function route($providers)
    {
        $this->routeSettings();
        $this->routeConnect($providers);
        $this->routeDisconnect($providers);
    }

    private function registerSettingsMenu()
    {
        app('xe.register')->push(
            'settings/menu',
            'member.social_login@default',
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
                'title' => '외부 로그인 설정',
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
        $providers = require __DIR__.'/option.php';

        $resolvedProvders = [];
        foreach ($providers as $provider => $info) {
            if ($info['client_id']) {
                $resolvedProvders[$provider] = $info;
            }
        }

        return $resolvedProvders;
    }

    /**
     * routeSettings
     *
     * @return void
     */
    private function routeSettings()
    {
        // register setting page
        Route::settings(
            static::getId(),
            function () {
                Route::get(
                    '/',
                    [
                        'as' => 'social_login::settings',
                        'uses' => function () {
                            return \XePresenter::make('social_login::tpl.setting');
                        },
                        'permission' => 'member.list',
                        'settings_menu' => 'member.social_login@default'
                    ]
                );
            }
        );
    }

    /**
     * routeLogin
     *
     * @param $providers
     *
     * @return void
     */
    private function routeConnect($providers)
    {
        Route::fixed(
            static::getId(),
            function () use ($providers) {
                // register each provider's connect page
                Route::group(
                    ['prefix' => 'login'],
                    function () use ($providers) {
                        // if a provider's client_id is setted, register a route for the provider
                        foreach ($providers as $provider => $info) {
                            if (array_has($info, 'client_id')) {
                                Route::get(
                                    $provider,
                                    [
                                        'as' => 'social_login::connect.'.$provider,
                                        'uses' => function (Request $request) use ($provider) {
                                            $namespace = 'Xpressengine\\Plugins\\SocialLogin\\Authenticators\\';
                                            $className = $namespace.studly_case($provider).'Auth';
                                            $auth = new $className($provider);
                                            $param = $auth->getCallbackParameter();

                                            $hasCode = $request->has($param);
                                            return $auth->execute($hasCode);
                                        }
                                    ]
                                );
                            }
                        }
                    }
                );
            }
        );
    }

    /**
     * routeLogin
     *
     * @param $providers
     *
     * @return void
     */
    private function routeDisconnect($providers)
    {
        Route::fixed(
            static::getId(),
            function () use ($providers) {
                // register each provider's connect page
                Route::group(
                    ['prefix' => 'disconnect', 'middleware' => 'auth'],
                    function () use ($providers) {
                        // if a provider's client_id is setted, register a route for the provider
                        foreach ($providers as $provider => $info) {
                            if (array_has($info, 'client_id')) {
                                Route::get(
                                    $provider,
                                    [
                                        'as' => 'social_login::disconnect.'.$provider,
                                        'uses' => function (Request $request) use ($provider) {

                                            // execute auth
                                            $namespace = 'Xpressengine\\Plugins\\SocialLogin\\Authenticators\\';
                                            $className = $namespace.studly_case($provider).'Auth';
                                            $auth = new $className($provider);
                                            $param = $auth->getCallbackParameter();

                                            $hasCode = $request->has($param);
                                            $auth->disconnect($hasCode);

                                            return redirect()->back()->with(
                                                'alert',
                                                [
                                                    'type' => 'success',
                                                    'message' => '연결해제 되었습니다'
                                                ]
                                            );
                                        }
                                    ]
                                );
                            }
                        }
                    }
                );
            }
        );
    }
}
