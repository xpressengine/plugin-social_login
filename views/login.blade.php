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
        </ul>
        <a href="{{ route('login', ['by' => 'email']) }}" class="xe-btn xe-btn-link">이메일로 로그인하기</a>
    </div>
</div>
<!--//소셜로그인-->
