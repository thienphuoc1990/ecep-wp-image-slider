<?php
/**
 * Plugin Name: ECEP Wordpress Image Slider
 * Plugin URI: https://github.com/thienphuoc1990/ecep-wp-image-slider
 * Description: This is a plugin for image slider with carousel bootstrap
 * Version: 1.0 
 * Author: Phuoc Dinh
 * Author URI: https://github.com/thienphuoc1990
 * License: GPLv2 or later 
 */
?>
<?php
if (!class_exists('ECEP_WP_Image_Slider')) {
	/**
	* 
	*/
	class ECEP_WP_Image_Slider
	{
		
		function __construct()
		{
			
		}

		/*==============	ECEP Create Sliders Post Type	======================*/
		function create_slider_post_type() {
			/* Set labels for post type fields */
			$labels = array(
				'name' 				  => __( 'Sliders', 'Post Type General Name' ),
				'singular_name' 	  => __( 'Slider', 'Post Type Singular Name' ),
				'menu_name'           => __( 'Sliders' ),
				'parent_item_colon'   => __( 'Parent Slider' ),
				'all_items'           => __( 'All Slider' ),
				'view_item'           => __( 'View Slider' ),
				'add_new_item'        => __( 'Add New Slider' ),
				'add_new'             => __( 'Add New' ),
				'edit_item'           => __( 'Edit Slider' ),
				'update_item'         => __( 'Update Slider' ),
				'search_items'        => __( 'Search Slider' ),
				'not_found'           => __( 'Slider Not Found' ),
				'not_found_in_trash'  => __( 'Slider Not found in Trash' ),
			);

			/* Set arguments for post type */
			$args = array(
				'labels' => $labels,
				'public' => true,
				'has_archive' => true,
				'rewrite' => ['slug' => 'slider'],
				'supports' => array(
					'title',
					'editor',
					'thumbnail',
				), 
			);

			/* Register Post Type */
			register_post_type( 'slider', $args);
		}

		/*==============	ECEP Add Moocsnews Slider Fields Meta Box	======================*/
		function add_slider_fields_meta_box() {
			add_meta_box(
				'slider_fields_meta_box', // $id
				'Slider Fields Metabox', // $title
				array(&$this, 'show_slider_fields_meta_box'), // $callback
				'slider', // $screen
				'normal', // $context
				'high' // $priority
			);
		}

		function show_slider_fields_meta_box() {
			global $post;  
			$meta = get_post_meta( $post->ID, 'slider_fields', true ); ?>

			<input type="hidden" name="slider_meta_box_nonce" value="<?php echo wp_create_nonce( basename(__FILE__) ); ?>">

			<p>
				<label for="slider_fields[button]">Button</label>
				<br>
				<input type="text" name="slider_fields[button]" id="slider-fields-button" class="regular-text" value="<?php echo esc_html($meta['button']); ?>">
			</p>

			<?php }

			/*==============	ECEP Save Moocsnews Slider Fields Meta Box	======================*/
			function save_slider_fields_meta( $post_id ) {   
			// verify nonce
				if ( !wp_verify_nonce( $_POST['slider_meta_box_nonce'], basename(__FILE__) ) ) {
					return $post_id; 
				}
			// check autosave
				if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
					return $post_id;
				}
			// check permissions
				if ( 'page' === $_POST['slider'] ) {
					if ( !current_user_can( 'edit_page', $post_id ) ) {
						return $post_id;
					} elseif ( !current_user_can( 'edit_post', $post_id ) ) {
						return $post_id;
					}  
				}

				$old = get_post_meta( $post_id, 'slider_fields', true );
				$new = $_POST['slider_fields'];

				if ( $new && $new !== $old ) {
					update_post_meta( $post_id, 'slider_fields', $new );
				} elseif ( '' === $new && $old ) {
					delete_post_meta( $post_id, 'slider_fields', $old );
				}
			}

			/*==============    ECEP Create Slider Location Custom Taxonomy Type   ======================*/
			function create_slider_location_nonhierarchical_taxonomy() {

				$labels = array(
					'name' => _x( 'Slider Locations', 'taxonomy general name' ),
					'singular_name' => _x( 'Slider Location', 'taxonomy singular name' ),
					'search_items' =>  __( 'Search Slider Location' ),
					'popular_items' => __( 'Popular Slider Locations' ),
					'all_items' => __( 'All Slider Locations' ),
					'parent_item' => null,
					'parent_item_colon' => null,
					'edit_item' => __( 'Edit Slider Location' ), 
					'update_item' => __( 'Update Slider Location' ),
					'add_new_item' => __( 'Add New Slider Location' ),
					'new_item_name' => __( 'New Slider Location Name' ),
					'separate_items_with_commas' => __( 'Separate Slider Locations with commas' ),
					'add_or_remove_items' => __( 'Add or remove Slider Locations' ),
					'choose_from_most_used' => __( 'Choose from the most used Slider Locations' ),
					'menu_name' => __( 'Slider Locations' ),
				); 

				register_taxonomy('slider_loc','slider',array(
					'hierarchical' => false,
					'labels' => $labels,
					'show_ui' => true,
					'show_admin_column' => true,
					'update_count_callback' => '_update_post_term_count',
					'query_var' => true,
					'rewrite' => array( 'slug' => 'slider_loc' ),
				));
			}

			function simple_slider_shortcode($atts = null) {
				global $add_my_script, $ss_atts;
				$add_my_script = true;
				$ss_atts = shortcode_atts(
					array(
						'location' => 'top',
						'limit' => -1,
					), $atts, 'simpleslider'
				);
				$args = array(
					'post_type' => 'slider',
					'posts_per_page' => $ss_atts['limit'],
					'orderby' => 'menu_order',
					'order' => 'ASC'
				);
				if ($ss_atts['location'] != '') {
					$args['tax_query'] = array(
						array( 'taxonomy' => 'slider_loc', 'field' => 'slug', 'terms' => $ss_atts['location'] )
					);
				}
				$the_query = new WP_Query( $args );
				$slides = array();
				$indicators = array();
				$i = 0;
				if ( $the_query->have_posts() ) {
					while ( $the_query->have_posts() ) {
						$the_query->the_post();
						$imghtml = get_the_post_thumbnail_url(get_the_ID());
						$meta = get_post_meta(get_the_ID(), 'slider_fields', true);
						if($i == 0){
							$slides[] = 
						 	'<div class="carousel-item active">
						      	<img class="d-block w-100" src="'.$imghtml.'" alt="'.get_the_title().'">
								  	<div class="carousel-caption d-none d-md-block">
									    '.$meta['button'].'
								  	</div>
						    </div>';
						    $indicators[] = '<li data-target="#carousel-'.$ss_atts['location'].'" data-slide-to="'.$i.'" class="active"></li>';
						}else{
							$slides[] = 
						 	'<div class="carousel-item">
						      	<img class="d-block w-100" src="'.$imghtml.'" alt="'.get_the_title().'">
								  	<div class="carousel-caption d-none d-md-block">
									    '.$meta['button'].'
								  	</div>
						    </div>';
						    $indicators[] = '<li data-target="#carousel-'.$ss_atts['location'].'" data-slide-to="'.$i.'"></li>';
						}
					    $i++;
					}
				}
				wp_reset_query();

				add_action('wp_footer', array(&$this, 'print_javascript'), 99);

				return 
				'<div id="carousel-'.$ss_atts['location'].'" class="carousel slide" data-ride="carousel">
					<ol class="carousel-indicators">
						'.implode('', $indicators).'
					</ol>
  					<div class="carousel-inner">
    					'.implode('', $slides).'
				  	</div>
				</div>';
			}

			function print_javascript(){
				echo '<script type="text/javascript">
					jQuery(document).ready(function($){
						$(".carousel").carousel({
  							interval: 5000,
  							pause: false
						});
					});
				</script>';
			}
		}
	}

	function iwis_load(){
		global $iwis;
		$iwis = new ECEP_WP_Image_Slider();
		add_action( 'init',  array($iwis, 'create_slider_post_type') );
		add_action( 'init', array($iwis, 'create_slider_location_nonhierarchical_taxonomy'), 0 );
		add_action( 'add_meta_boxes', array($iwis, 'add_slider_fields_meta_box') );
		add_action( 'save_post', array($iwis, 'save_slider_fields_meta') );
		add_shortcode( 'simpleslider', array($iwis, 'simple_slider_shortcode') );
	}

	add_action( 'plugins_loaded', 'iwis_load' );


	?>