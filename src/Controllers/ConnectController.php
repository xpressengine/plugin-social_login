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

/**
 * @category    WidgetPage
 * @package     Xpressengine\Plugins\WidgetPage\Controllers
 * @author      XE Team (developers) <developers@xpressengine.com>
 * @copyright   2015 Copyright (C) NAVER <http://www.navercorp.com>
 * @license     http://www.gnu.org/licenses/lgpl-3.0-standalone.html LGPL
 * @link        http://www.xpressengine.com
 */
class ConnectController extends Controller
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

    public function connect(Request $request, $provider)
    {
        $providers = $this->plugin->getProviders();
        $namespace = 'Xpressengine\\Plugins\\SocialLogin\\Authenticators\\';
        $className = $namespace.studly_case($provider).'Auth';

        $proxyClass = app('xe.interception')->proxy($className, 'SocialLoginAuth');
        
        $auth = new $proxyClass($provider);
        $param = $auth->getCallbackParameter();

        $hasCode = $request->has($param);
        return $auth->execute($hasCode);
    }

    public function disconnect(Request $request, $provider)
    {
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
}
