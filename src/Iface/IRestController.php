<?php
/**
 * User: paolo.fagni@gmail.com
 * Date: 07/11/14
 * Time: 12.54
 */

namespace pff\modules\Iface;


interface IRestController {

    public function getHandler();

    public function postHandler();

    public function putHandler();

    public function deleteHandler();

}
