<?php
/**
 * Handler.php
 *
 * PHP version 5
 *
 * @category
 * @package
 * @author      XE Developers <developers@xpressengine.com>
 * @copyright   2015 Copyright (C) NAVER Corp. <http://www.navercorp.com>
 * @license     http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html LGPL-2.1
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
use Xpressengine\User\UserHandler;

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
     * @param Socialite $socialite
     * @param UserHandler $users
     * @param DatabaseHandler $db
     * @param ConfigManager $cfg
     */
    public function __construct(Socialite $socialite, UserHandler $users, DatabaseHandler $db, ConfigManager $cfg)
    {
        $this->socialite = $socialite;
        $this->users = $users;
        $this->db = $db;
        $this->cfg = $cfg;
    }

    /**
     * @param string $provider
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function authorize($provider)
    {
        return $this->socialite->driver($provider)->redirect();
    }

    /**
     * @param string $provider
     * @param bool $stateless
     * @return Authenticatable|\Xpressengine\User\UserInterface
     */
    public function execute($provider, $stateless = false)
    {
        return $this->connect(
            $this->getUser($this->socialite->driver($provider), null, null, $stateless),
            $provider
        );
    }

    /**
     * @param Provider $provider
     * @param string|null $token
     * @param string|null $tokenSecret
     * @param bool $stateless
     * @return UserContract
     */
    public function getUser(Provider $provider, $token = null, $tokenSecret = null, $stateless = false)
    {
        if ($stateless) {
            $provider->stateless();
        }

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
     * @param UserContract $userInfo
     * @param string $provider
     * @return Authenticatable|\Xpressengine\User\UserInterface
     * @throws \Exception
     */
    protected function connect(UserContract $userInfo, $provider)
    {
        $user = $this->currentUser();

        if ($account = $this->findAccount($userInfo, $provider)) {
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
                $user = $this->users->create([
                    'email' => $userInfo->getEmail(),
                    'display_name' => $this->resolveDisplayName($userInfo->getNickname() ?: $userInfo->getName()),
                    'group_id' => array_filter([$this->cfg->getVal('user.join.joinGroup')]),
                    'account' => $data
                ]);
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
     * @param UserContract $userInfo
     * @param string $provider
     * @return \Xpressengine\User\Models\UserAccount|null
     */
    protected function findAccount(UserContract $userInfo, $provider)
    {
        return $this->users->accounts()
            ->where(['provider' => $provider, 'account_id' => $userInfo->getId()])
            ->first();
    }

    /**
     * @param string $displayName
     * @return string
     */
    private function resolveDisplayName($displayName)
    {
        $i = 0;
        $name = $displayName;
        while (true) {
            if (!$this->users->users()->where('display_name', $name)->first()) {
                break;
            }
            $name = $displayName.'_'.$i++;
        }

        return $name;
    }

    /**
     * @param Authenticatable $user
     * @param string $provider
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
     * @param Authenticatable $user
     * @return \Xpressengine\User\Models\UserAccount[]
     */
    public function getConnected(Authenticatable $user)
    {
        return $this->users->accounts()->where('user_id', $user->getAuthIdentifier())->get();
    }

    /**
     * @param Request $request
     * @return void
     */
    public function setRequest(Request $request)
    {
        $this->request = $request;
    }

    /**
     * @param string|null $provider
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
     * @param string $provider
     * @param array $config
     * @return void
     */
    public function setConfig($provider, array $config)
    {
        $data = $this->cfg->getVal('social_login.providers');
        $data[$provider] = $config;

        $this->cfg->setVal('social_login.providers', $data);
    }
}
