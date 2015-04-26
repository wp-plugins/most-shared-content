<?php
/*
Plugin Name: Most shared content
Plugin URI: http://imtips.co/most-shared-content.html
Description: Checks facebook share count for each post and displays the most shared content of your blog.
Author: Shabbir Bhimani
Version: 0.2
Author URI: http://imtips.co/
*/

add_action ( 'admin_menu', 'shared_plugin_menu');
function shared_plugin_menu() {
    add_options_page('My Plugin Options', 'Most shared content', 'manage_options', 'shared', 'shared_plugin_options');
}

// Function from StackExchange
function url_get_contents($url="graph.facebook.com",$useragent='cURL',$headers=false,
    $follow_redirects=false,$debug=false) {
    # initialise the CURL library
    $ch = curl_init();

    # specify the URL to be retrieved
    curl_setopt($ch, CURLOPT_URL,$url);

    # we want to get the contents of the URL and store it in a variable
    curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);

    # specify the useragent: this is a required courtesy to site owners
    curl_setopt($ch, CURLOPT_USERAGENT, $useragent);

    # ignore SSL errors
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    # return headers as requested
    if ($headers==true){
        curl_setopt($ch, CURLOPT_HEADER,1);
    }

    # only return headers
    if ($headers=='headers only') {
        curl_setopt($ch, CURLOPT_NOBODY ,1);
    }

    # follow redirects - note this is disabled by default in most PHP installs from 4.4.4 up
    if ($follow_redirects==true) {
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    }

    # if debugging, return an array with CURL's debug info and the URL contents
    if ($debug==true) {
        $result['contents']=curl_exec($ch);
        $result['info']=curl_getinfo($ch);
    }

    # otherwise just return the contents as a variable
    else $result=curl_exec($ch);

    # free resources
    curl_close($ch);

    # send back the data
    return $result;
}
add_filter( 'manage_edit-post_columns', 'add_new_columns');
function add_new_columns( $columns ) {
    $column_meta = array( 'meta' => 'Shares' );
    $columns = array_slice( $columns, 0, 5, true ) + $column_meta + array_slice( $columns, 5, NULL, true );
    return $columns;
}
add_action( 'manage_posts_custom_column' , 'custom_columns' );
function custom_columns( $column ) {
    global $post;

    switch ( $column ) {
        case 'meta':
            $metaData = get_post_meta( $post->ID, 'MostSharedContent_ShareCount', true );
            print_r($metaData);
            break;
    }
}

function shared_plugin_options() {
    global $post;
    $meta_field = 'MostSharedContent_ShareCount';
    $posts_per_page = 20;
?>
<div class="wrap">
<?php if($_REQUEST['regen'] == 1) { ?>
<h2>Generating Shares ...</h2>
<ul>
<?php
    $pageno = isset($_REQUEST['pageno'])?intval($_REQUEST['pageno']):1;
    $offset = ($pageno-1) * $posts_per_page;
    $args = array( 'posts_per_page' => $posts_per_page, 'offset'=> $offset);
    $sharedposts = get_posts( $args );
    foreach ( $sharedposts as $post ) : setup_postdata( $post );
    $url = get_the_permalink();
    $fburl = "https://graph.facebook.com/?ids=$url";
    $json=url_get_contents($fburl);
    $fbdata = json_decode($json, true);
    $post_id = get_the_ID();
    $meta_value = isset($fbdata[$url]['shares'])?$fbdata[$url]['shares']:0;
    update_post_meta($post_id, $meta_field, $meta_value);
    ?>
<li>
<a href="<?php echo $url ?>"><?php the_title(); ?></a> - Shares (<?php echo $meta_value ?>)
</li>
<?php
    endforeach;
    wp_reset_postdata();
    if(count($sharedposts) == $posts_per_page) { ?>
<li><a href="<?php echo admin_url()?>options-general.php?page=shared&regen=1&pageno=<?php echo ++$pageno ?>" class="button button-primary">Next</a></li>
<?php } else { ?>
<li><a href="<?php echo admin_url()?>options-general.php?page=shared" class="button button-primary">Finish</a></li>
<?php } ?>
</ul>
<?php } else { ?>
<h2>Most Shared Content</h2>
<ul>
<?php
    $pageno = isset($_REQUEST['pageno'])?intval($_REQUEST['pageno']):1;
    $offset = ($pageno-1) * $posts_per_page;
    $args = array( 'posts_per_page' => $posts_per_page, 'offset'=> $offset, 'meta_key' => $meta_field, 'orderby' => 'meta_value_num');
    $sharedposts = get_posts( $args );
    foreach ( $sharedposts as $post ) : setup_postdata( $post );
        $post_id = get_the_ID();
        list($meta_value) = get_post_custom_values($meta_field, $post_id);
        if($meta_value == NULL) break;
    ?>
<li>
<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a> - Shares (<?php echo $meta_value ?>)
</li>
<?php
    endforeach;
    wp_reset_postdata();
    if(count($sharedposts) == $posts_per_page) : ?>
<li><a href="<?php echo admin_url()?>options-general.php?page=shared&pageno=<?php echo ++$pageno ?>" class="button button-primary">Next</a></li>
<?php endif ?>
<li><a href="<?php echo admin_url()?>options-general.php?page=shared&regen=1" class="button button-primary">Generate Shares</a></li>
</ul>
<?php } ?>
</div>
<?php
}
