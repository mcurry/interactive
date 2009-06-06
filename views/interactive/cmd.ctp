<table>
  <?php
    foreach($results as $cmd => $result) {
      echo '<strong>' . $cmd . '</strong><br />';
      if(is_array($result)) {
        echo $toolbar->makeNeatArray($result);
      } else {
        if(is_bool($result)) {
          $result = ife($result, 'true', 'false');
        }

				if($raw) {
					echo htmlentities($result) . '<br />';
				}
        echo $result . '<br /><br />';
      }
    }
  ?>
</table>