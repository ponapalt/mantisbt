<?php
# MantisBT - A PHP based bugtracking system

# MantisBT is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 2 of the License, or
# (at your option) any later version.
#
# MantisBT is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with MantisBT.  If not, see <http://www.gnu.org/licenses/>.

require_api( 'authentication_api.php' );
require_api( 'api_token_api.php' );

use Mantis\Exceptions\ClientException;

require_once( dirname( __FILE__ ) . '/../../api/soap/mc_api.php' );
require_once( dirname( __FILE__ ) . '/../../api/soap/mc_account_api.php' );

/**
 * A command that creates a user API token.
 */
class UserTokenDeleteCommand extends Command {
	/**
	 * @var integer The user id.
	 */
	private $user_id;

	/**
	 * @var string The token name.
	 */
	private $name;

	/**
	 * Constructor
	 *
	 * @param array $p_data The command data.
	 */
	function __construct( array $p_data ) {
		parent::__construct( $p_data );
	}

	/**
	 * Validate the data.
	 */
	function validate() {
		// acting user id
		$t_current_user_id = auth_get_current_user_id();

		// user is to create token for
		$this->user_id = (int)$this->query( 'user_id' );

		// any user can create a token for themselves, but only an admin can create for other users.
		if( $t_current_user_id != $this->user_id ) {
			// if not administrator, throw a client exception
			if( !access_has_global_level( config_get_global( 'manage_user_threshold' ) ) ) {
				throw new ClientException(
					"User can't create tokens for other users",
					ERROR_ACCESS_DENIED
				);
			}
		}

		// ensure target user is not protected
		if( user_is_protected( $this->user_id ) ) {
			throw new ClientException(
				"User is protected",
				ERROR_PROTECTED_ACCOUNT
			);
		}

		$t_token_id = $this->query( 'id' );
		$t_row = api_token_get( $t_token_id );

		if( $t_row === false ) {
			throw new ClientException(
				"Token doesn't exist",
				ERROR_USER_TOKEN_NOT_FOUND
			);
		}

		// Target user doesn't own token with specified id.
		if( (int)$t_row['user_id'] != $this->user_id ) {
			throw new ClientException(
				"User can't revoke token",
				ERROR_ACCESS_DENIED
			);
		}
	}

	/**
	 * Process the command.
	 * 
	 * @return void
	 */
	function process() {
		$t_token_id = $this->query( 'id' );
		api_token_revoke( $t_token_id, $this->user_id );
	}
}
