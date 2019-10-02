@inject('plugin', 'Xpressengine\Plugins\SocialLogin\Plugin')
{{ XeFrontend::css($plugin->asset('assets/auth.css'))->load() }}

<div class="user __xe_userLogin">
    <div class="auth-sns-user">
        <h2 class="user__title">{{ xe_trans('xe::login') }}</h2>
        <ul class="auth-sns-user-list">
            @foreach($providers as $provider => $info)
            <li class="auth-sns-user__item--{{ $provider }}">
                <a href="{{ route('social_login::auth', ['provider' => $provider]) }}">
                    {{ xe_trans('social_login::signInBy', ['provider' => xe_trans($info['title'])]) }}
                </a>
            </li>
            @endforeach
            <li class="auth-sns-user__item--email">
                <a href="{{ route('login', ['by' => 'email']) }}">
                    {{ xe_trans('social_login::signInBy', ['provider' => xe_trans('xe::email')]) }}
                </a>
            </li>
        </ul>
        <p class="auth-user__text"><a href="{{ route('auth.register') }}">{{ xe_trans('xe::doSignUp') }}</a></p>
    </div>
</div>
