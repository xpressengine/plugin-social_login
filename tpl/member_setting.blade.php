<h1>외부 로그인 설정</h1>
<p>외부 벤더에서 제공하는 소셜로그인 계정을 관리할 수 있습니다.</p>
<div class="setting_card login_connect">
    <h2>외부 로그인 연결</h2>

    @foreach($providers as $provider => $info)
        @if(array_has($info, 'client_id', false))
            <div class="setting_group">
                <div class="setting_group_con">
                    <!--[D] 계정 연결되어 있는 경우 on 클래스 추가-->

                    @if(isset($accounts[$provider]))
                        <div class="setting_left on">
                            <i class="xi-{{ $provider }}"></i>계정에 연결되어 있습니다.
                        </div>
                        <div class="setting_right">
                            <button data-link="{{ route("social_login::disconnect.$provider") }}" class="__xe_socialDisconnect btn_txt">연결해제</button>
                        </div>
                    @else
                        <div class="setting_left">
                            <i class="xi-{{ $provider }}"></i>{{ $info['title'] }} 계정에 연결할 수 있습니다.
                        </div>
                        <div class="setting_right">
                            <button data-link="{{ route("social_login::connect.$provider") }}" class="__xe_socialConnect btn_txt">연결</button>
                        </div>
                    @endif
                </div>
            </div>
        @endif
    @endforeach
</div>
