<?php
/*
 * Plugin Name:     SQLinkFinder
 * Description:     Gather all public facing links and generate a JSON file.
 * Author:          Ben Goliwas
 * Author URI:      https://www.TheSprinter.com/
 * Text Domain:     SQLinkFinder
 * License:         GPL v2 or later
 * License URI:     https://www.gnu.org/licenses/gpl-2.0.html
 * Version:         1.0.1
 */

/* 
REFERENCES:
 Page Info: https://developer.wordpress.org/reference/functions/get_bloginfo/
 Insert Attachment: https://developer.wordpress.org/reference/functions/wp_insert_attachment/
 Other WP File Uploads: https://developer.wordpress.org/reference/functions/wp_handle_upload/
 Creator: Ben Goliwas - BenGoliwas@Gmail.com
*/

// ADD MENU BUTTON / POPULATE ADMIN PAGE
add_action( 'admin_menu', 'sqlf_menu_page' );
function sqlf_menu_page() {
	add_menu_page(
		'Site Quality Link Finder', // page title
		'Site Links', // menu link text
		'manage_options', // capability to access the page
		'sqlf-slug', // page URL slug
		'sqlf_page_content', // callback function /w content - function below vv
		'dashicons-images-alt', // menu icon
		5 // priority
	);
}
// COLLECT POST / PAGE TITLE, URL, LAST MODIFIED
function sqlf_page_content(){
    // CREATE ARRAY FOR JSON
    $page_links = array();
    // GET PUBLIC POST TYPES
    $args = array(
        'public'   => true,
        'exclude_from_search' => false
    );
    // GET POST TYPES
    $post_types = get_post_types( $args, 'names', 'and' );
    // DECLARE PAGE META DATA VAR
    $page_meta = ""; 
    // REMOVE ATTACHMENT
    unset($post_types['attachment']);
    // LOOP THROUGH POST TYPES
    foreach ( $post_types  as $post_type ) :
        $page_meta .= "<h1 style=\"text-transform: uppercase;\">".$post_type."</h1>";
        $post_args = array(
            'post_type'	   => $post_type,
            'order'        => 'ASC',
            'orderby'      => 'post_title',
            'numberposts'  => -1
        );
        // GET POST TYPE POSTS
        $posts = get_posts( $post_args );
        if(!$posts) {
            $page_meta .= "No Posts";
        }
        // POST META INFO
        foreach ( $posts as $ind_post ) :
            $link = get_permalink($ind_post->ID);
            $title = $ind_post->post_title;
            $lastMod = get_the_time('m-d-Y | h:m A', $ind_post->ID); 
            // PRINT POST INFO
            $page_meta .= "> <strong>".$title."</strong> | ".$link." | ".$lastMod."<br>";
            // ADD LINKS TO JSON ARRAY
            $page_meta_json[] = array(
                'link'      => $url,
                'title'     => $ind_post->post_title,
                'modified'  => $lastMod
            );
        // END LOOP OF POSTS
        endforeach;
    // END LOOP OF POST TYPES    
    endforeach;
    wp_reset_postdata();
    // GET SITE INFORMATION
    $upload_dir = wp_upload_dir();
    $blog_title = get_bloginfo( 'name' );
    $blog_url = get_bloginfo( 'wpurl' );
    $blog_admin = get_bloginfo( 'admin_email' );
    $blog_modified = get_lastpostmodified();
    // SITE INFO
    $site_info[] = array(
        'name' => $blog_title,
        's_url' => $blog_url,
        'last_modified' => $blog_modified
    );
    //OUTPUT AS JSON
    $output = json_encode($page_meta_json);
    // GET UPLOAD DIRECTORY
    $upload_dir = wp_upload_dir();
    $path_dir = $upload_dir['path'];
    $url_dir = $upload_dir['url'];
    // CREATE FILE NAME
    $nblog_title = str_replace(' ', '', $blog_title);
    $file_name = $nblog_title."-posts.json";
    //ASSEMBLE LINK TO JSON FILE
    $sqlf = $url_dir."/".$file_name;
    $sqlf_link = "<a id='sqlf-link' href=".$sqlf.">".$sqlf."</a>";
    $jfilename = $path_dir."/".$file_name;
    // WRITE JSON FILE
    $jfile_write = fopen("$jfilename", "w+");
    fwrite($jfile_write, $output);
    chmod($jfilename, 0777);
    fclose($jfile_write);
    // OUTPUT LINK TO JSON FILE & LIST OF POSTS / PAGES
    echo "<div style=\"width:80%;padding:3rem;margin:0 auto;margin-top:50px;\">
        <h1>Site Quality Link Finder</h1><p>Access JSON here: "
        .$sqlf_link
        ."</p><hr>"
        .$page_meta
        ."</div>";
}