<?php
/**
 * User: paolo.fagni@gmail.com
 * Date: 07/11/14
 * Time: 11.01
 */

namespace pff\modules\Core;

use pff\Abs\AView;

class RestView extends AView
{
    public function __construct()
    {
    }

    /**
     * @var array
     */
    private $_data;

    /**
     * Sets a value to be passed to a View
     *
     * @return mixed
     */
    public function set($name, $value)
    {
        $this->_data[$name] = $value;
    }

    /**
     * Renders the view
     *
     * @return mixed
     */
    public function render()
    {
        echo trim(json_encode($this->_data));
    }

    /**
     * Returns the rendered HTML without output to browser
     *
     * @return mixed
     */
    public function renderHtml()
    {
        return false;
    }
}
