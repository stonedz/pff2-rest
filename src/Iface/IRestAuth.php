<?php
/**
 * User: paolo.fagni@gmail.com
 * Date: 07/11/14
 * Time: 16.29
 */

namespace pff\modules\Iface;

use pff\Iface\IController;

interface IRestAuth
{
    public function authorize(IController $controller);
}
