<?php
/**
 * User: paolo.fagni@gmail.com
 * Date: 05/11/14
 * Time: 14.38
 */

namespace pff\modules;

use pff\Abs\AModule;
use pff\Core\Outputs\JSONOut;
use pff\Iface\IBeforeHook;
use pff\Iface\IBeforeSystemHook;
use pff\Iface\IConfigurableModule;
use pff\modules\Core\RestView;
use pff\modules\Iface\IRestAuth;

class Pff2Rest extends AModule implements IConfigurableModule, IBeforeHook, IBeforeSystemHook
{
    private $isRest = false;

    private $annotationName;
    /**
     * @var array
     */
    private $apiversions;

    private $authEnabled;

    private $authType;

    private $authClass;

    public function __construct($confFile = 'pff2-rest/module.conf.local.yaml')
    {
        $this->loadConfig($confFile);
    }

    /**
     * @param array $parsedConfig
     * @return mixed
     */
    public function loadConfig($parsedConfig)
    {
        $conf = $this->readConfig($parsedConfig);
        $this->annotationName = $conf['moduleConf']['annotationName'];
        $this->apiversions    = $conf['moduleConf']['apiVersions'];
        $this->authEnabled    = $conf['moduleConf']['enableAuth'];
        $this->authType       = $conf['moduleConf']['authType'];
        $this->authClass      = $conf['moduleConf']['authClass'];
    }

    public function manageExceptionsRest(\Exception $exception)
    {
        $this->_controller->setOutput(new JSONOut());
        $this->_controller->resetViews();
        $code = (int)$exception->getCode();
        header(' ', true, $code);

        $view = new RestView();
        $view->set('error', true);
        $view->set('message', $exception->getMessage());
        $view->render();
    }

    /**
     * Executes actions before the Controller
     *
     * @return mixed
     */
    public function doBefore()
    {
        /** @var Pff2Annotations $reader */
        $reader = $this->_controller->loadModule('pff2-annotations');
        $isRestController = is_a($this->_controller, 'pff\modules\\Iface\\IRestController');
        if ($isRestController || $reader->getMethodAnnotation($this->annotationName)) {
            $this->isRest = true;
            if ($this->authEnabled) {
                $validatorName = '\pff\models\\'.$this->authClass;
                /** @var IRestAuth $validator */
                $validator = new $validatorName();
                $validator->authorize($this->_controller);
            }

            $this->_controller->setOutput(new JSONOut());
            $this->_controller->resetViews();
        }
        $verb = $_SERVER['REQUEST_METHOD'];

        if ($isRestController) {
            switch ($verb) {
                case 'GET':
                    $this->getApp()->setAction('getHandler');
                    break;
                case 'POST':
                    $this->getApp()->setAction('postHandler');
                    break;
                case 'PUT':
                    $this->getApp()->setAction('putHandler');
                    break;
                case 'DELETE':
                    $this->getApp()->setAction('deleteHandler');
                    break;
            }
        }
    }

    /**
     * Executed before the system startup
     *
     * @return mixed
     */
    public function doBeforeSystem()
    {
        $tmpUrl = $this->_app->getUrl();
        $tmpUrl = explode('/', $tmpUrl);
        if (in_array(strtolower($tmpUrl[0]), $this->apiversions)) {
            $tmpApi = $tmpUrl[0];
            array_shift($tmpUrl);
            $tmpController = $tmpUrl[0];
            array_shift($tmpUrl);
            $this->_app->setUrl(ucfirst($tmpApi).'_'.ucfirst($tmpController).'/index/'.implode('/', $tmpUrl));
            set_exception_handler([$this, 'manageExceptionsRest']);
        }
    }
}
