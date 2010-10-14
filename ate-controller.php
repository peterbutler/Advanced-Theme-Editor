<?php
global $editorType, $editFile;
// Handle saves
if(isset($_REQUEST['action'])){
  switch($_REQUEST['action']){
    case 'preview':
    case 'save':
      check_admin_referer();
      $newContent = stripslashes($_POST['newContent']);
      $filePath   = stripslashes($_POST['path']);
      $fileName   = stripslashes($_POST['name']);
      $theme      = stripslashes($_POST['theme']);
      $return = ateSavePreview($fileName, $theme, $newContent);
      if(is_wp_error($return)){
        echo "<div class='error'><p>";
        echo $return->get_error_message();
        echo "</p></div>";
      }
      break;
    case 'go live':
    case 'commit':
      check_admin_referer();
      $newContent = stripslashes($_POST['newContent']);
      $filePath   = stripslashes($_POST['path']);
      $fileName   = stripslashes($_POST['name']);
      $theme      = stripslashes($_POST['theme']);
      $return = ateSavePreview($fileName, $theme, $newContent);
      $return = ateCommitTheme($theme);
      if(is_wp_error($return)){
        echo "<div class='error'><p>";
        echo $return->get_error_message();
        echo "</p></div>";
      }
      break;
  }
}


// Fetch all available themes
$themes = get_themes();

// Get theme to edit
if(isset($_GET['editTheme'])){
  $theme = stripslashes($_GET['editTheme']);
}else{
  // Fetch currently activated theme
  $theme = get_current_theme();
}

// Make sure we have a preview/working dir setup
$themes = get_themes();
foreach($themes as $themeName=>$data){
  if($data['Template Dir'] == $templateDir){
    $theme = $themeName;
    break;
  }
}

if(!atePreviewThemeExists($theme)){
  ateCreatePreviewDir($theme);
}

// Are we reverting to a previous theme?
if(isset($_GET['themeRev'])){
  $repoPath = ateGetRepoPath(intval($_GET['themeRev']));
  $previewDir = ateGetPreviewDir($theme);
  ateCopyDir($repoPath, ateGetPreviewDir($theme));
}


// indicate which theme is being edited
update_option('ate_editing_theme', $themes[$theme]['Template']);
update_option('ate_editing_stylesheet', $themes[$theme]['Stylesheet']);

// Make sure we're dealing with a valid theme
if(!isset($themes[$theme])){
  $error = new WP_Error('invalidTheme', __("Theme does not exist."));
}

$currentTheme = $themes[$theme];

// Do we have a working directory for this theme?

// Set up template files list
$editableFiles = ateGetTemplateFiles(ateGetPreviewDir($theme));
//$editableFiles = array_merge($themes[$theme]['Stylesheet Files'], $themes[$theme]['Template Files']);

// Set up current file to edit
if (empty($_GET['editFile'])) {
	$editFile['path'] = $editableFiles['style.css']['path'];
  $editFile['name'] = basename($editFile['path']);
} else {
  // A filename was passed in, so we'll fetch it
	$editFile['name'] = stripslashes($_GET['editFile']);
	$editFile['path'] = $themes[$theme]['Template Dir'] .'/'. $editFile['name'] ;
}

$editFile['type']   = pathinfo($editFile['path'], PATHINFO_EXTENSION);
$editFile['theme']  = $theme; 

// Get record id for this file
$fileID = ateCreateRecord($editFile['name'], $theme);
$editFile['id'] = $fileID;

validate_file_to_edit($editFile, $allowed_files);

// Set up editor type
switch($editFile['type']){
  case 'jpg':
  case 'jpeg':
  case 'gif':
  case 'png':
    $editorType = 'image';
    $imageSRC = $themes[$theme]['Theme Root URI'] .'/'. $themes[$theme]['Template'].'/'.$editFile['name'];
    break;
  default:
    $editorType = 'text';

    // Check if we've already got a working version
    $record = ateGetRecord($fileID);
    
    // Check if we're loading in a revision, or whatever is saved
    if(isset($_GET['fileRev'])){
      $revision = ateGetFileRevision(intval($_GET['fileRev']));
      $content  = $revision['content'];
    }else{
      $content = ateFetchFileContents(ateGetPreviewDir($theme).'/'.$editFile['name']);
    }
    break;
}

$removeVars[] = 'themeRev';
$removeVars[] = 'fileRev';
$submissionURL = ateCreateURL('themes.php', $_GET, $removeVars);


