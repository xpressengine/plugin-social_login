<?php
namespace Xpressengine\Plugins\SocialLogin\Providers;
use Laravel\Socialite\Two\AbstractProvider;
use Laravel\Socialite\Two\ProviderInterface;
use Laravel\Socialite\Two\InvalidStateException;
use Laravel\Socialite\Two\User;
use Illuminate\Support\Arr;
use Ramsey\Uuid\Uuid;
class AppleProvider extends AbstractProvider implements ProviderInterface
{
    protected $scopes = ['name', 'email'];
    protected $encodingType = PHP_QUERY_RFC3986;
    protected $scopeSeparator = " ";
    protected function getAuthUrl($state)
    {
        return $this->buildAuthUrlFromBase(
            'https://appleid.apple.com/auth/authorize',
            $state
        );
    }
    protected function getTokenUrl()
    {
        return 'https://appleid.apple.com/auth/token';
    }
    public function getAccessToken($code)
    {
        $query = $this->getTokenFields($code);
        $response = $this->getHttpClient()->post($this->getTokenUrl(), [
            'headers' => ['Accept' => 'application/json'],
            'form_params' => $this->getTokenFields($code),
            // 'query' => $query,
        ]);
        return json_decode($response->getBody(), true);
    }
    public function user()
    {
        $response = $this->getAccessTokenResponse($this->getCode());
        $user = $this->mapUserToObject($this->getUserByToken(
            Arr::get($response, 'id_token')
        ));
        return $user->setToken(Arr::get($response, 'id_token'))
            ->setRefreshToken(Arr::get($response, 'refresh_token'))
            ->setExpiresIn(Arr::get($response, 'expires_in'));
    }
    protected function mapUserToObject(array $user)
    {
        if (request()->filled("user")) {
            $userRequest = json_decode(request("user"), true);
            if (array_key_exists("name", $userRequest)) {
                $user["name"] = $userRequest["name"];
                $fullName = trim(
                    ($user["name"]['firstName'] ?? "")
                    . " "
                    . ($user["name"]['lastName'] ?? "")
                );
            }
        }

        return (new User)
            ->setRaw($user)
            ->map([
                "id" => $user["sub"],
                "name" => $fullName ?? null,
                "email" => $user["email"] ?? null,
            ]);
    }
    protected function getTokenFields($code)
    {
        return [
            'grant_type'   => 'authorization_code',
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'redirect_uri' => $this->redirectUrl,
            'code' => $code,
        ];
    }
    protected function getUserByToken($token)
    {
        //dd(explode('.', $token));
        // dd(json_decode(base64_decode( explode('.', $token)[2] )));
        //  dd(json_decode(base64_decode( explode('.', $token)[1] )));
        $claims = explode('.', $token)[1];
        return json_decode(base64_decode($claims), true);
    }
    protected function getCodeFields($state = null)
    {
        $fields = [
            'client_id'  => $this->clientId,
            'redirect_uri' => $this->redirectUrl,
            'scope' => $this->formatScopes($this->getScopes(), $this->scopeSeparator),
            'response_type' => 'code',
            'response_mode' => 'form_post'
        ];
        if ($this->usesState()) {
            $fields['state'] = $state;
            $fields['nonce'] = Uuid::uuid4().'.'.$state;
        }
        return array_merge($fields, $this->parameters);
    }
}
