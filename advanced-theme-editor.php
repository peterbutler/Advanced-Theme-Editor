<?php
/*
Plugin Name: Advanced Theme Editor
Plugin URI: http://codesessions.net/plugins/advanced-theme-editor/
Description: Adds a little extra functionality to the normal WP theme editor.
Version: 0.1
Author: Peter Butler
Author URI: http://codesessions.net/
*/

include_once(WP_PLUGIN_DIR.'/advanced-theme-editor/ate-functions.php');

// ================ 
// = INSTALLATION = 
// ================ 
register_activation_hook( __FILE__, 'ateInstaller');
function ateInstaller(){
  global $wpdb;
  $filesTable         = "CREATE TABLE IF NOT EXISTS `".$wpdb->prefix."ate_files` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `file_name` varchar(255) DEFAULT NULL,
    `theme` varchar(255) DEFAULT NULL,
    `status` varchar(255) DEFAULT NULL,
    `path` varchar(255) DEFAULT NULL,
    `working_path` varchar(255) DEFAULT NULL,
    `created` timestamp NULL DEFAULT NULL,
    `modified` timestamp NULL DEFAULT NULL,
    `working_last_modified` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique` (`theme`,`file_name`)
  ) ENGINE=MyISAM;";
  $wpdb->query($filesTable);
  
  $fileRevisionsTable = "CREATE TABLE IF NOT EXISTS `".$wpdb->prefix."ate_file_revisions` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `theme` varchar(255) DEFAULT NULL,
    `file_id` int(11) DEFAULT NULL,
    `file_name` varchar(255) DEFAULT NULL,
    `path` varchar(255) DEFAULT NULL,
    `user_id` int(11) DEFAULT NULL,
    `content` text,
    `timestamp` timestamp NULL DEFAULT NULL,
    `modification_type` int(11) DEFAULT NULL,
    `comments` text,
    PRIMARY KEY (`id`)
  ) ENGINE=MyISAM;";
  $wpdb->query($fileRevisionsTable);
  
  $themeRevisionsTable = "CREATE TABLE IF NOT EXISTS `".$wpdb->prefix."ate_theme_revisions` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `theme` varchar(100) DEFAULT NULL,
    `modified` timestamp NULL DEFAULT NULL,
    `comment` text,
    `path` varchar(200) DEFAULT NULL,
    PRIMARY KEY (`id`)
  ) ENGINE=MyISAM;";
  $wpdb->query($themeRevisionsTable);
}

// ======== 
// = INIT = 
// ======== 
// We need to hook into the WP core to get our page included.
add_action('admin_menu', 'ateAdminPages');
// Set up some globals
add_action('admin_init', 'ateInit');
// Some JS at the end of the file
add_action('admin_footer', 'ateJSInterface');

function ateAdminPages() {
  $ate_page = add_submenu_page( 'themes.php', 'Advanced Theme Editor', 'Advanced Editor', 'edit_themes', 'advanced-theme-editor', 'ateAdminPage');
  add_action( "admin_print_scripts-$ate_page", 'ateAdminHeadJS' );
  add_action( "admin_print_styles-$ate_page", 'ateAdminHeadCSS' );
}

function ateInit(){
  if($_GET['page'] == 'advanced-theme-editor'){
    // Image file types
    global $ateImageFileTypes;
    $ateImageFileTypes[] = 'gif';
    $ateImageFileTypes[] = 'jpg';
    $ateImageFileTypes[] = 'jpeg';
    $ateImageFileTypes[] = 'png';  
  }
}

// JS includes
function ateAdminHeadJS(){ 
  //wp_enqueue_script('ate-editor', WP_PLUGIN_URL."/advanced-theme-editor/js/jquery-1.4.2.min.js");
  //wp_enqueue_script('ate-editor', WP_PLUGIN_URL."/advanced-theme-editor/js/jquery-ui-1.8.5.custom.min.js");
  wp_enqueue_script('jquery-ui-core');
  wp_enqueue_script('jquery-ui-resizable');
  wp_enqueue_script('jquery-ui-draggable');
  wp_enqueue_script('jquery-ui-droppable');
  wp_enqueue_script('ate-editor', WP_PLUGIN_URL."/advanced-theme-editor/js/jquery-linedtextarea/jquery-linedtextarea.js", array('jquery', 'jquery-ui-core', 'jquery-ui-resizable'));
}

// CSS Includes
function ateAdminHeadCSS(){
  wp_enqueue_style('editorLayout', WP_PLUGIN_URL."/advanced-theme-editor/css/editorLayout.css");
  //wp_enqueue_style('linedtextarea', WP_PLUGIN_URL."/advanced-theme-editor/js/jquery-linedtextarea/jquery-linedtextarea.css");
}

// The actual page
function ateAdminPage(){
  include_once(WP_PLUGIN_DIR.'/advanced-theme-editor/ate-controller.php');
  include_once(WP_PLUGIN_DIR.'/advanced-theme-editor/ate-view.php');
}


// ============ 
// = FRONTEND = 
// ============ 
// Check to see if the request is coming from the preview window
add_action('setup_theme', 'ateCheckPreview');

function ateCheckPreview(){
  if($_GET['ateMode'] == 'preview'){
    // Switch to view selected theme (rather than current theme)
    add_filter('stylesheet', 'ateThemePreview', 99);
    add_filter('template', 'ateStylesheetPreview', 99);

    // Make sure we're looking at the preview version
    add_filter('template_directory',  'atePreviewFilter', 99, 3);
    add_filter('stylesheet_directory',  'atePreviewFilter', 99, 3);
    add_filter('stylesheet_directory_uri',  'atePreviewFilter', 99, 3);
    add_filter('template_directory_uri',  'atePreviewFilter', 99, 3);

  }
}

function ateStyleSheetPreview($stylesheet){
  return get_option('ate_editing_stylesheet');
}
function ateThemePreview($stylesheet){
  return get_option('ate_editing_theme');
}
function atePreviewFilter($templateDir, $template = '', $themRoot = ''){
  $themes = get_themes();
  foreach($themes as $themeName=>$data){
    
    if($data['Template Dir'] == $templateDir || $data['Stylesheet'] == $template || $data['Template'] == $template){
      $theme = $themeName;
      break;
    }
  }
  
  if(!atePreviewThemeExists($theme)){
    ateCreatePreviewDir($theme);
  }
  $dir = ateGetPreviewDir('', $templateDir);
  return $dir;
}