@inject('plugin', 'Xpressengine\Plugins\SocialLogin\Plugin')
{{ XeFrontend::css($plugin->asset('assets/auth.css'))->load() }}
<!--소셜로그인-->
<div class="member __xe_memberLogin">
    <div class="auth-sns v2">
        <h1>{{xe_trans('xe::doLogin')}}</h1>
        <ul>
            @foreach($providers as $provider => $info)
                <li class="sns-{{ $provider }}"><a href="{{ route('social_login::connect', ['provider'=>$provider]) }}"><i class="xi-{{ $provider }}"></i>{{ $info['title'] }}계정으로 로그인</a></li>
            @endforeach
            <li class="sns-email"><a href="{{ route('login', ['by' => 'email']) }}"><i class="xi-mail-o"></i>이메일로 로그인</a></li>
        </ul>
        <p class="auth-text"><a href="{{ route('auth.register') }}">{{xe_trans('xe::doSignUp')}}</a></p>
    </div>
</div>
<!--//소셜로그인-->
