<?php
namespace FrontendBridge\Controller\Component;
use Cake\Controller\Component;
use Cake\Event\Event;
use Cake\Utility\Hash;
use Cake\Controller\ComponentRegistry;


class FrontendBridgeComponent extends Component {
/**
 * Holds a reference to the controller which uses this component
 *
 * @var Controller
 */
	protected $_controller;
	
/**
 * Holds the data which will be made available to the frontend controller
 *
 * @var array
 */
	protected $_jsonData = array();

/**
 * Holds additional data to be set into frontend data by the controller.
 *
 * @var array
 */
	protected $_additionalAppData = array();

/**
 * the current request object
 *
 * @var CakeRequest
 */
	protected $_request;


/**
 * Constructor
 *
 * @param ComponentRegistry $registry A ComponentRegistry object.
 * @param array $config Array of configuration settings.
 */
	public function __construct(ComponentRegistry $registry, array $config = []) {
		parent::__construct($registry, $config);

		$this->_controller = $registry->getController();
		$this->_request = $this->_controller->request;
	}

/**
 * Events supported by this component.
 *
 * @return array
 */
	public function implementedEvents() {
		return [
		];
	}

/**
 * Pass data to the frontend controller
 *
 * @param string $key 
 * @param mixed $value 
 * @return void
 */
	public function setJson($key, $value = null) {
		if(is_array($key)) {
			foreach($key as $k => $v) {
				$this->setJson($k, $v);
			}
			return;
		}

		$this->_jsonData[$key] = $value;
	}
	
/**
 * Pass data to the frontend controller
 *
 * @param string $key 
 * @param mixed $value 
 * @return void
 */
	public function set($key, $value = null) {
		$this->setJson($key, $value);
	}

/**
 * Adds additional data to the appData 
 *
 * @param string $key 
 * @param mixed $value 
 * @author Robert Scherer
 */
	public function addAppData($key, $value = null) {
		$this->_additionalAppData[ $key ] = $value;
	}

/**
 * Set a variable to both the frontend controller and the backend view
 *
 * @param string $key 
 * @param mixed $value 
 * @return void
 */
	public function setBoth($key, $value = null) {
		$this->_controller->set($key, $value);
		$this->setJson($key, $value);
	}

/**
 * Should be called explicitely in Controller::beforeRender()
 *
 * @return void
 */
	public function beforeRender(Controller $controller) {
		$this->setJson('isAjax', $controller->request->is('ajax'));
		$this->setJson('isMobile', $controller->request->is('mobile'));

		$appData = array(
			'jsonData' => $this->_jsonData,
			'webroot' => 'http' . (env('HTTPS') ? 's' : '') . '://' . env('HTTP_HOST') . $this->_controller->webroot,
			'url' => isset($this->_controller->params['url']['url']) ? $this->_controller->params['url']['url'] : '',
			'controller' => $this->_controller->name,
			'action' => $this->_controller->action,
			'params' => array(
				'named' => $this->_controller->params['named'],
				'pass' => $this->_controller->params['pass'],
				'plugin' => $this->_controller->params['plugin'],
				'controller' => Inflector::underscore($this->_controller->name),
				'action' => $this->_controller->action
			),
		);
		if(!$this->_request->is('ajax')) {
			$r = new ReflectionClass('Types');
			$appData['Types'] = $r->getConstants();
		}
		// merge in the additional frontend data
		$appData = Set::merge($appData, $this->_additionalAppData);
		$this->_controller->set('frontendData', $appData);
	}
}