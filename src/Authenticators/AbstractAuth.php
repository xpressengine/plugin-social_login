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
use Xpressengine\User\Exceptions\JoinNotAllowedException;
use Xpressengine\User\Rating;
use Xpressengine\User\UserHandler;
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
            $user = $this->registerUser($userInfo);
        } catch (JoinNotAllowedException $e) {
            return redirect()->route('login')->with(
                'alert',
                ['type' => 'danger', 'message' => '사이트 관리자가 회원가입을 허용하지 않습니다.']
            );
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
    private function connect($userInfo)
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

        \XeUser::deleteAccount($account);
    }

    private function authorization()
    {
        return $this->socialite->driver($this->provider)->redirect();
    }


    private function getAuthenticatedUser()
    {
        return $this->socialite->driver($this->provider)->user();
    }

    /**
     * register user
     *
     * @param $userInfo
     *
     * @return UserInterface
     * @throws \Exception
     */
    private function registerUser($userInfo)
    {

        /** @var UserHandler $handler */
        $handler = app('xe.user');

        $userData = $this->resolveUserInfo($userInfo);
        $accountData = $this->resolveAccountInfo($userInfo);

        // retrieve account and email
        $existingAccount = $handler->accounts()
            ->where(['provider' => $this->provider, 'accountId' => $userInfo->id])
            ->first();
        $existingEmail = data_get($userInfo, 'email', false) ? $handler->emails()->findByAddress(
            $userInfo->email
        ) : null;

        $user = null;
        XeDB::beginTransaction();
        try {
            // when new user
            if ($existingAccount === null && $existingEmail === null) {
                // check joinable setting
                $this->checkJoinable();

                // resolve displayName
                $userData['displayName'] = $this->resolveDisplayName($handler, $userData['displayName']);

                // resolve account
                $userData['account'] = $accountData;

                // force email to be confirmed
                $userData['emailConfirmed'] = true;

                $user = $handler->create($userData);

            } elseif ($existingAccount !== null && $existingEmail === null) {

                // if email exists, insert email
                if ($userData['email'] !== null) {
                    $existingEmail = $handler->emails()->create(
                        $existingAccount->user,
                        ['address' => $userData['email']]
                    );
                }
            } elseif ($existingAccount === null && $existingEmail !== null) {

                // if account is not exists, insert account
                $existingAccount = $handler->accounts()->create($existingEmail->user, $accountData);

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
                $existingAccount = $handler->accounts()->update($existingAccount);
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

        if (data_get($userInfo, 'email', false)) {
            $existingEmail = $handler->emails()->findByAddress($userInfo->email);
        } else {
            $existingEmail = null;
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
                $accountData = $this->resolveAccountInfo($userInfo);
                $existingAccount = $handler->accounts()->create($user, $accountData);
            }

            if ($existingEmail === null) {
                $existingEmail = $handler->emails()->create($user, ['address' => $userData['email']]);
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

    private function resolveUserInfo($userInfo)
    {
        return [
            'email' => $userInfo->email,
            'displayName' => $userInfo->nickname ?: $userInfo->name,
            'status' => \XeUser::STATUS_ACTIVATED,
            'rating' => Rating::MEMBER
        ];
    }

    private function resolveAccountInfo($userInfo)
    {
        return [
            'email' => $userInfo->email,
            'accountId' => $userInfo->id,
            'provider' => $this->provider,
            'token' => $userInfo->token,
            'data' => json_encode($userInfo->user)
        ];
    }

    protected function checkJoinable()
    {
        $config = app('xe.config')->getVal('user.join.joinable', false);
        if ($config !== true) {
            throw new JoinNotAllowedException();
        }
    }

    private function resolveDisplayName(UserHandler $handler, $displayName)
    {
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
