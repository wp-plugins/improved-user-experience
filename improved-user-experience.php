<?php
/**
 * Plugin Name: Improved User Experience
 * Plugin URI: http://xavisys.com/2009/10/improved-user-experience-wordpress-plugin/
 * Description: Better lost password handling as well as control over what contact information in the user profiles
 * Version: 1.0.0
 * Author: Aaron D. Campbell
 * Author URI: http://xavisys.com/
 * Text Domain: improved-user-experience
 */

class improvedUserExperience
{
	/**
	 * @var array Plugin settings
	 */
	private $_settings;

	public function __construct() {
		$this->_getSettings();

		/**
		 * Add filters and actions
		 */
		add_filter( 'init', array( $this, 'init_locale') );
		add_action( 'admin_menu', array( $this, 'admin_menu'));
		add_action( 'admin_init', array( $this, 'registerOptions' ) );
		add_filter( 'plugin_action_links', array( $this, 'addSettingLink' ), null, 2 );

		if ( $this->_settings['email_login'] == 'yes' ) {
			add_action('wp_authenticate', array($this, 'allow_email_login'), 0, 2);
		}

		if ( $this->_settings['shortened_pw_reset'] == 'yes' ) {
			add_action('password_reset', array($this, 'password_reset'), null, 2);
		}

		if ( !empty($_GET['message']) && $_GET['message'] == 'resetpass' && defined('IS_PROFILE_PAGE') && IS_PROFILE_PAGE ) {
			add_action('admin_notices', array($this, 'warning'));
		}
		add_action('iue-password-message', array($this, 'warning'));
	}

	public function init_locale() {
		$lang_dir = basename(dirname(__FILE__)) . '/languages';
		load_plugin_textdomain('improved-user-experience', 'wp-content/plugins/' . $lang_dir, $lang_dir);
	}

	/**
	 * Function to allow login with E-Mail address as well as username.
	 *
	 * @param string &user - Username
	 * @param string &pass - Password
	 *
	 * @return void
	 */
	public function allow_email_login($user, $pass) {
		global $wpdb;
		if (is_email($user)) {
			$usernameQuery = $wpdb->prepare("SELECT `user_login` FROM `{$wpdb->users}` WHERE user_email = %s", $user);
			$username = $wpdb->get_var($usernameQuery);
			if (!empty($username)) {
				$user = $username;
			}
		}
		return;
	}

	public function password_reset( $user, $new_pass ) {
		wp_set_auth_cookie($user->ID);
		if ( stristr($this->_settings['change_pw_page'], 'message=resetpass') === false ) {
			$this->_settings['change_pw_page'] .= ( strpos($this->_settings['change_pw_page'], '?') === false )? '?':'&';
			$this->_settings['change_pw_page'] .= 'message=resetpass';
		}
		wp_safe_redirect($this->_settings['change_pw_page']);
		exit;
	}

    /**
     * Echo a warning into the admin section, if needed
     */
    public function warning() {
        echo "<div class='updated'><p><strong>"
            .__('Please update your password.', 'improved-user-experience')
            ."</strong></p></div>";
    }

	/**
	 * This adds the options page for this plugin to the Options page
	 *
	 * @access public
	 */
	public function admin_menu() {
		// We make sure they can "create_users" which should mean they are an administrator.
		add_options_page(__('Improved User Experience', 'improved-user-experience'), __('User Experience', 'improved-user-experience'), 'create_users', 'improved-user-experience', array($this, 'options'));
	}

	public function registerOptions() {
		/**
		 * @todo Remove once all sites are 2.7+
		 */
		if ( function_exists('register_setting') ) {
			register_setting( 'iue-options', 'iue' );
		}
	}

	/**
	 * This is used to display the options page for this plugin
	 */
	public function options() {
?>
		<div class="wrap">
			<h2><?php _e('Improved User Experience', 'improved-user-experience'); ?></h2>
			<h3><?php _e('Settings', 'improved-user-experience'); ?></h3>
			<form action="options.php" method="post">
<?php
		/**
		 * @todo Use only settings_fields once all sites are 2.7+
		 */
		if ( function_exists('settings_fields') ) {
			settings_fields( 'iue-options' );
		} else {
			wp_nonce_field('update-options');
?>
			<input type="hidden" name="action" value="update" />
			<input type="hidden" name="page_options" value="iue" />
<?php
		}
?>
				<table class="form-table">
					<tr valign="top">
						<th scope="row">
							<?php _e("E-Mail Login:", 'improved-user-experience'); ?>
						</th>
						<td>
							<input name="iue[email_login]" id="iue_email_login_no" type="radio" value="no"<?php checked('no', $this->_settings['email_login']) ?>>
							<label for="iue_email_login_no">
								<?php _e("Require username to login (normal WordPress functionality)",'improved-user-experience');?>
							</label>
							<br />
							<input name="iue[email_login]" id="iue_email_login_yes" type="radio" value="yes"<?php checked('yes', $this->_settings['email_login']) ?>>
							<label for="iue_email_login_yes">
								<?php _e("Allow users to login using E-Mail address or username",'improved-user-experience');?>
							</label>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row">
							<?php _e("Lost Password:", 'improved-user-experience'); ?>
						</th>
						<td>
							<input name="iue[shortened_pw_reset]" id="iue_shortened_pw_reset_no" type="radio" value="no"<?php checked('no', $this->_settings['shortened_pw_reset']) ?>>
							<label for="iue_shortened_pw_reset_no">
								<?php _e("Use the WordPress lost password procedure.",'improved-user-experience');?>
							</label>
							<br />
							<input name="iue[shortened_pw_reset]" id="iue_shortened_pw_reset_yes" type="radio" value="yes"<?php checked('yes', $this->_settings['shortened_pw_reset']) ?>>
							<label for="iue_shortened_pw_reset_yes">
								<?php _e("Use the short lost password procedure.",'improved-user-experience');?>
							</label>
							<br />
							<br />
							<span class="setting-description"><?php _e("The WordPress lost password process requires the user to request a password reset, check their E-Mail, follow a link in the E-Mail, check their E-Mail again to get their new password, login with that password, and change their password to something they want.  The short method requires the user to request a password reset, check their E-Mail, follow a link in the E-Mail which logs them in and takes them to their profile where they can change their password.", 'efficient_related_posts'); ?></span>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row">
							<label for="iue_change_pw_page"><?php _e("Change Password Page:",'improved-user-experience'); ?></label>
						</th>
						<td>
							<input id="iue_change_pw_page" name="iue[change_pw_page]" type="text" class="regular-text code" value="<?php echo attribute_escape($this->_settings['change_pw_page']); ?>" size="50" /><br />
							<span class="setting-description"><?php _e("If you have a custom page for users to change their password, enter it here and the user will be directed there instead of the default profile page.", 'improved-user-experience'); ?></span>
						</td>
					</tr>
				</table>
				<p class="submit">
					<input type="submit" name="Submit" value="<?php _e('Update Options &raquo;', 'improved-user-experience'); ?>" />
				</p>
			</form>
		</div>
<?php
	}

	private function _getSettings() {
		$defaults = array(
			'email_login'			=> 'yes',
			'shortened_pw_reset'	=> 'yes',
			'change_pw_page'		=> 'wp-admin/profile.php',
		);
		$this->_settings = get_option('iue');
		$this->_settings = wp_parse_args($this->_settings, $defaults);

		/*
		$this->_settings['max_relations_stored'] = intval($this->_settings['max_relations_stored']);
		$this->_settings['num_to_display'] = intval($this->_settings['num_to_display']);
		*/
	}

	public function addSettingLink( $links, $file ){
		if ( $file == plugin_basename(__FILE__) ) {
			// Add settings link to our plugin
			$link = '<a href="options-general.php?page=improved-user-experience">' . __('Settings', 'improved-user-experience') . '</a>';
			array_unshift( $links, $link );
		}
		return $links;
	}
}

// Instantiate our class
$improvedUserExperience = new improvedUserExperience();

/**
 * For use with debugging
 */
if ( !function_exists('dump') ) {
	function dump($v, $title = '', $echo = true) {
		if (!empty($title)) {
			$title = '<h4>' . htmlentities($title) . '</h4>';
		}
		ob_start();
		var_dump($v);
		$v = ob_get_clean();
		$v = $title . '<pre>' . htmlentities($v) . '</pre>';
		if ( $echo ) {
			echo $v;
		} else {
			return $v;
		}
	}
}
