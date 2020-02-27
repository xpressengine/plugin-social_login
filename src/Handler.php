<?php
/**
 * Handler.php
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

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Arr;
use Laravel\Socialite\Contracts\Factory as Socialite;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Contracts\User as UserContract;
use Xpressengine\Config\ConfigManager;
use Xpressengine\Database\DatabaseHandler;
use Xpressengine\Http\Request;
use Xpressengine\Plugins\SocialLogin\Exceptions\ExistsAccountException;
use Xpressengine\Plugins\SocialLogin\Exceptions\ExistsEmailException;
use Xpressengine\User\Models\User;
use Xpressengine\User\UserHandler;
use Xpressengine\User\UserInterface;
use Xpressengine\User\UserRegisterHandler;

/**
 * Handler
 *
 * @category    SocialLogin
 * @package     Xpressengine\Plugins\SocialLogin
 * @author      XE Developers <developers@xpressengine.com>
 * @copyright   2019 Copyright XEHub Corp. <https://www.xehub.io>
 * @license     http://www.gnu.org/licenses/lgpl-3.0-standalone.html LGPL
 * @link        https://xpressengine.io
 */
class Handler
{
    /**
     * @var Socialite
     */
    protected $socialite;

    /**
     * @var UserHandler
     */
    protected $users;

    /**
     * @var DatabaseHandler
     */
    protected $db;

    /**
     * @var ConfigManager
     */
    protected $cfg;

    /**
     * @var Request
     */
    protected $request;

    /**
     * Handler constructor.
     *
     * @param Socialite       $socialite socialite
     * @param UserHandler     $users     user handler
     * @param DatabaseHandler $db        database handler
     * @param ConfigManager   $cfg       config manager
     */
    public function __construct(Socialite $socialite, UserHandler $users, DatabaseHandler $db, ConfigManager $cfg)
    {
        $this->socialite = $socialite;
        $this->users = $users;
        $this->db = $db;
        $this->cfg = $cfg;
    }

    /**
     * @param string $provider provider
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function authorize($provider)
    {
        return $this->socialite->driver($provider)->redirect();
    }

    /**
     * @param string $provider  provider
     * @param bool   $stateless stateless
     *
     * @return Authenticatable|\Xpressengine\User\UserInterface
     * @throws \Exception
     *
     * @deprecated since 1.0.5 instead use registerUser or connectAccount
     */
    public function execute($provider, $stateless = false)
    {
        return $this->connect($this->getUser($provider, null, null, $stateless), $provider);
    }

    /**
     * @param string      $provider    provider
     * @param string|null $token       token
     * @param string|null $tokenSecret token secret
     * @param bool        $stateless   stateless
     *
     * @return UserContract
     */
    public function getUser($provider, $token = null, $tokenSecret = null, $stateless = false)
    {
        $providerInstance = $this->socialite->driver($provider);

        if ($stateless) {
            $providerInstance->stateless();
        }

        if ($token !== null) {
            if ($providerInstance instanceof \Laravel\Socialite\One\AbstractProvider) {
                return $providerInstance->userFromTokenAndSecret($token, $tokenSecret);
            } elseif ($providerInstance instanceof \Laravel\Socialite\Two\AbstractProvider) {
                return $providerInstance->userFromToken($token);
            }
        }

        return $providerInstance->user();
    }

    public function getRegisteredUserAccount(UserContract $userContract, $providerName)
    {
        return $this->findAccount($userContract->getId(), $providerName);
    }

    /**
     * Register user
     *
     * @param array $userData Register user data
     *
     * @return UserInterface
     */
    public function registerUser($userData)
    {
        $email = array_get($userData, 'email', null);
        $accountId = array_get($userData, 'account_id', null);
        $providerName = array_get($userData, 'provider_name', null);

        if ($this->users->users()->where('email', $email)->exists() === true) {
            throw new ExistsEmailException;
        }

        if ($this->findAccount($accountId, $providerName) !== null) {
            throw new ExistsAccountException;
        }

        $userAccountData = [
            'email' => array_get($userData, 'email', null),
            'account_id' => $accountId,
            'provider' => $providerName,
            'token' => array_get($userData, 'token', null),
            'token_secret' => array_get($userData, 'token_secret', null) ?? ''
        ];

        $loginId = array_get($userData, 'login_id', strtok($email, '@'));
        $userData['login_id'] = $this->resolveLoginId($loginId);
        $userData['display_name'] = array_get($userData, 'display_name', null);
        $userData['group_id'] = array_filter([$this->cfg->getVal('user.register.joinGroup')]);
        $userData['account'] = $userAccountData;

        if ($this->cfg->getVal('user.register.register_process') === User::STATUS_PENDING_EMAIL) {
            $userData['status'] = User::STATUS_ACTIVATED;
            if ($email !== array_get($userData, 'contract_email', null)) {
                $userData['status'] = User::STATUS_PENDING_EMAIL;
            }
        }

        return $this->users->create($userData);
    }

    /**
     * Check need register form
     *
     * @param UserContract $userContract UserContract
     *
     * @return bool
     */
    public function checkNeedRegisterForm(UserContract $userContract)
    {
        if ($userContract->getEmail() === null) {
            return true;
        }

        if ($this->users->users()->where('email', $userContract->getEmail())->exists() === true) {
            return true;
        }

        $displayName = $userContract->getNickname() ?: $userContract->getName();
        if ($displayName === null) {
            return true;
        }

        if (app('xe.config')->getVal('user.register.display_name_unique') === true) {
            if ($this->users->users()->where('display_name', $displayName)->exists() === true) {
                return true;
            }
        }

        $dynamicFieldHandler = app('xe.dynamicField');
        $fieldTypes = $dynamicFieldHandler->gets('user');
        if (count($fieldTypes) > 0) {
            return true;
        }

        $termsHandler = app('xe.terms');
        $terms = $termsHandler->fetchEnabled();
        if (app('xe.config')->getVal('user.register.term_agree_type') !== UserRegisterHandler::TERM_AGREE_NOT &&
            $terms->count() > 0) {
            return true;
        }

        return false;
    }

    /**
     * Connect account to user
     *
     * @param UserInterface $user         User
     * @param UserContract  $userContract UserContract
     * @param string        $providerName Provider name
     *
     * @return void
     */
    public function connectAccount(UserInterface $user, UserContract $userContract, $providerName)
    {
        if ($this->findAccount($userContract->getId(), $providerName) !== null) {
            throw new ExistsAccountException;
        }

        $accountData = [
            'email' => $userContract->getEmail(),
            'account_id' => $userContract->getId(),
            'provider' => $providerName,
            'token' => $userContract->token,
            'token_secret' => $userContract->tokenSecret ?? ''
        ];

        $this->users->createAccount($user, $accountData);
    }

    /**
     * @param UserContract $userInfo user info
     * @param string       $provider provider
     *
     * @return Authenticatable|\Xpressengine\User\UserInterface
     * @throws \Exception
     *
     * @deprecated since 1.0.5 instead use registerUser or connectAccount
     */
    protected function connect(UserContract $userInfo, $provider)
    {
        $user = $this->currentUser();

        if ($account = $this->findAccount($userInfo->getId(), $provider)) {
            if (!$user) {
                return $account->user;
            }

            if ($account->user_id !== $user->getId()) {
                throw new ExistsAccountException;
            }

            return $user;
        }

        $email = $userInfo->getEmail() ? $this->users->emails()->findByAddress($userInfo->getEmail()) : null;
        if ($email) {
            if (!$user) {
                $user = $email->user;
            } elseif ($email->user_id !== $user->getId()) {
                throw new ExistsEmailException;
            }
        }

        $this->db->beginTransaction();
        try {
            $data = [
                'email' => $userInfo->getEmail(),
                'account_id' => $userInfo->getId(),
                'provider' => $provider,
                'token' => $userInfo->token,
                'token_secret' => $userInfo->tokenSecret ?? ''
            ];

            if (!$user) {
                $email = $userInfo->getEmail();

                $loginId = strtok($email, '@');
                if ($this->users->users()->where('login_id')->exists() === true) {
                    $loginId .= 1;
                }

                $userData = [
                    'email' => $email,
                    'login_id' => $loginId,
                    'display_name' => $this->resolveDisplayName($userInfo->getNickname() ?: $userInfo->getName()),
                    'group_id' => array_filter([$this->cfg->getVal('user.register.joinGroup')]),
                    'account' => $data
                ];

                //이메일 인증 후 가입 설정을 사용하고 있을 경우 바로 활성화
                if ($this->cfg->getVal('user.register.register_process') === User::STATUS_PENDING_EMAIL) {
                    $userData['status'] = User::STATUS_ACTIVATED;
                }

                $user = $this->users->create($userData);
            } else {
                $this->users->createAccount($user, $data);
            }

            if (!$email && $address = $userInfo->getEmail()) {
                $this->users->createEmail($user, ['address' => $address]);
            }
        } catch (\Exception $e) {
            $this->db->rollback();
            throw $e;
        }
        $this->db->commit();

        return $user;
    }

    /**
     * current user
     *
     * @return Authenticatable|null
     */
    protected function currentUser()
    {
        $user = $this->request->user();
        if (!$user instanceof Authenticatable) {
            return null;
        }

        return $user;
    }

    /**
     * find account
     *
     * @param string $userContractId user info
     * @param string $providerName   providerName
     *
     * @return \Xpressengine\User\Models\UserAccount|null
     */
    protected function findAccount($userContractId, $providerName)
    {
        if ($userContractId instanceof UserContract) {
            $userContractId = $userContractId->getId();
        }

        return $this->users->accounts()
            ->where(['provider' => $providerName, 'account_id' => $userContractId])
            ->first();
    }

    /**
     * Resolve loginId
     *
     * @param string $loginId loginId
     *
     * @return string
     */
    private function resolveLoginId($loginId)
    {
        $i = 1;

        $resolveLoginId = $loginId;
        while (true) {
            if ($this->users->users()->where('login_id', $resolveLoginId)->exists() === false) {
                break;
            }
            $resolveLoginId .= $i;
        }

        return $resolveLoginId;
    }

    /**
     * @param string $displayName display name
     *
     * @return string
     *
     * @deprecated since 1.0.5
     */
    private function resolveDisplayName($displayName)
    {
        $i = 0;
        $name = $displayName;
        while (true) {
            if (!$this->users->users()->where('display_name', $name)->first()) {
                break;
            }
            $name = $displayName . '_' . $i++;
        }

        return $name;
    }

    /**
     * disconnect
     *
     * @param Authenticatable $user     user
     * @param string          $provider provider
     *
     * @return void
     */
    public function disconnect(Authenticatable $user, $provider)
    {
        $account = $this->users->accounts()->where([
            'provider' => $provider, 'user_id' => $user->getAuthIdentifier()
        ])->first();

        if ($account) {
            $this->users->deleteAccount($account);
        }
    }

    /**
     * get connected
     *
     * @param Authenticatable $user user
     *
     * @return \Xpressengine\User\Models\UserAccount[]
     */
    public function getConnected(Authenticatable $user)
    {
        return $this->users->accounts()->where('user_id', $user->getAuthIdentifier())->get();
    }

    /**
     * set request
     *
     * @param Request $request request
     *
     * @return void
     */
    public function setRequest(Request $request)
    {
        $this->request = $request;
    }

    /**
     * get config
     *
     * @param string|null $provider provider
     *
     * @return array|null
     */
    public function getConfig($provider = null)
    {
        $data = $this->cfg->getVal('social_login.providers', []);

        if (!$provider) {
            return $data;
        }

        return Arr::get($data, $provider);
    }

    /**
     * set config
     *
     * @param string $provider provider
     * @param array  $config   config
     *
     * @return void
     */
    public function setConfig($provider, array $config)
    {
        $data = $this->cfg->getVal('social_login.providers');
        $data[$provider] = $config;

        $this->cfg->setVal('social_login.providers', $data);
    }
}
