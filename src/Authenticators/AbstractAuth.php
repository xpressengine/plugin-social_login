<?php
/**
 *  This file is part of the Xpressengine package.
 *
 * PHP version 5
 *
 * @category
 * @package     Xpressengine\
 * @author      XE Team (khongchi) <khongchi@xpressengine.com>
 * @copyright   2000-2014 Copyright (C) NAVER <http://www.navercorp.com>
 * @license     http://www.gnu.org/licenses/lgpl-3.0-standalone.html LGPL
 * @link        http://www.xpressengine.com
 */
namespace Xpressengine\Plugins\SocialLogin\Authenticators;

use Auth;
use Laravel\Socialite\SocialiteManager;
use Member;
use XeDB;
use Xpressengine\Member\Entities\Database\AccountEntity;
use Xpressengine\Member\Entities\Database\MailEntity;
use Xpressengine\Member\Entities\MemberEntityInterface;
use Xpressengine\Member\Exceptions\JoinNotAllowedException;
use Xpressengine\Member\Rating;
use Xpressengine\Member\Repositories\MemberRepositoryInterface;
use Xpressengine\Support\Exceptions\XpressengineException;

/**
 * @category
 * @package     ${NAMESPACE}
 * @author      XE Team (khongchi) <khongchi@xpressengine.com>
 * @license     http://www.gnu.org/licenses/lgpl-3.0-standalone.html LGPL
 * @link        http://www.xpressengine.com
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

        // if authorized member info is not saved completely, save member info.
        try {
            $member = $this->registerMember($userInfo);
        } catch (JoinNotAllowedException $e) {
            return redirect()->route('login')->with(
                'alert',
                ['type' => 'danger', 'message' => '사이트 관리자가 회원가입을 허용하지 않습니다.']
            );
        } catch (\Exception $e) {
            throw $e;
        }

        // check member's status
        if ($member->getStatus() === Member::STATUS_ACTIVATED) {
            $this->loginMember($member);
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
        $member = Auth::user();
        $this->connectToMember($member, $userInfo);

        return "
            <script>
                window.opener.location.reload();
                window.close();
            </script>
        ";
    }

    public function disconnect()
    {
        $member = \Auth::user();

        $account = $member->getAccountByProvider($this->provider);

        \Member::deleteAccount($account);
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
     * registerMember
     *
     * @param $userInfo
     *
     * @return MemberEntityInterface
     * @throws \Exception
     */
    private function registerMember($userInfo)
    {

        /** @var MemberRepositoryInterface $members */
        $handler = app('xe.member');

        $memberData = $this->resolveMemberInfo($userInfo);
        $accountData = $this->resolveAccountInfo($userInfo);

        // retrieve account and email
        $existingAccount = $handler->fetchOneAccount(['provider' => $this->provider, 'accountId' => $userInfo->id]);
        $existingEmail = data_get($userInfo, 'email', false) ? $handler->fetchOneMail(
            ['address' => $userInfo->email]
        ) : null;

        $member = null;
        XeDB::beginTransaction();
        try {
            // when new member
            if ($existingAccount === null && $existingEmail === null) {
                // check joinable setting
                $this->checkJoinable();

                // resolve displayName
                $memberData['displayName'] = $this->resolveDisplayName($handler, $memberData['displayName']);

                // resolve account
                $memberData['account'] = $accountData;

                // force email to be confirmed
                $memberData['emailConfirmed'] = true;

                $member = $handler->create($memberData);
            } elseif ($existingAccount !== null && $existingEmail === null) {
                // if email exists, insert email
                if ($memberData['email'] !== null) {
                    $existingEmail = $handler->insertMail(
                        new MailEntity(
                            [
                                'memberId' => $existingAccount->memberId,
                                'address' => $memberData['email'],
                            ]
                        )
                    );
                }
            } elseif ($existingAccount === null && $existingEmail !== null) {
                // if account is not exists, insert account
                $accountData['memberId'] = $existingEmail->memberId;
                $existingAccount = $handler->insertAccount(new AccountEntity($accountData));
            } elseif ($existingAccount !== null && $existingEmail !== null) {
                if ($existingAccount->memberId !== $existingEmail->memberId) {
                    // email is registered by another member!!
                    $e = new XpressengineException();
                    $e->setMessage('이미 다른 회원에 의해 등록된 이메일입니다.');
                    throw $e;
                }
            }

            // update token
            if ($existingAccount !== null && $existingAccount->token !== $accountData['token']) {
                $existingAccount->token = $accountData['token'];
                $existingAccount = $handler->updateAccount($existingAccount);
            }
        } catch (\Exception $e) {
            XeDB::rollback();
            throw $e;
        }
        XeDB::commit();

        // member exists, get existing member
        if ($member === null) {
            $member = $handler->findMember($existingAccount->memberId);
        }

        return $member;
    }


    private function connectToMember($member, $userInfo)
    {
        $handler = app('xe.member');

        // retrieve account and email
        $existingAccount = $handler->fetchOneAccount(['provider' => $this->provider, 'accountId' => $userInfo->id]);
        if (data_get($userInfo, 'email', false)) {
            $existingEmail = $handler->fetchOneMail(
                ['address' => $userInfo->email]
            );
        } else {
            $existingEmail = null;
        }


        $id = $member->getId();

        if ($existingAccount !== null && $existingAccount->memberId !== $id) {
            $e = new XpressengineException();
            $e->setMessage('이미 다른 회원에 의해 등록된 계정입니다.');
            throw $e;
        }

        if ($existingEmail !== null && $existingEmail->memberId !== $id) {
            $e = new XpressengineException();
            $e->setMessage('이미 다른 회원에 의해 등록된 이메일입니다.');
            throw $e;
        }

        $memberData = $this->resolveMemberInfo($userInfo);

        XeDB::beginTransaction();
        try {
            if ($existingAccount === null) {
                $accountData = $this->resolveAccountInfo($userInfo);
                $accountData['memberId'] = $id;
                $existingAccount = $handler->insertAccount(new AccountEntity($accountData));
            }

            if ($existingEmail === null) {
                $existingEmail = $handler->insertMail(
                    new MailEntity(
                        [
                            'memberId' => $id,
                            'address' => $memberData['email'],
                        ]
                    )
                );
            }
        } catch (\Exception $e) {
            XeDB::rollback();
            throw $e;
        }
        XeDB::commit();
    }

    private function loginMember($member)
    {
        app('auth')->login($member);
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

    private function resolveMemberInfo($userInfo)
    {
        return [
            'email' => $userInfo->email,
            'displayName' => $userInfo->nickname ?: $userInfo->name,
            'profileImagePath' => $userInfo->avatar,
            'status' => Member::STATUS_ACTIVATED,
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
        $config = app('xe.config')->getVal('member.join.joinable', false);
        if ($config !== true) {
            throw new JoinNotAllowedException();
        }
    }

    private function resolveDisplayName($handler, $displayName)
    {
        $i = 0;
        $name = $displayName;
        while (true) {
            if ($handler->fetchOne(['displayName' => $name]) !== null) {
                $name = $displayName.' '.$i++;
            } else {
                return $name;
            }
        }
    }
}
