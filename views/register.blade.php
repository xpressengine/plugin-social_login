@inject('plugin', 'Xpressengine\Plugins\SocialLogin\Plugin')


<!--소셜로그인-->
<div class="member __xe_memberLogin">
    <div class="auth-sns v2">
        <h1>회원 가입하기</h1>
        <ul>
        @foreach($providers as $provider => $info)
            @if($info['activate'])<li class="sns-{{ $provider }}"><a href="{{ route('social_login::connect', ['provider'=>$provider]) }}"><i class="xi-{{ $provider }}"></i>{{ $info['title'] }}계정으로 가입</a></li>@endif
        @endforeach
        </ul>
        <a href="{{ route('auth.register', ['use_email']) }}" class="xe-btn xe-btn-link">이메일로 가입하기</a>
    </div>
</div>
<!--//소셜로그인-->
