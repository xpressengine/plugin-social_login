<!--소셜로그인-->
@inject('plugin', 'Xpressengine\Plugins\SocialLogin\Plugin')
<h3>SNS 계정으로 회원가입하기</h3>
<div class="auth-sns v2">
    <ul>
    @foreach($providers as $provider => $info)
        @if($info['activate'])<li class="sns-{{ $provider }}"><a href="{{ route('social_login::connect', ['provider'=>$provider]) }}"><i class="xi-{{ $provider }}"></i>{{ $info['title'] }}계정으로 가입</a></li>@endif
    @endforeach
    </ul>
</div>
<!--//소셜로그인-->
