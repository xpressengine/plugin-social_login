<div class="row">
    <div class="col-sm-12">
        <div class="panel">
            <div class="panel-body">
                <form id="form-skin">
                    <div class="form-group">
                        <label>스킨</label>
                        <select name="skin" class="form-control">
                            <option>선택</option>
                            @foreach($skins as $skin)
                            <option value="{{ $skin->getId() }}" {{ $selected->getId() === $skin->getId() ? 'selected' : '' }}>{{ xe_trans($skin->getTitle()) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <p class="text-right btns" style="display:none;">
                        <button type="submit" class="btn btn-primary">{{xe_trans('xe::save')}}</button>
                    </p>
                </form>
                <div class="form-group">
                    <label>{{ xe_trans('social_login::socialLoginProviderSetting') }}</label>
                    <p>{!! xe_trans('social_login::descSocialLoginProviderSetting') !!}</p>
                    <div class="list-group">
                    @foreach($providers as $provider => $info)
                        <div class="list-group-item __xe_social_login {{ $provider }}">
                        @include('social_login::tpl.show', compact('provider', 'info'))
                        </div>
                    @endforeach
                    </div>
                </div>

            </div>
        </div>


    </div>
</div>

{!! app('xe.frontend')->html('social_login.update')->content("
<script>
    jQuery(function($) {
        window.updateSocialLogin = function (data) {
            XE.toast(data.type, data.message);
            XE.page(data.url, '.__xe_social_login.'+data.provider);
        };
        $('select', '#form-skin').change(function () {
            $('.btns', '#form-skin').slideDown();
        });
        $('#form-skin').submit(function (e) {
            e.preventDefault();
            $.ajax({
                url: '".route('social_login::settings.skin.update')."',
                type: 'put',
                data: $(this).serialize(),
                success: function () {
                    $('.btns', '#form-skin').slideUp();
                    XE.toast('success', '".xe_trans('xe::saved')."');
                }
            });
        });
    });
</script>
")->load() !!}
