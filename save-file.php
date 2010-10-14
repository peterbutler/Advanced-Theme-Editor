<?php
	check_admin_referer();
	$newContent = stripslashes($_POST['new-content']);
	$filePath   = stripslashes($_POST['file-path']);
	$theme      = stripslashes($_POST['theme']);
  $return = ateSaveFileChanges($filePath, $theme, $newContent);
  if(is_wp_error($return)){
    echo "<div class='error'><p>";
    echo $return->get_error_message();
    echo "</p></div>";
  }

/*
	$theme = urlencode($theme);
	if (is_writeable($file)) {
		//is_writable() not always reliable, check return value. see comments @ http://uk.php.net/is_writable
		$f = fopen($file, 'w+');
		if ($f !== FALSE) {
			fwrite($f, $newcontent);
			fclose($f);
			$location = "theme-editor.php?file=$file&theme=$theme&a=te&scrollto=$scrollto";
		} else {
			$location = "theme-editor.php?file=$file&theme=$theme&scrollto=$scrollto";
		}
	} else {
		$location = "theme-editor.php?file=$file&theme=$theme&scrollto=$scrollto";
	}

	$location = wp_kses_no_null($location);
	$strip = array('%0d', '%0a', '%0D', '%0A');
	$location = _deep_replace($strip, $location);
	header("Location: $location");
	exit();

*/


function copyr($source, $dest){
  // Simple copy for a file
  if (is_file($source)) {
    $c = copy($source, $dest);
    chmod($dest, 0777);
    return $c;
  }
   
  // Make destination directory
  if (!is_dir($dest)) {
    $oldumask = umask(0);
    mkdir($dest, 0777);
    umask($oldumask);
  }
   
  // Loop through the folder
  $dir = dir($source);
  while (false !== $entry = $dir->read()) {
    // Skip pointers
    if ($entry == "." || $entry == "..") {
      continue;
    }
     
    // Deep copy directories
    if ($dest !== "$source/$entry") {
      copyr("$source/$entry", "$dest/$entry");
    }
  }
   
  // Clean up
  $dir->close();
  return true;
}