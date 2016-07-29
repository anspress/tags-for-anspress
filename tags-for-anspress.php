<?php
/**
 * Tags extension for AnsPress
 *
 * AnsPress - Question and answer plugin for WordPress
 *
 * @package   Tags for AnsPress
 * @author    Rahul Aryan <support@anspress.io>
 * @license   GPL-2.0+
 * @link      http://anspress.io/tags-for-anspress
 * @copyright 2014 anspress.io & Rahul Aryan
 *
 * @wordpress-plugin
 * Plugin Name:       Tags for AnsPress
 * Plugin URI:        http://anspress.io/tags-for-anspress
 * Description:       Extension for AnsPress. Add tags in AnsPress.
 * Donate link: https://www.paypal.com/cgi-bin/webscr?business=rah12@live.com&cmd=_xclick&item_name=Donation%20to%20AnsPress%20development
 * Version:           3.0.1
 * Author:            Rahul Aryan
 * Author URI:        http://anspress.io
 * Text Domain:       tags-for-anspress
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

class Tags_For_AnsPress
{

	/**
	 * Class instance
	 * @var object
	 * @since 1.0
	 */
	private static $instance;


	/**
	 * Get active object instance
	 *
	 * @since 1.0
	 *
	 * @access public
	 * @static
	 * @return object
	 */
	public static function get_instance() {

		if ( ! self::$instance ) {
			self::$instance = new Categories_For_AnsPress(); }

		return self::$instance;
	}
	/**
	 * Initialize the class
	 * @since 2.0
	 */
	public function __construct() {

		if ( ! class_exists( 'AnsPress' ) ) {
			return; // AnsPress not installed.
		}

		$this->includes();

		ap_register_page( ap_get_tag_slug(), __( 'Tag', 'tags-for-anspress' ), array( $this, 'tag_page' ), false );
		ap_register_page( ap_get_tags_slug(), __( 'Tags', 'tags-for-anspress' ), array( $this, 'tags_page' ) );

		add_action( 'ap_option_groups', array( $this, 'option_fields' ), 20 );
		add_action( 'init', array( $this, 'textdomain' ) );
		add_action( 'widgets_init', array( $this, 'widget_positions' ) );

		add_action( 'init', array( $this, 'register_question_tag' ), 1 );
		add_action( 'ap_admin_menu', array( $this, 'admin_tags_menu' ) );
		add_action( 'ap_display_question_metas', array( $this, 'ap_display_question_metas' ), 10, 2 );
		add_action( 'ap_question_info', array( $this, 'ap_question_info' ) );
		add_action( 'ap_enqueue', array( $this, 'ap_enqueue' ) );
		add_action( 'ap_enqueue', array( $this, 'ap_localize_scripts' ) );
		add_filter( 'term_link', array( $this, 'term_link_filter' ), 10, 3 );
		add_action( 'ap_ask_form_fields', array( $this, 'ask_from_tag_field' ), 10, 2 );
		add_action( 'ap_ask_fields_validation', array( $this, 'ap_ask_fields_validation' ) );
		add_action( 'ap_processed_new_question', array( $this, 'after_new_question' ), 0, 2 );
		add_action( 'ap_processed_update_question', array( $this, 'after_new_question' ), 0, 2 );
		add_filter( 'ap_page_title', array( $this, 'page_title' ) );
		add_filter( 'ap_breadcrumbs', array( $this, 'ap_breadcrumbs' ) );
		add_action( 'ap_list_head', array( $this, 'ap_list_head' ) );
		add_filter( 'terms_clauses', array( $this, 'terms_clauses' ), 10, 3 );
		add_filter( 'get_terms', array( $this, 'get_terms' ), 10, 3 );
		add_action( 'ap_user_subscription_tab', array( $this, 'subscription_tab' ) );
		add_action( 'ap_user_subscription_page', array( $this, 'subscription_page' ) );
		add_action( 'wp_ajax_ap_tags_suggestion', array( $this, 'ap_tags_suggestion' ) );
	    add_action( 'wp_ajax_nopriv_ap_tags_suggestion', array( $this, 'ap_tags_suggestion' ) );
	    add_action( 'ap_rewrite_rules', array( $this, 'rewrite_rules' ), 10, 3 );
		add_filter( 'ap_default_pages', array( $this, 'tags_default_page' ) );
		add_filter( 'ap_default_page_slugs', array( $this, 'default_page_slugs' ) );
		add_filter( 'ap_subscribe_btn_type', array( $this, 'subscribe_type' ) );
		add_filter( 'ap_subscribe_btn_action_type', array( $this, 'subscribe_btn_action_type' ) );
		add_filter( 'ap_current_page_is', array( $this, 'ap_current_page_is' ) );
		add_filter( 'ap_list_filters', array( $this, 'ap_list_filters' ) );
		add_filter( 'ap_main_questions_args', array( __CLASS__, 'ap_main_questions_args' ) );
		add_action( 'ap_list_filter_search_tag', array( __CLASS__, 'filter_search_tag' ) );
		add_filter( 'ap_question_subscribers_action_id', array( __CLASS__, 'subscribers_action_id' ) );
	}

	/**
	 * Include required files
	 */
	public function includes() {
		if ( ! defined( 'TAGS_FOR_ANSPRESS_DIR' ) ) {
			define( 'TAGS_FOR_ANSPRESS_DIR', plugin_dir_path( __FILE__ ) );
		}

		if ( ! defined( 'TAGS_FOR_ANSPRESS_URL' ) ) {
			define( 'TAGS_FOR_ANSPRESS_URL', plugin_dir_url( __FILE__ ) );
		}
		require_once( TAGS_FOR_ANSPRESS_DIR . 'functions.php' );
	}

	/**
	 * Tag page layout.
	 */
	public function tag_page() {
		global $questions, $question_tag;
		$tag_id = sanitize_title( get_query_var( 'q_tag' ) );

		$question_args = array(
			'tax_query' => array(
				array(
					'taxonomy' => 'question_tag',
					'field' => 'slug',
					'terms' => array( $tag_id ),
				),
			),
		);

		$question_tag       = get_term_by('slug', $tag_id, 'question_tag' );

		if ( $question_tag ) {
			$questions = ap_get_questions( $question_args );
			include( ap_get_theme_location( 'tag.php', TAGS_FOR_ANSPRESS_DIR ) );
		} else {
			global $wp_query;
			$wp_query->set_404();
			status_header( 404 );
			include ap_get_theme_location( 'not-found.php' );
		}

	}

	/**
	 * Tags page layout
	 */
	public function tags_page() {

		global $question_tags, $ap_max_num_pages, $ap_per_page, $tags_rows_found;
		$paged 				= max( 1, get_query_var( 'paged' ) );
		$per_page           = ap_opt( 'tags_per_page' );
		$per_page           = $per_page == 0 ? 1 : $per_page;
		$offset             = $per_page * ( $paged - 1);

		$tag_args = array(
			'ap_tags_query' => 'num_rows',
			'parent'        => 0,
			'number'        => $per_page,
			'offset'        => $offset,
			'hide_empty'    => false,
			'order'         => 'DESC',
		);

		if ( @$_GET['ap_sort'] == 'new' ) {
			$tag_args['orderby'] = 'id';
			$tag_args['order']      = 'ASC';
		} elseif ( @$_GET['ap_sort'] == 'name' ) {
			$tag_args['orderby']    = 'name';
			$tag_args['order']      = 'ASC';
		} else {
			$tag_args['orderby'] = 'count';
		}

		if ( isset( $_GET['ap_s'] ) ) {
			$tag_args['search'] = sanitize_text_field( $_GET['ap_s'] );
		}

		/**
		 * FILTER: ap_tags_shortcode_args
		 * Filter applied before getting categories.
		 * @var array
		 */
		$tag_args = apply_filters( 'ap_tags_shortcode_args', $tag_args );

		$question_tags 		= get_terms( 'question_tag' , $tag_args );
		$total_terms        = wp_count_terms( 'question_tag', [ 'hide_empty' => false, 'parent' => 0 ] );
		$ap_max_num_pages   = ceil( @$total_terms / @$per_page );

		include ap_get_theme_location( 'tags.php', TAGS_FOR_ANSPRESS_DIR );
	}

	/**
	 * Load plugin text domain
	 *
	 * @since 1.0
	 *
	 * @access public
	 * @return void
	 */
	public static function textdomain() {

		// Set filter for plugin's languages directory
		$lang_dir = dirname( plugin_basename( __FILE__ ) ) . '/languages/';

		// Load the translations
		load_plugin_textdomain( 'tags-for-anspress', false, $lang_dir );

	}

	public function widget_positions() {

		register_sidebar( array(
			'name'          => __( 'AP Tags', 'tags-for-anspress' ),
			'id'            => 'ap-tags',
			'before_widget' => '<div id="%1$s" class="ap-widget-pos %2$s">',
			'after_widget'  => '</div>',
			'description'   => __( 'Widgets in this area will be shown in anspress tags page.', 'tags-for-anspress' ),
			'before_title'  => '<h3 class="ap-widget-title">',
			'after_title'   => '</h3>',
		) );
	}

	/**
	 * Register tag taxonomy for question cpt
	 * @return void
	 * @since 2.0
	 */
	public function register_question_tag() {

		/**
		 * Labesl for tag taxonomy
		 * @var array
		 */

		$tag_labels = array(
			'name' 				=> __( 'Question Tags', 'tags-for-anspress' ),
			'singular_name' 	=> _x( 'Tag', 'tags-for-anspress' ),
			'all_items' 		=> __( 'All Tags', 'tags-for-anspress' ),
			'add_new_item' 		=> _x( 'Add New Tag', 'tags-for-anspress' ),
			'edit_item' 		=> __( 'Edit Tag', 'tags-for-anspress' ),
			'new_item' 			=> __( 'New Tag', 'tags-for-anspress' ),
			'view_item' 		=> __( 'View Tag', 'tags-for-anspress' ),
			'search_items' 		=> __( 'Search Tag', 'tags-for-anspress' ),
			'not_found' 		=> __( 'Nothing Found', 'tags-for-anspress' ),
			'not_found_in_trash' => __( 'Nothing found in Trash', 'tags-for-anspress' ),
			'parent_item_colon' => '',
		);

		/**
		 * FILTER: ap_question_tag_labels
		 * Filter ic called before registering question_tag taxonomy
		 */
		$tag_labels = apply_filters( 'ap_question_tag_labels',  $tag_labels );

		/**
		 * Arguments for tag taxonomy
		 * @var array
		 * @since 2.0
		 */
		$tag_args = array(
			'hierarchical' => true,
			'labels' => $tag_labels,
			'rewrite' => false,
		);

		/**
		 * FILTER: ap_question_tag_args
		 * Filter ic called before registering question_tag taxonomy
		 */
		$tag_args = apply_filters( 'ap_question_tag_args',  $tag_args );

		/**
		 * Now let WordPress know about our taxonomy
		 */
		register_taxonomy( 'question_tag', array( 'question' ), $tag_args );

	}

	/**
	 * Apppend default options
	 * @param   array $defaults
	 * @return  array
	 * @since   1.0
	 */
	public static function ap_default_options($defaults) {

		$defaults['max_tags']       	= 5;
		$defaults['min_tags']       	= 1;
		$defaults['tags_page_title']   	= __('Tags', 'tags-for-anspress' );
		$defaults['tags_per_page']   	= 20;
		$defaults['tags_page_slug']   	= 'tags';
		$defaults['tag_page_slug']   	= 'tag';

		return $defaults;
	}

	/**
	 * Add tags menu in wp-admin
	 * @return void
	 * @since 2.0
	 */
	public function admin_tags_menu() {
		add_submenu_page( 'anspress', __( 'Questions Tags', 'tags-for-anspress' ), __( 'Tags', 'tags-for-anspress' ), 'manage_options', 'edit-tags.php?taxonomy=question_tag' );
	}
	/**
	 * Register option fields
	 * @return void
	 * @since 1.2.1
	 */
	public function option_fields() {

		if ( ! is_admin() ) {
			return; }

		$settings = ap_opt();
		ap_register_option_group( 'tags', __( 'Tags', 'tags-for-anspress' ), array(
			array(
				'name'              => 'tags_per_page',
				'label'             => __( 'Tags to show', 'tags-for-anspress' ),
				'description'       => __( 'Numbers of tags to show in tags page.', 'tags-for-anspress' ),
				'type'              => 'number',
			),
			array(
				'name'              => 'max_tags',
				'label'             => __( 'Maximum tags', 'tags-for-anspress' ),
				'description'       => __( 'Maximum numbers of tags that user can add when asking.', 'tags-for-anspress' ),
				'type'              => 'number',
			),
			array(
				'name'              => 'min_tags',
				'label'             => __( 'Minimum tags', 'tags-for-anspress' ),
				'description'       => __( 'minimum numbers of tags that user must add when asking.', 'tags-for-anspress' ),
				'type'              => 'number',
			),
			array(
				'name' 		=> 'tags_page_title',
				'label' 	=> __( 'Tags page title', 'tags-for-anspress' ),
				'desc' 		=> __( 'Title for tags page', 'tags-for-anspress' ),
				'type' 		=> 'text',
				'show_desc_tip' => false,
			),
			array(
				'name' 		=> 'tags_page_slug',
				'label' 	=> __( 'Tags page slug', 'tags-for-anspress' ),
				'desc' 		=> __( 'Slug tags page', 'tags-for-anspress' ),
				'type' 		=> 'text',
				'show_desc_tip' => false,
			),

			array(
				'name' 		=> 'tag_page_slug',
				'label' 	=> __( 'Tag page slug', 'tags-for-anspress' ),
				'desc' 		=> __( 'Slug for tag page', 'tags-for-anspress' ),
				'type' 		=> 'text',
				'show_desc_tip' => false,
			),
		));
	}


	/**
	 * Append meta display
	 * @param  array $metas
	 * @param array $question_id
	 * @return array
	 * @since 2.0
	 */
	public function ap_display_question_metas($metas, $question_id) {

		if ( ap_question_have_tags( $question_id ) && ! is_singular( 'question' ) ) {
			$metas['tags'] = ap_question_tags_html( array( 'label' => ap_icon( 'tag', true ), 'show' => 1 ) ); }

		return $metas;
	}

	/**
	 * Hook tags after post
	 * @param   object $post
	 * @return  string
	 * @since   1.0
	 */
	public function ap_question_info($post) {

		if ( ap_question_have_tags() ) {
			echo '<div class="widget"><span class="ap-widget-title">'.__( 'Tags' ).'</span>';
			echo '<div class="ap-post-tags clearfix">'. ap_question_tags_html( array( 'list' => true, 'label' => '' ) ) .'</div></div>';
		}
	}

	/**
	 * Enqueue scripts
	 * @since 1.0
	 */
	public function ap_enqueue() {
		wp_enqueue_script( 'tags_js', ap_get_theme_url( 'js/tags_js.js', TAGS_FOR_ANSPRESS_URL ) );
		wp_enqueue_style( 'tags_css', ap_get_theme_url( 'css/tags.css', TAGS_FOR_ANSPRESS_URL ) );
	}

	/**
	 * Add translated strings to the javascript files
	 * @since 1.0
	 */
	public function ap_localize_scripts() {
		$l10n_data = array(
			'deleteTag' => __( 'Delete Tag', 'tags-for-anspress' ),
			'addTag' => __( 'Add Tag', 'tags-for-anspress' ),
			'tagAdded' => __( 'added to the tags list.', 'tags-for-anspress' ),
			'tagRemoved' => __( 'removed from the tags list.', 'tags-for-anspress' ),
			'suggestionsAvailable' => __( 'Suggestions are available. Use the up and down arrow keys to read it.', 'tags-for-anspress' ),
		);

		wp_localize_script(
			'tags_js',
			'apTagsTranslation',
			$l10n_data
		);
	}

	/**
	 * Filter tag term link
	 * @param  string $url      Default URL of taxonomy.
	 * @param  array  $term     Term array.
	 * @param  string $taxonomy Taxonomy type.
	 * @return string           New URL for term.
	 */
	public function term_link_filter( $url, $term, $taxonomy ) {
		if ( 'question_tag' == $taxonomy ) {
			if ( get_option( 'permalink_structure' ) != '' ) {
				return ap_get_link_to( array( 'ap_page' => ap_get_tag_slug(), 'q_tag' => $term->slug ) );
			} else {
				return ap_get_link_to( array( 'ap_page' => ap_get_tag_slug(), 'q_tag' => $term->term_id ) );
			}
		}
		return $url;
	}

	/**
	 * add tag field in ask form
	 * @param  array $validate
	 * @return void
	 * @since 2.0
	 */
	public function ask_from_tag_field($args, $editing) {
		global $editing_post;

		if ( $editing ) {
			$tags = get_the_terms( $editing_post->ID, 'question_tag' );
		}

		$tags_post = isset( $_POST['tags'] ) ? $_POST['tags'] : '';
		$tag_val = $editing ? $tags : $tags_post;

		$tag_field = '<div class="ap-field-tags ap-form-fields">';
			$tag_field .= '<label class="ap-form-label" for="tags">'.__('Tags', 'tags-for-anspress' ).'</label>';
			$tag_field .= '<div data-role="ap-tagsinput" class="ap-tags-input">';
				$tag_field .= '<div id="ap-tags-add">';
					$tag_field .= '<input id="tags" class="ap-tags-field ap-form-control" placeholder="'.__('Type and hit enter', 'tags-for-anspress' ).'" autocomplete="off" />';
					$tag_field .= '<ul id="ap-tags-suggestion">';
					$tag_field .= '</ul>';
				$tag_field .= '</div>';

				$tag_field .= '<ul id="ap-tags-holder" aria-describedby="ap-tags-list-title">';
		foreach ( (array) $tag_val as $tag ) {
			if( !empty( $tag->slug ) ){
				$tag_field .= '<li class="ap-tagssugg-item"><button role="button" class="ap-tag-remove"><span class="sr-only"></span> <span class="ap-tag-item-value">'. $tag->slug .'</span><i class="apicon-x"></i></button><input type="hidden" name="tags[]" value="'. $tag->slug .'" /></li>';
			}
		}
				$tag_field .= '</ul>';

			$tag_field .= '</div>';

		$tag_field .= '</div>';

		$args['fields'][] = array(
			'name' 		=> 'tag',
			'label' 	=> __( 'Tags', 'tags-for-anspress' ),
			'type'  	=> 'custom',
			'taxonomy' 	=> 'question_tag',
			'desc' 		=> __( 'Slowly type for suggestions', 'tags-for-anspress' ),
			'order' 	=> 11,
			'html' 		=> $tag_field,
		);

		return $args;
	}

	/**
	 * add tag in validation field
	 * @param  array $fields
	 * @return array
	 * @since  1.0
	 */
	public function ap_ask_fields_validation($args) {
		$args['tags'] = array(
			'sanitize' => array( 'sanitize_tags' ),
			'validate' => array( 'comma_separted_count' => ap_opt( 'min_tags' ) ),
		);

		return $args;
	}

	/**
	 * Things to do after creating a question
	 * @param  int    $post_id
	 * @param  object $post
	 * @return void
	 * @since 1.0
	 */
	public function after_new_question($post_id, $post) {

		global $validate;

		if ( empty( $validate ) ) {
			return;
		}

		$fields = $validate->get_sanitized_fields();
		if ( isset( $fields['tags'] ) ) {
			$tags = explode(',', $fields['tags'] );
			wp_set_object_terms( $post_id, $tags, 'question_tag' );
		}
	}

	/**
	 * Tags page title
	 * @param  string $title
	 * @return string
	 */
	public function page_title($title) {
		if ( is_question_tags() ) {
			$title = ap_opt('tags_page_title' );
		} elseif ( is_question_tag() ) {
			$tag_id = sanitize_title( get_query_var( 'q_tag' ) );
			$tag = get_term_by( 'slug', $tag_id, 'question_tag' );
			$title = $tag->name;
		}

		return $title;
	}

	/**
	 * Hook into AnsPress breadcrums to show tags page.
	 * @param  array $navs Breadcrumbs navs.
	 * @return array
	 */
	public function ap_breadcrumbs($navs) {
		if ( is_question_tag() ) {
			$tag_id = sanitize_title( get_query_var( 'q_tag' ) );
			$tag = get_term_by( 'slug', $tag_id, 'question_tag' );
			$navs['page'] = array();
			$navs['tag'] = array( 'title' => $tag->name, 'link' => get_term_link( $tag, 'question_tag' ), 'order' => 8 );
		} elseif ( is_question_tags() ) {
			$navs['page'] = array( 'title' => __( 'Tags', 'tags-for-anspress' ), 'link' => ap_get_link_to( 'tags' ), 'order' => 8 );

		}

		return $navs;
	}

	public function ap_list_head() {

		global $wp;

		if ( ! isset( $wp->query_vars['ap_sc_atts_tags'] ) ) {
			ap_tag_sorting(); }
	}

	public function terms_clauses($query, $taxonomies, $args) {
		if ( isset( $args['ap_tags_query'] ) && $args['ap_tags_query'] == 'num_rows' ) {
			$query['fields'] = 'SQL_CALC_FOUND_ROWS '. $query['fields'];
		}

		if ( in_array( 'question_tag', $taxonomies ) && isset( $args['ap_query'] ) && $args['ap_query'] == 'tags_subscription' ) {
			global $wpdb;

			$query['join']     = $query['join'].' INNER JOIN '.$wpdb->prefix.'ap_meta apmeta ON t.term_id = apmeta.apmeta_actionid';
			$query['where']    = $query['where']." AND apmeta.apmeta_type='subscriber' AND apmeta.apmeta_param='tag' AND apmeta.apmeta_userid='".$args['user_id']."'";
		}

		return $query;
	}

	public function get_terms($terms, $taxonomies, $args) {
		if ( isset( $args['ap_tags_query'] ) && $args['ap_tags_query'] == 'num_rows' ) {
			global $tags_rows_found,  $wpdb;

			$tags_rows_found = $wpdb->get_var( apply_filters( 'ap_get_terms_found_rows', 'SELECT FOUND_ROWS()', $terms, $taxonomies, $args ) );
			// wp_cache_set( $this->cache_key.'_count', $this->total_count, 'tags-for-anspress' );
		}
		return $terms;
	}

	public function subscription_tab($active) {

		echo '<li class="'.($active == 'tag' ? 'active' : '').'"><a href="?tab=tag">'.__( 'Tag', 'tags-for-anspress' ).'</a></li>';
	}

	public function subscription_page($active) {

		$active = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'question';

		if ( $active != 'tag' ) {
			return; }

		global $question_tags, $ap_max_num_pages, $ap_per_page, $tags_rows_found;

		$paged              = get_query_var( 'paged' ) ? get_query_var( 'paged' ) : 1;
		$per_page           = ap_opt( 'tags_per_page' );
		$total_terms        = $tags_rows_found;
		$offset             = $per_page * ( $paged - 1) ;
		$ap_max_num_pages   = ceil( $total_terms / $per_page );

		$tag_args = array(
			'ap_tags_query' => 'num_rows',
			'ap_query'      => 'tags_subscription',
			'parent'        => 0,
			'number'        => $per_page,
			'offset'        => $offset,
			'hide_empty'    => false,
			'order'         => 'DESC',
			'user_id'       => get_current_user_id(),
		);

		if ( @$_GET['ap_sort'] == 'new' ) {
			$tag_args['orderby'] = 'id';
			$tag_args['order']      = 'ASC';
		} elseif ( @$_GET['ap_sort'] == 'name' ) {
			$tag_args['orderby']    = 'name';
			$tag_args['order']      = 'ASC';
		} else {
			$tag_args['orderby'] = 'count';
		}

		if ( isset( $_GET['ap_s'] ) ) {
			$tag_args['search'] = sanitize_text_field( $_GET['ap_s'] );
		}

		$question_tags = get_terms( 'question_tag' , $tag_args );

		include ap_get_theme_location( 'tags.php', TAGS_FOR_ANSPRESS_DIR );
	}

	/**
	 * Handle tags suggestion on question form
	 */
	public function ap_tags_suggestion() {
		$keyword = sanitize_text_field( wp_unslash( $_POST['q'] ) );

		$tags = get_terms('question_tag', array(
			'orderby' => 'count',
			'order' => 'DESC',
			'hide_empty' => false,
			'search' => $keyword,
			'number' => 8,
		));

		if ( $tags ) {
			$items = array();
			foreach ( $tags as $k => $t ) {
				$items [ $k ] = $t->slug;
			}

			$result = array( 'status' => true, 'items' => $items );
			die( json_encode( $result ) );
		}

		die( json_encode( array( 'status' => false ) ) );
	}

	/**
	 * Add category pages rewrite rule
	 * @param  array $rules AnsPress rules.
	 * @return array
	 */
	public function rewrite_rules($rules, $slug, $base_page_id) {
		global $wp_rewrite;

		$tags_rules = array();

		$tags_rules[$slug. ap_get_tag_slug() .'/([^/]+)/?'] = 'index.php?page_id='.$base_page_id.'&ap_page='. ap_get_tag_slug() .'&q_tag='.$wp_rewrite->preg_index( 1 );

		$tags_rules[$slug. ap_get_tag_slug() . '/([^/]+)/page/?([0-9]{1,})/?$'] = 'index.php?page_id='.$base_page_id.'&ap_page='. ap_get_tag_slug() .'&q_tag='.$wp_rewrite->preg_index( 1 ).'&paged='.$wp_rewrite->preg_index( 2 );

		$tags_rules[$slug. ap_get_tags_slug() . '/([^/]+)/page/?([0-9]{1,})/?$'] = 'index.php?page_id='.$base_page_id.'&ap_page='. ap_get_tags_slug() .'&q_tag='.$wp_rewrite->preg_index( 1 ).'&paged='.$wp_rewrite->preg_index( 2 );

		return $tags_rules + $rules;
	}

	/**
	 * Add default tags page, so that tags page should work properly after
	 * Changing tags page slug.
	 * @param  array $default_pages AnsPress default pages.
	 * @return array
	 */
	public function tags_default_page($default_pages) {
		$default_pages['tags'] = array();
		$default_pages['tag'] = array();

		return $default_pages;
	}

	/**
	 * Add default page slug
	 * @param  array $default_slugs AnsPress pages slug.
	 * @return array
	 */
	public function default_page_slugs($default_slugs) {
		$default_slugs['tags'] 	= ap_get_tags_slug();
		$default_slugs['tag'] 	= ap_get_tag_slug();
		return $default_slugs;
	}

	public function subscribe_type($type) {
		if ( is_question_tag() ) {
			$subscribe_type = 'tag'; } else {
			return $type; }
	}

	public function subscribe_btn_action_type($args) {
		if ( is_question_tag() ) {
			global $question_tag;
			$args['action_id'] 	= $question_tag->term_id;
			$args['type'] 		= 'tag';
		}

		return $args;
	}

	/**
	 * Override ap_current_page_is function to check if tags or tag page.
	 * @param  string $page Current page slug.
	 * @return string
	 */
	public function ap_current_page_is($page) {
		if ( is_question_tags() ) {
			$template = 'tags';
		} elseif ( is_question_tag() ) {
			$template = 'tag';
		}

		return $page;
	}
	/**
	 * Filter main questions query args. Modify and add label args.
	 * @param  array $args Questions args.
	 * @return array
	 */
	public static function ap_main_questions_args( $args ) {
		global $questions, $wp;
		$query = $wp->query_vars;

		$filters = ap_list_filters_get_active( 'tag' );
		$tags_operator = !empty( $wp->query_vars['ap_tags_operator'] ) ? $wp->query_vars['ap_tags_operator'] : 'IN';

		if ( isset( $query['ap_tags'] ) && is_array( $query['ap_tags'] ) ) {
			$args['tax_query'][] = array(
				'taxonomy' => 'question_tag',
				'field'    => 'slug',
				'terms'    => $query['ap_tags'],
				'operator' => $tags_operator,
			);
		} elseif ( false !== $filters ) {
			$filters = (array) wp_unslash( $filters );
			$filters = array_map( 'sanitize_text_field', $filters );
			$args['tax_query'][] = array(
				'taxonomy' => 'question_tag',
				'field'    => 'term_id',
				'terms'    => $filters,
			);
		}

		return $args;
	}

	/**
	 * Add tags sorting in list filters
	 * @return array
	 */
	public static function ap_list_filters( $filters ) {
		global $wp;

		if ( ! isset( $wp->query_vars['ap_tags'] ) ) {
			$filters['tag'] = array(
				'title' => __( 'Tag', 'anspress-question-answer' ),
				'items' => ap_get_tag_filter(),
				'search' => true,
				'multiple' => true,
			);
		}

		return $filters;
	}

	/**
	 * Send ajax response for filter search.
	 * @param  string $search_query Search string.
	 */
	public static function filter_search_tag( $search_query ) {
		ap_ajax_json( [
			'apData' => array(
			'filter' => 'tag',
			'searchQuery' => $search_query,
			'items' => ap_get_tag_filter( $search_query ),
			),
		] );
	}

	/**
	 * Subscriber action ID.
	 * @param  integer $action_id Current action ID.
	 * @return integer
	 */
	public static function subscribers_action_id( $action_id ) {
		if ( is_question_tag() ) {
			global $question_tag;
			$action_id = $question_category->term_id;
		}

		return $action_id;
	}
}

/**
 * Get everything running
 *
 * @since 1.0
 *
 * @access private
 * @return void
 */

function tags_for_anspress() {
	if ( ! version_compare(AP_VERSION, '2.3', '>' ) ) {
		function ap_tag_admin_error_notice() {
		    echo '<div class="update-nag error"> <p>'.sprintf(__('Tags extension require AnsPress 2.4-RC or above. Download from Github %shttp://github.com/anspress/anspress%s', 'tags-for-anspress' ), '<a target="_blank" href="http://github.com/anspress/anspress">', '</a>' ).'</p></div>';
		}
		add_action( 'admin_notices', 'ap_tag_admin_error_notice' );
		return;
	}

	if ( apply_filters( 'anspress_load_ext', true, 'tags-for-anspress' ) ) {
		$ap_tags = new Tags_For_AnsPress();
	}
}
add_action( 'plugins_loaded', 'tags_for_anspress' );

/**
 * Load extensions files before loading AnsPress
 * @return void
 * @since  1.0
 */
function anspress_loaded_tags_for_anspress() {
	add_filter( 'ap_default_options', array( 'Tags_For_AnsPress', 'ap_default_options' ) );
}
add_action( 'before_loading_anspress', 'anspress_loaded_tags_for_anspress' );
