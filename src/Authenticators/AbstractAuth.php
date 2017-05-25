<?php
/**
 *  This file is part of the Xpressengine package.
 *
 * @category
 * @package     Xpressengine\
 * @author      XE Developers (khongchi) <khongchi@xpressengine.com>
 * @copyright   2015 Copyright (C) NAVER Crop. <http://www.navercorp.com>
 * @license     LGPL-2.1
 * @license     http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html
 * @link        https://xpressengine.io
 */

namespace Xpressengine\Plugins\SocialLogin\Authenticators;

use App\Facades\XeUser;
use Auth;
use Laravel\Socialite\SocialiteManager;
use XeDB;
use Xpressengine\Support\Exceptions\XpressengineException;
use Xpressengine\User\UserInterface;

/**
 * @category
 * @package     ${NAMESPACE}
 */
class AbstractAuth
{
    protected $provider;

    public function __construct($provider)
    {
        $this->socialite = new SocialiteManager(app());
        $this->provider = $provider;
        $this->extendProvider();
    }

    protected function extendProvider()
    {
    }

    /**
     * getCallbackParameter
     *
     * @return string
     */
    public function getCallbackParameter()
    {
        return 'code';
    }

    public function execute($hasCode)
    {
        if (!$hasCode) {
            return $this->authorization();
        }

        // get user info from oauth server
        $userInfo = $this->getAuthenticatedUser();

        if (\Auth::check() === false) {
            return $this->login($userInfo);
        } else {
            return $this->connect($userInfo);
        }
    }

    public function login($userInfo)
    {
        // if authorized user info is not saved completely, save user info.
        try {
            $handler = app('xe.user');
            $userData = $this->resolveUserInfo($userInfo);
            $user = $this->resolveUser($userData);

            // if user not exist, redirect to register page after saving token.
            if($user === null) {

                $info = [];
                $info['provider'] = $this->provider;
                $info['email'] = $userInfo->email;
                $info['displayName'] = $userData['displayName'];
                $info['token'] = $userInfo->token;
                if($userInfo instanceof \Laravel\Socialite\One\User) {
                    $info['tokenSecret'] = $userInfo->tokenSecret;
                }

                $token = app('xe.user.register.tokens')->create('social_login', $info);

                return redirect()->route('auth.register', ['token' => $token->id]);
            }
        } catch (\Exception $e) {
            throw $e;
        }

        // check user's status
        if ($user->getStatus() === XeUser::STATUS_ACTIVATED) {
            $this->loginMember($user);
        } else {
            return redirect()->route('login')->with('alert', ['type' => 'danger', 'message' => '사용중지된 계정입니다.']);
        }

        return redirect()->intended('/');
    }

    /**
     * connect
     *
     * @param $userInfo
     *
     * @return string
     * @throws \Exception
     */
    public function connect($userInfo)
    {
        $user = Auth::user();
        $this->connectToUser($user, $userInfo);

        return "
            <script>
                window.opener.location.reload();
                window.close();
            </script>
        ";
    }

    public function disconnect()
    {
        $user = \Auth::user();

        $account = $user->getAccountByProvider($this->provider);

        app('xe.user')->deleteAccount($account);
    }

    private function authorization()
    {
        return $this->socialite->driver($this->provider)->redirect();
    }

    public function getAuthenticatedUser($token = null, $tokenSecret = null)
    {

        $provider = $this->socialite->driver($this->provider);
        if($token !== null) {

            if ($provider instanceof \Laravel\Socialite\One\AbstractProvider) {
                return $provider->userFromTokenAndSecret($token, $tokenSecret);
            } else if ($provider instanceof \Laravel\Socialite\Two\AbstractProvider) {
                return $provider->userFromToken($token);
            }
        }
        return $provider->user();
    }

    /**
     * register user
     *
     * @param $userData
     *
     * @return UserInterface
     * @throws \Exception
     */
    private function resolveUser($userData)
    {
        $handler = app('xe.user');
        $accountData = $userData['account'];

        // retrieve account and email
        $existingAccount = $handler->accounts()
            ->where(['provider' => $this->provider, 'accountId' => $accountData['accountId']])
            ->first();
        $existingEmail = array_get($accountData, 'email', false) ? $handler->emails()->findByAddress(
            $accountData['email']
        ) : null;

        $user = null;

        // when new user
        if ($existingAccount === null && $existingEmail === null) {
            return null;
        }

        XeDB::beginTransaction();
        try {
            if ($existingAccount !== null && $existingEmail === null) {

                // if email exists, insert email
                if ($accountData['email'] !== null) {
                    $existingEmail = $handler->createEmail(
                        $existingAccount->user,
                        ['address' => $accountData['email']]
                    );
                }
            } elseif ($existingAccount === null && $existingEmail !== null) {

                // if account is not exists, insert account
                $existingAccount = $handler->createAccount($existingEmail->user, $accountData);

            } elseif ($existingAccount !== null && $existingEmail !== null) {
                if ($existingAccount->userId !== $existingEmail->userId) {
                    // email is registered by another user!!
                    $e = new XpressengineException();
                    $e->setMessage('이미 다른 회원에 의해 등록된 이메일입니다.');
                    throw $e;
                }
            }

            // update token
            if ($existingAccount !== null && $existingAccount->token !== $accountData['token']) {
                $existingAccount->token = $accountData['token'];

                if(array_has($accountData, 'tokenSecret')) {
                    $existingAccount->tokenSecret = $accountData['tokenSecret'];
                }

                $existingAccount = $handler->updateAccount($existingAccount);
            }
        } catch (\Exception $e) {
            XeDB::rollback();
            throw $e;
        }
        XeDB::commit();

        // user exists, get existing user
        if ($user === null) {
            $user = $existingAccount->user;
        }

        return $user;
    }

    private function connectToUser($user, $userInfo)
    {
        $handler = app('xe.user');

        // retrieve account and email
        $existingAccount = $handler->accounts()
            ->where(['provider' => $this->provider, 'accountId' => $userInfo->id])
            ->first();

        $existingEmail = null;
        if (data_get($userInfo, 'email', false)) {
            $existingEmail = $handler->emails()->findByAddress($userInfo->email);
        }

        $id = $user->getId();

        if ($existingAccount !== null && $existingAccount->userId !== $id) {
            $e = new XpressengineException();
            $e->setMessage('이미 다른 회원에 의해 등록된 계정입니다.');
            throw $e;
        }

        if ($existingEmail !== null && $existingEmail->userId !== $id) {
            $e = new XpressengineException();
            $e->setMessage('이미 다른 회원에 의해 등록된 이메일입니다.');
            throw $e;
        }

        $userData = $this->resolveUserInfo($userInfo);

        XeDB::beginTransaction();
        try {
            if ($existingAccount === null) {
                $existingAccount = $handler->createAccount($user, $userData['account']);
            }
            if ($existingEmail === null) {
                $existingEmail = $handler->createEmail($user, ['address' => $userData['email']]);
            }
        } catch (\Exception $e) {
            XeDB::rollback();
            throw $e;
        }
        XeDB::commit();
    }

    private function loginMember($user)
    {
        app('auth')->login($user);
    }

    /**
     * getConfig
     *
     * @param $provider
     *
     * @return mixed
     */
    protected function getConfig($provider)
    {
        return config('services.'.$provider);
    }

    public function resolveUserInfo($userInfo)
    {
        $accountInfo = $this->resolveAccountInfo($userInfo);
        $displayName = $this->resolveDisplayName($userInfo->nickname ?: $userInfo->name);
        return [
            'email' => $userInfo->email,
            'displayName' => $displayName,
            'account' => $accountInfo
        ];
    }

    private function resolveAccountInfo($userInfo)
    {
        return [
            'email' => $userInfo->email,
            'accountId' => $userInfo->id,
            'provider' => $this->provider,
            'token' => $userInfo->token,
            'tokenSecret' => isset($userInfo->tokenSecret) ? $userInfo->tokenSecret : ''
        ];
    }

    private function resolveDisplayName($displayName)
    {
        $handler = app('xe.user');

        $i = 0;
        $name = $displayName;
        while (true) {
            if ($handler->users()->where(['displayName' => $name])->first() !== null) {
                $name = $displayName.' '.$i++;
            } else {
                return $name;
            }
        }
    }
}
