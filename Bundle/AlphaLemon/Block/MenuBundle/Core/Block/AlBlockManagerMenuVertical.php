<?php
/**
 * This file is part of the RedKite CMS Application and it is distributed
 * under the GPL LICENSE Version 2.0. To use this application you must leave
 * intact this copyright notice.
 *
 * Copyright (c) RedKite Labs <webmaster@redkite-labs.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * For extra documentation and help please visit http://www.redkite-labs.com
 *
 * @license    GPL LICENSE Version 2.0
 *
 */

namespace AlphaLemon\Block\MenuBundle\Core\Block;

use AlphaLemon\Block\MenuBundle\Core\Block\AlBlockManagerMenu;

/**
 * AlBlockManagerMenuVertical
 *
 * @author RedKite Labs <webmaster@redkite-labs.com>
 */
class AlBlockManagerMenuVertical extends AlBlockManagerMenu
{
    protected $blocksTemplate = 'MenuBundle:Content:menu_vertical.html.twig';
}
