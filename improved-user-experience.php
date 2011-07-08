<?php
/**
 * Plugin Name: Improved User Experience
 * Plugin URI: http://xavisys.com/wordpress-plugins/improved-user-experience/
 * Description: Better lost password handling as well as control over what contact information in the user profiles
 * Version: 1.1.2
 * Author: Aaron D. Campbell
 * Author URI: http://xavisys.com/
 * Text Domain: improved-user-experience
 */

require_once('xavisys-plugin-framework.php');
class improvedUserExperience extends XavisysPlugin {
	/**
	 * @var efficientRelatedPosts - Static property to hold our singleton instance
	 */
	static $instance = false;

	protected function _init() {
		$this->_hook = 'improvedUserExperience';
		$this->_file = plugin_basename( __FILE__ );
		$this->_pageTitle = __( 'Improved User Experience', $this->_slug );
		$this->_menuTitle = __( 'Improved User Experience', $this->_slug );
		$this->_accessLevel = 'manage_options';
		$this->_optionGroup = 'iue-options';
		$this->_optionNames = array('iue');
		$this->_optionCallbacks = array();
		$this->_slug = 'improved-user-experience';
		$this->_paypalButtonId = '10147595';

		/**
		 * Add filters and actions
		 */
		add_filter( $this->_slug .'-opt-iue', array( $this, 'filterSettings' ) );
	}

	protected function _postSettingsInit() {
		if ( $this->_settings['iue']['email_login'] == 'yes' ) {
			add_action('wp_authenticate', array( $this, 'allow_email_login' ), 0, 2);
		}

		if ( $this->_settings['iue']['shortened_pw_reset'] == 'yes' ) {
			add_action('password_reset', array( $this, 'password_reset' ), null, 2);
			add_action('login_form_resetpass', array( $this, 'password_reset' ), null, 2);
		}

		if ( !empty($_GET['message']) && $_GET['message'] == 'resetpass' ) {
			add_action('iue-password-message', array($this, 'warning'));
			if ( defined('IS_PROFILE_PAGE') && IS_PROFILE_PAGE ) {
				add_action('admin_notices', array($this, 'warning'));
			}
		}
	}

	/**
	 * Function to instantiate our class and make it a singleton
	 */
	public static function getInstance() {
		if ( !self::$instance ) {
			self::$instance = new self;
		}
		return self::$instance;
	}

	public function addOptionsMetaBoxes() {
		add_meta_box( $this->_slug . '-general-settings', __('General Settings', $this->_slug), array($this, 'generalSettingsMetaBox'), 'xavisys-' . $this->_slug, 'main');
	}

	/**
	 * This is used to display the options page for this plugin
	 */
	public function generalSettingsMetaBox() {
?>
				<table class="form-table">
					<tr valign="top">
						<th scope="row">
							<?php _e("E-Mail Login:", $this->_slug); ?>
						</th>
						<td>
							<input name="iue[email_login]" id="iue_email_login_no" type="radio" value="no"<?php checked('no', $this->_settings['iue']['email_login']) ?>>
							<label for="iue_email_login_no">
								<?php _e("Require username to login (normal WordPress functionality)", $this->_slug);?>
							</label>
							<br />
							<input name="iue[email_login]" id="iue_email_login_yes" type="radio" value="yes"<?php checked('yes', $this->_settings['iue']['email_login']) ?>>
							<label for="iue_email_login_yes">
								<?php _e("Allow users to login using E-Mail address or username", $this->_slug);?>
							</label>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row">
							<?php _e("Lost Password:", $this->_slug); ?>
						</th>
						<td>
							<input name="iue[shortened_pw_reset]" id="iue_shortened_pw_reset_no" type="radio" value="no"<?php checked('no', $this->_settings['iue']['shortened_pw_reset']) ?>>
							<label for="iue_shortened_pw_reset_no">
								<?php _e("Use the WordPress lost password procedure.", $this->_slug);?>
							</label>
							<br />
							<input name="iue[shortened_pw_reset]" id="iue_shortened_pw_reset_yes" type="radio" value="yes"<?php checked('yes', $this->_settings['iue']['shortened_pw_reset']) ?>>
							<label for="iue_shortened_pw_reset_yes">
								<?php _e("Use the short lost password procedure.", $this->_slug);?>
							</label>
							<br />
							<br />
							<span class="setting-description"><?php _e("The WordPress lost password process requires the user to request a password reset, check their E-Mail, follow a link in the E-Mail, check their E-Mail again to get their new password, login with that password, and change their password to something they want.  The short method requires the user to request a password reset, check their E-Mail, follow a link in the E-Mail which logs them in and takes them to their profile where they can change their password.", 'efficient_related_posts'); ?></span>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row">
							<label for="iue_change_pw_page"><?php _e("Change Password Page:", $this->_slug); ?></label>
						</th>
						<td>
							<input id="iue_change_pw_page" name="iue[change_pw_page]" type="text" class="regular-text code" value="<?php echo attribute_escape($this->_settings['iue']['change_pw_page']); ?>" size="50" /><br />
							<span class="setting-description"><?php _e("If you have a custom page for users to change their password, enter it here and the user will be directed there instead of the default profile page.", $this->_slug); ?></span>
						</td>
					</tr>
				</table>
<?php
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
		if ( is_email($user) ) {
			$usernameQuery = $wpdb->prepare("SELECT `user_login` FROM `{$wpdb->users}` WHERE user_email = %s", $user);
			$username = $wpdb->get_var($usernameQuery);
			if (!empty($username)) {
				$user = $username;
			}
		}
		return;
	}

	public function password_reset( $user = null, $new_pass = null ) {
		if ( empty( $user) )
			$user = check_password_reset_key($_GET['key'], $_GET['login']);

		wp_set_auth_cookie($user->ID);
		if ( stristr($this->_settings['iue']['change_pw_page'], 'message=resetpass') === false ) {
			$this->_settings['iue']['change_pw_page'] .= ( strpos($this->_settings['iue']['change_pw_page'], '?') === false )? '?':'&';
			$this->_settings['iue']['change_pw_page'] .= 'message=resetpass';
		}
		wp_safe_redirect($this->_settings['iue']['change_pw_page']);
		exit;
	}

    /**
     * Echo a warning into the admin section, if needed
     */
    public function warning() {
        echo "<div class='updated'><p><strong>"
            .__('Please update your password.', $this->_slug)
            ."</strong></p></div>";
    }

	public function filterSettings($settings) {
		$defaults = array(
			'email_login'			=> 'yes',
			'shortened_pw_reset'	=> 'yes',
			'change_pw_page'		=> 'wp-admin/profile.php',
		);
		$settings = wp_parse_args($settings, $defaults);

		return $settings;
	}
}

// Instantiate our class
$improvedUserExperience = improvedUserExperience::getInstance();
