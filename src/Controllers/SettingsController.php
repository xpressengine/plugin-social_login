<?php
/**
 * SettingsController.php
 *
 * PHP version 7
 *
 * @category    SocialLogin
 * @package     Xpressengine\Plugins\SocialLogin
 * @author      XE Team (developers) <developers@xpressengine.com>
 * @copyright   2015 Copyright (C) NAVER <http://www.navercorp.com>
 * @license     http://www.gnu.org/licenses/old-licenses/lgpl-2.1-standalone.html LGPL
 * @link        http://www.xpressengine.com
 */
namespace Xpressengine\Plugins\SocialLogin\Controllers;

use App\Http\Controllers\Controller;
use XePresenter;
use XeSkin;
use Xpressengine\Http\Request;

/**
 * @category    SocialLogin
 * @package     Xpressengine\Plugins\SocialLogin
 * @author      XE Team (developers) <developers@xpressengine.com>
 * @copyright   2015 Copyright (C) NAVER <http://www.navercorp.com>
 * @license     http://www.gnu.org/licenses/old-licenses/lgpl-2.1-standalone.html LGPL
 * @link        http://www.xpressengine.com
 */
class SettingsController extends Controller
{
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

    public function show($provider)
    {
        return api_render('social_login::tpl.show', [
            'provider' => $provider,
            'info' => app('xe.social_login')->getConfig($provider)
        ]);
    }

    public function edit($provider)
    {
        return api_render('social_login::tpl.edit', [
            'provider' => $provider,
            'info' => app('xe.social_login')->getConfig($provider)
        ]);
    }

    public function update(Request $request, $provider)
    {
        $inputs = $request->only('activate','client_id','client_secret','title');
        $inputs['activate'] = $inputs['activate'] === 'Y';

        app('xe.social_login')->setConfig($provider, $inputs);

        return XePresenter::makeApi([
            'type' => 'success',
            'message' => xe_trans('xe::saved'),
            'url' => route('social_login::settings.provider.show', ['provider' => $provider]),
            'provider' => $provider
        ]);
    }

    public function updateSkin(Request $request)
    {
        if ($skin = XeSkin::get($request->get('skin'))) {
            XeSkin::assign('social_login', $skin);
        }
    }
}
