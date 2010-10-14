<?php
// Get all files inside a template directory
function ateGetTemplateFiles($directory){
  // Open Directory
  if($dirHandle = opendir($directory)){
     while (false !== ($file = readdir($dirHandle))) {
      // Skip current/parent references
      if ($file != "." && $file != ".." && substr($file, 0, 9) != '.working-') {
        if(is_dir($directory."/".$file)){
          $files[$file]['contents'] = ateGetTemplateFiles($directory."/".$file);
          $files[$file]['path']     = $directory."/".$file;
          $files[$file]['type']     = 'folder';
          $files[$file]['name']     = $file;
        }else{
          $files[$file]['path'] = $directory."/".$file;
          $files[$file]['name'] = $file;
          $files[$file]['type'] = pathinfo($files[$file]['path'], PATHINFO_EXTENSION);
        }
      }
    }
  }
  return $files;
}

//Display theme files/folders
function ateDisplayFiles($files, $theme, $location = ''){
  global $fileDisplayAlt;
  if($location != ''):?>
  <ul>
  <?php endif; ?>
  <?php if(is_array($files)): ?>
  <?php foreach($files as $file): ?>
  <?php
  if(empty($fileDisplayAlt)){
    $fileDisplayAlt = "alt";
  }else{
    $fileDisplayAlt = '';
  }
  ?>
    <li class="<?php echo $file['type']; ?> <?php echo $fileDisplayAlt; ?>" title="<?php echo $location.$file['name']; ?>">
      <?php if($file['type'] == 'folder'): ?>
        <a href="#"><?php echo $file['name']; ?></a>
        <?php ateDisplayFiles($file['contents'], $theme, $location.$file['name'].'/'); ?>
      <?php else: ?>
        <a href="themes.php?page=advanced-theme-editor&editTheme=<?php echo $theme; ?>&type=<?php echo $file['type']; ?>&editFile=<?php echo $location.$file['name']; ?>" title="<?php echo $location.$file['name']; ?>" name="<?php echo $file['name']; ?>"><?php echo $file['name']; ?></a>
      <?php endif; ?>
    </li>
  <?php endforeach; ?>
  <?php endif; ?>
<?php if($location != ''):?>
  </ul>
  <?php endif;

}

//Display theme history
function ateDisplayThemeHist($theme){
  $themeHistory = ateGetThemeHist($theme);
  
  $removeVars[] = 'themeRev';
  $removeVars[] = 'fileRev';
  $linkURL = ateCreateURL('themes.php', $_GET, $removeVars);

?>
  <li class="noLink">Revert to theme changes saved:</li>
<?php
  foreach($themeHistory as $revision){
?>
    <li><a href="<?php echo $linkURL.'&themeRev='.$revision['id']; ?>"><?php echo human_time_diff(strtotime($revision['modified'])); ?> ago</a></li>
<?php
  }
}

function ateCreateURL($baseFile, $currentParams, $ignoreParams){
  $linkURL = $baseFile.'?';
  foreach($currentParams as $key=>$var){
    if(!in_array($key, $ignoreParams)){
      $params[] = $key.'='.$var;
    }
  }
  $linkURL .= implode('&', $params);
  return $linkURL;
}
//Display file history
function ateDisplayFileHist($fileID){
  $fileHistory = ateGetFileHist($fileID);
  $linkURL = 'themes.php?';
  foreach($_GET as $key=>$var){
    if($key != 'themeRev' && $key != 'fileRev'){
      $params[] = $key.'='.$var;
    }
  }
  $linkURL .= implode('&', $params);

?>
  <li class="noLink">Revert to version of this file saved:</li>
<?php
  for($count = 0; $count < count($fileHistory); ++$count):
  ?>
    <li><a href="<?php echo $linkURL.'&fileRev='.$fileHistory[$count]['id']; ?>"><?php echo human_time_diff(strtotime($fileHistory[$count]['timestamp'])); ?> ago</a></li>
  <?php
  endfor;
}

// Read a file
function ateFetchFileContents($path){
  if ( !is_file($path) ){
    $error = new WP_Error('invalidFile', __("File does not exist."));
  }
  
  if ( !isset($error) && filesize($path) > 0 ) {
  	$f = fopen($path, 'r');
  	$content = fread($f, filesize($path));

  	return $content;
  }
}

// Get revision saved in DB
function ateGetFileRevision($revID){
  global $wpdb;
  $revision = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."ate_file_revisions WHERE id = $revID", ARRAY_A);
  return $revision;
}
// Get revision saved in DB
function ateGetThemeRevision($revID){
  global $wpdb;
  $revision = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."ate_theme_revisions WHERE id = $revID", ARRAY_A);
  return $revision;
}

// Commit theme, take live
function ateCommitTheme($theme){
  global $wpdb;
  $themes = get_themes();
  $templateDir = $themes[$theme]['Template Dir'];
  $previewTemplateDir = ateGetPreviewDir($theme);
  // Move preview to live
  ateCopyDir($previewTemplateDir, $templateDir);
  
  // Save record of commit
  $insert['theme'] = $theme;
  $insert['modified'] = date("Y-m-d H:i:s");
  $wpdb->insert($wpdb->prefix.'ate_theme_revisions', $insert);
  $commitID = mysql_insert_id();
  
  // Move copy to repository
  $repoPath = ateGetRepoPath($commitID);
  ateCopyDir($previewTemplateDir, $repoPath);
  
  // Update db listing with path
  $update['path'] = $repoPath;
  $where['id']    = $commitID;
  $wpdb->update($wpdb->prefix.'ate_theme_revisions', $update, $where);
}

// Get path to archived theme
function ateGetRepoPath($revID){
  $revision = ateGetThemeRevision($revID);
  $themes = get_themes();
  return WP_PLUGIN_DIR."/advanced-theme-editor/repo/".$themes[$revision['theme']]['Template']."/$revID";
}
// Get info from most recent commit
function ateGetLastCommit($theme){
  global $wpdb;
  $lastCommit = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."ate_theme_revisions WHERE theme = '$theme' order by modified desc limit 1", ARRAY_A);
  return $lastCommit;
}

// Get theme change history
function ateGetThemeHist($theme){
  global $wpdb;
  $revisions = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."ate_theme_revisions WHERE theme = '$theme' order by modified desc", ARRAY_A);
  return $revisions;
}

// Get file change history
function ateGetFileHist($fileID){
  global $wpdb;
  $revisions = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."ate_file_revisions WHERE file_id = $fileID order by timestamp desc", ARRAY_A);
  return $revisions;
}
// Save changes to a file
function ateSavePreview($fileName, $theme, $newContent){
  global $wpdb;
  global $current_user;
  get_currentuserinfo();
  $fileID = ateCreateRecord($fileName, $theme);
  
  // We're just previewing this, so we want to save the file to a working location
  $workingPath = ateGetPreviewDir($theme).'/'.$fileName;
  
  // Save to workingPath
	if (is_writeable(dirname($workingPath))) {
    $oldContent = ateFetchFileContents($workingPath);
		$file = fopen($workingPath, 'w+');
		if ($file !== FALSE) {
		  // Write to file
			fwrite($file, $newContent);
			fclose($file);
			
            // List file as "under construction"
            ateSetFileStatus($fileID, 'working', $workingPath);
            ateSaveRevision($fileID, $workingPath, $theme, $newContent, mktime(), $current_user->ID);

			return true;
		}else{
      return new WP_Error('IO_ERROR', __("Could not open file to write"));
		}
	}else{
    // if we got here, something went wrong
    return new WP_Error('unknown_error', __("Path not writeable: ".$workingPath));
	}
}
// Check if the preview/working theme has been created
function atePreviewThemeExists($theme){
  $themes = get_themes();
  $templateDir = $themes[$theme]['Template Dir'];
  $previewTemplateDir = ateGetPreviewDir($theme);
  if(is_dir($previewTemplateDir)){
    return true;
  }else{
    return false;
  }
}


function ateCreatePreviewDir($theme){
  $themes = get_themes();
  $templateDir = $themes[$theme]['Template Dir'];
  $previewTemplateDir = ateGetPreviewDir($theme);
  ateCopyDir($templateDir, $previewTemplateDir);
}

function ateGetPreviewDir($theme = '', $templateDir = ''){
  if($templateDir == ''){
    $themes = get_themes();
    $templateDir = $themes[$theme]['Template Dir'];
  }

  if(substr($templateDir, 0, 7) == 'http://'){
    $templateDir = substr($templateDir, 7);
    $url = true;
  }else{
    $url = false;
  }
  $previewTemplateDirPathParts = explode('/', $templateDir);
  $previewTemplateDirPathParts[count($previewTemplateDirPathParts)-1] = '.preview-'.$previewTemplateDirPathParts[count($previewTemplateDirPathParts)-1];
  
  if($url){
    $previewTemplateDir = 'http://';
  }else{
    $previewTemplateDir = '';
  }
  $previewTemplateDir .= implode($previewTemplateDirPathParts, '/');
  return $previewTemplateDir;
}

function ateCopyDir($source, $dest){
  // Simple copy for a file
  if (is_file($source)) {
    $c = copy($source, $dest);
    chmod($dest, 0777);
    return $c;
  }

  // Make destination directory
  if (!is_dir($dest)) {
    // Only check from document root on down
    $noRoot = str_replace(WP_CONTENT_DIR, '', $dest);
    // We need to make sure dirs all the way up to the target exist
    $requiredDirs = explode('/', $noRoot);
    $createDir = WP_CONTENT_DIR;
    foreach($requiredDirs as $dir){
      $createDir .= $dir.'/';
      if(!is_dir($createDir)){
        $oldumask = umask(0);
        mkdir($createDir, 0777);
        umask($oldumask);
      }
    }
  }

  // Loop through the folder
  $dir = dir($source);
  $workingFiles = array();
  while (false !== $entry = $dir->read()) {
    // Skip pointers
    if ($entry == "." || $entry == "..") {
      continue;
    }
    
    // Find .working- files, and save those for now (to overwrite current files)
    if(substr($entry, 0, 9) == '.working-'){
      $workingFiles[] = $entry;
      continue;
    }

    // Deep copy directories
    if ($dest !== "$source/$entry") {
      ateCopyDir($source."/".$entry, $dest."/".$entry);
    }
  }
  foreach($workingFiles as $file){
    $newFileName = str_replace('.working-', '', $file);
    ateCopyDir($source."/".$file, $dest."/".$newFileName);
  }
  // Clean up
  $dir->close();
  return true;
}

function ateSetFileStatus($fileID, $status, $workingPath){
  global $wpdb, $current_user;
  $update['status']                = $status;
  $update['working_path']          = $workingPath;
  $update['working_last_modified'] = date("Y-m-d H:i:s");
  $where['id']                     = $fileID;
  $wpdb->update($wpdb->prefix.'ate_files', $update, $where);
}

// Create main file record.  Returns File ID, or error
function ateCreateRecord($fileName, $theme){
  global $wpdb;
  
  $themes = get_themes();
  $path = $themes[$theme]['Template Dir'].'/'.$fileName;
  
  if ( !is_file($path) ){
    $error = new WP_Error('invalidFile', __("File does not exist."));
  }
  
  
  $insert['path']      = $path;
  $insert['file_name'] = $fileName;
  $insert['theme']     = $theme;
  $insert['created']   = date("Y-m-d H:i:s", filectime($path));
  $insert['modified']  = date("Y-m-d H:i:s", filemtime($path));
  
  if(@$wpdb->insert($wpdb->prefix.'ate_files', $insert)){
    // Get file ID
    $fileID = mysql_insert_id();
    // Since this is a new file, we need to save an initial revision of it
    $content = ateFetchFileContents($path);
    ateSaveRevision($fileID, $path, $theme, $content, strtotime($insert['modified']), $current_user->ID, 'Initial state');
  }else{
    $fileID = $wpdb->get_var($wpdb->prepare("SELECT id FROM ".$wpdb->prefix."ate_files WHERE path = '%s'",  $path));
  }
  return $fileID;
}

// Fetch main file record.  Will take fileID or path
// If neither are provided, returns false
function ateGetRecord($fileID = 0, $path = ''){
  global $wpdb;
  $record = false;
  if(intval($fileID) != 0){
    $record = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."ate_files WHERE id = $fileID", ARRAY_A);
  }elseif($path != '' ){
    $record = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."ate_files WHERE path = '$path'", ARRAY_A);
  }
  return $record;
}

// Save revision
function ateSaveRevision($id, $path, $theme, $content, $timestamp = '', $user = 'unknown', $comments = ''){
  if ( !is_file($path) ){
    $error = new WP_Error('invalidFile', __("File does not exist."));
  }

  global $wpdb;
  
  if($timestamp == ''){
    $timestamp = date("Y-m-d H:i:s");
  }else{
    $timestamp = date("Y-m-d H:i:s", $timestamp);
  }
  $insert['file_id']        = $id;
  $insert['path']      = $path;
  $insert['file_name'] = basename($path);
  $insert['theme']     = $theme;
  $insert['content']   = $content;
  $insert['timestamp'] = $timestamp;
  $insert['user_id']      = $user;
  $insert['comments']  = $comments;
  
  if(@$wpdb->insert($wpdb->prefix.'ate_file_revisions', $insert)){
    return true;
  }else{
    return false;
  }
}
function ateGetPreviewURL($path){
  return bloginfo('home').'?ateMode=preview';
}


function ateJSInterface(){
  global $editorType, $editFile, $pagenow;
  if($_GET['page'] == 'advanced-theme-editor'){
?>
<script type="text/javascript" src="../wp-content/plugins/advanced-theme-editor/js/ate-cssParse.js" ></script>
<script type="text/javascript">
  var editFile = new Array();
  editFile['theme'] = "<?php echo $editFile['theme']; ?>";
  editFile['name'] = "<?php echo $editFile['name']; ?>";
  editFile['path'] = "<?php echo $editFile['path']; ?>";
  editFile['type'] = "<?php echo $editFile['type']; ?>";
  editFile['id']   =  <?php echo $editFile['id']; ?>;
  
  var editorType  = "<?php echo $editorType; ?>";

</script>
<script type="text/javascript" src="../wp-content/plugins/advanced-theme-editor/js/ate-editor.js" ></script>
<?php
  }
}


/**
 * Based heavily on wp_text_diff()
 * Builds HTML representation of differences between 2 files
 * Modified to show changes relevant to php/html editing
 */
function ateFileDiff( $left_string, $right_string, $args = null ) {
	$defaults = array( 'title' => '', 'title_left' => '', 'title_right' => '' );
	$args = wp_parse_args( $args, $defaults );

	if ( !class_exists( 'WP_Text_Diff_Renderer_Table' ) )
		require( ABSPATH . WPINC . '/wp-diff.php' );

	$left_string  = ateNormalizeWhitespace($left_string);
	$right_string = ateNormalizeWhitespace($right_string);

	$left_lines  = split("\n", $left_string);
	$right_lines = split("\n", $right_string);

	$text_diff = new Text_Diff($left_lines, $right_lines);
	$renderer  = new WP_Text_Diff_Renderer_Table();
	$diff = $renderer->render($text_diff);

	if ( !$diff )
		return '';


	$r  = "<table class='diff'>\n";
	$r .= "<col class='ltype' /><col class='content' /><col class='ltype' /><col class='content' />";

	if ( $args['title'] || $args['title_left'] || $args['title_right'] )
		$r .= "<thead>";
	if ( $args['title'] )
		$r .= "<tr class='diff-title'><th colspan='4'>$args[title]</th></tr>\n";
	if ( $args['title_left'] || $args['title_right'] ) {
		$r .= "<tr class='diff-sub-title'>\n";
		$r .= "\t<td></td><th>$args[title_left]</th>\n";
		$r .= "\t<td></td><th>$args[title_right]</th>\n";
		$r .= "</tr>\n";
	}
	if ( $args['title'] || $args['title_left'] || $args['title_right'] )
		$r .= "</thead>\n";

	$r .= "<tbody>\n$diff\n</tbody>\n";
	$r .= "</table>";


	return $r;
}

/**
 * Based on normalize_whitespace().  Modified to leave in multiple spaces (so that indents make it through
**/
function ateNormalizeWhitespace($string){
	$string  = trim($string);
	$string  = str_replace("\r", "\n", $string);
	$string  = preg_replace( array( '/\n+/', '/[ \t]+/' ), array( "\n", ' ' ), $string );
	return $string;
}

// ======== 
// = AJAX = 
// ======== 
add_action('wp_ajax_ateMoveFile', 'ateAjaxMoveFile');

function ateAjaxMoveFile(){
  $theme = $_POST['theme'];
  $from  = $_POST['from'];
  $to    = $_POST['to'];
  return ateMoveFile($theme, $from, $to);
}

function ateMoveFile($theme, $from, $to){
  $workingDir = ateGetPreviewDir($theme);
  return rename("$workingDir/$from", "$workingDir/$to/$from");
}