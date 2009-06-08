<?php
class InteractiveShell extends Shell {
	var $uses = array('Interactive.Interactive');
	
	function main() {
		$this->out('Interactive Shell');
		$this->hr();


		$cmd = $this->in('Enter Command ("Q" to exit):');
		if(strtoupper($cmd) == 'Q') {
			exit(0);
		}

		$results = $this->Interactive->process($cmd);
		$this->__display($results);
		
		echo "\n";
		$this->hr();
		$this->main();
	}
	
	function __display($results) {
    foreach($results as $result) {
      if(is_array($result['output'])) {
        print_r($result['output']);
      } else {
        if(is_bool($result['output'])) {
          $result['output'] = ife($result['output'], 'true', 'false');
        }

				if($result['raw']) {
					echo htmlentities($result['output']) . "\n";
				}
				
        echo $result['output'] . "\n";
      }
    }
	}
}
?>