<?php
/**
 * Server-side rendering of the `core/rss` block.
 *
 * @package gutenberg
 */

/**
 * Renders the `core/rss` block on server.
 *
 * @param array $attributes The block attributes.
 *
 * @return string Returns the post content with received rss posts.
 */
function render_block_core_rss( $attributes ) {

	$url = $attributes['feedURL'];

	if ( empty( $url ) ) {
		return;
	}

	while ( stristr( $url, 'http' ) !== $url ) {
		$url = substr( $url, 1 );
	}

	// self-url destruction sequence.
	if ( in_array( untrailingslashit( $url ), array( site_url(), home_url() ) ) ) {
		return '<div class="components-placeholder"><div class="notice notice-error"><strong>' . __( 'Use `Latest Posts` for this domain', 'gutenberg' ) . '</strong></div></div>';
	}

	$rss = fetch_feed( $url );

	if ( is_wp_error( $rss ) ) {
		if ( is_admin() || current_user_can( 'manage_options' ) ) {
			return '<div class="components-placeholder"><div class="notice notice-error"><strong>' . __( 'RSS Error:', 'gutenberg' ) . '</strong> ' . $rss->get_error_message() . '</div></div>';
		}
		return;
	}

	if ( ! $rss->get_item_quantity() ) {
		$rss->__destruct();
		unset( $rss );
		return '<div class="components-placeholder"><div class="notice notice-error">' . __( 'An error has occurred, which probably means the feed is down. Try again later.', 'gutenberg' ) . '</div></div>';
	}

	$items = (int) $attributes['postsToShow'];
	if ( $items < 1 || 20 < $items ) {
		$items = 5;
	}

	$list_items = '';
	foreach ( $rss->get_items( 0, $items ) as $item ) {
		$link = $item->get_link();

		while ( stristr( $link, 'http' ) != $link ) {
			$link = substr( $link, 1 );
		}

		$title = esc_html( trim( strip_tags( $item->get_title() ) ) );
		if ( empty( $title ) ) {
			$title = __( '(Untitled)', 'gutenberg' );
		}

		$link = esc_url( strip_tags( $link ) );
		if ( $link ) {
			$title = "<a href='$link'>$title</a>";
		}

		$title = "<div class='wp-block-rss__item-title'>$title</div>";

		$date = '';
		if ( $attributes['displayDate'] ) {
			$date = $item->get_date( 'U' );

			if ( $date ) {
				$date = sprintf(
					'<time datetime="%1$s" class="wp-block-rss__item-post-date">%2$s</time>',
					date_i18n( get_option( 'c' ), $date ),
					date_i18n( get_option( 'date_format' ), $date )
				);
			}
		}

		$author = '';
		if ( $attributes['displayAuthor'] ) {
			$author = $item->get_author();
			if ( is_object( $author ) ) {
				$author = $author->get_name();
				$author = '<span class="wp-block-rss__item-author">' . __( 'By', 'gutenberg' ) . ' ' . esc_html( strip_tags( $author ) ) . '</span>';
			}
		}

		$excerpt = '';
		if ( $attributes['displayExcerpt'] ) {
			$excerpt = @html_entity_decode( $item->get_description(), ENT_QUOTES, get_option( 'blog_charset' ) );
			$excerpt = esc_attr( wp_trim_words( $excerpt, 55, ' [&hellip;]' ) );

			// Change existing [...] to [&hellip;].
			if ( '[...]' == substr( $excerpt, -5 ) ) {
				$excerpt = substr( $excerpt, 0, -5 ) . '[&hellip;]';
			}

			$excerpt = '<div class="wp-block-rss__item-excerpt">' . esc_html( $excerpt ) . '</div>';
		}

		$list_items .= "<li class='wp-block-rss__item'>{$title}{$date}{$author}{$excerpt}</li>";
	}

	$list_items_markup = "<ul class='wp-block-rss'>{$list_items}</ul>";
	$rss->__destruct();
	unset( $rss );
	return $list_items_markup;
}

/**
 * Registers the `core/rss` block on server.
 */
function register_block_core_rss() {
	register_block_type( 'core/rss', array(
		'attributes'      => array(
			'feedURL'        => array(
				'type' => 'string',
			),
			'postsToShow'    => array(
				'type'    => 'number',
				'default' => 5,
			),
			'displayExcerpt' => array(
				'type' => 'boolean',
			),
			'displayAuthor'  => array(
				'type' => 'boolean',
			),
			'displayDate'    => array(
				'type' => 'boolean',
			),
		),
		'render_callback' => 'render_block_core_rss',
	) );
}

add_action( 'init', 'register_block_core_rss' );