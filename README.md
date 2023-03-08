# WP Post Sitemap

## Overview

`WP Post Sitemap`  allows you to embed a shortcode `[wp-post-sitemap]` to create a sitemap on your WordPress websites.

```
[wp-post-sitemap]
```

## How to Install

#### With git command

```
cd /path/to/your-wordpress/wp-content/plugin/
git clone https://github.com/kaz-yamam0t0/wp-post-sitemap
```

#### Without git command

```
cd /path/to/your-wordpress/wp-content/plugin/

curl -O https://github.com/kaz-yamam0t0/wp-post-sitemap/archive/refs/heads/main.zip
tar -xzvf main.zip
rm -f main.zip
```

## Options

The following options can be designated:

| Name | Description | Possible Values | Default |
| ----- | ----- | ----- | ----- |
| type | Types of posts or taxonomies | category, post_tag, post, page | category, page | 
| *_head | Head of the list of each types | (string) | (empty) |
| *_note | Notes or descriptions placed behind each title | excerpt, category, post_tag, (other taxonomies) | (empty) |
| *_comment | Whether the comment count is displayed | true, false | false |
| exlcude | Excluded Post ID (for posts, pages, or other post types) | (empty) | (empty) |
| exclude_* | Excluded Taxonomy ID (for all taxonomies) | (empty) | (empty) | 

## Filters

| Filter Name | Args | Description | 
| ----- | ----- | ----- |
| wpsm_list_name_htmlspecialchars | (bool)$flag, (WP_Post\|WP_Term)$target_obj | Whether each name is escaped |
| wpsm_list_name | (string)$name, (WP_Post\|WP_Term)$target_obj | for each name | 
| wpsm_list_name_{taxonomy} | (string)$name, (WP_Term)$target_obj | for each name of {taxonomy} |
| wpsm_list_name_{post_type} | (string)$name, (WP_Post)$target_obj | for each name of {post_type} |
| wpsm_list_{post_type}_note | (string)$note, (WP_Post\|WP_Term)$target_obj | for each note of {post_type} |
| wpsm_list_{post_type}_comment | (bool)$comment, (WP_Post\|WP_Term)$target_obj | for each comment-display flag of {post_type} |
| wpsm_exclude | (array?)$exclude, (array)$wp_query_args | excluded post IDs |
| wpsm_exclude_{taxonomy} | (array?)$exclude, (array)$get_terms_args | excluded taxonomy IDs |

## Example of filters

```php
add_filter("wpsm_list_name_htmlspecialchars", function($flag, $target_obj=null) {
	// if the link is for terms, the link won't be escaped.
	return !($target_obj instanceof WP_Term);
}, 10, 2);

add_filter("wpsm_list_name", function($name, $target_obj=null) {
	if ($target_obj instanceof WP_Term) {
		return sprintf('<b>[%s]</b> %s', 
						htmlspecialchars($target_obj->taxonomy, ENT_QUOTES),
						htmlspecialchars($name, ENT_QUOTES));
	} else {
		return '[post]'.$name;
	}
}, 10, 2);
```
