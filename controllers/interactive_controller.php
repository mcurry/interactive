<?php
class InteractiveController extends InteractiveAppController {
	var $name = 'Interactive';
  var $uses = array('Interactive.InteractiveQuery');
	var $components = array('RequestHandler');
	
	var $helpers = array('DebugKit.Toolbar' => array('output' => 'DebugKit.HtmlToolbar'));
  var $showRaw = false;
	
	var $objectPath = null;
	var $objectCache = true;
	
  function cmd() {
		//the debug_kit toolbar component, which is probably included in AppController
		//forces the output to be FirePHP, which means we can't use makeNeatArray
		$this->helpers['DebugKit.Toolbar']['output'] = 'DebugKit.HtmlToolbar';
		
		if (Configure::read('debug') == 0) {
			return $this->redirect($this->referer());
		}
    
    Configure::write('debug', 0);
    
    if(empty($this->data['Interactive']['cmd'])) {
      return;
    }
    
    $cmds = explode("\n", $this->data['Interactive']['cmd']);
    
    $results = array();
    foreach($cmds as $cmd) {
      $cmd = trim($cmd);
      if(empty($cmd)) {
        continue;
      }
      
      if(!$type = $this->__findCmdType($cmd)) {
        continue;
      }
      
      $func = sprintf('__%sCall', $type);
      $results[$cmd] = $this->{$func}($cmd);
    }

		$this->set('raw', $this->showRaw);
    $this->set('results', $results);
  }
  
  function __classCall($cmd) {
    list($className, $function) = preg_split('/(::|->)/', $cmd, 2);
		$Class = $this->__getClass($className);
		
		if(!$Class) {
			return $this->__codeCall($cmd);
		}
    
    preg_match('/^([a-zA-Z_]{1,})\((.{0,})\)/', $function, $matches);
    
		if(!$matches) {
			return $Class->{$function};
		}
		
		$args = array();
		if(!empty($matches[2])) {
			$args = explode(',', $matches[2]);
			foreach($args as $i => $arg) {
				$args[$i] = eval('return ' . $arg . ';');
			}
		}
      
    return call_user_func_array(array($Class, $matches[1]), $args);
  }
  
  function __sqlCall($cmd) {
    return $this->InteractiveQuery->query($cmd);
  }
  
  function __codeCall($cmd) {
    return eval('return ' . $cmd . ';');
  }
	
	function __getClass($className) {
		$className = $this->__fixClassName($className);
		$types = array('model', 'controller', 'helper', 'component');
		$classType = false;
		
		foreach($types as $type) {
			$objects = Configure::listObjects($type, $this->objectPath, $this->objectCache);
			if(in_array($className, $objects)) {
				$classType = $type;
				break;
			}
		}
		
		switch($classType) {
			case 'model':
				return ClassRegistry::init($className);
			case 'controller':
				App::import('Controller', $className);
				$className = $className . 'Controller';
				return new $className();
			case 'component':
				App::import('Controller', 'Controller');
				$Controller = new Controller();
				App::import('Component', $className);
				$className = $className . 'Component';
				$Class = new $className();
				$Class->initialize($Controller);
				$Class->startup($Controller);
				return $Class;
			case 'helper':
				$this->showRaw = true;
				$this->helpers[] = $className;
				App::import('View', 'View');
				$View =& new View($this);
				$loaded = array();
				$helpers = $View->_loadHelpers($loaded, $this->helpers);
				return $helpers[$className];
		}
		
		return false;
	}
  
	function __fixClassName($className) {
		return ucfirst(preg_replace('/(\$|controller|component)/i', '', $className));
	}
	
  function __findCmdType($cmd) {
    if (preg_match('/(::|->)/', $cmd)) {
      return 'class';
    }

    if (preg_match('/^(select|insert|update|delete)/i', $cmd)) {
      return 'sql';
    }
    
    return 'code';
  }
}