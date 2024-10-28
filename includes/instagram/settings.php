<?php //delete_option('atsf_access_token'); ?>

<div class="atsf-main atsf-instagram"><?php
	$user = self::get_user();

	if( !self::get_access_token() || !$user ) {
		?><div class="atsf-screen atsf-auth-screen"><?php

			?><div class="atsf-sign-in-wrap">
				<a class="atsf-sign-in" href="<?php echo esc_url($auth_url); ?>"><?php
					_e('<strong>Connect</strong> to Instagram', 'atsf');
				?>
					<span class="stripe"></span>
					<span class="stripe"></span>
					<span class="stripe"></span>
					<span class="stripe"></span>
				</a>
			</div><?php
				if( $error_type === 'user_denied' ) {
					?><div class="atsf-error atsf-popup"><?php
						_e('Hmm, it seems you\'ve changed your min? It\'s OK you can do it again later ;)', 'atsf');
					?></div><?php
				} elseif( $error_type ) {
					?><div class="atsf-error atsf-popup"><?php
						_e('Oh dear! Something went wrong, please try again in a bit.', 'atsf');
					?></div><?php
				} elseif ( !$error_type && !$access_token ) {
					?><div class="atsf-message"><?php
						_e('Sign in, authorize the plugin and you are done. It\'s that easy!', 'atsf');
					?></div><?php
				}
		?></div><?php
	} else {

		?><div class="atsf-screen atsf-settings-screen">

			<div class="atsf-avatar">
				<img src="<?php echo esc_url($user->profile_picture); ?>" alt="<?php _e('Avatar', 'atsf'); ?>" />
			</div>

			<div class="atsf-name"><?php
				printf( __('Hey, %s!', 'atsf'), $user->full_name ? esc_html($user->full_name) : $user->username );
			?></div>

			<div class="atsf-subtitle"><?php
				printf( _n(
						'You\'ve got just one post :(',
						'You\'ve got %s posts. Awesome!',
						intval($user->counts->media), 'atsf'
					), intval($user->counts->media)
				);
			?></div>

			
			<span class="atsf-help"><?php
				echo apply_filters( 'atsf_help_message', __('Now all you need to do is copy the shortcode below and paste it in places where you want to display your Instagram feed.', 'atsf'), $user );
			?></span><?php

			/*
			//no shortcode message - 'Yay, you are all set! Aww, it seems your theme doesn\'t integrate with this plugin, it just can\'t show your feed :( Sorry, there is no shortcode for this yet.'
			*/

			$shortcode = '[atsf_instagram count="' . rand(1, intval($user->counts->media)) . '"]';

			?><div class="atsf-shortcode-preview-wrap">
				<input class="atsf-shortcode-preview"
					type="text" value='<?php echo $shortcode; ?>' 
					onclick="this.focus();this.select()"
					readonly
				/>
			</div><?php


			/*$media = self::get_media( 3 ); 

			if( $media ) {
				?><div class="atsf-sample-media">

					<em><?php 
						$media_count = count($media);
						printf( _n(
							'Here\'s the only one post from your feed.', 
							'Here are the latest %s posts from your feed.', 
							$media_count, 
							'atsf'
						), $media_count ); 

					?></em>

					<ul class="atsf-sample-feed"><?php
						foreach ($media as $post) {
							?><li data-id="<?php echo esc_attr($post->id); ?>">
								<a target="_blank"
									style="background-image: url(<?php echo esc_url($post->images->thumbnail->url); ?>);" 
									href="<?php echo esc_url($post->link); ?>"
								></a>
							</li><?php
						}
					?></ul>

				</div><?php
			} else {
				?><div class="atsf-sample-media">
					<div class="atsf-error atsf-popup"><?php
						_e('Oh dear! Something went wrong, couldn\'t get the feed. <br> Try clearing the cache or reconnect with Instagram.', 'atsf');
					?></div>
				</div><?php
			} // end if media*/


			$clear_message = __('Clear the cache only when you have new posts and the plugin has not yet retrived them from Instagram. Do not use this too often or Instagram might block you for making to many requests.', 'atsf');

			$logout_message = __('This is going to disconnect you from Instagram. You will need to re-authenticate again. If you want to change users, make sure to log out from Instagram too.', 'atsf');


			?><form class="atsf-cache" method="post" action="<?php echo esc_url($admin_url); ?>"> 
				<button type="submit" class="button" name="clear_cache" value="instagram"
					onclick='return confirm("<?php echo $clear_message; ?>")'><?php _e('Clear the cache', 'atsf'); ?></button>

				<button type="submit" class="button" name="logout" value="instagram"
					onclick='return confirm("<?php echo $logout_message; ?>")'><?php _e('Disconnect', 'atsf'); ?></button>
			</form>

		</div><?php
	}
?></div>