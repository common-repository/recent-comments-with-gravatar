<?php 
/*
Plugin Name: Recent Comments with Gravatar
Plugin URI: http://suhanto.net/recent-comments-gravatar-widget-wordpress/
Description: Display recent comments on the sidebar with commenter's gravatars for your blog. These recent comments with gravatar is displayed as widget that can be placed anywhere within your blog.
Author: Agus Suhanto
Version: 1.2
Author URI: http://suhanto.net/

Copyright 2010 Agus Suhanto (email : agus@suhanto.net)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

// wordpress plugin action hook
add_action('plugins_loaded', 'recent_comments_gravatar_init');

// initialization function
global $recent_comments_gravatar;
function recent_comments_gravatar_init() {
   $recent_comments_gravatar = new recent_comments_gravatar();
}

/*
 * This is the namespace for the 'recent_comments_gravatar' plugin / widget.
 */
class recent_comments_gravatar {

   protected $_name = "Recent Comments with Gravatar";
   protected $_folder;
   protected $_path;
   protected $_width = 350;
   protected $_height = 320;
   protected $_link = 'http://suhanto.net/recent-comments-gravatar-widget-wordpress/';
   
   /*
    * Constructor
    */
   function __construct() {
      $path = __FILE__;
      if (!$path) { $path = $_SERVER['PHP_SELF']; }
         $current_dir = dirname($path);
      $current_dir = str_replace('\\', '/', $current_dir);
      $current_dir = explode('/', $current_dir);
      $current_dir = end($current_dir);
      if (empty($current_dir) || !$current_dir)
         $current_dir = 'recent-comments-gravatar';
      $this->_folder = $current_dir;
      $this->_path = '/wp-content/plugins/' . $this->_folder . '/';

      $this->init();
   }
   
   /*
    * Initialization function, called by plugin_loaded action.
    */
   function init() {
      add_action('template_redirect', array(&$this, 'template_redirect'));
      add_filter("plugin_action_links_$plugin", array(&$this, 'link'));
      load_plugin_textdomain($this->_folder, false, $this->_folder);      
      
      if (!function_exists('register_sidebar_widget') || !function_exists('register_widget_control'))
         return;
      register_sidebar_widget($this->_name, array(&$this, "widget"));
      register_widget_control($this->_name, array(&$this, "control"), $this->_width, $this->_height);
   }
   
   /*
    * Inserts the style into the head section.
    */
   function template_redirect() {
      $options = get_option($this->_folder);
      $this->validate_options($options);
      
      if (!isset($options['use_style']) || $options['use_style'] != 'checked')
         wp_enqueue_style($this->_folder, $this->_path . 'style.css', null, '1.1');
   }
   
   /*
    * Options validation.
    */
   function validate_options(&$options) {
      if (!is_array($options)) {
         $options = array(
            'title' => 'Recent Comments',
            'num_of_comments' => '10', 
            'gravatar_width' => '46', 
            'author_emails' => '',      
            'show_in_post' => 'checked',
            'show_in_page' => 'checked',
            'use_style' => '',
            'link_to_us' => '');
      }
      
      // validations and defaults
      if (intval($options['num_of_comments']) == 0) $options['num_of_comments'] = '10';
   }
   
   /*
    * Get time diff between 2 times.
    */
   function get_time_diff($time) {

      $difference = time() - strtotime($time);
      
      $weeks = round($difference / 604800);  
      $difference = $difference % 604800;
      $days = round($difference / 86400);
      $difference = $difference % 86400;
      $hours = round($difference / 3600);
      $difference = $difference % 3600;
      $minutes = round($difference / 60);
      $difference = $difference % 60;
      $seconds = $difference;
      
      if ($weeks > 0)
         return $weeks . ' ' . __('weeks', $this->_folder);
      else if ($days > 0)
         return $days . ' ' . __('days', $this->_folder);
      else if ($hours > 0)
         return $hours . ' ' . __('hours', $this->_folder);
      else if ($minutes > 0)
         return $minutes . ' ' . __('minutes', $this->_folder);
      else if ($seconds > 0)
         return $seconds . ' ' . __('seconds', $this->_folder);
   }

   /*
    * Called by register_sidebar_widget() function.
    * Rendering of the widget happens here.
    */
   function widget($args) {     
      global $wpdb;
      
      extract($args);
            
      $options = get_option($this->_folder);
      $this->validate_options($options);
      
      if (is_single() && $options['show_in_post'] != 'checked') return;
      if (is_page() && $options['show_in_page'] != 'checked') return;
      
      $author_emails = array();
      if (!empty($options['author_emails'])) {
         $author_emails = explode(',', $options['author_emails']);
      }
            
      $sql = "SELECT a.*, b.post_title from $wpdb->comments a JOIN $wpdb->posts b ON a.comment_post_id = b.id WHERE comment_approved= '1' AND a.comment_type != 'pingback'
              ORDER BY comment_date DESC LIMIT " . $options['num_of_comments'];
      
      $comments = $wpdb->get_results($sql);
      
      echo $before_widget;
      echo $before_title;
      echo is_single() && ($options['single_mode'] == 'checked') ? $options['single_mode_title'] : $options['title'];
      echo $after_title;
      
      echo '<div class="rcg-div">';
      if ($comments) {
         echo '<ul>';
         foreach ($comments as $comment) {
            $author_has_url = !(empty($comment->comment_author_url) || 'http://' == $comment->comment_author_url);
            $is_author = in_array($comment->comment_author_email, $author_emails);
            $url_author = '<a href="' . $comment->comment_author_url . '" title="' . $comment->comment_author . '" rel="external nofollow" target="_blank">';
            $url = '<a href="'. get_permalink($comment->comment_post_ID).'#comment-'.$comment->comment_ID .'" title="'.$comment->comment_author .' | '.get_the_title($comment->comment_post_ID).'">';
            echo '<li ' . ($is_author ? 'class="rcg-author"' : '') . '><div class="rcg-wrapper">';
            echo '<div class="rcg-avatar">';
            echo $author_has_url ? $url_author : '<span title="' . $comment->comment_author . '">';
            echo get_avatar($comment->comment_author_email, intval($options['gravatar_width']));
            echo $author_has_url ? '</a>' : '</span>';
            echo '</div>';

            echo '<div class="rcg-text" style="padding-left:' . (intval($options['gravatar_width']) + 10) . 'px">';
            if ($author_has_url) echo $url_author;
            echo $comment->comment_author;
            if ($author_has_url) echo '</a>';
            echo ' ' . ($is_author ? __('answered on', $this->_folder) :  __('commented on', $this->_folder)) . ' ';
            echo $url;
            echo $comment->post_title;
            echo '</a><br/> ';
            echo ' (' . $this->get_time_diff($comment->comment_date_gmt) . ' ' . __('ago', $this->_folder) . ')';
            echo '</div>';
            echo '</div></li>';
         }
         echo '</ul>';
      }
      if ($options['link_to_us'] == 'checked') {
         echo '<div class="rcg-link"><a href="' . $this->_link . '" target="_blank">'. __('Get this widget for your own blog free!', $this->_folder) . '</a></div>';
      }
      echo '</div>';
      echo $after_widget;
   }
   
   /*
    * Plugin control funtion, used by admin screen.
    */
   function control() {
      $options = get_option($this->_folder);
      $this->validate_options($options);
   
      if ($_POST[$this->_folder . '-submit']) {
         $options['title'] = htmlspecialchars(stripslashes($_POST[$this->_folder . '-title']));
         $options['num_of_comments'] = htmlspecialchars(stripslashes($_POST[$this->_folder . '-num_of_comments']));
         $options['gravatar_width'] = htmlspecialchars($_POST[$this->_folder . '-gravatar_width']);
         $options['author_emails'] = htmlspecialchars(stripslashes($_POST[$this->_folder . '-author_emails']));
         $options['show_in_post'] = htmlspecialchars(stripslashes($_POST[$this->_folder . '-show_in_post']));
         $options['show_in_page'] = htmlspecialchars(stripslashes($_POST[$this->_folder . '-show_in_page']));
         $options['use_style'] = htmlspecialchars($_POST[$this->_folder . '-use_style']);
         $options['link_to_us'] = htmlspecialchars($_POST[$this->_folder . '-link_to_us']);
         update_option($this->_folder, $options);
      }
?>      
      <p>
         <label for="<?php echo($this->_folder) ?>-title"><?php _e('Title: ', $this->_folder); ?></label>
         <input type="text" id="<?php echo($this->_folder) ?>-title" name="<?php echo($this->_folder) ?>-title" value="<?php echo $options['title']; ?>" size="50"></input>
      </p>
      <p>
         <label for="<?php echo($this->_folder) ?>-num_of_comments"><?php _e('Num. of comments to display: ', $this->_folder); ?></label>
         <input type="text" id="<?php echo($this->_folder) ?>-num_of_comments" name="<?php echo($this->_folder) ?>-num_of_comments" value="<?php echo $options['num_of_comments']; ?>" size="2"></input> (<?php _e('default 10', $this->_folder) ?>) (<a href="<?php echo $this->_link?>#num-of-comments" target="_blank">?</a>)
      </p>
      <p>
         <label for="<?php echo($this->_folder) ?>-gravatar_width"><?php _e('Gravatar width: ', $this->_folder); ?></label>
         <input type="text" id="<?php echo($this->_folder) ?>-gravatar_width" name="<?php echo($this->_folder) ?>-gravatar_width" value="<?php echo $options['gravatar_width']; ?>" size="2"></input>px (<?php _e('default 46', $this->_folder) ?>) (<a href="<?php echo $this->_link?>#gravatar-width" target="_blank">?</a>) 
      </p>
      <p>
         <label for="<?php echo($this->_folder) ?>-author_emails"><?php _e('Author emails (comma separated): ', $this->_folder); ?></label> (<a href="<?php echo $this->_link?>#author-emails" target="_blank">?</a>)
         <input type="text" id="<?php echo($this->_folder) ?>-author_emails" name="<?php echo($this->_folder) ?>-author_emails" value="<?php echo $options['author_emails']; ?>" size="50"></input>
      </p>      
      <p>
          <input type="checkbox" id="<?php echo($this->_folder) ?>-show_in_post" name="<?php echo($this->_folder) ?>-show_in_post" value="checked" <?php echo $options['show_in_post'];?> /> <?php _e('Show in Post', $this->_folder) ?> (<a href="<?php echo $this->_link?>#show-in-post" target="_blank">?</a>)       
      </p>
      <p>
          <input type="checkbox" id="<?php echo($this->_folder) ?>-show_in_page" name="<?php echo($this->_folder) ?>-show_in_page" value="checked" <?php echo $options['show_in_page'];?> /> <?php _e('Show in Page', $this->_folder) ?> (<a href="<?php echo $this->_link?>#show-in-page" target="_blank">?</a>)       
      </p>
      <p>
          <input type="checkbox" id="<?php echo($this->_folder) ?>-use_style" name="<?php echo($this->_folder) ?>-use_style" value="checked" <?php echo $options['use_style'];?> /> <?php _e('Use custom style', $this->_folder) ?> (<a href="<?php echo $this->_link?>#custom-style" target="_blank">?</a>) 
      </p>
      <p>
          <input type="checkbox" id="<?php echo($this->_folder) ?>-link_to_us" name="<?php echo($this->_folder) ?>-link_to_us" value="checked" <?php echo $options['link_to_us'];?> /> <?php _e('Link to us (optional)', $this->_folder) ?> (<a href="<?php echo $this->_link?>#link-to-us" target="_blank">?</a>) 
      </p>
      <p><?php printf(__('More details about these options, visit <a href="%s" target="_blank">Plugin Home</a>', $this->_folder), $this->_link) ?></p>      
      <input type="hidden" id="<?php echo($this->_folder) ?>-submit" name="<?php echo($this->_folder) ?>-submit" value="1" />
<?php      
   }
   
   /*
    * Add extra link to widget list.
    */
   function link($links) {
      $options_link = '<a href="' . $this->_link . '">' . __('Donate', $this->_folder) . '</a>';
      array_unshift($links, $options_link);
      return $links;
   }
}
