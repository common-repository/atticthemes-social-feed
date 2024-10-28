<?php
if( !class_exists('AtticThemes_Instagram') ) {

	class AtticThemes_Instagram extends AtticThemes_SocialFeed {


		/**
		 * turn loggin on/off
		 */
		const DEBUG = false;


		/**
		 * Authorization URL
		 */
		const AUTHURL = 'https://api.instagram.com/oauth/authorize/?response_type=code';

		/**
		 * Clinet ID "client_id"
		 */
		const CLINET_ID = 'ca45060555ab426399be4d98e98daf9a';

		/**
		 * Redirect URI (set in the app/client) "redirect_uri"
		 */
		const REDIRECT_URI = 'http://atticthemes.com/red/instagram';

		/**
		 * Nonce
		 */
		const NONCE = '__instagram_auth__';



		

		/**
		 * access token option name
		 */
		const ACCESS_TOKEN_NAME = 'atsf_instagram_access_token';


		/**
		 * transiant name
		 */
		const MEDIA_DATA_TRANSIENT_NAME = 'atsf_instagram_media';

		/**
		 * user data option name
		 */
		const USER_DATA_TRANSIENT_NAME = 'atsf_instagram_user';

		/**
		 * user data option name
		 */
		const CACHE_EXPIRE_TIME_NAME = 'atsf_instagram_cache_expire_time';


		/**
		 * User data
		 */
		public static $user;

		/**
		 * Media data
		 */
		public static $media;


		/**
		 * access tocken
		 */
		public static $access_token;


		/**
		 * Get user info
		 */
		public static function get_user() {

			/**
			 * get the user data from memory
			 */
			if( !self::$user ) {
				/**
				 * try to get the data from transient storage
				 */
				self::$user = get_transient( self::USER_DATA_TRANSIENT_NAME );

				
				/**
				 * If the user was found in transient storage just return that
				 */
				if( self::$user ) {
					self::log('getting user data from storage');
					return self::$user;
				} else {
					/**
					 * if couldn't, then request new user data from API and update it in transient storage
					 */
					$user_data = self::request_user_data();

					//self::log($user_data);

					if( $user_data ) {
						self::$user = new stdClass;
						self::$user->full_name = $user_data->full_name;
						self::$user->username = $user_data->username;
						self::$user->bio = $user_data->bio;
						self::$user->profile_picture = $user_data->profile_picture;

						self::$user->counts = new stdClass;
						self::$user->counts->media = $user_data->counts->media;
						self::$user->counts->following = $user_data->counts->follows;
						self::$user->counts->followed = $user_data->counts->followed_by;

						if( self::$user ) {
							set_transient( self::USER_DATA_TRANSIENT_NAME, self::$user, self::get_cache_expire_time() );
							return self::$user;
						}

						self::log('got user data from API');
					} else {
						// FAILED
						self::log('Could\'t get user data from the API.');
					}
					
					return self::$user;
				}
			} else {
				/**
				 * return the user data if the data was found in the memory
				 */
				self::log('getting user data from memory');

				return self::$user;
			}
			
		}


		/**
		 * Request user info
		 */
		public static function request_user_data() {
			self::log('* requesting user data');

			$response = parent::request( array(
					'method' => 'GET',
					'url' => add_query_arg('access_token', self::get_access_token(), 
						sprintf('https://api.instagram.com/v1%s', apply_filters('atsf_instagram_api_user_endpoint', '/users/self/' ) )
					)
				)
			);

			return self::parse_response($response);
		}


		/**
		 * Get user media
		 */
		public static function get_media( $count = 1 ) {
			/**
			 * Get user info
			 */
			$user = self::get_user();
			$max = $user ? $user->counts->media : 0;

			/**
			 * If the user has no posts just return false
			 */
			if( $max < 1 ) {
				self::log('the user has 0 posts');
				return false;
			}

			/**
			 * If user requested more then they have
			 */
			if( $count > $max ) {
				/**
				 * cap the $count to the maximum number of avaiable posts
				 */
				$count = $max;
				self::log('the user doesn\'t have as many posts as requested, will get whatever they have.');
				//return false;
			}


			/**
			 * if the media data isn't available from memory
			 */
			if( !self::$media || count(self::$media) < $count ) {

				/**
				 * try to get the data from transient storage
				 */
				self::$media = get_transient( self::MEDIA_DATA_TRANSIENT_NAME );

				
				/**
				 * If the media was found in transient storage just return that
				 */
				if( self::$media ) {

					self::log('getting media data from storage');

					/**
					 * Check if we have more media then requested
					 * If true, slice the requested amount and return
					 */
					if( count(self::$media) > $count ) {
						self::log('got more then requested - slicing');
						return array_slice( self::$media, 0, $count );
					}

					/**
					 * Check if we have less media then requested
					 * If true, request more from instagram by removing the cached media
					 * and making a call to this function
					 */
					if( count(self::$media) < $count ) {

						self::$media = null;
						delete_transient( self::MEDIA_DATA_TRANSIENT_NAME );

						/**
						 * As a precaution - not to end up in a infinit loop of recurtion -
						 * we want to delete the user data as well.
						 * The issue can happen when the user delets post(s) but still tries to fatch more then they have, 
						 * but the old/cached user data says they have more and the recursion continues infinitely
						 */
						self::$user = null;
						delete_transient( self::USER_DATA_TRANSIENT_NAME );


						self::log('don\'t have enaugh media stored - requesting more');
						self::get_media( $count );
					}

					return self::$media;
				} else {
					/**
					 * if couldn't, then request new media data from API and update it in transient storage
					 */
					$media_data = self::request_media_data( $count );

					if( $media_data ) {

						$feed = array();

						foreach ($media_data as $media) {

							$post = new stdClass;
							$post->id = $media->id;
							$post->date = $media->created_time;
							$post->type = $media->type;
							$post->link = $media->link;
							$post->caption = isset($media->caption) ? wp_encode_emoji( $media->caption->text ) : '';

							$post->images = new stdClass;
							$post->images->thumbnail = $media->images->thumbnail;
							$post->images->medium = $media->images->low_resolution;
							$post->images->large = $media->images->standard_resolution;

							if( isset($media->videos) ) {
								$post->videos = new stdClass;
								$post->videos->medium = $media->videos->low_resolution;
								$post->videos->large = $media->videos->standard_resolution;
							}

							$feed[] = $post;
						}

						self::$media = $feed;
						set_transient( self::MEDIA_DATA_TRANSIENT_NAME, self::$media, self::get_cache_expire_time() );

						self::log('got media data from API');

						return self::$media;
					} else {
						// FAILED
						self::log('Could\'t get media data from the API.');
					}
					
					return self::$media;
				}
			} else {
				/**
				 * return media data if the data was found in the memory
				 */
				self::log('got media data from memory');

				return self::$media;
			}

		}

		/**
		 * Request media data
		 */
		public static function request_media_data( $count = 1 ) {
			$user = self::get_user();

			self::log('* requesting media data');

			$response = parent::request( array(
					'method' => 'GET',
					'url' => add_query_arg( array(
						'access_token' => self::get_access_token(),
						'count' => min( $count, $user->counts->media ),
					), sprintf('https://api.instagram.com/v1%s', apply_filters('atsf_instagram_api_media_endpoint', '/users/self/media/recent/') )
					),
				)
			);

			//self::log(var_export(self::parse_response($response), true));

			return self::parse_response($response);
		}




		/**
		 * parse response body
		 */
		private static function parse_response( $response ) {
			$body = json_decode( $response['body'] );

			//self::log(var_export($data, true));

			if( $body && isset($body->meta) && $body->meta->code === 200 ) {
				return $body->data;
			} else {
				//return $body->meta;
			}

			return false;
		}

		/**
		 * Get cache expiration time
		 * default: 1 Hour
		 */
		public static function get_cache_expire_time() {
			return get_option( self::CACHE_EXPIRE_TIME_NAME, 1 ) * (60 * 60 * 24);
		}

		/**
		 * Get access tocken from storage or memory
		 */
		public static function get_access_token() {
			if( !self::$access_token ) {
				self::$access_token = get_option( self::ACCESS_TOKEN_NAME, false );
			}
			return self::$access_token;
		}

		/**
		 * Clear user and media data
		 */
		public static function clear_cache() {
			delete_transient( self::USER_DATA_TRANSIENT_NAME );
			delete_transient( self::MEDIA_DATA_TRANSIENT_NAME );
		}

		/**
		 * Disconnect the account and clear all related data
		 */
		public static function logout() {
			self::clear_cache();
			delete_option( self::ACCESS_TOKEN_NAME );
		}




		/**
		 * Handle the shortcode
		 */
		public static function shortcode( $attrs ) {
			$attrs = shortcode_atts( array(
				'count' => 3,
			), $attrs );

			$user = self::get_user();
			$media = self::get_media( $attrs['count'] );

			$output = '';

			if( $media && $user ) {
				$output .= '<ul class="atsf-instagram-feed">';

				foreach ($media as $post) {
					$output .= '<li>';
					$output .= '<a href="'. esc_url($post->link) .'" target="_blank">';
					$output .= '<img src="'. esc_url($post->images->thumbnail->url) .'" alt="" />';
					$output .= '</a>';
					$output .= '</li>';
				}

				$output .= '</ul>';
			}

			return apply_filters( 'atsf_instagram', $output, $media, $user );
		}





		public static function add_admin_menu() {
			add_options_page( '', __('Instagram', 'atsf'), 'manage_options', 'atsf-instagram', array(__CLASS__, 'render_admin') );
		}

		public static function render_admin() {
			self::log('-----------------');

			$admin_url = get_admin_url(null, 'options-general.php?page=atsf-instagram');

			$relay_data = new stdClass;
			$relay_data->relay_uri = esc_url($admin_url);
			$relay_data->nonce = wp_create_nonce( self::NONCE );

			$auth_url = add_query_arg( array(
				'client_id' => self::CLINET_ID,
				'redirect_uri' => add_query_arg( array(
						'relay' => base64_encode( json_encode($relay_data) ),
					), self::REDIRECT_URI ),
			), self::AUTHURL );




			$service = isset($_GET['service']) ? $_GET['service'] : false;
			$nonce = isset($_GET['nonce']) ? wp_verify_nonce($_GET['nonce'], self::NONCE) : false;
			$status = isset($_GET['status']) ? intval($_GET['status']) : false;
			$access_token = isset($_GET['access_token']) ? $_GET['access_token'] : false;

			$error_type = isset($_GET['error_type']) ? $_GET['error_type'] : false;

			if( $service === 'instagram' ) {

				if( $status === 200 && $nonce && $access_token ) {
					update_option( self::ACCESS_TOKEN_NAME, $access_token );
				} elseif( $status !== 200 && $nonce && $error_type === 'OAuthAccessTokenError' ) {
					self::logout();
				}
			}

			$clear_cache = isset($_POST['clear_cache']) ? $_POST['clear_cache'] : false;
			$logout = isset($_POST['logout']) ? $_POST['logout'] : false;

			if( $clear_cache === 'instagram' ) {
				self::clear_cache();
			}

			if( $logout === 'instagram' ) {
				self::logout();
			}

			self::log('-----------------');

			require_once( plugin_dir_path( parent::FILE ) . 'includes/instagram/settings.php' );
		}


		public static function register_widget() {
			register_widget( 'AtticThemes_Instagram_Widget' );
		}


		/**
		 * On plugin activation
		 */
		public static function activate() {
			update_option( self::CACHE_EXPIRE_TIME_NAME, 1 );
		}



		private static function log( $message ) {
			if( !self::DEBUG ) return;

			error_log(var_export($message, true));
		}

	} //end class

	/**
	 * Add admin menu
	 */
	add_action( 'admin_menu', array('AtticThemes_Instagram', 'add_admin_menu') );


	/**
	 * Add the sortcode
	 */
	add_shortcode( 'atsf_instagram', array('AtticThemes_Instagram', 'shortcode') );


	/**
	 * On plugin activation
	 */
	register_activation_hook( AtticThemes_SocialFeed::FILE, array('AtticThemes_Instagram', 'activate') );


	/**
	 * Add the widget class and register
	 */
	require_once( plugin_dir_path( AtticThemes_SocialFeed::FILE ) . 'includes/instagram/widget.php' );
	add_action( 'widgets_init', array('AtticThemes_Instagram', 'register_widget') );
}