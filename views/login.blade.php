@inject('plugin', 'Xpressengine\Plugins\SocialLogin\Plugin')
<!--소셜로그인-->
<div class="member __xe_memberLogin">
    <div class="auth-sns v2">
        <h1>{{xe_trans('xe::doLogin')}}</h1>
        <ul>
            @foreach($providers as $provider => $info)
                @if($info['activate'])<li class="sns-{{ $provider }}"><a href="{{ route('social_login::connect', ['provider'=>$provider]) }}"><i class="xi-{{ $provider }}"></i>{{ $info['title'] }}계정으로 로그인</a></li>@endif
            @endforeach
        </ul>
        <a href="#showEmailLogin" onclick="$('.__xe_memberLogin').toggle();return false;" class="xe-btn xe-btn-link">이메일로 로그인하기</a>
    </div>
</div>
<!--//소셜로그인-->

<!-- 로그인 폼  -->
<div class="__xe_memberLogin" style="display: none;">
    @include('user.skins.default.auth.login')
</div>
