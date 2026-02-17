<?php
declare(strict_types=1);

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

    public function __construct(string $confFile = 'pff2-rest/module.conf.yaml')
    {
        $this->loadConfig($this->readConfig($confFile));
    }

    /**
     * @param array<string, mixed> $parsedConfig
     */
    public function loadConfig(array $parsedConfig): void
    {
        $this->annotationName = $parsedConfig['moduleConf']['annotationName'];
        $this->apiversions = $parsedConfig['moduleConf']['apiVersions'];
        $this->authEnabled = $parsedConfig['moduleConf']['enableAuth'];
        $this->authType = $parsedConfig['moduleConf']['authType'];
        $this->authClass = $parsedConfig['moduleConf']['authClass'];
    }

    public function manageExceptionsRest(\Throwable $exception): void
    {
        $this->_controller->setOutput(new JSONOut());
        $this->_controller->resetViews();
        $code = (int) $exception->getCode();
        header(' ', true, $code);

        $view = new RestView();
        $view->set('error', true);
        $view->set('message', $exception->getMessage());
        $view->set('file', $exception->getFile() . '::' . $exception->getLine());
        $view->render();
    }

    /**
     * Executes actions before the Controller
     *
     */
    public function doBefore(): void
    {
        $isRestController = is_a($this->_controller, 'pff\modules\\Iface\\IRestController');
        $hasRestAnnotation = false;
        if (class_exists('\\pff\\modules\\Pff2Annotations')) {
            try {
                $reader = $this->_controller->loadModule('pff2-annotations');
                if (is_object($reader) && method_exists($reader, 'getMethodAnnotation')) {
                    $hasRestAnnotation = (bool) $reader->getMethodAnnotation($this->annotationName);
                }
            } catch (\Throwable) {
            }
        }

        if ($isRestController || $hasRestAnnotation) {
            $this->isRest = true;
            if ($this->authEnabled) {
                $validatorName = '\pff\models\\' . $this->authClass;
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
                case 'OPTIONS':
                    http_response_code(200);
                    exit;
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
     */
    public function doBeforeSystem(): void
    {
        $tmpUrl = $this->_app->getUrl();
        $tmpUrl = explode('/', $tmpUrl);
        if (in_array(strtolower($tmpUrl[0]), $this->apiversions)) {
            $tmpApi = $tmpUrl[0];
            array_shift($tmpUrl);
            $tmpController = $tmpUrl[0];
            array_shift($tmpUrl);
            $this->_app->setUrl(ucfirst($tmpApi) . '_' . ucfirst($tmpController) . '/index/' . implode('/', $tmpUrl));
            set_exception_handler([$this, 'manageExceptionsRest']);
        }
    }
}
