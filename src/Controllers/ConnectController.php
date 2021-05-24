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

use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use XeDB;
use App\Http\Controllers\Controller;
use XeFrontend;
use XePresenter;
use Xpressengine\Http\Request;
use Xpressengine\Plugins\SocialLogin\Exceptions\ExistsAccountException;
use Xpressengine\Plugins\SocialLogin\Exceptions\ExistsEmailException;
use Xpressengine\Plugins\SocialLogin\Handler;
use Xpressengine\Plugins\SocialLogin\Plugin;
use Xpressengine\Support\Exceptions\HttpXpressengineException;
use Xpressengine\User\Models\User;
use Xpressengine\User\Parts\AgreementPart;
use Xpressengine\User\Parts\DefaultPart;
use Xpressengine\User\Parts\RegisterFormPart;
use Xpressengine\User\UserHandler;
use Xpressengine\User\UserRegisterHandler;
use Illuminate\Http\UploadedFile;

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
    /** @var Handler $socialLoginHandler */
    protected $socialLoginHandler;

    /** @var UserHandler $userHandler */
    protected $userHandler;

    /**
     * ConnectController constructor.
     *
     * @param Handler     $socialLoginHandler social login handler
     * @param UserHandler $userHandler        user handler
     */
    public function __construct(Handler $socialLoginHandler, UserHandler $userHandler)
    {
        $this->socialLoginHandler = $socialLoginHandler;
        $this->userHandler = $userHandler;

        $this->middleware('guest', ['except' => ['auth', 'connect', 'disconnect']]);
        $this->middleware('auth', ['only' => ['disconnect']]);

        XePresenter::setSkinTargetId('social_login');
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
        if (auth()->check() === false && app('xe.config')->getVal('user.register.joinable') === false) {
            return redirect('/')->with(
                ['alert' => ['type' => 'danger', 'message' => xe_trans('xe::joinNotAllowed')]]
            );
        }

        if ($request->get('_p')) {
            $request->session()->put('social_login::pop', true);
        }

        return $this->socialLoginHandler->authorize($provider);
    }

    private function loginUser(Request $request, $user)
    {
        switch ($user->getStatus()) {
            case User::STATUS_DENIED:
                return redirect()->route('login')->with('alert', [
                    'type' => 'danger',
                    'message' => xe_trans('social_login::disabledAccount')
                ]);

            case User::STATUS_PENDING_ADMIN:
                auth()->login($user);
                return redirect()->route('auth.pending_admin');
                break;

            case User::STATUS_PENDING_EMAIL:
                auth()->login($user);
                return redirect()->route('auth.pending_email');
                break;
        }

        auth()->login($user);
        $redirectUrl = url('/');
        if ($request->session()->pull('social_login::pop')) {
            $redirectUrl = $request->session()->pull('url.intended') ?: '/';
        }

        return redirect()->intended($redirectUrl);
    }

    /**
     * connect
     *
     * @param Request $request      request
     * @param string  $providerName provider name
     *
     * @return \Illuminate\Http\RedirectResponse|string
     * @throws \Throwable
     */
    public function connect(Request $request, $providerName)
    {
        try {
            $userContract = $this->socialLoginHandler->getUser(
                $providerName,
                $request->get('token', null),
                $request->get('token_secret', null),
                true
            );
        } catch (\Throwable $e) {
            return redirect()->route('social_login::login');
        }

        //로그인 시도
        if (auth()->check() === false) {

            if (app('xe.config')->getVal('user.register.joinable') === false) {
                return redirect()->back()->with(
                    ['alert' => ['type' => 'danger', 'message' => xe_trans('xe::joinNotAllowed')]]
                );
            }

            $userAccount = $this->socialLoginHandler->getRegisteredUserAccount($userContract, $providerName);
            if ($userAccount !== null) {
                $user = $userAccount->user;

                return $this->loginUser($request, $user);
            }

            //가입된 계정이 없을 경우 회원가입
            if (app('xe.config')->getVal('social_login.registerType', Plugin::REGISTER_TYPE_SIMPLE) === Plugin::REGISTER_TYPE_SIMPLE &&
                $this->socialLoginHandler->checkNeedRegisterForm($userContract) === false) {

                $userData = [
                    'email' => $userContract->getEmail(),
                    'contract_email' => $userContract->getEmail(),
                    'display_name' => $userContract->getNickname() ?: $userContract->getName(),
                    'account_id' => $userContract->getId(),
                    'provider_name' => $providerName,
                    'token' => $userContract->token,
                    'token_secret' => $userContract->tokenSecret ?? ''
                ];


                XeDB::beginTransaction();
                try {
                    $user = $this->socialLoginHandler->registerUser($userData);
                } catch (ExistsAccountException $e) {
                    XeDB::rollback();
                    $this->throwHttpException(xe_trans('social_login::alreadyRegisteredAccount'), 409, $e);
                } catch (ExistsEmailException $e) {
                    XeDB::rollback();
                    $this->throwHttpException(xe_trans('social_login::alreadyRegisteredEmail'), 409, $e);
                } catch (\Throwable $e) {
                    XeDB::rollback();
                    throw $e;
                }
                XeDB::commit();

                return $this->loginUser($request, $user);
            }

            $request->session()->put('userContract', $userContract);
            $request->session()->put('provider', $providerName);

            return redirect()->route('social_login::get_register_form');
        }

        //소셜 로그인 연결
        try {
            $this->socialLoginHandler->connectAccount($request->user(), $userContract, $providerName);
        } catch (ExistsAccountException $e) {
            $this->throwHttpException(xe_trans('social_login::alreadyRegisteredAccount'), 409, $e);
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

    protected function getRegisterParts(Request $request)
    {
        $registerConfig = app('xe.config')->get('user.register');

        $parts = UserHandler::getRegisterParts();
        $activated = array_keys(array_intersect_key(array_flip($registerConfig->get('forms', [])), $parts));

        $parts = collect($parts)->filter(function ($part, $key) use ($activated) {
            return in_array($key, $activated) || $part::isImplicit();
        })->sortBy(function ($part, $key) use ($activated) {
            return array_search($key, $activated);
        })->map(function ($part) use ($request) {
            return new $part($request);
        });

        return $parts;
    }

    public function getRegisterForm(Request $request)
    {
        $registerConfig = app('xe.config')->get('user.register');

        $userContract = $request->session()->get('userContract');
        $providerName = $request->session()->get('provider');

        $parts = $this->getRegisterParts($request);

        $defaultPartRule = [];
        if (isset($parts[DefaultPart::ID]) === true) {
            $defaultPartRule = $parts[DefaultPart::ID]->rules();
            if (isset($defaultPartRule['password']) === true) {
                unset($defaultPartRule['password']);
            }

            unset($parts[DefaultPart::ID]);
        }

        unset($parts[AgreementPart::ID]);

        $rules = $parts->map(function ($part) {
            return $part->rules();
        })->collapse()->all();

        $rules = array_merge($rules, $defaultPartRule);
        XeFrontend::rule('join', $rules);

        $isEmailDuplicated = $this->userHandler->users()->where('email', $userContract->getEmail())->exists();

        $terms = [];
        if (app('xe.config')->getVal('user.register.term_agree_type') !== UserRegisterHandler::TERM_AGREE_NOT) {
            $terms = app('xe.terms')->fetchEnabled();
        }

        return XePresenter::make(
            'register',
            compact('registerConfig', 'parts', 'userContract', 'providerName', 'isEmailDuplicated', 'terms')
        );
    }

    public function postRegister(Request $request)
    {
        $parts = $this->getRegisterParts($request);
        $parts->each(function (RegisterFormPart $part) use ($request) {
            if ($part::ID === DefaultPart::ID) {
                $rule = $part->rules();
                unset($rule['password']);

                $this->validate($request, $rule);
            } elseif ($part::ID === AgreementPart::ID) {
                $requireTerms = app('xe.terms')->fetchRequireEnabled();
                $termAgreeType = app('xe.config')->getVal('user.register.term_agree_type');

                if ($requireTerms->count() > 0 && $termAgreeType !== UserRegisterHandler::TERM_AGREE_NOT) {
                    $requireTermValidator = Validator::make(
                        $request->all(),
                        [],
                        ['user_agree_terms.accepted' => xe_trans('xe::pleaseAcceptRequireTerms')]
                    );

                    $requireTermValidator->sometimes(
                        'user_agree_terms',
                        'accepted',
                        function ($input) use ($requireTerms) {
                            $userAgreeTerms = $input['user_agree_terms'] ?? [];

                            foreach ($requireTerms as $requireTerm) {
                                if (in_array($requireTerm->id, $userAgreeTerms) === false) {
                                    return true;
                                }
                            }

                            return false;
                        }
                    )->validate();
                }
            } else {
                $part->validate();
            }
        });

        XeDB::beginTransaction();
        try {
            $user = $this->socialLoginHandler->registerUser($request->except('_token'));

            if(config('social_login.avatar_auto_upload')){
                $contractAvatar = $request->get('contract_avatar', null);
                if ($contractAvatar) {
                    // url 을 파일로 변경
                    $image_info = parse_url($contractAvatar);
                    $image_name = basename($image_info['path']); // top_logo.png
                    $sub_path = Carbon::now()->format('YmdHis');
                    $image_path = storage_path('app/public/social_login');

                    if (!is_dir($image_path)) {
                        mkdir($image_path);
                    }
                    if (!is_dir($image_path .'/'. $sub_path)) {
                        mkdir($image_path .'/'. $sub_path );
                    }
                    $full_path = $image_path .'/'. $sub_path .'/'. $image_name;
                    file_put_contents($full_path, file_get_contents($contractAvatar));

                    $avatar = new UploadedFile($full_path, $image_name);

                    $userData = [
                        'profile_img_file' => $avatar
                    ];
                    $this->userHandler->update($user, $userData);

                    $this->rmdirAll($image_path .'/'. $sub_path);
                }
            }

            $request->session()->forget(['userContract', 'provider']);
        } catch (ExistsAccountException $e) {
            XeDB::rollback();
            $this->throwHttpException(xe_trans('social_login::alreadyRegisteredAccount'), 409, $e);
        } catch (ExistsEmailException $e) {
            XeDB::rollback();
            $this->throwHttpException(xe_trans('social_login::alreadyRegisteredEmail'), 409, $e);
        } catch (\Throwable $e) {
            XeDB::rollback();
            throw $e;
        }
        XeDB::commit();

        return $this->loginUser($request, $user);
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
        if (count($this->socialLoginHandler->getConnected($user)) === 1 && !$user->password) {
            $this->throwHttpException(xe_trans('social_login::unableToDisconnect'), 406);
        }

        $this->socialLoginHandler->disconnect($user, $provider);

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

        $config = app('xe.config')->get('user.register');

        return XePresenter::make('login', compact('providers', 'config'));
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

    private function rmdirAll($deletePath)
    {
        $dirs = dir($deletePath);
        while ( ($entry = $dirs->read()) !== false ) {
            if(($entry != '.') && ($entry != '..')) {

                if (is_dir($deletePath.'/'.$entry)) {
                    $this->rmdirAll($deletePath.'/'.$entry);
                } else {
                    unlink($deletePath.'/'.$entry);
                }
            }
        }
        rmdir($deletePath);
    }

}
