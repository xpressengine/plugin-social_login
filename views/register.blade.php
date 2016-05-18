@inject('plugin', 'Xpressengine\Plugins\SocialLogin\Plugin')


    <!--소셜로그인-->
<div class="member __xe_memberLogin">
    <div class="auth_sns v2">
        <h1>회원 가입하기</h1>
        <ul>
            @if(isset($providers['facebook']))<li class="sns_facebook"><a href="{{ route($plugin->getIdWith('connect.facebook')) }}"><i class="xi-facebook"></i>{{ $providers['facebook']['title'] }}계정으로 가입</a></li>@endif
            @if(isset($providers['twitter']))<li class="sns_twitter"><a href="{{ route($plugin->getIdWith('connect.twitter')) }}"><i class="xi-twitter"></i>{{ $providers['twitter']['title'] }}계정으로 가입</a></li>@endif
            @if(isset($providers['naver']))<li class="sns_naver"><a href="{{ route($plugin->getIdWith('connect.naver')) }}"><i class="xi-naver"></i>{{ $providers['naver']['title'] }}계정으로 가입</a></li>@endif
            @if(isset($providers['google']))<li class="sns_google"><a href="{{ route($plugin->getIdWith('connect.google')) }}"><i class="xi-google-plus"></i>{{ $providers['google']['title'] }}계정으로 가입</a></li>@endif
            @if(isset($providers['github']))<li class="sns_github"><a href="{{ route($plugin->getIdWith('connect.github')) }}"><i class="xi-github"></i>{{ $providers['github']['title'] }}계정으로 가입</a></li>@endif
            @if(isset($providers['line']))<li class="sns_line"><a href="{{ route($plugin->getIdWith('connect.line')) }}"><i class="xi-line-messenger"></i>{{ $providers['line']['title'] }}계정으로 가입</a></li>@endif
        </ul>
        <a href="{{ route('auth.register', ['use_email']) }}" class="xe-btn xe-btn-link">이메일로 가입하기</a>
    </div>
</div>
<!--//소셜로그인-->
