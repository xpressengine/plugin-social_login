<?php
/**
 * ConnectController.php
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
use Xpressengine\Http\Request;
use Xpressengine\Plugins\SocialLogin\Exceptions\ExistsAccountException;
use Xpressengine\Plugins\SocialLogin\Exceptions\ExistsEmailException;
use Xpressengine\Support\Exceptions\HttpXpressengineException;
use Xpressengine\User\Models\User;

/**
 * ConnectController
 *
 * @category    SocialLogin
 * @package     Xpressengine\Plugins\SocialLogin
 * @author      XE Developers <developers@xpressengine.com>
 * @copyright   2019 Copyright XEHub Corp. <https://www.xehub.io>
 * @license     http://www.gnu.org/licenses/lgpl-3.0-standalone.html LGPL
 * @link        https://xpressengine.io
 */
class ConnectController extends Controller
{
    /**
     * ConnectController constructor.
     */
    public function __construct()
    {
        $this->middleware('guest', ['except' => ['auth', 'connect', 'disconnect']]);
        $this->middleware('auth', ['only' => ['disconnect']]);
    }

    /**
     * auth
     *
     * @param Request $request  request
     * @param string  $provider provider
     *
     * @return mixed
     */
    public function auth(Request $request, $provider)
    {
        if ($request->get('_p')) {
            $request->session()->put('social_login::pop', true);
        }

        return app('xe.social_login')->authorize($provider);
    }

    /**
     * connect
     *
     * @param Request $request  request
     * @param string  $provider provider
     *
     * @return \Illuminate\Http\RedirectResponse|string
     */
    public function connect(Request $request, $provider)
    {
        try {
            $user = app('xe.social_login')->execute($provider);
        } catch (ExistsAccountException $e) {
            $this->throwHttpException(xe_trans('social_login::alreadyRegisteredAccount'), 409, $e);
        } catch (ExistsEmailException $e) {
            $this->throwHttpException(xe_trans('social_login::alreadyRegisteredEmail'), 409, $e);
        }

        if (!auth()->check()) {
            if ($user->getStatus() !== User::STATUS_ACTIVATED) {
                return redirect()->route('login')->with('alert', [
                    'type' => 'danger',
                    'message' => xe_trans('social_login::disabledAccount')
                ]);
            }

            auth()->login($user);
        }

        $redirectUrl = '/';
        if ($request->session()->pull('social_login::pop')) {
            $redirectUrl = $request->session()->pull('url.intended') ?: '/';

            return "
                <script>
                var redirectUrl = '{$redirectUrl}';
                if (window.opener && redirectUrl != '/') {
                    window.opener.location.replace(redirectUrl);
                } else if (window.opener) {
                    window.opener.location.reload();
                }

                window.close();
                </script>
            ";
        }

        return redirect()->intended($redirectUrl);
    }

    /**
     * disconnect
     *
     * @param string $provider provider
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function disconnect($provider)
    {
        $user = auth()->user();
        if (count(app('xe.social_login')->getConnected($user)) === 1 && !$user->password) {
            $this->throwHttpException(xe_trans('social_login::unableToDisconnect'), 406);
        }

        app('xe.social_login')->disconnect($user, $provider);

        return redirect()->back()->with('alert', ['type' => 'success', 'message' => 'social_login::msgDisconnected']);
    }

    /**
     * login
     *
     * @param Request $request request
     *
     * @return \Illuminate\Http\RedirectResponse|mixed|\Xpressengine\Presenter\Presentable
     */
    public function login(Request $request)
    {
        $redirectUrl = $request->get(
            'redirectUrl',
            $request->session()->pull('url.intended') ?: url()->previous()
        );

        if ($redirectUrl !== $request->url()) {
            $request->session()->put('url.intended', $redirectUrl);
        }

        $providers = $this->getEnabledProviders();
        if (count($providers) < 1) {
            return redirect()->route('login', ['by' => 'email']);
        }

        XePresenter::setSkinTargetId('social_login');

        return XePresenter::make('login', compact('providers'));
    }

    /**
     * get enabled providers
     *
     * @return array|\Illuminate\Support\Collection
     */
    protected function getEnabledProviders()
    {
        if (!$config = app('xe.config')->get('social_login')) {
            return [];
        }

        return collect($config->get('providers'))->filter(function ($info) {
            return array_get($info, 'activate') === true;
        });
    }

    /**
     * throw http exception
     *
     * @param string $msg      massage
     * @param null   $code     code
     * @param null   $previous previous
     *
     * @return void
     * @throws HttpXpressengineException
     */
    protected function throwHttpException($msg, $code = null, $previous = null)
    {
        $e = new HttpXpressengineException([], $code, $previous);
        $e->setMessage($msg);

        throw $e;
    }
}
