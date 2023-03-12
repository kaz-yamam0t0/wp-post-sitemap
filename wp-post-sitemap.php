<?php
/*
Plugin Name: WP Post Sitemap
Plugin URI: https://github.com/kaz-yamam0t0/wp-post-sitemap/
Description: WP Post Sitemap is the plugin which allows you to embed simple sitemaps.
Author: kaz-yamam0t0
Author URI: https://github.com/kaz-yamam0t0/
Version: 0.0.1
*/

include dirname(__FILE__)."/functions.php";

add_shortcode('wp-post-sitemap', function($atts) {
	$attrs = shortcode_atts([
		"type" => "category, page", // category, post_tag, post, page
		//'taxonomy' => 'tag', // |category|tag|empty

		"orderby_post" => "date",
		"orderby_page" => "menu_order date",
		"orderby_category" => "name",
		"orderby_post_tag" => "name",

		"order_post" => "DESC",
		"order_page" => "DESC",
		"order_category" => "ASC",
		"order_post_tag" => "ASC",

		'category_head' => '',
		'post_tag_head' => '',
		'post_head' => '',
		'page_head' => '',

		// excerpt|category|post_tag|custom taxonomy
		'post_note' => '', 
		'page_note' => '', 

		"post_comment" => "false",
		"page_comment" => "false",

		"exclude" => "",
		"exclude_category" => "",
		"exclude_post_tag" => "",
	], $atts, 'wp-post-sitemap');

	// type
	$list = [];
	foreach(wpsm_parse_array($attrs["type"]) as $type) {
		$_list =& $list;
		if (!empty($attrs[$type."_head"])) {
			$list[] = $attrs[$type."_head"];
			$list[] = [];
			$_list =& $list[count($list)-1];
		}
	
		// taxonomy
		if ($type == "category" || $type == "post_tag") {
			$orderby = $attrs["orderby_".$type] ?? "name"; 
			$order = $attrs["order_".$type] ?? "ASC"; 

			$args = [
				"orderby" => $orderby,
				"order" => $order,
				"hide_empty" => true, 
				// "parent" => 0,
			];
			$exclude = apply_filters('wpsm_exclude_'.$type, 
						wpsm_parse_array($attrs["exclude_".$type] ?? null), $args);
			if (!empty($exclude)) {
				$args["exclude"] = $exclude;
			}
	
			$terms = get_terms($type, $args);
			foreach(array_values($terms) as $term) { // don't refer to the original $terms
				if ($term->parent == 0) {
					wpsm_add_taxonomy($_list, $term, $attrs, $terms);
				}
			}		
		} 
		// post, page, custom posts
		else {
			$orderby = $attrs["orderby_".$type] ?? "date"; 
			$order = $attrs["order_".$type] ?? "DESC"; 

			wpsm_add_posts($_list, [
				"post_type"  => $type,
				"posts_per_page" => -1, 
				"orderby" => $orderby,
				"order" => $order,
				"post_status" => "publish", 
			], $attrs);
		}
	}
	
	return sprintf('<div class="wpsm-post-sitemap">%s</div>',wpsm_build_list($list));
});