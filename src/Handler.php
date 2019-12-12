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
use Xpressengine\User\UserHandler;

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
     */
    public function execute($provider, $stateless = false)
    {
        return $this->connect(
            $this->getUser($this->socialite->driver($provider), null, null, $stateless),
            $provider
        );
    }

    /**
     * @param Provider    $provider    provider
     * @param string|null $token       token
     * @param string|null $tokenSecret token secret
     * @param bool        $stateless   stateless
     *
     * @return UserContract
     */
    public function getUser(Provider $provider, $token = null, $tokenSecret = null, $stateless = false)
    {
        if ($stateless) {
            $provider->stateless();
        }

        if ($token !== null) {
            if ($provider instanceof \Laravel\Socialite\One\AbstractProvider) {
                return $provider->userFromTokenAndSecret($token, $tokenSecret);
            } elseif ($provider instanceof \Laravel\Socialite\Two\AbstractProvider) {
                return $provider->userFromToken($token);
            }
        }

        return $provider->user();
    }

    /**
     * @param UserContract $userInfo user info
     * @param string       $provider provider
     *
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
                $email = $userInfo->getEmail();

                $loginId = strtok($email, '@');
                if ($this->users->users()->where('login_id')->exists() === true) {
                    $loginId .= 1;
                }

                $user = $this->users->create([
                    'email' => $email,
                    'login_id' => $loginId,
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
     * @param UserContract $userInfo user info
     * @param string       $provider provider
     *
     * @return \Xpressengine\User\Models\UserAccount|null
     */
    protected function findAccount(UserContract $userInfo, $provider)
    {
        return $this->users->accounts()
            ->where(['provider' => $provider, 'account_id' => $userInfo->getId()])
            ->first();
    }

    /**
     * @param string $displayName display name
     *
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
