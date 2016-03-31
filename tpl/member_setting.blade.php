<h1>외부 로그인 설정</h1>
<p>외부 벤더에서 제공하는 소셜로그인 계정을 관리할 수 있습니다.</p>
<div class="setting-card login-connect">
    <h2>외부 로그인 연결</h2>

    @foreach($providers as $provider => $info)
        @if(array_has($info, 'client_id', false))
            <div class="setting-group">

                <div class="setting-group-content">

                    <!--[D] 계정 연결되어 있는 경우 on 클래스 추가-->
                    @if(isset($accounts[$provider]))
                        <div class="setting-left on">
                            <i class="xi-{{ $provider }}"></i>계정에 연결되어 있습니다.
                        </div>
                        <div class="setting-right">
                            <button data-link="{{ route("social_login::disconnect.$provider") }}" class="__xe_socialDisconnect xe-btn xe-btn-text">연결해제</button>
                        </div>
                    @else
                        <div class="setting-left">
                            <i class="xi-{{ $provider }}"></i>{{ $info['title'] }} 계정에 연결할 수 있습니다.
                        </div>
                        <div class="setting-right">
                            <button data-link="{{ route("social_login::connect.$provider") }}" class="__xe_socialConnect xe-btn xe-btn-text">연결</button>
                        </div>
                    @endif
                </div>
            </div>
        @endif
    @endforeach
</div>
