<?php
/**
 *  This file is part of the Xpressengine package.
 *
 * PHP version 5
 *
 * @category
 * @package     Xpressengine\
 * @author      XE Team (developers) <developers@xpressengine.com>
 * @copyright   2015 Copyright (C) NAVER <http://www.navercorp.com>
 * @license     http://www.gnu.org/licenses/lgpl-3.0-standalone.html LGPL
 * @link        http://www.xpressengine.com
 */
namespace Xpressengine\Plugins\SocialLogin\Controllers;

use App\Http\Controllers\Controller;
use XePresenter;
use Xpressengine\Http\Request;
use Xpressengine\Plugins\SocialLogin\Plugin;
use Xpressengine\Plugins\SocialLogin\Skins\AuthSkin;

/**
 * @category    SocialLogin
 * @package     Xpressengine\Plugins\SocialLogin\Controllers
 * @author      XE Team (developers) <developers@xpressengine.com>
 * @copyright   2015 Copyright (C) NAVER <http://www.navercorp.com>
 * @license     http://www.gnu.org/licenses/lgpl-3.0-standalone.html LGPL
 * @link        http://www.xpressengine.com
 */
class SettingsController extends Controller
{
    protected $plugin = null;

    /**
     * SocialLoginController constructor.
     *
     * @param \Xpressengine\Plugins\SocialLogin\Plugin $plugin
     */
    public function __construct(Plugin $plugin)
    {
        $this->plugin = $plugin;
    }

    public function index()
    {
        if($this->plugin->checkUpdated() === false) {
            return redirect()->route('settings.plugins.show', 'social_login')->with('alert', ['type' => 'danger', 'message' => '소셜로그인 플러그인의 업데이트가 필요합니다. 업데이트 실행해주십시오.']);
        }

        $providers = $this->plugin->getProviders();

        app('xe.frontend')->js([
            'assets/core/xe-ui-component/js/xe-page.js',
            'assets/core/xe-ui-component/js/xe-form.js'
        ])->load();

        return \XePresenter::make('social_login::tpl.setting', compact('providers'));
    }

    public function show($provider)
    {
        $providers = $this->plugin->getProviders();
        return api_render('social_login::tpl.show', compact('providers', 'provider'));
    }

    public function edit($provider)
    {
        $providers = $this->plugin->getProviders();
        return api_render('social_login::tpl.edit', compact('providers', 'provider'));
    }

    public function update(Request $request, $provider)
    {
        $inputs = $request->only('activate','client_id','client_secret','title');
        $inputs['activate'] = $inputs['activate'] === 'Y' ? true : false;
        $config = app('xe.config')->get('social_login');
        $providers = $config->get('providers');
        $providers[$provider] = $inputs;
        app('xe.config')->setVal('social_login.providers', $providers);

        $url = route('social_login::settings.provider.show', ['provider'=> $provider]);
        return XePresenter::makeApi([
            'type' => 'success', 'message' => xe_trans('xe::saved'), 'url' => $url, 'provider' => $provider
        ]);
    }
}
