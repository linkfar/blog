<?php
add_action('widgets_init', 'uazoh_widgets_init');

function uazoh_widgets_init() {
register_widget('uazoh_Widget_Categories_List');
register_widget('uazoh_Widget_Articles_Tabs_List');
register_widget('uazoh_Widget_Homepage_post');
register_widget('uazoh_Widget_Homepage_project');

// Modified by Honlan
register_widget('honlan_homepage_posts');
// Modified by Honlan
}

// Modified by Honlan
class honlan_homepage_posts extends WP_Widget {  
    public function __construct() {
		$widget_ops = array('classname' => 'honlan_homepage_posts', 'description' => __( "根据条件筛选文章或项目" ));
		parent::__construct('honlan_homepage_posts','Honlan查询插件', $widget_ops);
	}

	public function widget( $args, $instance ) {
		if ($instance['post_type'] == 'post') {
			$number_of_article = 8;
		}
		else {
			$number_of_article = 6;
		}
		$query = new WP_Query( array ( 'post_type' => $instance['post_type'], 'orderby' => 'ID', 'order' => 'DESC', 'posts_per_page' => $number_of_article) );
		$number = 0;
		?>
		<style>
		.honlan-widget {
			width: 1000px;
			margin: 0 auto;
			margin-bottom: 15px;
			text-align: center;
		}
		.honlan-widget .cell {
			display: inline-block;
			width: 220px;
			height: 150px;
			margin-left: 10px;
			margin-right: 10px;
			margin-bottom: 24px;
			border-radius: 3px;
			position: relative;
			transition: box-shadow .4s;
			-o-transition: box-shadow .4s;
			-ms-transition: box-shadow .4s;
			-moz-transition: box-shadow .4s;
			-webkit-transition: box-shadow .4s;
		}
		.honlan-widget-project .cell {
			width: 270px;
			height: 180px;
			margin-left: 20px;
			margin-right: 20px;
			margin-bottom: 34px;
		}
		.honlan-widget .cell:hover {
			box-shadow: 2px 2px 3px rgba(0,0,0,0.3);
		}
		.honlan-widget .cell div {
			width: 100%;
			height: 100%;
			border-radius: 3px;
			background-size: cover;
		}
		.honlan-widget .cell p {
			width: 100%;
			margin-bottom: 0;
			line-height: 30px;
			text-align: center;
			color: white;
			background-color: rgba(0,0,0,.5);
			position: absolute;
			bottom: 0;
			border-bottom-left-radius: 3px;
			border-bottom-right-radius: 3px;
			font-size: 13px;
		}
		@media screen and (max-width:1000px) {
			.honlan-widget {
				width: 100%;
			}
			.honlan-widget .cell, .honlan-widget-project .cell {
				width: 40%;
				margin-left: 3%;
				margin-right: 3%;
			}
		}
		</style>
		<div class="honlan-widget-<?php echo $instance['post_type']?> honlan-widget">
		<?php
		while ( $query->have_posts() ) {
			$query->the_post();
			?>
			<div class="cell">
				<a href="<?php the_permalink();?>" target="_blank">
					<div style="background-image:url(<?php echo wp_get_attachment_image_src(get_post_thumbnail_id(get_the_ID()), 'large')[0];?>);">
					</div>
					<p style="text-align:center;"><?php the_title();?></p>
				</a>
			</div>
			<?php
		}
		echo "</div>";
		echo "</div>";
	}
		
	public function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['post_type'] = $new_instance['post_type'];
		return $instance;
	}
	public function form( $instance ) {
		$defaults = array('post_type' => 'post');
		$instance = wp_parse_args((array)$instance, $defaults);
		$post_type = $instance['post_type'];
		?>
		
		<p><label for="<?php echo $this->get_field_id('post_type'); ?>">文章类别</label><select class="widefat" id="<?php echo $this->get_field_id('post_type'); ?>" name="<?php echo $this->get_field_name('post_type'); ?>" multiple="multiple" size="5" autocomplete="off"><option value="post">post</option><option value="project">project</option></select></p>

		<?php
	}
}  
// Modified by Honlan

add_action('admin_enqueue_scripts', 'uazoh_widget_admin_enqueue_scripts');

function uazoh_widget_admin_enqueue_scripts($hook) {
if ('widgets.php' === $hook) {
$dir = get_template_directory_uri() . '/includes';
wp_enqueue_style('uazoh_widget_admin', "{$dir}/widget.css");
wp_enqueue_script('uazoh_widget_admin', "{$dir}/widget.js", array('jquery'));
}
}

function uazoh_widget_article_build_query($query_args = array()) {
$args = array(
'post_type' => array('post'),
'posts_per_page' => $query_args['number_of_article']
);

$tax_query = array();

if ($query_args['categories']) {
$tax_query[] = array(
'taxonomy' => 'category',
'field' => 'id',
'terms' => $query_args['categories']
);
}
if ($query_args['tags']) {
$tax_query[] = array(
'taxonomy' => 'post_tag',
'field' => 'id',
'terms' => $query_args['tags']
);
}
if ($query_args['relation'] && count($tax_query) == 2) {
$tax_query['relation'] = $query_args['relation'];
}

if ($tax_query) {
$args['tax_query'] = $tax_query;
}

switch ($query_args['orderby']) {
case 'popular':
$args['meta_key'] = 'uazoh_' . 'uazoh' . '_total_view';
$args['orderby'] = 'meta_value_num';
break;
case 'most_comment':
$args['orderby'] = 'comment_count';
break;
case 'random':
$args['orderby'] = 'rand';
break;
default:
$args['orderby'] = 'date';
break;
}
if (isset($query_args['post__not_in']) && $query_args['post__not_in']) {
$args['post__not_in'] = $query_args['post__not_in'];
}
return new WP_Query($args);
}

function uazoh_widget_posttype_build_query( $query_args = array() ) {
$default_query_args = array(
'post_type'  => 'post',
'posts_per_page' => -1,
'post__not_in'   => array(),
'ignore_sticky_posts' => 1,
'categories' => array(),
'tags'   => array(),
'relation'   => 'OR',
'orderby'=> 'lastest',
'cat_name'   => 'category',
'tag_name'   => 'post_tag'
);
$query_args = wp_parse_args( $query_args, $default_query_args );
$args = array(
'post_type'   => $query_args['post_type'],
'posts_per_page'  => $query_args['posts_per_page'],
'post__not_in'=> $query_args['post__not_in'],
'ignore_sticky_posts' => $query_args['ignore_sticky_posts']
);
$tax_query = array();
if ( $query_args['categories'] ) {
$tax_query[] = array(
'taxonomy' => $query_args['cat_name'],
'field'=> 'id',
'terms'=> $query_args['categories']
);
}
if ( $query_args['tags'] ) {
$tax_query[] = array(
'taxonomy' => $query_args['tag_name'],
'field'=> 'id',
'terms'=> $query_args['tags']
);
}
if ( $query_args['relation'] && count( $tax_query ) == 2 ) {
$tax_query['relation'] = $query_args['relation'];
}
if ( $tax_query ) {
$args['tax_query'] = $tax_query;
}
switch ( $query_args['orderby'] ) {
case 'popular':
$args['meta_key'] = 'uazoh_' . 'uazoh' . '_total_view';
$args['orderby'] = 'meta_value_num';
break;
case 'most_comment':
$args['orderby'] = 'comment_count';
break;
case 'random':
$args['orderby'] = 'rand';
break;
default:
$args['orderby'] = 'date';
break;
}
return new WP_Query( $args );
}


/*
 * uazoh_Widget_Articles_Tabs_List
 */

class uazoh_Widget_Articles_Tabs_List extends WP_Widget {

function __construct() {
$widget_ops = array('classname' => 'uazoh7-widget uazoh7-search-widget', 'description' => __('请选择要显示的分类', 'uazoh'));
$control_ops = array('width' => 'auto', 'height' => 'auto');
parent::__construct('uazoh_widget_articles_tabs_list', __('Uazoh主题TAB文章', 'uazoh'), $widget_ops, $control_ops);
}

function widget( $args, $instance ) {
extract( $args );
$title = apply_filters( 'widget_title', empty( $instance['title'] ) ? '' : $instance['title'], $instance, $this->id_base );
$query_args['posts_per_page'] = $instance['number_of_article'];
$query_args['orderby'] = $instance['orderby'];
$header_icons = array('user', 'star','tags');
echo $before_widget;
if ( ! empty( $title ) ) {
echo $before_title . $title . $after_title;
}

$posts = uazoh_widget_posttype_build_query( $query_args );

if ( ! empty( $instance['categories'] ) && $posts->have_posts() ) : ?>
<div class="uazoh7-widget-inner">
<div class="uazoh7-tabs">
<header>
<?php if($header_icons) {foreach($header_icons as $header_icon) { ?>
<span><i class="fa fa-<?php echo $header_icon; ?>"></i></span><?php }} ?>
</header>
<?php foreach ( $instance['categories'] as $cat_ID ) : 
$cat_posts = new WP_Query( array('cat' => $cat_ID,'posts_per_page' => $instance['number_of_article']) );if ( $cat_posts->have_posts() ) :?>
<article id="<?php echo $this->get_field_id('tab') . '-' . $cat_ID; ?>">
<?php $post_index = 1; while ( $cat_posts->have_posts() ) : $cat_posts->the_post(); ?>

<div class="uazoh7-sidebar-post">
<figure><a href="<?php the_permalink(); ?>"><?php if(has_post_thumbnail()){echo get_the_post_thumbnail($post->ID,'uazoh-image-size-4');}else{echo '<img src="'.get_stylesheet_directory_uri() .'/img/default-thumb.jpg" alt="'.get_the_title().'"/ >';} ?></a></figure>
<p class="title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></p>
<small class="meta"><?php the_time( get_option( 'date_format' ) ); ?></small>
</div>
<?php $post_index++; endwhile; ?>
<?php endif;wp_reset_postdata();?></article><?php endforeach; ?></div></div>
<?php endif;  $posts->have_posts();wp_reset_postdata();echo $after_widget;}
function form($instance) {
$defaults = array(
'title' => '',
'categories' => array(),
'number_of_article' => 3,
'orderby' => 'lastest',
);
$instance = wp_parse_args( (array) $instance, $defaults );
$title = strip_tags( $instance['title'] );

$form['categories'] = $instance['categories'];
$form['number_of_article'] = (int) $instance['number_of_article'];
$form['orderby'] = $instance['orderby'];
?>
<p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('标题:', 'uazoh'); ?></label><input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo esc_attr($title); ?>" /></p><p><label for="<?php echo $this->get_field_id('categories'); ?>"><?php _e('分类:', 'uazoh'); ?></label><select class="widefat" id="<?php echo $this->get_field_id('categories'); ?>" name="<?php echo $this->get_field_name('categories'); ?>[]" multiple="multiple" size="5" autocomplete="off"><option value=""><?php _e('-- None --', 'uazoh'); ?></option><?php $categories = get_categories();
foreach ($categories as $category) {
printf('<option value="%1$s" %4$s>%2$s (%3$s)</option>', $category->term_id, $category->name, $category->count, (in_array($category->term_id, $form['categories'])) ? 'selected="selected"' : '');
}
?>
</select></p><p><label for="<?php echo $this->get_field_id('number_of_article'); ?>"><?php _e('显示多少文章:', 'uazoh'); ?></label><input class="widefat" type="number" min="2" id="<?php echo $this->get_field_id('number_of_article'); ?>" name="<?php echo $this->get_field_name('number_of_article'); ?>" value="<?php echo esc_attr( $form['number_of_article'] ); ?>"></p><p><label for="<?php echo $this->get_field_id('orderby'); ?>"><?php _e('排序:', 'uazoh'); ?></label><select class="widefat" id="<?php echo $this->get_field_id('orderby'); ?>" name="<?php echo $this->get_field_name('orderby'); ?>" autocomplete="off"><?php
$orderby = array(
'lastest' => __('最新', 'uazoh'),
'most_comment' => __('评论数', 'uazoh'),
'random' => __('随机', 'uazoh')
);
foreach ($orderby as $value => $title) {
printf('<option value="%1$s" %3$s>%2$s</option>', $value, $title, ($value === $form['orderby']) ? 'selected="selected"' : '');
}
?>
</select>
</p>
<?php
}

function update($new_instance, $old_instance) {
$instance = $old_instance;
$instance['title'] = strip_tags($new_instance['title']);
$instance['categories'] = (empty($new_instance['categories'])) ? array() : array_filter($new_instance['categories']);
$instance['number_of_article'] = (int) $new_instance['number_of_article'];
if ( 0 >= $instance['number_of_article'] ) {
$instance['number_of_article'] = 3;
}
$instance['orderby'] = $new_instance['orderby'];
$instance['display_type'] = $new_instance['display_type'];

return $instance;
}

}


/*
 * uazoh_Widget_Categories_List
 */

class uazoh_Widget_Categories_List extends WP_Widget {

	public function __construct() {
		$widget_ops = array( 'classname' => 'uazoh7-category-widget', 'description' => __( "A list or dropdown of categories." ) );
		parent::__construct('uazoh_Widget_Categories_List','Uazoh主题分类目录', $widget_ops);
	}

	public function widget( $args, $instance ) {
		$title = apply_filters( 'widget_title', empty( $instance['title'] ) ? '分类' : $instance['title'], $instance, $this->id_base );
		$c = ! empty( $instance['count'] ) ? '1' : '0';

		echo $args['before_widget'];
		if ( $title ) {
			echo $args['before_title'] . $title . $args['after_title'];
		}
		$cat_args = array('orderby' => 'name', 'show_count' => $c);

		
?>
		<div class="uazoh7-category-widget" id="popular-post"><div class="uazoh7-widget-inner"><ul>
<?php
		$categories=get_categories($cat_args);
			foreach($categories as $category) { 
				echo '<li><p><a href="' . get_category_link( $category->term_id ) . '" title="' .$category->name . '" ' . '><i class="fa fa-folder-open"></i>' . $category->name.'</a> <a href="' . get_category_link( $category->term_id ) . '"><i class="fa fa-rss"></i></a> <span>'.$category->count.'</span></p><small>'.$category->description.'</small></li>';
			}
?>
		</ul></div></div>
<?php
		echo $args['after_widget'];}
	public function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title'] = strip_tags($new_instance['title']);
		$instance['count'] = !empty($new_instance['count']) ? 1 : 0;
		return $instance;
	}
	public function form( $instance ) {
		$instance = wp_parse_args( (array) $instance, array( 'title' => '') );
		$title = esc_attr( $instance['title'] );
		$count = isset($instance['count']) ? (bool) $instance['count'] :true;
?>
		<p><label for="<?php echo $this->get_field_id('title'); ?>"><?php _e( 'Title:' ); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" /></p>
		<input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id('count'); ?>" name="<?php echo $this->get_field_name('count'); ?>"<?php checked( $count ); ?> />
		<label for="<?php echo $this->get_field_id('count'); ?>">显示文章数量</label><br />
<?php
	}

}

/*
 * uazoh_Widget_Homepage_post
 */

class uazoh_Widget_Homepage_post extends WP_Widget {

function __construct() {
$widget_ops = array('classname' => 'uazoh7-homepage-post', 'description' => __('请选择要显示的分类', 'uazoh'));
$control_ops = array('width' => 'auto', 'height' => 'auto');
parent::__construct('uazoh_Widget_Homepage_post', __('Uazoh主题首页文章', 'uazoh'), $widget_ops, $control_ops);
}

function widget( $args, $instance ) {
extract( $args );
$title = apply_filters( 'widget_title', empty( $instance['title'] ) ? '' : $instance['title'], $instance, $this->id_base );
$query_args['posts_per_page'] = $instance['number_of_article'];
$query_args['orderby'] = $instance['orderby'];
echo $before_widget;
if ( ! empty( $title ) ) {
echo $before_title . $title . $after_title;
}

$posts = uazoh_widget_posttype_build_query( $query_args );

if ( ! empty( $instance['categories'] ) && $posts->have_posts() ) : ?>
<?php foreach ( $instance['categories'] as $cat_ID ) : 
$cat_posts = new WP_Query( array('cat' => $cat_ID,'posts_per_page' => $instance['number_of_article']) );if ( $cat_posts->have_posts() ) :?>
<?php $post_index = 1; while ( $cat_posts->have_posts() ) : $cat_posts->the_post(); ?>
<article class="uazoh7-post-preview uazoh7-padding-left-30">
<div class="uazoh7-post-preview-inner">
<figure><a href="<?php the_permalink(); ?>" class="colorbox" title="<?php the_title(); ?>"><?php echo get_the_post_thumbnail($post->ID,'uazoh-image-size-7'); ?></a>
<figcaption><a href="<?php $thumrb_id = get_post_thumbnail_id($post->ID);$image_urrl = wp_get_attachment_url($thumrb_id); echo $image_urrl; ?>" title="<?php the_title(); ?>" class="colorbox"><i class="fa fa-plus"></i></a></figcaption></figure>
<div class="header">
<div class="date">
<span class="day"><?php the_time('d') ?></span><span class="month"><?php the_time('Y') ?>,<?php the_time('m') ?></span>
</div>
<p><a href="<?php the_permalink(); ?>" title="<?php the_title(); ?>" target="_blank"> <?php the_title(); ?></a>
</div>
<p><?php the_excerpt();?></p>
</p></div>
</article>
<?php $post_index++; endwhile; endif;wp_reset_postdata();endforeach;endif;  $posts->have_posts();wp_reset_postdata();echo $after_widget;}
function form($instance) {
$defaults = array(
'title' => '',
'categories' => array(),
'number_of_article' => 3,
'orderby' => 'lastest',
);
$instance = wp_parse_args( (array) $instance, $defaults );
$title = strip_tags( $instance['title'] );

$form['categories'] = $instance['categories'];
$form['number_of_article'] = (int) $instance['number_of_article'];
$form['orderby'] = $instance['orderby'];
?>
<p><label for="<?php echo $this->get_field_id('categories'); ?>"><?php _e('分类:', 'uazoh'); ?></label><select class="widefat" id="<?php echo $this->get_field_id('categories'); ?>" name="<?php echo $this->get_field_name('categories'); ?>[]" multiple="multiple" size="5" autocomplete="off"><option value=""><?php _e('-- None --', 'uazoh'); ?></option><?php $categories = get_categories();
foreach ($categories as $category) {
printf('<option value="%1$s" %4$s>%2$s (%3$s)</option>', $category->term_id, $category->name, $category->count, (in_array($category->term_id, $form['categories'])) ? 'selected="selected"' : '');
}
?>
</select></p><p><label for="<?php echo $this->get_field_id('number_of_article'); ?>"><?php _e('显示多少文章:', 'uazoh'); ?></label><input class="widefat" type="number" min="2" id="<?php echo $this->get_field_id('number_of_article'); ?>" name="<?php echo $this->get_field_name('number_of_article'); ?>" value="<?php echo esc_attr( $form['number_of_article'] ); ?>"></p><p><label for="<?php echo $this->get_field_id('orderby'); ?>"><?php _e('排序:', 'uazoh'); ?></label><select class="widefat" id="<?php echo $this->get_field_id('orderby'); ?>" name="<?php echo $this->get_field_name('orderby'); ?>" autocomplete="off"><?php
$orderby = array(
'lastest' => __('最新', 'uazoh'),
'most_comment' => __('评论数', 'uazoh'),
'random' => __('随机', 'uazoh')
);
foreach ($orderby as $value => $title) {
printf('<option value="%1$s" %3$s>%2$s</option>', $value, $title, ($value === $form['orderby']) ? 'selected="selected"' : '');
}
?>
</select>
</p>
<?php
}

function update($new_instance, $old_instance) {
$instance = $old_instance;
$instance['title'] = strip_tags($new_instance['title']);
$instance['categories'] = (empty($new_instance['categories'])) ? array() : array_filter($new_instance['categories']);
$instance['number_of_article'] = (int) $new_instance['number_of_article'];
if ( 0 >= $instance['number_of_article'] ) {
$instance['number_of_article'] = 3;
}
$instance['orderby'] = $new_instance['orderby'];
$instance['display_type'] = $new_instance['display_type'];

return $instance;
}

}


/*
 * uazoh_Widget_Homepage_project
 */

class uazoh_Widget_Homepage_project extends WP_Widget {

function __construct() {
$widget_ops = array('classname' => 'uazoh7-homepage-project', 'description' => __('请选择要显示的分类', 'uazoh'));
$control_ops = array('width' => 'auto', 'height' => 'auto');
parent::__construct('uazoh_Widget_Homepage_project', __('Uazoh主题首页项目展示', 'uazoh'), $widget_ops, $control_ops);
}
function widget( $args, $instance ) {
extract( $args );
$title = apply_filters( 'widget_title', empty( $instance['title'] ) ? '' : $instance['title'], $instance, $this->id_base );
$query_args['posts_per_page'] = $instance['number_of_article'];
$query_args['orderby'] = $instance['orderby'];
echo $before_widget;
if ( ! empty( $title ) ) {
echo $before_title . $title . $after_title;
}
$posts = uazoh_widget_posttype_build_query( $query_args );
if ( ! empty( $instance['categories'] ) && $posts->have_posts() ) : ?>
<?php foreach ( $instance['categories'] as $cat_ID ) : 
$cat_posts = new WP_Query( array('cat' => $cat_ID,'posts_per_page' => $instance['number_of_article']) );if ( $cat_posts->have_posts() ) :?>
<?php $post_index = 1; while ( $cat_posts->have_posts() ) : $cat_posts->the_post(); ?>
<article class="uazoh7-project uazoh7-padding-left-30">
<div class="uazoh7-project-inner">
<figure><a href="<?php the_permalink(); ?>"><?php echo get_the_post_thumbnail($post->ID,'uazoh-image-size-6'); ?></a><figcaption><a href="<?php $thumrb_id = get_post_thumbnail_id($post->ID);$image_urrl = wp_get_attachment_url($thumrb_id); echo $image_urrl; ?>" title="<?php the_title(); ?>" class="colorbox"><i class="fa fa-plus"></i></a></figcaption></figure>
<div class="uazoh7-project-details">
<div class="uazoh7-project-likes"><i class="fa fa-thumbs-o-up"></i> <?php post_views('', ''); ?></div>
<p class="link"><a href="<?php the_permalink(); ?>" title="<?php the_title(); ?>"><?php the_title(); ?></a></p>
<p class="filter"><?php the_tags(' ', ' , ' , ''); ?></p>
</div></div></article>
<?php $post_index++; endwhile; endif;wp_reset_postdata();endforeach;endif;  $posts->have_posts();wp_reset_postdata();echo $after_widget;}
function form($instance) {
$defaults = array(
'title' => '',
'categories' => array(),
'number_of_article' => 3,
'orderby' => 'lastest',
);
$instance = wp_parse_args( (array) $instance, $defaults );
$title = strip_tags( $instance['title'] );

$form['categories'] = $instance['categories'];
$form['number_of_article'] = (int) $instance['number_of_article'];
$form['orderby'] = $instance['orderby'];
?>
<p><label for="<?php echo $this->get_field_id('categories'); ?>"><?php _e('分类:', 'uazoh'); ?></label><select class="widefat" id="<?php echo $this->get_field_id('categories'); ?>" name="<?php echo $this->get_field_name('categories'); ?>[]" multiple="multiple" size="5" autocomplete="off"><option value=""><?php _e('-- None --', 'uazoh'); ?></option><?php $categories = get_categories();
foreach ($categories as $category) {
printf('<option value="%1$s" %4$s>%2$s (%3$s)</option>', $category->term_id, $category->name, $category->count, (in_array($category->term_id, $form['categories'])) ? 'selected="selected"' : '');
}
?>
</select></p><p><label for="<?php echo $this->get_field_id('number_of_article'); ?>"><?php _e('显示多少文章:', 'uazoh'); ?></label><input class="widefat" type="number" min="2" id="<?php echo $this->get_field_id('number_of_article'); ?>" name="<?php echo $this->get_field_name('number_of_article'); ?>" value="<?php echo esc_attr( $form['number_of_article'] ); ?>"></p><p><label for="<?php echo $this->get_field_id('orderby'); ?>"><?php _e('排序:', 'uazoh'); ?></label><select class="widefat" id="<?php echo $this->get_field_id('orderby'); ?>" name="<?php echo $this->get_field_name('orderby'); ?>" autocomplete="off"><?php
$orderby = array(
'lastest' => __('最新', 'uazoh'),
'most_comment' => __('评论数', 'uazoh'),
'random' => __('随机', 'uazoh')
);
foreach ($orderby as $value => $title) {
printf('<option value="%1$s" %3$s>%2$s</option>', $value, $title, ($value === $form['orderby']) ? 'selected="selected"' : '');
}
?></select></p><?php
}
function update($new_instance, $old_instance) {
$instance = $old_instance;
$instance['title'] = strip_tags($new_instance['title']);
$instance['categories'] = (empty($new_instance['categories'])) ? array() : array_filter($new_instance['categories']);
$instance['number_of_article'] = (int) $new_instance['number_of_article'];
if ( 0 >= $instance['number_of_article'] ) {
$instance['number_of_article'] = 3;
}
$instance['orderby'] = $new_instance['orderby'];
$instance['display_type'] = $new_instance['display_type'];
return $instance;
}
}
