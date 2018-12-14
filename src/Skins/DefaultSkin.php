<?php
/**
 * DefaultSkin.php
 *
 * PHP version 7
 *
 * @category    SocialLogin
 * @package     Xpressengine\Plugins\SocialLogin
 * @author      XE Team (developers) <developers@xpressengine.com>
 * @copyright   2015 Copyright (C) NAVER <http://www.navercorp.com>
 * @license     http://www.gnu.org/licenses/old-licenses/lgpl-2.1-standalone.html LGPL
 * @link        http://www.xpressengine.com
 */

namespace Xpressengine\Plugins\SocialLogin\Skins;

use Xpressengine\Skin\AbstractSkin;

/**
 * DefaultSkin
 *
 * @category    SocialLogin
 * @package     Xpressengine\Plugins\SocialLogin
 * @author      XE Developers <developers@xpressengine.com>
 * @copyright   2015 Copyright (C) NAVER <http://www.navercorp.com>
 * @license     http://www.gnu.org/licenses/old-licenses/lgpl-2.1.html LGPL-2.1
 * @link        http://www.xpressengine.com
 */
class DefaultSkin extends AbstractSkin
{
    /**
     * login
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function login()
    {
        return view('social_login::views.login', $this->data);
    }
}
