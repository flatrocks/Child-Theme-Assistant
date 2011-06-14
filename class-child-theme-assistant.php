<?php  
/*
  class-child-theme-assistant.php
  <project>

  Created by Tom Wilson on 2011-06-05.
  Copyright 2011 Tom Wilson. All rights reserved.
*/

// Wordpress won't document correct way to locate this; safer to just bring our own tools.
if(! class_exists('PclZip'))
  require_once('lib/pclzip/pclzip.lib.php');

class ChildThemeAssistant {
  const MINIMUM_PHP_VERSION = '5.0.0';
  const MINIMUM_WP_VERSION = '3.0.0';   // might work for earlier
  
  private $message = '';  // Info message included at top of view
  
  /* Static methods */
 
  public static function init() {
     define('CTASST_PLUGIN_URL', plugins_url( '' , __FILE__));  // Ends with '/'
     define('CTASST_PLUGIN_DIR', dirname(__FILE__));            // Does NOT end with '/'
     define('CTASST_PLUGIN_TEMPDIR', CTASST_PLUGIN_DIR . '/temp');
     wp_enqueue_style( 'child_theme_assistant_styles', CTASST_PLUGIN_URL . '/admin.css' );
  }

  public static function add_menu_items() {
    if (current_user_can('manage_options')) {
      add_theme_page(__('Child Theme Assistant','child_theme_asst'), __('Child Theme Assistant','child-theme-assistant'), 'manage_options', 'child_theme_asst', 'ChildThemeAssistant::display_content');
    }
  }
  
  public static function display_content() {     
    if (version_compare(PHP_VERSION, self::MINIMUM_PHP_VERSION, '<')) {
      echo 'Child Theme Assistant requires php version ' . self::MINIMUM_PHP_VERSION . '.<br />Current version is' . PHP_VERSION . '.';
      return;
    }
    if (version_compare(get_bloginfo('version'), self::MINIMUM_WP_VERSION, '<')) {
      echo 'Child Theme Assistant requires Wordpress version ' . self::MINIMUM_WP_VERSION . '.<br />Current version is ' . get_bloginfo('version') . '.';
      return;
    }
    
    $instance = new ChildThemeAssistant();
    
    switch ($_GET['view']) {
      case 'overview':
      default:
        $instance->handle_overview_request();
        break;
      case 'create':
        $instance->handle_create_request();
        break;
      case 'templates':
        if (isset($_POST['action']) && $_POST['action'] == 'compare_files') {
          $instance->handle_compare_request();
        } else {
          $instance->handle_templates_request();
        }
        break;
// Should be get request for compare but wp screws it up, just post
//      case 'compare':
//        $instance->handle_compare_request();
//        break;        
      case 'download':
        $instance->handle_download_request();
        break;
    }
  } 
 
  private static function theme_names_parent_filter($theme) {return empty($theme['Parent Theme']);}
  private static function theme_names_child_filter($theme) {return !empty($theme['Parent Theme']);} 
  public static function theme_names($type = null) {
    $themes = get_themes();
    var_dump($themes);
    switch($type) {
      case 'parent': // no closures... to support downlevel php
        $themes = array_filter($themes, 'self::theme_names_parent_filter');
        break;
      case 'child':
        $themes = array_filter($themes, 'self::theme_names_child_filter');
        break;
    }
    $theme_names = array();    
    foreach($themes as $theme) {
      $theme_names[] = $theme['Name'];
    }
    return $theme_names;    
  }
 
  // download file based on src created in ... method
  public static function execute_download() {
  
    if (!current_user_can('administrator'))
      exit;

    $tempfile = base64_decode($_GET['file']);
    $name = base64_decode($_GET['name']);
    echo $tempfile;
    echo '<br>';
    echo $name;  
    if (!file_exists($tempfn))
      exit;

    // Set headers
    header("Pragma: no-cache");
    header("Expires: 0");
    header("Cache-Control: public");
    header("Content-Description: File Transfer");
    header("Content-Disposition: attachment; filename=$name.zip");
    header("Content-Type: application/zip");
    header("Content-Transfer-Encoding: binary");
    readfile($tempfile);
    unlink($tempfile);
  }  
  
  
  /* Request handlers */  
  
 
  // Always static overview page 
  function handle_overview_request() {
    $this->render_overview();
  }
      
  // Create child theme
  function handle_create_request() {
    if (empty($_POST) || !wp_verify_nonce($_POST['form_id'], 'create')) {
      $this->render_create();
    } else {
      $success = false;
      do {
        // Require parent theme and associated template
        $parent_theme = get_theme($_POST['parent_name']);
        if (!$parent_theme) {
          $this->message = 'Parent theme not found';
          break;
        }
        $template_name = $parent_theme['Stylesheet'];
        if (!$template_name) {
          $this->message = 'Parent template not found';
          break;
        }
        // Make sure theme name is unique and not empty
        $theme_name = preg_replace('/(\*\/)/', '', $_POST['theme_name']);
        if (empty($theme_name)) {
          $this->message = 'You must enter a theme name';
          break;
        }
        if (in_array($theme_name, self::theme_names())) {
          $this->message = "There is already a theme named \"$theme_name\"";
          break;
        }
    
        // Clean up optional header params
        $theme_uri = preg_replace('/(\*\/)/', '', $_POST['theme_uri']);
        $description = preg_replace('/(\*\/)/', '', $_POST['description']);
        $author = preg_replace('/(\*\/)/', '', $_POST['author']);
        $author_uri = preg_replace('/(\*\/)/', '', $_POST['author_uri']);
        $version = preg_replace('/(\*\/)/', '', $_POST['version']);

        // Create directory name from theme name, spread out code for clarity
        $new_directory_basename = strtolower($theme_name);                                    // Base for name
        $new_directory_basename = preg_replace('/\s/', '-', $new_directory_basename);         // Whitespace becomes _
        $new_directory_basename = preg_replace('/[^a-z0-9-]/', '', $new_directory_basename);  // Strip unwanted characters
        $new_directory_basename = preg_replace('/-+/', '-', $new_directory_basename);         // Collapse multiple _'s
        $new_directory_name = $new_directory_basename;
        $fnindex = 1; // 10 tries to find unique name
        while (file_exists(get_theme_root() . '/' . $new_directory_name) && $fnindex < 10) {
             $new_directory_name = "{$new_directory_basename}_{$fnindex}";
             $fnindex++;
        }
        if ($fnindex >= 10) {
          $this->message = "Can't find an unused directory name for the new theme";
          break;
        }

        // New style.css file content
        $content = <<<STYLE_CSS
/*
Theme Name: $theme_name
Theme URI:  $theme_uri
Description:  $description 
Author:  $author
Author URI:   $author_uri
Template:  $template_name
Version:  $version
*/
@import url("../$template_name/style.css");

STYLE_CSS;

        // Finally... create theme
        $parent_theme_dir_path = join('/', array(get_theme_root(), $parent_theme['Stylesheet']));
        $new_theme_dir_path = join('/', array(get_theme_root(), $new_directory_name));
        if (! wp_mkdir_p($new_theme_dir_path)) {
          $this->message = "Can't create theme directory \"$new_directory_name\"";
          break;
        }
        if (!file_put_contents($new_theme_dir_path . '/style.css', $content)) {
          $this->message = "Can't create \"{$new_directory_name}/style.css\"";
          break;
        }

        $success = true; // woo hoo!
      } while(false);
      
      if ($success) {
        $this->render_overview();
      } else {
        $this->render_create();
      }
    }
  }
  
  // Any request for templates view
  function handle_templates_request() {
    if (empty($_POST) || wp_verify_nonce($_POST['form_id'],'templates')) {
      $this->render_templates();
    } else {  
      // figure out what's been requested
      $child_theme = get_theme($_POST['child_name']);
      $parent_theme =  get_theme($child_theme['Parent Theme']);    

      if (!$child_theme) {
        $this->message = 'Child theme not found';
        $this->render_templates();
        return;
      }
      if (!$parent_theme) {
        $this->message = 'Parent theme not found';
        $this->render_templates();
        return;
      }
      
      $child_theme_dir_path = join('/', array(get_theme_root(), $child_theme['Stylesheet']));
      $parent_theme_dir_path = join('/', array(get_theme_root(), $parent_theme['Stylesheet']));    
      
      $action = $_POST['action'];
      $file = $_POST['file'];
      switch ($action) {
        case 'copy_file':
          $from = join('/', array($parent_theme_dir_path, $file));
          $to = join('/', array($child_theme_dir_path, $file));                
          if (!copy($from, $to))
            $this->message = "$file could not be copied";
          $this->render_templates();
          break;          
        case 'create_file':
          $target = join('/', array($child_theme_dir_path, $file));          
          if (empty($file)) {
            $this->message = "You must enter a file name";
          } elseif (file_exists($target)) {
              $this->message = "$file already exists";
          } else {
            if (!file_put_contents($target, "/* $file */"))
              $this->message = "Can't create $file";
          }
          $this->render_templates();            
          break;
        case 'delete_file':
          $target = join('/', array($child_theme_dir_path, $file));
          echo($target);
          if (!unlink($target))
            $this->message = "$file could not be deleted";
          $this->render_templates(); 
          break;
        default:
          $this->render_templates();
          break;
      }
    }
  }
    
  function handle_compare_request() {
    echo("Not done yet!");
  }

  function handle_download_request() {
    if (empty($_POST) || !wp_verify_nonce($_POST['form_id'], 'download')) {
      $this->render_download();
    } else {
      do {
        $tempfile = CTASST_PLUGIN_TEMPDIR . '/' . uniqid('ctasst', false) . '.zip';
      } while (file_exists($tempfn));
    
      // try zipping to tempfile, capture output and return code
      $exec_output = array();
      $exec_rc = 0;
      $theme = get_theme($_POST['theme_name']);
      $theme_dir = $theme['Stylesheet'];
      $theme_root = get_theme_root();    
    
      $archive = new PclZip($tempfile);
      if ($archive->create("$theme_root/$theme_dir", PCLZIP_OPT_REMOVE_PATH, "$theme_root/") == 0) {
        if (file_exists($tempfile))
          unlink($tempfile);
      }
   
      if (!file_exists($tempfile)) {
        $this->message .= "Cannot create zip file.";
        $this->render_download();
      } else {
        $tempfile64 = base64_encode($tempfile);
        $theme_directory64 = base64_encode($theme_directory);
        $downloader_src = plugins_url( "download.php?file=$tempfile64&name=$theme_directory64" , __FILE__ );
        $this->message .= <<<DOWNLOAD
<script type="text/javascript">
  jQuery(document).ready(function() {
   csst_iframe = document.createElement("IFRAME");
   csst_iframe.style.visibility = 'hidden';  
   csst_iframe.setAttribute("src", "$downloader_src");    
   document.body.appendChild(csst_iframe);
  });
</script>           
DOWNLOAD;
        $this->render_overview();
      }
    }
  }
  
  /* Helper methods */
 
 
  
  /* Views */
 
  function render_view_header() {
?>
<div class='wrap ctasst'>
  <div id='icon-themes' class='icon32'>
    <br />
  </div>
  <h2>Child Theme Assistant</h2>
  <p>
    <a href='<?php echo add_query_arg( 'view', 'overview', get_permalink()); ?>'>Overview</a> | 
    <a href='<?php echo add_query_arg( 'view', 'create', get_permalink()); ?>'>Create child theme</a> | 
    <a href='<?php echo add_query_arg( 'view', 'templates', get_permalink()); ?>'>Manage templates</a> |
    <a href='<?php echo add_query_arg( 'view', 'download', get_permalink()); ?>'>Download</a>
  </p>
<?php
    if (!empty($this->message)) {
      echo("  <p class='message'>{$this->message}</p>\n");
    }
  }

  function render_view_footer() {
?>  
</div>
<?php
  }
 
  // Always static overview page 
  function render_overview() {
    $this->render_view_header();
?>
  <h3>Overview</h3>
  <p>
    Child Theme Assistant provides away to create and manage child themes from the Wordpress admin panel.
  </p>
  <p class='warning'>This plugin lets you create and delete template files, which means you can really shoot yourself in the foot
    if you are not careful!
  <p>
    <dl>
      <dt>Create child theme</dt><dd>Creates a new child theme based on any current regular (non-child) theme</dd>
      <dt>Manage templates</dt><dd>View child and parent templates, and:
        <ul>
          <li>Copy a template from parent theme</li>
          <li>Create a new template</li>
          <li>Delete a template</li>
          <li>Compare a child theme template to the associated parent theme template</li>
        </ul>
      </dd>
      <dt>Download</dt><dd>Zip and download a template</dd>
    </dl>
  </p>
<?php
    $this->render_view_footer();
  }
 
  function render_create() {
    $this->render_view_header();    
?>
    <h3>Create child theme</h3>
    <form method='post' action='<?php echo str_replace( '%7E', '~', $_SERVER['REQUEST_URI']); ?>'>  
      <?php wp_nonce_field('create','form_id'); ?>
      <table>
        <tr>
          <th>Parent theme</th>
          <td>
            <select name='parent_name' >
              <?php foreach(self::theme_names('parent') as $parent_name) {
                var_dump(self::theme_names('parent'));
                $seltext = ($parent_name == $_POST['parent_name']) ? "selected='selected'" : '';
                echo "<option {$seltext}>$parent_name</option>\n";
              } ?>
            </select></td>
        </tr><tr>
          <th>Theme Name:</th>
          <td><input type='text' name='theme_name' value='<?php echo $_POST['theme_name']; ?>' size='20'> (required)</td>  
        </tr><tr>
          <th>Theme URI:</th>
          <td><input type='text' name='theme_uri' value='<?php echo $_POST['theme_uri']; ?>' size='20'></td>  
        </tr><tr>
          <th>Description:</th>
          <td><input type='text' name='description' value='<?php echo $_POST['description']; ?>' size='50'></td>  
        </tr><tr>
          <th>Author:</th>
          <td><input type='text' name='author' value='<?php echo $_POST['author']; ?>' size='20'></td> 
        </tr><tr>         
          <th>Author URI:</th>
          <td><input type='text' name='author_uri' value='<?php echo $_POST['author_uri']; ?>' size='20'></td>  
        </tr><tr>
          <th>Version:</th>
          <td><input type='text' name='version' value='<?php echo $_POST['version']; ?>' size='20'></td>
      </tr>
      </table> 
      <p class='submit'>  
        <input type='submit' name='Submit' value='<?php _e('Create') ?>' />  
      </p>  
    </form>
<?php
    $this->render_view_footer();
  }

  function render_templates() {
    $child_theme = get_theme($_POST['child_name']);
    $parent_theme =  get_theme($child_theme['Parent Theme']);
    $child_theme_dir_path = join('/', array(get_theme_root(), $child_theme['Stylesheet']));
    $parent_theme_dir_path = join('/', array(get_theme_root(), $parent_theme['Stylesheet']));    
    $child_files = scandir($child_theme_dir_path);
    function no_dots($fn) {return substr($fn, 0, 1) != '.';}
    $child_files = array_filter($child_files, no_dots);    
    $parent_files = scandir($parent_theme_dir_path);
    $parent_files = array_filter($parent_files, no_dots);
    $all_files = array_unique(array_merge($child_files, $parent_files));
    sort($all_files);
    
    $this->render_view_header();  ?>
  <h3>Manage templates</h3>
  <form method='post' action='<?php echo str_replace( '%7E', '~', $_SERVER['REQUEST_URI']); ?>'>  
    <?php wp_nonce_field('templates','form_id'); ?>    
    <p>
      Select child theme
      <select name='child_name' >
        <option></option>
        <?php 
          foreach(self::theme_names('child') as $child_name) {
            $seltext = ($child_name == $_POST['child_name']) ? "selected='selected'" : '';
            echo "<option {$seltext}>$child_name</option>\n";
          } 
        ?>
      </select>
      <input type="submit" value="Select" name="Submit">
    </p>
  </form> 
<?php 
    if ($child_theme && $parent_theme) { 
?>
    <p>
      Parent theme: <?php echo $parent_theme['Name']; ?>
    </p>
    <table class="widefat template_table">
      <thead>
        <tr>
          <th>Parent template</th>
          <th><!-- parent action --></th>
          <th>Child template</th>
          <th><!-- child action --></th>
        </tr>
      </thead>
      <tfoot>
        <tr>
          <th>Parent template</th>
          <th><!-- parent action --></th>
          <th>Child template</th>
          <th><!-- child action --></th>
        </tr>
      </tfoot>
      <tbody>
<?php
    $form_action = str_replace( '%7E', '~', $_SERVER['REQUEST_URI']);
    foreach($all_files as $filename) {
      $parent_file_exists = in_array($filename, $parent_files);
      $child_file_exists = in_array($filename, $child_files);
      $parent_file = $parent_file_exists ? $filename : '';
      $child_file = $child_file_exists ? $filename : '<span class="inherited">(inherited)</span>';
      if ($parent_file_exists) {
        if ($child_file_exists) {
          // should not be a post, but way easier for wp
          $parent_action = <<<FORM
            <form method='post' action='$form_action'>
              <input type='hidden' name='child_name' value='$child_name'>
              <input type='hidden' name='file' value='$filename'>
              <input type='hidden' name='action' value='compare_files'>    
              <input type='submit' value='<- Compare ->'>
            </form> 
FORM;
        } else {
          $parent_action = <<<FORM
            <form method='post' action='$form_action'>
              <input type='hidden' name='child_name' value='$child_name'>
              <input type='hidden' name='file' value='$filename'>
              <input type='hidden' name='action' value='copy_file'>
              <input type='submit' name='copy' value='Copy ->'>
            </form>
FORM;
        }
      } else {
        $parent_action = '';
      }
      if ($child_file_exists) {
        if ($filename == 'style.css') {
          $child_action = '<span class="inherited">(required)</span>';
        } else {
          $child_action = <<<FORM
            <form method='post' action='$form_action'>
              <input type='hidden' name='child_name' value='$child_name'>
              <input type='hidden' name='file' value='$filename'>
              <input type='hidden' name='action' value='delete_file'>
              <input type='submit' name='compare' value='Delete' onclick='return confirm("Are you sure?");'>
            </form>
FORM;
        }
      } else {
        $child_action = '';
      }
?>
       <tr>
         <td><span class='inherited'><?php echo $parent_file; ?></span></td>
         <td style='text-align:center'><?php echo $parent_action; ?></td>
         <td><?php echo $child_file; ?></td>
         <td><?php echo $child_action; ?></td>
       </tr>
<?php
    }
?>
       <tr>
         <td></td>
         <td></td>
         <td colspan='2'>
            <form method='post' action='<?php echo $form_action; ?>'>
              <input type='hidden' name='child_name' value='<?php echo $child_name; ?>'>
              <input type='text' name='file'>
              <input type='hidden' name='action' value='create_file'>
              <input type='submit' name='compare' value='Create file'>
            </form>
          </td>
       </tr>    
      </tbody>
    </table>
<?php 
    }
    $this->render_view_footer();
  }
    
  function render_download() {
    $this->render_view_header();    
?>
  <h3>Download</h3>
  <p>Zip and download the selected theme.</p>
  <form method='post' action='<?php echo str_replace( '%7E', '~', $_SERVER['REQUEST_URI']); ?>'>  
    <?php wp_nonce_field('download','form_id'); ?>
    <select name='theme_name' >
      <?php 
        foreach(self::theme_names() as $theme_name) {
          $seltext = ($theme_name == $_POST['theme_name']) ? "selected='selected'" : '';
          echo "<option {$seltext}>$theme_name</option>\n";
        } 
      ?>
    </select>
    <input type="submit" name="download" value="Download">
  </form>
<?php
    $this->render_view_footer();   
  }
  
}
