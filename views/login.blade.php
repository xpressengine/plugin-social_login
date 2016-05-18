@inject('plugin', 'Xpressengine\Plugins\SocialLogin\Plugin')


<!--소셜로그인-->
<div class="member __xe_memberLogin">
    <div class="auth-sns v2">
        <h1>계정에 로그인</h1>
        <ul>
            @if(isset($providers['facebook']))<li class="sns-facebook"><a href="{{ route($plugin->getIdWith('connect.facebook')) }}"><i class="xi-facebook"></i>{{ $providers['facebook']['title'] }}계정으로 로그인</a></li>@endif
            @if(isset($providers['twitter']))<li class="sns-twitter"><a href="{{ route($plugin->getIdWith('connect.twitter')) }}"><i class="xi-twitter"></i>{{ $providers['twitter']['title'] }}계정으로 로그인</a></li>@endif
            @if(isset($providers['naver']))<li class="sns-naver"><a href="{{ route($plugin->getIdWith('connect.naver')) }}"><i class="xi-naver"></i>{{ $providers['naver']['title'] }}계정으로 로그인</a></li>@endif
            @if(isset($providers['google']))<li class="sns-google"><a href="{{ route($plugin->getIdWith('connect.google')) }}"><i class="xi-google-plus"></i>{{ $providers['google']['title'] }}계정으로 로그인</a></li>@endif
            @if(isset($providers['github']))<li class="sns-github"><a href="{{ route($plugin->getIdWith('connect.github')) }}"><i class="xi-github"></i>{{ $providers['github']['title'] }}계정으로 로그인</a></li>@endif
            @if(isset($providers['line']))<li class="sns-line"><a href="{{ route($plugin->getIdWith('connect.line')) }}"><i class="xi-line-messenger"></i>{{ $providers['line']['title'] }}계정으로 로그인</a></li>@endif
        </ul>
        <a href="#showEmailLogin" onclick="$('.__xe_memberLogin').toggle();return false;" class="xe-btn xe-btn-link">이메일로 로그인하기</a>
    </div>
</div>
<!--//소셜로그인-->

<!-- 로그인 폼  -->
<div class="member __xe_memberLogin" style="display: none;">
    <h1>계정에 로그인</h1>
    <form action="{{ route('login') }}" method="post" {{--data-rule="{{ $loginRuleName }}"--}}>
        <input type="hidden" name="_token" value="{{ csrf_token() }}">
        <input type="hidden" name="redirectUrl" value="{{ $redirectUrl or '' }}">
        <fieldset>
            <legend>로그인</legend>
            <div class="auth-group {{--wrong--}}">
                <label for="name" class="sr-only">이메일 주소 / 사용자 이름</label>
                <input name="email" type="text" id="name" class="xe-form-control" value="{{ old('email') }}" placeholder="이메일 주소 / 사용자 이름">
                {{--<em class="txt_message">잘못된 이메일 주소입니다. 이메일 주소를 확인하시고 다시 입력해주세요.</em>--}}
            </div>
            <div class="auth-group">
                <label for="pwd" class="sr-only">비밀번호</label>
                <input name="password" type="password" id="pwd" class="xe-form-control" placeholder="비밀번호">
            </div>
            <div class="xe-form-group">
                <!--[D] 로그인 유지가 기본인 경우 inpuit에 "disabled="disabled"추가-->
                <!--[D] 다른 xe-form-group과 다르게 label(for=""), input(id="")  같은 값으로 매칭-->
                <input type="checkbox" id="chk" name="remember">
                <label for="chk" data-toggle="collapse" role="button" aria-expanded="false" aria-controls="__xe_infoRemember" data-target="#__xe_infoRemember"><span>로그인 유지하기</span></label>
                <a href="{{ route('auth.reset') }}" class="pull-right">암호를 잊었습니까?</a>
                <!--[D] 체크 시 하단메시지 노출-->
                <div class="auth-noti collapse" id="__xe_infoRemember">
                    <p>브라우저를 닫더라도 로그인이 계속 유지될 수 있습니다. 로그인 유지 기능을 사용할 경우 다음 접속부터는 로그인할 필요가 없습니다. 단, 게임방, 학교 등 공공장소에서 이용 시 개인정보가 유출될 수 있으니 꼭 로그아웃을 해주세요.</p>
                </div>
            </div>

            {{-- recaptcha--}}

            <button type="submit" class="xe-btn xe-btn-primary">로그인</button>
        </fieldset>
    </form>
    {{--<div class="hr">
        <p class="txt_hr"><span>or</span></p>
    </div>
    <div class="auth_sns">
        <ul>
            @if(isset($providers['facebook']))<li class="sns_facebook"><a href="{{ route($plugin->getIdWith('connect.facebook')) }}"><i class="xi-facebook"></i></a></li>@endif
            @if(isset($providers['twitter']))<li class="sns_twitter"><a href="{{ route($plugin->getIdWith('connect.twitter')) }}"><i class="xi-twitter"></i></a></li>@endif
            @if(isset($providers['naver']))<li class="sns_naver"><a href="{{ route($plugin->getIdWith('connect.naver')) }}"><i class="xi-naver"></i></a></li>@endif
            @if(isset($providers['google']))<li class="sns_google"><a href="{{ route($plugin->getIdWith('connect.google')) }}"><i class="xi-google-plus"></i></a></li>@endif
            @if(isset($providers['github']))<li class="sns_github"><a href="{{ route($plugin->getIdWith('connect.github')) }}"><i class="xi-github"></i></a></li>@endif
            @if(isset($providers['line']))<li class="sns_line"><a href="{{ route($plugin->getIdWith('connect.line')) }}"><i class="xi-line-messenger"></i></a></li>@endif
        </ul>
    </div>--}}
    <p class="auth_txt">아직 회원이 아닙니까? <a href="{{ route('auth.register') }}">회원가입하기</a></p>
</div>
<!-- //로그인 폼  -->

