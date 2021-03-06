<?php
require_once get_template_directory() . '/includes/start.php';

/**
 * Append table name in $wpdb.
 *
 * @since 1.0.0
 */
function ap_append_table_names() {
	global $wpdb;

	$wpdb->ap_qameta      = $wpdb->prefix . 'ap_qameta';
	$wpdb->ap_votes       = $wpdb->prefix . 'ap_votes';
	$wpdb->ap_views       = $wpdb->prefix . 'ap_views';
	$wpdb->ap_reputations = $wpdb->prefix . 'ap_reputations';
}
ap_append_table_names(); // we should call this

/**
 * To retrieve AnsPress option.
 *
 * @param  string $key   Name of option to retrieve or keep it blank to get all options of AnsPress.
 * @param  string $value Enter value to update existing option.
 * @return string
 * @since  0.1
 */
function ap_opt( $key = false, $value = null ) {
	$settings = wp_cache_get( 'anspress_opt', 'ap' );

	if ( false === $settings ) {
		$settings = get_option( 'anspress_opt' );

		if ( ! $settings ) {
			$settings = array();
		}

		wp_cache_set( 'anspress_opt', $settings, 'ap' );
	}

	$settings = $settings + ap_default_options();

	if ( ! is_null( $value ) ) {

		$settings[ $key ] = $value;
		update_option( 'anspress_opt', $settings );

		// Clear cache if option updated.
		wp_cache_delete( 'anspress_opt', 'ap' );

		return;
	}

	if ( false === $key ) {
		return $settings;
	}

	if ( isset( $settings[ $key ] ) ) {
		return $settings[ $key ];
	}

	return null;
}

/**
 * Create base page for AnsPress.
 *
 * This function is called in plugin activation. This function checks if base page already exists,
 * if not then it create a new one and update the option.
 *
 * @see anspress_activate
 * @since 1.0.0
 */
function ap_create_base_page() {
	$opt = ap_opt();

	$pages = ap_main_pages();

	foreach ( $pages as $slug => $page ) {
		// Check if page already exists.
		$_post = get_page( ap_opt( $slug ) );

		if ( ! $_post || 'trash' === $_post->post_status ) {
			$args = wp_parse_args(
				$page, array(
					'post_type'      => 'page',
					'post_content'   => '[anspress]',
					'post_status'    => 'publish',
					'comment_status' => 'closed',
				)
			);

			if ( 'base_page' !== $slug ) {
				$args['post_parent'] = ap_opt( 'base_page' );
			}

			// Now create post.
			$new_page_id = wp_insert_post( $args );

			if ( $new_page_id ) {
				$page = get_page( $new_page_id );

				ap_opt( $slug, $page->ID );
				ap_opt( $slug . '_id', $page->post_name );
			}
		}
	} // End foreach().
}

/**
 * All pages required of AnsPress.
 *
 * @return array
 * @since 4.1.0
 */
function ap_main_pages() {
	$pages = array(
		'base_page'       => array(
			'label'      => __( 'Archives page', 'anspress-question-answer' ),
			'desc'       => __( 'Page used to display question archive (list). Sometimes this page is used for displaying other subpages of AnsPress.<br/>This page is also referred as <b>Base Page</b> in AnsPress documentations and support forum.', 'anspress-question-answer' ),
			'post_title' => __( 'Questions', 'anspress-question-answer' ),
			'post_name'  => 'questions',
		),
		'ask_page'        => array(
			'label'      => __( 'Ask page', 'anspress-question-answer' ),
			'desc'       => __( 'Page used to display ask form.', 'anspress-question-answer' ),
			'post_title' => __( 'Ask a question', 'anspress-question-answer' ),
			'post_name'  => 'ask',
		),
		'user_page'       => array(
			'label'      => __( 'User page', 'anspress-question-answer' ),
			'desc'       => __( 'Page used to display user profile.', 'anspress-question-answer' ),
			'post_title' => __( 'Profile', 'anspress-question-answer' ),
			'post_name'  => 'profile',
		),
		'categories_page' => array(
			'label'      => __( 'Categories page', 'anspress-question-answer' ),
			'desc'       => __( 'Page used to display question categories. NOTE: Categories addon must be enabled to render this page.', 'anspress-question-answer' ),
			'post_title' => __( 'Categories', 'anspress-question-answer' ),
			'post_name'  => 'categories',
		),
  );

	/**
	 * Hook for filtering main pages of AnsPress. Custom main pages
	 * can be registered using this hook.
	 *
	 * @param array $pages Array of pages.
	 * @since 4.1.5
	 */
	return apply_filters( 'ap_main_pages', $pages );
}

function ap_has_base_page() {
	$base_page = ap_opt( 'base_page' );
	
	if ( $base_page ) {
		return true;
	}
	return false;
}

/**
 * Return the total numbers of post.
 *
 * @param string         $post_type Post type.
 * @param boolean|string $ap_type ap_meta type.
 * @return array
 * @since  2.0.0
 * @TODO use new qameta table.
 */
function ap_total_posts_count( $post_type = 'question', $ap_type = false, $user_id = false ) {
	global $wpdb;

	if ( 'question' === $post_type ) {
		$type = "p.post_type = 'question'";
	} elseif ( 'answer' === $post_type ) {
		$type = "p.post_type = 'answer'";
	} else {
		$type = "(p.post_type = 'question' OR p.post_type = 'answer')";
	}

	$meta = '';
	$join = '';

	if ( 'unanswered' === $ap_type ) {
		$meta = 'AND qameta.answers = 0';
		$join = "INNER JOIN {$wpdb->ap_qameta} qameta ON p.ID = qameta.post_id";
	} elseif ( 'best_answer' === $ap_type ) {
		$meta = 'AND qameta.selected > 0';
		$join = "INNER JOIN {$wpdb->ap_qameta} qameta ON p.ID = qameta.post_id";
	}

	$where = "WHERE p.post_status NOT IN ('trash', 'draft', 'private') AND $type $meta";

	if ( false !== $user_id && (int) $user_id > 0 ) {
		$where .= ' AND p.post_author = ' . (int) $user_id;
	}

	$where     = apply_filters( 'ap_total_posts_count', $where );
	$query     = "SELECT count(*) as count, p.post_status FROM $wpdb->posts p $join $where GROUP BY p.post_status";
	$cache_key = md5( $query );
	$count     = wp_cache_get( $cache_key, 'counts' );

	if ( false !== $count ) {
		return $count;
	}

	$count = $wpdb->get_results( $query, ARRAY_A ); // @codingStandardsIgnoreLine
	$counts = array();

	foreach ( (array) get_post_stati() as $state ) {
		$counts[ $state ] = 0;
	}

	$counts['total'] = 0;


	if ( ! empty( $count ) ) {
		foreach ( $count as $row ) {
			$counts[ $row['post_status'] ] = (int) $row['count'];
			$counts['total']              += (int) $row['count'];
		}
	}

	wp_cache_set( $cache_key, (object) $counts, 'counts' );
	return (object) $counts;
}

/**
 * Echo anspress links.
 *
 * @param string|array $sub Sub page.
 * @since 2.1
 */
function ap_link_to( $sub ) {
	echo ap_get_link_to( $sub ); // xss ok.
}

/**
 * Return link to AnsPress pages.
 *
 * @param string|array $sub Sub pages/s.
 * @return string
 */
function ap_get_link_to( $sub ) {
	$url = false;

	if ( 'ask' === $sub ) {
		$url = get_permalink( ap_opt( 'ask_page' ) );
	}

	if ( false === $url ) {
		/**
		 * Define default AnsPress page slugs.
		 *
		 * @var array
		 */
		$default_pages = array(
			'question' => ap_opt( 'question_page_slug' ),
		);

		$default_pages = apply_filters( 'ap_default_page_slugs', $default_pages );

		if ( is_array( $sub ) && isset( $sub['ap_page'] ) && isset( $default_pages[ $sub['ap_page'] ] ) ) {
			$sub['ap_page'] = $default_pages[ $sub['ap_page'] ];
		} elseif ( ! is_array( $sub ) && ! empty( $sub ) && isset( $default_pages[ $sub ] ) ) {
			$sub = $default_pages[ $sub ];
		}

		$base = rtrim( ap_base_page_link(), '/' );
		$args = '';

		if ( get_option( 'permalink_structure' ) !== '' ) {
			if ( ! is_array( $sub ) && 'base' !== $sub ) {
				$args = $sub ? '/' . $sub : '';
			} elseif ( is_array( $sub ) ) {
				$args = '/';

				if ( ! empty( $sub ) ) {
					foreach ( (array) $sub as $s ) {
						$args .= $s . '/';
					}
				}
			}

			$args = rtrim( $args, '/' ) . '/';
		} else {
			if ( ! is_array( $sub ) ) {
				$args = $sub ? '&ap_page=' . $sub : '';
			} elseif ( is_array( $sub ) ) {
				$args = '';

				if ( ! empty( $sub ) ) {
					foreach ( $sub as $k => $s ) {
						$args .= '&' . $k . '=' . $s;
					}
				}
			}
		}

		$url = $base . $args;
	} // End if().

	/**
	 * Allows filtering anspress links.
	 *
	 * @param string       $url Generated url.
	 * @param string|array $sub AnsPress sub pages.
	 *
	 * @since unknown
	 */
	return apply_filters( 'ap_link_to', $url, $sub );
}

/**
 * Retrieve permalink to base page.
 *
 * @return  string URL to AnsPress base page
 * @since   2.0.0
 * @since   3.0.0 Return link to questions page if base page not selected.
 */
function ap_base_page_link() {
	if ( empty( ap_opt( 'base_page' ) ) ) {
		return home_url( '/questions/' );
	}
	return get_permalink( ap_opt( 'base_page' ) );
}

/**
 * Get slug of base page.
 *
 * @return string
 * @since  2.0.0
 * @since  3.0.0 Return `questions` if base page is not selected.
 * @since  4.1.6 Make sure always `questions` is returned if no base page is set.
 */
function ap_base_page_slug() {
	$slug = 'questions';

	if ( ! empty( ap_opt( 'base_page' ) ) ) {
		$base_page = get_post( ap_opt( 'base_page' ) );

		if ( $base_page ) {
			$slug = $base_page->post_name;

			if ( $base_page->post_parent > 0 ) {
				$parent_page = get_post( $base_page->post_parent );
				$slug        = $parent_page->post_name . '/' . $slug;
			}
		}
	}

	return apply_filters( 'ap_base_page_slug', $slug );
}

/**
 * Get current question ID in single question page.
 *
 * @return integer|false
 * @since unknown
 * @since 4.1.0 Remove `question_name` query var check. Get question ID from queried object.
 */
function get_question_id() {
	if ( is_question() && get_query_var( 'question_id' ) ) {
		return (int) get_query_var( 'question_id' );
	}

	if ( is_question() ) {
		return get_queried_object_id();
	}

	if ( get_query_var( 'edit_q' ) ) {
		return get_query_var( 'edit_q' );
	}

	return false;
}

/**
 * Pre fetch users and update cache.
 *
 * @param  array $ids User ids.
 * @since 4.0.0
 */
function ap_post_author_pre_fetch( $ids ) {
	$users = get_users(
		[
			'include' => $ids,
			'fields'  => array( 'ID', 'user_login', 'user_nicename', 'user_email', 'display_name' ),
		]
	);

	foreach ( (array) $users as $user ) {
		update_user_caches( $user );
	}

	update_meta_cache( 'user', $ids );
}

/**
 * Check if current page is question page.
 *
 * @return boolean
 * @since 0.0.1
 * @since 4.1.0 Also check and return true if singular question.
 */
function is_question() {
	if ( is_singular( 'question' ) ) {
		return true;
	}

	return false;
}

/**
 * Return or echo user display name.
 *
 * Get display name from comments if WP_Comment object is passed. Else
 * fetch name form user profile. If anonymous user then fetch name from
 * current question, answer or comment.
 *
 * @param  WP_Comment|array|integer $args {
 *      Arguments or `WP_Comment` or user ID.
 *
 *      @type integer $user_id User ID.
 *      @type boolean $html    Shall return just text name or name with html markup.
 *      @type boolean $echo    Return or echo.
 *      @type string  $anonymous_label A placeholder name for anonymous user if no name found in post or comment.
 * }
 *
 * @return string|void If `$echo` argument is tru then it will echo name.
 * @since 0.1
 * @since 4.1.2 Improved args and PHPDoc.
 */
function ap_user_display_name( $args = [] ) {
	global $post;

	$defaults = array(
		'user_id'         => get_the_author_meta( 'ID' ),
		'html'            => false,
		'echo'            => false,
	);

	// When only user id passed.
	if ( is_numeric( $args ) ) {
		$defaults['user_id'] = $args;
		$args                = $defaults;
	} else {
		$args = wp_parse_args( $args, $defaults );
	}

	extract( $args ); // @codingStandardsIgnoreLine

	$user = get_userdata( $user_id );

	 

	$return = ! $html ? $user->display_name : '<a href="' . ap_user_link( $user_id ) . '" itemprop="url"><span itemprop="name">' . $user->display_name . '</span></a>';

	/**
	 * Filter AnsPress user display name.
	 *
	 * Filter can be used to alter user display name or
	 * appending some extra information of user, like: rank, reputation etc.
	 * Make sure to return plain text when `$args['html']` is true.
	 *
	 * @param string $return Name of user to return.
	 * @param array  $args   Arguments.
	 *
	 * @since 2.0.1
	 */
	$return = apply_filters( 'ap_user_display_name', $return, $args );

	if ( ! $args['echo'] ) {
		return $return;
	}

	echo $return; // xss okay.
}

function ap_user_display_meta( $args = [] ) {
	global $post;

	$defaults = array(
		'user_id'         => get_the_author_meta( 'ID' ),
	);

	// When only user id passed.
	if ( is_numeric( $args ) ) {
		$defaults['user_id'] = $args;
		$args                = $defaults;
	} else {
		$args = wp_parse_args( $args, $defaults );
	}

	$return = '';
	$return = apply_filters( 'ap_user_display_meta', $return, $args );
	echo $return;
}

/**
 * Verify the __nonce field.
 *
 * @param string $action Action.
 * @return bool
 * @since  2.4
 */
function ap_verify_nonce( $action ) {
	return wp_verify_nonce( ap_sanitize_unslash( '__nonce', 'p' ), $action );
}

/**
 * Sanitize and unslash string or array or post/get value at the same time.
 *
 * @param  string|array   $str    String or array to sanitize. Or post/get key name.
 * @param  boolean|string $from   Get value from `$_REQUEST` or `query_var`. Valid values: request, query_var.
 * @param  mixed          $default   Default value if variable not found.
 * @return array|string
 * @since  3.0.0
 */
function ap_sanitize_unslash( $str, $from = false, $default = '' ) {
	// If not false then get from $_REQUEST or query_var.
	if ( false !== $from ) {
		if ( in_array( strtolower( $from ), [ 'request', 'post', 'get', 'p', 'g', 'r' ], true ) ) {
			$str = ap_isset_post_value( $str, $default );
		} elseif ( 'query_var' === $from ) {
			$str = get_query_var( $str );
		}
	}

	// Return default if empty.
	if ( empty( $str ) ) {
		return $default;
	}

	if ( is_array( $str ) ) {
		$str = wp_unslash( $str );
		return array_map( 'sanitize_text_field', $str );
	}

	return sanitize_text_field( wp_unslash( $str ) );
}

/**
 * Check if $_REQUEST var exists and get value. If not return default.
 *
 * @param  string $var     Variable name.
 * @param  mixed  $default Default value.
 * @return mixed
 * @since  3.0.0
 */
function ap_isset_post_value( $var, $default = '' ) {
	if ( isset( $_REQUEST[ $var ] ) ) { // input var okay.
		return wp_unslash( $_REQUEST[ $var ] ); // input var okay, xss ok, sanitization ok.
	}

	return $default;
}

/**
 * Sort array by order value. Group array which have same order number and then sort them.
 *
 * @param array $array Array to order.
 * @return array
 * @since 2.0.0
 * @since 4.1.0 Use `WP_List_Util` class for sorting.
 */
function ap_sort_array_by_order( $array ) {
	$new_array = [];

	if ( ! empty( $array ) && is_array( $array ) ) {
		$i = 1;
		foreach ( $array as $k => $a ) {
			if ( is_array( $a ) ) {
				$array[ $k ]['order'] = isset( $a['order'] ) ? $a['order'] : $i;
			}

			$i += 2;
		}

		$util = new WP_List_Util( $array );
		return $util->sort( 'order', 'ASC', true );
	}
}

/**
 * Convert array notation (string, not real array) to dot notation.
 *
 * @param boolean|string $path Path name.
 * @return string Path separated by dot notation.
 */
function ap_to_dot_notation( $path = false ) {
	$parsed = rtrim( str_replace( '..', '.', str_replace( [ ']', '[' ], '.', $path ) ), '.' );
	return $parsed;
}

/**
 * Send properly formatted AnsPress json string.
 *
 * @param  array|string $response Response array or string.
 */
function ap_ajax_json( $response ) {
	ap_send_json( ap_ajax_responce( $response ) );
}

/**
 * Send a array as a JSON.
 *
 * @param array $result Results.
 */
function ap_send_json( $result = array() ) {
	header( 'Content-Type: text/html; charset=' . get_option( 'blog_charset' ) );
	$result['is_ap_ajax'] = true;
	$json                 = '<div id="ap-response">' . wp_json_encode( $result, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP ) . '</div>';

	wp_die( $json ); // xss ok.
}

/**
 * Format an array as valid AnsPress ajax response.
 *
 * @param  array|string $results Response to send.
 * @return array
 * @since  unknown
 * @since  4.1.0 Removed `template` variable. Send `snackbar` for default message.
 */
function ap_ajax_responce( $results ) {
	if ( ! is_array( $results ) ) {
		$message_id         = $results;
		$results            = array();
		$results['message'] = $message_id;
	}

	$results['ap_responce'] = true;

	if ( isset( $results['message'] ) ) {
		$error_message = ap_responce_message( $results['message'] );

		if ( false !== $error_message ) {
			$results['snackbar'] = array(
				'message'      => $error_message['message'],
				'message_type' => $error_message['type'],
			);

			$results['success'] = 'error' === $error_message['type'] ? false : true;
		}
	}

	/**
	 * Filter AnsPress ajax response body.
	 *
	 * @param array $results Results.
	 * @since 2.0.1
	 */
	$results = apply_filters( 'ap_ajax_responce', $results );

	return $results;
}

/**
 * Check if user answered on a question.
 *
 * @param integer $question_id  Question ID.
 * @param integer $user_id      User ID.
 * @return boolean
 *
 * @since unknown
 * @since 4.1.6 Changed cache group to `counts`.
 * @todo clear cache after answer.
 */
function ap_is_user_answered( $question_id, $user_id ) {
	global $wpdb;
	$count = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $wpdb->posts where post_parent = %d AND ( post_author = %d AND post_type = 'answer') AND ( post_status = 'publish' )", $question_id, $user_id ) ); // db call ok.
	return $count > 0 ? true : false;
}

/**
 * Truncate string but preserve full word.
 *
 * @param string $text String.
 * @param int    $limit Limit string to.
 * @param string $ellipsis Ellipsis.
 * @return string
 *
 * @since 4.1.8 Strip tags.
 */
function ap_truncate_chars( $text, $limit = 40, $ellipsis = '...' ) {
	$text = strip_tags( $text );
	$text = str_replace( array( "\r\n", "\r", "\n", "\t" ), ' ', $text );
	if ( strlen( $text ) > $limit ) {
		$endpos = strpos( $text, ' ', (string) $limit );

		if ( false !== $endpos ) {
			$text = trim( substr( $text, 0, $endpos ) ) . $ellipsis;
		}
	}
	return $text;
}

/**
 * Remove white space from string.
 *
 * @param string $contents String.
 * @return string
 */
function ap_trim_traling_space( $contents ) {
	$contents = preg_replace( '#(^(&nbsp;|\s)+|(&nbsp;|\s)+$)#', '', $contents );
	return $contents;
}

/**
 * Search array by key and value.
 *
 * @param  array  $array Array to search.
 * @param  string $key   Array key to search.
 * @param  mixed  $value Value of key supplied.
 * @return array
 * @since  4.0.0
 */
function ap_search_array( $array, $key, $value ) {
	$results = array();

	if ( is_array( $array ) ) {
		if ( isset( $array[ $key ] ) && $array[ $key ] == $value ) {
			$results[] = $array;
		}

		foreach ( $array as $subarray ) {
			$results = array_merge( $results, ap_search_array( $subarray, $key, $value ) );
		}
	}

	return $results;
}

/**
 * Return question title with solved prefix if answer is accepted.
 *
 * @param boolean|integer $question_id Question ID.
 * @return string
 *
 * @since   2.3 @see `ap_page_title`
 */
function ap_question_title_with_solved_prefix( $question_id = false ) {
	if ( false === $question_id ) {
		$question_id = get_question_id();
	}

	$solved = ap_have_answer_selected( $question_id );

	if ( ap_opt( 'show_solved_prefix' ) ) {
		return get_the_title( $question_id ) . ' ' . ( $solved ? __( '[Solved] ', 'anspress-question-answer' ) : '' );
	}

	return get_the_title( $question_id );
}

/**
 * Return edit link for question and answer.
 *
 * @param mixed $_post Post.
 * @return string
 * @since 2.0.1
 */
function ap_post_edit_link( $_post ) {
	$_post     = ap_get_post( $_post );
	$nonce     = wp_create_nonce( 'edit-post-' . $_post->ID );
	$base_page = 'question' === $_post->post_type ? ap_get_link_to( 'ask' ) : ap_get_link_to( 'edit' );
	$edit_link = add_query_arg(
		array(
			'id'      => $_post->ID,
			'__nonce' => $nonce,
		), $base_page
	);

	/**
	 * Allows filtering post edit link.
	 *
	 * @param string $edit_link Url to edit post.
	 * @since unknown
	 */
	return apply_filters( 'ap_post_edit_link', $edit_link );
}

/**
 * Return post status based on AnsPress options.
 *
 * @param  boolean|integer $user_id    ID of user creating question.
 * @param  string          $post_type  Post type, question or answer.
 * @param  boolean         $edit       Is editing post.
 * @return string
 * @since  3.0.0
 */
function ap_new_edit_post_status( $user_id = false, $post_type = 'question', $edit = false ) {
	if ( false === $user_id ) {
		$user_id = get_current_user_id();
	}

	$new_edit   = $edit ? 'edit' : 'new';
	$option_key = $new_edit . '_' . $post_type . '_status';
	$status     = 'publish';

	return $status;
}

/**
 * Send ajax response after posting an answer.
 *
 * @param integer|object $question_id Question ID or object.
 * @param integer|object $answer_id   Answer ID or object.
 * @return void
 * @since 4.0.0
 * @since 4.1.0 Moved from includes\answer-form.php.
 */
function ap_answer_post_ajax_response( $question_id, $answer_id ) {
	$question = ap_get_post( $question_id );
	// Get existing answer count.
	$current_ans = ap_count_published_answers( $question_id );

	global $post;
	$post = ap_get_post( $answer_id );
	setup_postdata( $post );

	ob_start();
	global $withcomments;
	$withcomments = true;

	ap_template_part( 'answer' );

	$html        = ob_get_clean();
	$count_label = sprintf( _n( '%d Answer', '%d Answers', $current_ans, 'anspress-question-answer' ), $current_ans );

	$result = array(
		'success'      => true,
		'ID'           => $answer_id,
		'form'         => 'answer',
		'div_id'       => '#post-' . get_the_ID(),
		'can_answer'   => ap_user_can_answer( $post->ID ),
		'html'         => $html,
		'snackbar'     => [ 'message' => __( 'Answer submitted successfully', 'anspress-question-answer' ) ],
		'answersCount' => [
			'text'   => $count_label,
			'number' => $current_ans,
		],
	);

	ap_ajax_json( $result );
}

/**
 * Return Link to user pages.
 *
 * @param  boolean|integer $user_id    user id.
 * @param  string|array    $sub        page slug.
 * @return string
 * @since  unknown
 * @since  4.1.1 Profile link not linking to BuddyPress when active.
 * @since  4.1.2 User user nicename in url as author_name query var gets user by nicename.
 */
function ap_user_link( $user_id = false, $sub = false ) {
	$link = '';

	if ( false === $user_id ) {
		$user_id = get_the_author_meta( 'ID' );
	}

	if ( empty( $user_id ) && is_author() ) {
		$user_id = get_queried_object_id();
	}

	$user = get_user_by( 'id', $user_id );
	if ( $user ) {
		$slug = get_option( 'ap_user_path' );
		$link = home_url( $slug ) . '/' . $user->user_nicename . '/';

		// Append sub.
		if ( ! empty( $sub ) ) {
			if ( is_array( $sub ) ) {
				$link = rtrim( $link, '/' ) . implode( '/', $sub ) . '/';
			} else {
				$link = $link . rtrim( $sub, '/' ) . '/';
			}
		}
		return apply_filters( 'ap_user_link', $link, $user_id, $sub );
	}
	return '';
}

/**
 * Return link to answers.
 *
 * @param  boolean|integer $question_id Question ID.
 * @return string
 */
function ap_answers_link( $question_id = false ) {
	if ( ! $question_id ) {
		return get_permalink() . '#answers';
	}
	return get_permalink( $question_id ) . '#answers';
}

/**
 * Convert number to 1K, 1M etc.
 *
 * @param  integer $num       Number to convert.
 * @param  integer $precision Precision.
 * @return string
 */
function ap_short_num( $num, $precision = 2 ) {
	if ( $num >= 1000 && $num < 1000000 ) {
		$n_format = number_format( $num / 1000, $precision ) . 'K';
	} elseif ( $num >= 1000000 && $num < 1000000000 ) {
		$n_format = number_format( $num / 1000000, $precision ) . 'M';
	} elseif ( $num >= 1000000000 ) {
		$n_format = number_format( $num / 1000000000, $precision ) . 'B';
	} else {
		$n_format = $num;
	}

	return $n_format;
}

/**
 * Insert a value or key/value pair after a specific key in an array.  If key doesn't exist, value is appended
 * to the end of the array.
 *
 * @param array  $array
 * @param string $key
 * @param array  $new
 *
 * @return array
 */
function ap_array_insert_after( $array = [], $key, $new ) {
	$keys  = array_keys( $array );
	$index = array_search( $key, $keys );
	$pos   = false === $index ? count( $array ) : $index + 1;

	return array_merge( array_slice( $array, 0, $pos ), $new, array_slice( $array, $pos ) );
}

/**
 * Check if current page is AnsPress. Also check if showing question or
 * answer page in BuddyPress.
 *
 * @return boolean
 * @since 4.1.0 Improved check. Check for main pages.
 * @since 4.1.1 Check for @see ap_current_page().
 * @since 4.1.8 Added filter `is_anspress`.
 */
function is_anspress() {
	$ret = false;
	
	$page_slug      = array_keys( ap_main_pages() );
	$queried_object = get_queried_object();

	// Check if main pages.
	if ( $queried_object instanceof WP_Post ) {
		$page_ids = [];
		foreach ( $page_slug as $slug ) {
			$page_ids[] = ap_opt( $slug );
		}

		if ( in_array( $queried_object->ID, $page_ids ) ) {
			$ret = true;
		}
	}

	// Check if ap_page.
	if ( is_search() && 'question' === get_query_var( 'post_type' ) ) {
		$ret = true;
	} elseif ( '' !== ap_current_page() ) {
		$ret = true;
	}

	/**
	 * Filter for overriding is_anspress() return value.
	 *
	 * @param boolean $ret True or false.
	 * @since 4.1.8
	 */
	return apply_filters( 'is_anspress', $ret );
}

/**
 * Sanitize comma delimited strings.
 *
 * @param  string|array $str Comma delimited string.
 * @param  string       $pieces_type Type of piece, string or number.
 * @return string
 */
function sanitize_comma_delimited( $str, $pieces_type = 'int' ) {
	$str = ! is_array( $str ) ? explode( ',', $str ) : $str;

	if ( ! empty( $str ) ) {
		$str       = wp_unslash( $str );
		$glue      = 'int' !== $pieces_type ? '","' : ',';
		$sanitized = [];
		foreach ( $str as $s ) {
			if ( '0' == $s || ! empty( $s ) ) {
				$sanitized[] = 'int' === $pieces_type ? intval( $s ) : str_replace( [ "'", '"', ',' ], '', sanitize_text_field( $s ) );
			}
		}

		$new_str = implode( $glue, esc_sql( $sanitized ) );

		if ( 'int' !== $pieces_type ) {
			return '"' . $new_str . '"';
		}

		return $new_str;
	}
}

/**
 * Output new/edit question form.
 *
 * @param null $deprecated Deprecated argument.
 * @return void
 *
 * @since unknown
 * @since 4.1.0 Moved from includes\ask-form.php. Deprecated first argument. Using new form class.
 * @since 4.1.5 Don't use ap_ajax as action. Set values here while editing. Get values form session if exists.
 *
 * @category haveTests
 */
function ap_ask_form( $deprecated = null ) {

	if ( ! is_null( $deprecated ) ) {
		_deprecated_argument( __FUNCTION__, '4.1.0', 'Use $_GET[id] for currently editing question ID.' );
	}

	$editing    = false;
	$editing_id = ap_sanitize_unslash( 'id', 'r' );

	// If post_id is empty then its not editing.
	if ( ! empty( $editing_id ) ) {
		$editing = true;
	}

	if ( $editing && ! ap_user_can_edit_question( $editing_id ) ) {
		echo '<p>' . esc_attr__( 'You cannot edit this question.', 'anspress-question-answer' ) . '</p>';
		return;
	}

	if ( ! $editing && ! ap_user_can_ask() ) {
		echo '<p>' . esc_attr__( 'You do not have permission to ask a question.', 'anspress-question-answer' ) . '</p>';
		return;
	}

	$args = array(
		'hidden_fields' => array(
			array(
				'name'  => 'action',
				'value' => 'ap_form_question',
			),
		),
	);

	$values         = [];
	// $session_values = anspress()->session->get( 'form_question' );
	$session_values = false;
	
	// Add value when editing post.
	if ( $editing ) {
		$question = ap_get_post( $editing_id );

		$form['editing']      = true;
		$form['editing_id']   = $editing_id;
		$form['submit_label'] = __( 'Update Question', 'anspress-question-answer' );

		$values['post_title']   = $question->post_title;
		$values['post_content'] = $question->post_content;
		$values['is_private']   = 'private_post' === $question->post_status ? true : false;

		if ( isset( $values['anonymous_name'] ) ) {
			$fields = ap_get_post_field( 'fields', $question );

			$values['anonymous_name'] = ! empty( $fields['anonymous_name'] ) ? $fields['anonymous_name'] : '';
		}
	} elseif ( ! empty( $session_values ) ) {
		// Set last session values if not editing.
		$values = $session_values;
	}

	// Generate form.
	anspress()->get_form( 'question' )->set_values( $values )->generate( $args );
}

/**
 * Return response with type and message.
 *
 * @param string $id           messge id.
 * @param bool   $only_message return message string instead of array.
 * @return string
 * @since 2.0.0
 */
function ap_responce_message( $id, $only_message = false ) {
	 
	$msg = array(
		'success'                       => array(
			'type'    => 'success',
			'message' => __( 'Success', 'anspress-question-answer' ),
		),
		'something_wrong'               => array(
			'type'    => 'error',
			'message' => __( 'Something went wrong, last action failed.', 'anspress-question-answer' ),
		),
		'comment_edit_success'          => array(
			'type'    => 'success',
			'message' => __( 'Comment updated successfully.', 'anspress-question-answer' ),
		),
		'cannot_vote_own_post'          => array(
			'type'    => 'warning',
			'message' => __( 'You cannot vote on your own question or answer.', 'anspress-question-answer' ),
		),
		'cannot_vote_twice_in_question' => array(
			'type' 		=> 'error',
			'message' => '한질문 안에서 추천은 한번만 가능합니다'
		),
		'no_permission_to_view_private' => array(
			'type'    => 'warning',
			'message' => __( 'You do not have permission to view private posts.', 'anspress-question-answer' ),
		),
		'captcha_error'                 => array(
			'type'    => 'error',
			'message' => __( 'Please check captcha field and resubmit it again.', 'anspress-question-answer' ),
		),
		'post_image_uploaded'           => array(
			'type'    => 'success',
			'message' => __( 'Image uploaded successfully', 'anspress-question-answer' ),
		),
		'answer_deleted_permanently'    => array(
			'type'    => 'success',
			'message' => __( 'Answer has been deleted permanently', 'anspress-question-answer' ),
		),
		'upload_limit_crossed'          => array(
			'type'    => 'warning',
			'message' => __( 'You have already attached maximum numbers of allowed uploads.', 'anspress-question-answer' ),
		),
		'profile_updated_successfully'  => array(
			'type'    => 'success',
			'message' => __( 'Your profile has been updated successfully.', 'anspress-question-answer' ),
		),
		'voting_down_disabled'          => array(
			'type'    => 'warning',
			'message' => __( 'Voting down is disabled.', 'anspress-question-answer' ),
		),
		'you_cannot_vote_on_restricted' => array(
			'type'    => 'warning',
			'message' => __( 'You cannot vote on restricted posts', 'anspress-question-answer' ),
		),
		'you_cannot_edit_question'	=>	array(
			'type'		=> 'warning',
			'message' => __( 'You cannot edit question', 'anspress-question-answer' ),
		),
	);

	/**
	 * Filter ajax response message.
	 *
	 * @param array $msg Messages.
	 * @since 2.0.1
	 */
	$msg = apply_filters( 'ap_responce_message', $msg );

	if ( isset( $msg[ $id ] ) && $only_message ) {
		return $msg[ $id ]['message'];
	}

	if ( isset( $msg[ $id ] ) ) {
		return $msg[ $id ];
	}

	return false;
}

/**
 * Allow HTML tags.
 *
 * @return array
 * @since 0.9
 */
function ap_form_allowed_tags() {
	global $ap_kses_check;
	$ap_kses_check = true;

	$allowed_style = array(
		'align' => true,
	);

	$allowed_tags = array(
		'p'          => array(
			'style' => $allowed_style,
			'title' => true,
		),
		'span'       => array(
			'style' => $allowed_style,
		),
		'a'          => array(
			'href'  => true,
			'title' => true,
		),
		'br'         => array(),
		'em'         => array(),
		'strong'     => array(
			'style' => $allowed_style,
		),
		'pre'        => array(),
		'code'       => array(),
		'blockquote' => array(),
		'img'        => array(
			'src'   => true,
			'style' => $allowed_style,
		),
		'ul'         => array(),
		'ol'         => array(),
		'li'         => array(),
		'del'        => array(),
	);

	/**
	 * Filter allowed HTML KSES tags.
	 *
	 * @param array $allowed_tags Allowed tags.
	 */
	return apply_filters( 'ap_allowed_tags', $allowed_tags );
}

/**
 * Return human readable time format.
 *
 * @param  string         $time Time.
 * @param  boolean        $unix Is $time is unix.
 * @param  integer        $show_full_date Show full date after some period. Default is 3 days in epoch.
 * @param  boolean|string $format Date format.
 * @return string|null
 * @since  2.4.7 Checks if showing default date format is enabled.
 */
function ap_human_time( $time, $unix = true, $show_full_date = 259200, $format = false ) {

	if ( false === $format ) {
		$format = get_option( 'date_format' );
	}

	if ( ! is_numeric( $time ) && ! $unix ) {
		$time = strtotime( $time );
	}

	// If default date format is enabled then just return date.
	if ( ap_opt( 'default_date_format' ) ) {
		return date_i18n( $format, $time );
	}

	if ( $time ) {
		if ( $show_full_date + $time > current_time( 'timestamp' ) ) {
			return sprintf(
				/* translators: %s: human-readable time difference */
				__( '%s ago', 'anspress-question-answer' ),
				human_time_diff( $time, current_time( 'timestamp' ) )
			);
		}

		return date_i18n( $format, $time );
	}
}

/**
 * Return post IDs of main pages.
 *
 * @return array
 * @since 4.1.0
 */
function ap_main_pages_id() {
	$main_pages = array_keys( ap_main_pages() );
	$pages_id   = [];

	foreach ( $main_pages as $slug ) {
		$pages_id[ $slug ] = ap_opt( $slug );
	}

	return $pages_id;
}

/**
 * Generate answer form.
 *
 * @param  mixed   $question_id  Question iD.
 * @param  boolean $editing      true if post is being edited.
 * @return void
 * @since unknown
 * @since 4.1.0 Moved from includes\answer-form.php. Using new Form class.
 * @since 4.1.5 Don't use ap_ajax as action.
 * @since 4.1.6 Fixed: editing answer creates new answer.
 */
function ap_answer_form( $question_id, $editing = false ) {
	$editing    = false;
	$editing_id = ap_sanitize_unslash( 'id', 'r' );

	// If post_id is empty then its not editing.
	if ( ! empty( $editing_id ) ) {
		$editing = true;
	}

	if ( $editing && ! ap_user_can_edit_answer( $editing_id ) ) {
		echo '<p>' . esc_attr__( 'You cannot edit this answer.', 'anspress-question-answer' ) . '</p>';
		return;
	}

	if ( ! $editing ) {
		$thing = ap_user_can_answer( $question_id, false, true );
		if ( is_wp_error( $thing ) ) {
			ap_template_part( 'message', null, array(
				'type' => 'error',
				'header' => '잠시만요!',
				'body' => $thing->get_error_message(),
			) );
			return;
		}
		if ( ! $thing ) {
			echo '<p>' . esc_attr__( 'You do not have permission to answer this question.', 'anspress-question-answer' ) . '</p>';
			return;
		}
	}

	$args = array(
		'hidden_fields' => array(
			array(
				'name'  => 'action',
				'value' => 'ap_form_answer',
			),
			array(
				'name'  => 'question_id',
				'value' => (int) $question_id,
			),
		),
	);

	$values         = [];
	// $session_values = anspress()->session->get( 'form_answer_' . $question_id );
	$session_values = false;

	// Add value when editing post.
	if ( $editing ) {
		$answer = ap_get_post( $editing_id );

		$form['editing']      = true;
		$form['editing_id']   = $editing_id;
		$form['submit_label'] = __( 'Update Answer', 'anspress-question-answer' );

		$values['post_title']   = $answer->post_title;
		$values['post_content'] = $answer->post_content;

		$args['hidden_fields'][] = array(
			'name'  => 'post_id',
			'value' => (int) $editing_id,
		);

	} elseif ( ! empty( $session_values ) ) {
		// Set last session values if not editing.
		$values = $session_values;
	}

	anspress()->get_form( 'answer' )->set_values( $values )->generate( $args );
}

/**
 * Check if post object is AnsPress CPT i.e. question or answer.
 *
 * @param WP_Post $_post WordPress post object.
 * @return boolean
 * @since 4.1.2
 */
function ap_is_cpt( $_post ) {
	return ( in_array( $_post->post_type, [ 'answer', 'question' ], true ) );
}

/**
 * Include tinymce assets.
 *
 * @return void
 * @since 4.1.0
 */
function ap_ajax_tinymce_assets() {
	if ( ! class_exists( '_WP_Editors' ) ) {
		require ABSPATH . WPINC . '/class-wp-editor.php';
	}

	\_WP_Editors::enqueue_scripts();

	ob_start();
	print_footer_scripts();
	$scripts = ob_get_clean();

	echo str_replace( 'jquery-core,jquery-migrate,', '', $scripts ); // xss okay.
	\_WP_Editors::editor_js();
}

/**
 * Get current user id for AnsPress profile.
 *
 * This function must be used only in AnsPress profile. This function checks for
 * user ID in queried object, hence if not in user page
 *
 * @return integer Always returns 0 if not in AnsPress profile page.
 * @since 4.1.1
 */
function ap_current_user_id() {
	if ( 'user' === ap_current_page() ) {
		$query_object = get_queried_object();
		if ( $query_object instanceof WP_User ) {
			return $query_object->ID;
		}
	}

	return 0;
}

/**
 * Activity type to human readable title.
 *
 * @param  string $type Activity type.
 * @return string
 */
function ap_activity_short_title( $type ) {
	$title = array(
		'new_question'           => __( 'asked', 'anspress-question-answer' ),
		'approved_question'      => __( 'approved', 'anspress-question-answer' ),
		'approved_answer'        => __( 'approved', 'anspress-question-answer' ),
		'new_answer'             => __( 'answered', 'anspress-question-answer' ),
		'delete_answer'          => __( 'deleted answer', 'anspress-question-answer' ),
		'restore_question'       => __( 'restored question', 'anspress-question-answer' ),
		'restore_answer'         => __( 'restored answer', 'anspress-question-answer' ),
		'new_comment'            => __( 'commented', 'anspress-question-answer' ),
		'delete_comment'         => __( 'deleted comment', 'anspress-question-answer' ),
		'new_comment_answer'     => __( 'commented on answer', 'anspress-question-answer' ),
		'edit_question'          => __( 'edited question', 'anspress-question-answer' ),
		'edit_answer'            => __( 'edited answer', 'anspress-question-answer' ),
		'edit_comment'           => __( 'edited comment', 'anspress-question-answer' ),
		'edit_comment_answer'    => __( 'edited comment on answer', 'anspress-question-answer' ),
		'answer_selected'        => __( 'selected answer', 'anspress-question-answer' ),
		'answer_unselected'      => __( 'unselected answer', 'anspress-question-answer' ),
		'status_updated'         => __( 'updated status', 'anspress-question-answer' ),
		'best_answer'            => __( 'selected as best answer', 'anspress-question-answer' ),
		'unselected_best_answer' => __( 'unselected as best answer', 'anspress-question-answer' ),
		'changed_status'         => __( 'changed status', 'anspress-question-answer' ),
	);

	$title = apply_filters( 'ap_activity_short_title', $title );

	if ( isset( $title[ $type ] ) ) {
		return $title[ $type ];
	}

	return $type;
}

/**
 * Return short link to a item.
 *
 * @param array $args Arguments.
 * @return string Shortlink to a AnsPress page or item.
 *
 * @category haveTest
 *
 * @since unknown
 * @since 4.1.6 Fixed: trailing slash.
 */
function ap_get_short_link( $args ) {
	array_unshift( $args, [ 'ap_page' => 'shortlink' ] );
	return add_query_arg( $args, home_url( '/' ) );
}

/**
 * Return category link for search filter of base page
 *
 * @return void
 */
function ap_question_category_link( $term_id ) {
	$base_page = ap_base_page_slug();
	return home_url( $base_page ) . '/?ap_category[]=' . $term_id;
}

function ap_get_questions_page_url() {
  $base_page_slug = ap_base_page_slug();
  return  esc_url( home_url( $base_page_slug ) . '/' );
}

/**
 * Check wheather this page is related with login( login, registration, lost password )
 *
 * @return boolean
 */
function ap_is_login_related_page() {
  global $wp;
	$related = false;
	
  if ( $wp->request == 'register' || $wp->request == 'login' || $wp->request == 'lostpassword' || ( ap_is_user_page() && is_user_logged_in() ) ) {
    $related = true;
  }
  return $related;
}

function ap_is_admin_edit_or_new_question_page( $screen = false ) {
	if ( ! $screen ) {
		$screen = get_current_screen();
	}
	if ( $screen->post_type == 'question' && ( $screen->action == 'add' || ( isset( $_GET['action'] ) && $_GET['action'] == 'edit' ) ) ) {
		return true;
	}
	return false;
}

function is_edit_page($new_edit = null){
	global $pagenow;
	//make sure we are on the backend
	if (!is_admin()) return false;

	if($new_edit == "edit")
			return in_array( $pagenow, array( 'post.php',  ) );
	elseif($new_edit == "new") //check for new post page
			return in_array( $pagenow, array( 'post-new.php' ) );
	else //check for either new or edit
			return in_array( $pagenow, array( 'post.php', 'post-new.php' ) );
}

function ap_get_term_family( $term_id, $taxonomy ) {
	$term_family = get_term_children( $term_id, $taxonomy );
	$term_family[] = $term_id;
	$terms = implode( ',', $term_family );

	return $terms;
}