<?php
function wpsm_h($s) {
	return htmlspecialchars($s, ENT_QUOTES);
}
function wpsm_parse_bool($s) {
	if (is_string($s)) $s = trim(strtolower($s));
	if ($s === "false" || $s === "0" || empty($s)) {
		return false;
	}
	return true;
}
function wpsm_parse_array($s) {
	if (is_array($s)) return $s;
	if (is_object($s)) return (array)$s;

	$s = strtolower(trim((string)$s));
	if (empty($s)) return [];

	return preg_split('/[,\s]+/', $s, -1, PREG_SPLIT_NO_EMPTY);
}


function wpsm_add_taxonomy(&$list, $term=null, $attrs=null) {
	$_escape = apply_filters("wpsm_list_name_htmlspecialchars", true, $term);
	$_name = apply_filters('wpsm_list_name', $term->name, $term);
	$_name = apply_filters('wpsm_list_name_'.$term->taxonomy, $_name, $term);

	if ($_escape) $_name = wpsm_h($_name);

	$list[] = sprintf('<a href="%s" class="wpsm-link wpsm-link-%s">%s</a>', 
					wpsm_h(get_term_link($term)), 
					wpsm_h($term->taxonomy),
					$_name );
	
	// posts 
	// @TODO other post_types
	$orderby = $attrs["orderby_post"] ?? "date"; 
	$order = $attrs["order_post"] ?? "DESC"; 

	$_list = [];
	wpsm_add_posts($_list, [
		"post_type"  => "post",
		"tax_query" => [
			"relation" => "AND",
			[
				"taxonomy" => $term->taxonomy,
				"field" => "id",
				"terms" => $term->term_id,
				"include_children" => false,
				"operator" => "IN",
			],
		], 
		"posts_per_page" => -1, 
		"post_status" => "publish", 

		"orderby" => $orderby,
		"order" => $order,
	], $attrs);


	$list[] = $_list;
}
function _wpsm_walk_posts(&$list, $posts, $children, $parent=0) {
	if (empty($children[$parent])) return;

	foreach($children[$parent] as $id) {
		if (! isset($posts[$id])) continue;

		$list[] = $posts[$id];
		unset($posts[$id]); // prevent infinite loop
		
		if (!empty($children[$id])) {
			$list[] = [];
			_wpsm_walk_posts($list[count($list)-1], $posts, $children, $id);
		}
	}
}
function wpsm_add_posts(&$list, $query, $attrs) {
	$posts = [];
	$children = [];

	$exclude = apply_filters('wpsm_exclude', 
							wpsm_parse_array($attrs["exclude"] ?? null), $query);
	if (!empty($exclude)) {
		$query["post__not_in"] = $exclude;
	}

	$wq = new WP_Query($query);
	while($wq->have_posts()) {
		$wq->the_post();
		// wpsm_add_post($list);

		$id = get_the_ID();
		$post = get_post($id);
		$post_type = $post->post_type;

		// each post
		$_escape = apply_filters("wpsm_list_name_htmlspecialchars", true, $post);
		$_name = apply_filters('wpsm_list_name', get_the_title($id), $post);
		$_name = apply_filters('wpsm_list_name_'.$post_type, $_name, $post);
	
		if ($_escape) $_name = wpsm_h($_name);
		
		// $parent = get_post_parent($id);
		$parent = (int)$post->post_parent;

		$s = sprintf('<a href="%s" class="wpsm-link wpsm-link-%s">%s</a>', 
						wpsm_h(get_permalink($post)), 
						wpsm_h($post_type),
						$_name );
		
		// {post_type}_comment
		$_comment = apply_filters('wpsm_list_'.$post_type."_comment", 
								wpsm_parse_bool($attrs[$post_type."_comment"] ?? false), $post);
		if (wpsm_parse_bool($_comment) === true) {
			if ($post->comment_count > 0) {
				$s .= sprintf(' <span class="wpsm-comment-count">(%d)</span>', $post->comment_count);
			}
		}

		// {post_type}_note
		$_note = apply_filters('wpsm_list_'.$post_type."_note", 
					$attrs[$post_type."_note"] ?? null, $post);
		
		if ($_note == "excerpt") {
			$_s = strip_tags(get_the_excerpt());
			if ($_s) {
				$s .= sprintf(' - <span class="wpsm-note wpsm-note-excerpt">%s</span>',$_s);
			}
		} else if (!empty($_note)) {
			if ($_terms = get_the_terms($id, $_note)) {
				$_tax = [];
				foreach($_terms as $_term) {
					$_tax[] = sprintf('<span class="wpsm-note wpsm-note-%s-item">%s</span>',
									wpsm_h($_note),wpsm_h($_term->name));
				}
				if (!empty($_tax)) {
					$s .= sprintf(' - <span class="wpsm-note wpsm-note-%s">%s</span>',
									wpsm_h($_note),
									join(", ", $_tax));
				}
			}
		}

		$posts[$id] = $s;

		if (!isset($children[$parent])) $children[$parent] = [];
		$children[$parent][] = $id;
	}

	wp_reset_postdata();

	_wpsm_walk_posts($list, $posts, $children);
}

function wpsm_build_list($list) {
	$res = '<ul>';
	$len = count($list);
	for ($i=0; $i<$len; ) {
		$res .= '<li>';

		while($i<$len) {
			$li = $list[$i++];
			if (is_string($li)) {
				$res .= $li;
			} else if (is_array($li)) {
				$res .= wpsm_build_list($li);
			} 
			if ($i >= $len || is_string($list[$i])) break;
		}
		$res .= '</li>';
	}
	$res .= '</ul>';

	return $res;
}

