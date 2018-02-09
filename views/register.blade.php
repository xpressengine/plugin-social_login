@inject('plugin', 'Xpressengine\Plugins\SocialLogin\Plugin')

{{ XeFrontend::css($plugin->asset('assets/auth.css'))->load() }}

<div class="member">
    <h1>{{xe_trans('xe::signUp')}}</h1>
    <div class="auth-sns v2">
        <ul>
        @foreach($providers as $provider => $info)
            @if($info['activate'])<li class="sns-{{ $provider }}"><a href="{{ route('social_login::connect', ['provider'=>$provider]) }}"><i class="xi-{{ $provider }}"></i>{{ $info['title'] }}계정으로 가입</a></li>@endif
        @endforeach
        </ul>
        <a href="{{ route('auth.register', ['by' => 'email']) }}" class="xe-btn xe-btn-link">이메일로 가입하기</a>
    </div>
    <p class="auth-text">{{xe_trans('xe::alreadyHaveAccount')}} <a href="{{ route('login') }}">{{xe_trans('xe::login')}}</a></p>
</div>
