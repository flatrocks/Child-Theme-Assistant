<?php  
/* 
Plugin Name: Child Theme Assistant
Plugin URI:  
Description: Plugin for creating and managing child themes
Author: T. Wilson
Version: 0.0
Author URI: http://www.rollnorocks.com
*/  

  define("CHILD_THEME_ASSITANT_NS_PREFIX", "ctasst");
  
  add_action('admin_menu', 'ctasst_add_menu_items');

   function ctasst_add_menu_items() {
    if (current_user_can('manage_options')) {
      add_theme_page(__('Child Theme Asst','child_theme_asst'), __('Child Theme Asst','child_theme_asst'), 'manage_options', 'child_theme_asst', 'ctasst_render_page');
    }
  }
 
  function ctasst_render_page() {
    // Check if form has been submitted
    if ($_POST['ctasst_hidden'] != 'Y') {
      ctasst_render_create_plugin_form();
      return;
    }
    
    // All steps to try and create new Theme...
    $message = false;  
    do {
      // Capture input, clean out /* which would break the comment block in the css file
      $parent_theme = get_theme($_POST['ctasst_parent_name']);
       if (!$parent_theme) {
        $message = "Parent Theme not found";
        break;
      }
      $template_name = $parent_theme['Stylesheet'];
        if (!$template_name) {
        $message = "Parent template not found";
        break;
      }     
       
      // Make sure theme name is unique
      $theme_name = preg_replace('/(\*\/)/', '', $_POST['ctasst_theme_name']);
      // dumb down for old php ver's: $theme_names = array_map(function ($theme) {return $theme['Name'];}, get_themes());
      $theme_names = array(); foreach(get_themes() as $theme) {$theme_names[] = $theme['Name'];}
      if (in_array($theme_names , $theme_names)) {
        $message = "There is already a theme named \"$theme_name\".  Please choose a different name.";
        break;
      }
      
      // Clean up optional header params
      $theme_uri = preg_replace('/(\*\/)/', '', $_POST['ctasst_theme_uri']);
      $description = preg_replace('/(\*\/)/', '', $_POST['ctasst_description']);
      $author = preg_replace('/(\*\/)/', '', $_POST['ctasst_author']);
      $author_uri = preg_replace('/(\*\/)/', '', $_POST['ctasst_author_uri']);
      $version = preg_replace('/(\*\/)/', '', $_POST['ctasst_version']);

      // Create directory name, spread out code for clarity
      $new_directory_basename = strtolower($theme_name);                                                  // Base for name
      $new_directory_basename = preg_replace('/\s/', '_', $new_directory_basename);              // Whitespace becomes _
      $new_directory_basename = preg_replace('/[^a-z0-9_]/', '', $new_directory_basename);   // Strip unwanted characters
      $new_directory_basename = preg_replace('/_+/', '_', $new_directory_basename);        // Collapse multiple _'s
      $new_directory_name = $new_directory_basename;
      $fnindex = 1;
      while (file_exists(get_theme_root() . '/' . $new_directory_name) && $fnindex < 20) {
           $new_directory_name = "{$new_directory_basename}_{$fnindex}";
           $fnindex++;
      }
      if ($fnindex >= 20) {
        $message = "Can't find an unused directory name for the new Template.";
        break;
      }
      
      // New file content
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

       // Try to create plugin using parent theme file permissions
      $parent_theme_dir_path = join('/', array(get_theme_root(), $parent_theme['Stylesheet']));
      $new_theme_dir_path = join('/', array(get_theme_root(), $new_directory_name));
      $parent_theme_permissions = fileperms($parent_theme_dir_path);
      if (! mkdir($new_theme_dir_path, $parent_theme_permissions)) {
        $message = "Can't create theme directory \"{$new_directory_name}\"}";
        break;          
      }
      $write_bytes = file_put_contents($new_theme_dir_path . '/style.css', $content);
      if (!$write_bytes) {
        $message = "Can't create \"{$new_directory_name}/style.css\"";
        break;  
      }
      
    } while (0);

    if ($message) {
      ctasst_render_create_plugin_form($message);
    } else {
      ctasst_render_success_page($theme_name);
    }
  }
    
  function ctasst_render_create_plugin_form($message = '') {
    // dumb down for old php ver's: $parent_themes = array_filter (get_themes(), function ($theme) { return empty($theme['Parent Theme']);});
    $parent_theme_names = array(); foreach(get_themes() as $theme) { if (empty($theme['Parent Theme'])) $parent_theme_names[] = $theme['Name'];}
 ?>
<div class="wrap ctasst_form">
  <div id="icon-themes" class="icon32">
    <br>
  </div>
  <h2>Child Theme Assistant</h2>
    <?php echo empty($message) ? '' : "<p class='message'>$message</p>"; ?>
    <form name="ctasst_form" method="post" action="<?php echo str_replace( '%7E', '~', $_SERVER['REQUEST_URI']); ?>">  
      <input type="hidden" name="ctasst_hidden" value="Y">  
      <?php    echo "<h3>Create child theme</h3>"; ?>  
      <table>
        <tr>
          <th>Parent theme</th>
          <td>
            <select name="ctasst_parent_name" >
              <?php foreach($parent_theme_names as $parent_theme_name) {
                $seltext = ($parent_theme_name == $_POST['ctasst_parent_name']) ? "selected='selected'" : "";
                echo "<option {$seltext}>$parent_theme_name</option>\n";
              } ?>
            </select></td>
        </tr><tr>
          <th>Theme Name:</th>
          <td><input type="text" name="ctasst_theme_name" value="<?php echo $_POST['ctasst_theme_name']; ?>" size="20"> (required)</td>  
        </tr><tr>
          <th>Theme URI:</th>
          <td><input type="text" name="ctasst_theme_uri" value="<?php echo $_POST['ctasst_theme_uri']; ?>" size="20"></td>  
        </tr><tr>
          <th>Description:</th>
          <td><input type="text" name="ctasst_description" value="<?php echo $_POST['ctasst_description']; ?>" size="50"></td>  
        </tr><tr>
          <th>Author:</th>
          <td><input type="text" name="ctasst_author" value="<?php echo $_POST['ctasst_author']; ?>" size="20"></td> 
        </tr><tr>         
          <th>Author URI:</th>
          <td><input type="text" name="ctasst_author_uri" value="<?php echo $_POST['ctasst_author_uri']; ?>" size="20"></td>  
        </tr><tr>
          <th>Version:</th>
          <td><input type="text" name="ctasst_version" value="<?php echo $_POST['ctasst_version']; ?>" size="20"></td>
      </tr>
    </table>
    <p>Switch after creation?
      <input type="checkbox" name="ctasst_switch">
    </p>  
    <p class="submit">  
      <input type="submit" name="Submit" value="<?php _e('Create') ?>" />  
    </p>  
  </form>
</div>
<?php   
  }

   function ctasst_render_success_page($new_theme = '') {
 ?>
<div class="wrap ctasst_form">
  <div id="icon-themes" class="icon32">
    <br>
  </div>
  <h2>Child Theme Assistant</h2>
    <p class='message'><?php echo "New theme \"$new_theme\" created successfully"; ?></p>
    <p><a href="themes.php">Manage Themes</a></p>
</div>
<?php
   }