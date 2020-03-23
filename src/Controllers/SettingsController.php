<?php
/**
 * SettingsController.php
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

namespace Xpressengine\Plugins\SocialLogin\Controllers;

use App\Http\Controllers\Controller;
use XePresenter;
use XeSkin;
use Xpressengine\Http\Request;

/**
 * SettingsController
 *
 * @category    SocialLogin
 * @package     Xpressengine\Plugins\SocialLogin
 * @author      XE Developers <developers@xpressengine.com>
 * @copyright   2019 Copyright XEHub Corp. <https://www.xehub.io>
 * @license     http://www.gnu.org/licenses/lgpl-3.0-standalone.html LGPL
 * @link        https://xpressengine.io
 */
class SettingsController extends Controller
{
    /**
     * index
     *
     * @return mixed|\Xpressengine\Presenter\Presentable
     */
    public function index()
    {
        $providers = app('xe.social_login')->getConfig();

        $skins = XeSkin::getList('social_login');
        $selected = XeSkin::getAssigned('social_login');

        app('xe.frontend')->js([
            'assets/core/xe-ui-component/js/xe-page.js',
            'assets/core/xe-ui-component/js/xe-form.js'
        ])->load();

        return XePresenter::make('social_login::tpl.setting', compact('providers', 'skins', 'selected'));
    }

    /**
     * show
     *
     * @param string $provider provider
     *
     * @return mixed
     */
    public function show($provider)
    {
        return api_render('social_login::tpl.show', [
            'provider' => $provider,
            'info' => app('xe.social_login')->getConfig($provider)
        ]);
    }

    /**
     * edit
     *
     * @param string $provider provider
     *
     * @return mixed
     */
    public function edit($provider)
    {
        return api_render('social_login::tpl.edit', [
            'provider' => $provider,
            'info' => app('xe.social_login')->getConfig($provider)
        ]);
    }

    /**
     * update
     *
     * @param Request $request  request
     * @param string  $provider provider
     *
     * @return mixed|\Xpressengine\Presenter\Presentable
     */
    public function update(Request $request, $provider)
    {
        $inputs = $request->only('activate', 'client_id', 'client_secret', 'title');
        $inputs['activate'] = $inputs['activate'] === 'Y';

        app('xe.social_login')->setConfig($provider, $inputs);

        return XePresenter::makeApi([
            'type' => 'success',
            'message' => xe_trans('xe::saved'),
            'url' => route('social_login::settings.provider.show', ['provider' => $provider]),
            'provider' => $provider
        ]);
    }

    /**
     * update skin
     *
     * @param Request $request request
     *
     * @return void
     *
     * @deprecated since 1.0.5 instead use updateConfig
     */
    public function updateSkin(Request $request)
    {
        if ($skin = XeSkin::get($request->get('skin'))) {
            XeSkin::assign('social_login', $skin);
        }
    }

    public function updateConfig(Request $request)
    {
        if ($skin = XeSkin::get($request->get('skin'))) {
            XeSkin::assign('social_login', $skin, 'desktop');
            XeSkin::assign('social_login', $skin, 'mobile');
        }

        if ($registerType = $request->get('registerType')) {
            $socialLoginConfig = app('xe.config')->get('social_login');
            $socialLoginConfig->set('registerType', $registerType);
            app('xe.config')->modify($socialLoginConfig);
        }

        return redirect()->back();
    }
}
