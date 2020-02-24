<?php
/**
 * Plugin.php
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

namespace Xpressengine\Plugins\SocialLogin;

use Laravel\Socialite\Contracts\Factory as Socialite;
use Route;
use XeLang;
use Xpressengine\Plugin\AbstractPlugin;
use Xpressengine\Plugins\SocialLogin\Providers\NaverProvider;
use Xpressengine\Plugins\SocialLogin\Providers\KakaoProvider;
use Xpressengine\User\UserHandler;
use XeInterception;

/**
 * Plugin
 *
 * @category    SocialLogin
 * @package     Xpressengine\Plugins\SocialLogin
 * @author      XE Developers <developers@xpressengine.com>
 * @copyright   2019 Copyright XEHub Corp. <https://www.xehub.io>
 * @license     http://www.gnu.org/licenses/lgpl-3.0-standalone.html LGPL
 * @link        https://xpressengine.io
 */
class Plugin extends AbstractPlugin
{
    /**
     * install
     *
     * @return void
     */
    public function install()
    {
        $this->importConfig();
        $this->importLang();
    }

    /**
     * update
     *
     * @return void
     */
    public function update()
    {
        $this->importLang();
    }

    /**
     * boot
     *
     * @return void
     */
    public function boot()
    {
        // register settings menu
        $this->registerSettingsMenu();

        // register user settings section
        $this->registerSection();

        // register route
        $this->routes();

        app('router')->pushMiddlewareToGroup('web', Middleware::class);
    }

    /**
     * register
     *
     * @return void
     */
    public function register()
    {
        app()->singleton(Handler::class, function ($app) {
            $proxyHandler = XeInterception::proxy(Handler::class, 'SocialLoginHandler');
            $handler = new $proxyHandler($app[Socialite::class], $app['xe.user'], $app['xe.db'], $app['xe.config']);

            $handler->setRequest($app['request']);

            $app->refresh('request', $handler, 'setRequest');

            return $handler;
        });
        app()->alias(Handler::class, 'xe.social_login');

        app()->resolving('xe.social_login', function ($handler) {
            $providers = $handler->getConfig();
            if (empty($providers)) {
                $this->importConfig();
                $providers = $handler->getConfig();
            }

            // set config for redirect
            foreach ($providers as $provider => $info) {
                array_set($info, 'redirect', route('social_login::connect', ['provider' => $provider]));
                config(['services.' . $provider => $info]);
            }
        });

        app()->resolving(Socialite::class, function ($socialite) {
            $socialite->extend('naver', function ($app) {
                $config = $app['config']['services.naver'];
                return new NaverProvider(
                    $app['request'],
                    $config['client_id'],
                    $config['client_secret'],
                    $config['redirect']
                );
            });
        });
		//02.02 추가
		app()->resolving(Socialite::class, function ($socialite) {
            $socialite->extend('kakao', function ($app) {
                $config = $app['config']['services.kakao'];
                return new KakaoProvider(
                    $app['request'],
                    $config['client_id'],
                    $config['client_secret'],
                    $config['redirect']
                );
            });
        });
    }

    /**
     * import config
     *
     * @return void
     */
    protected function importConfig()
    {
        $providers = require __DIR__ . '/option.php';

        foreach ($providers as $provider => $info) {
            $providers[$provider]['activate'] = !!$info['client_id'];
        }

        app('xe.config')->set('social_login', ['providers' => $providers]);
    }

    /**
     * import lang
     *
     * @return void
     */
    protected function importLang()
    {
        XeLang::putFromLangDataSource('social_login', $this->path('langs/lang.php'));
    }

    /**
     * register route
     *
     * @return void
     */
    private function routes()
    {
        Route::group([
            'namespace' => 'Xpressengine\\Plugins\\SocialLogin\\Controllers',
            'middleware' => ['web']
        ], function () {
            require __DIR__ . '/routes.php';
        });
    }

    /**
     * register settings menu
     *
     * @return void
     */
    private function registerSettingsMenu()
    {
        app('xe.register')->push(
            'settings/menu',
            'user.social_login@default',
            [
                'title' => 'social_login::socialLogin',
                'description' => 'social_login::descSocialLoginMenu',
                'display' => true,
                'ordering' => 350
            ]
        );
    }

    /**
     * register section
     *
     * @return void
     */
    private function registerSection()
    {
        UserHandler::setSettingsSections('social_login@section', [
            'title' => 'social_login::socialLoginSetting',
            'content' => function ($user) {
                $providers = app('xe.social_login')->getConfig();
                $accountList = data_get($user, 'accounts', []);

                $accounts = [];

                foreach ($accountList as $account) {
                    $accounts[$account->provider] = $account;
                }

                return view('social_login::tpl.member_setting', compact('accounts', 'providers'));
            }
        ]);
    }
}
