<?php
namespace Xpressengine\Plugins\SocialLogin\Skins;

use Xpressengine\Skin\AbstractSkin;

class DefaultSkin extends AbstractSkin
{
    public function login()
    {
        return view('social_login::views.login', $this->data);
    }
}
