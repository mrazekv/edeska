<?php
/**
* Plugin Name: Uredni deska
* Plugin URI: https://www.yourwebsiteurl.com/
* Description: Zobrazeni uredni desky (komunikace pres API)
* Version: 1.0
* Author: Vojta Mrazek
* Author URI: http://yourwebsiteurl.com/
**/


define( 'UREDNIDESKA__PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
#define( 'UREDNIDESKA_DELETE_LIMIT', 100000 );

#register_activation_hook( __FILE__, array( 'UREDNIDESKA', 'plugin_activation' ) );
#register_deactivation_hook( __FILE__, array( 'UREDNIDESKA', 'plugin_deactivation' ) );

#require_once( UREDNIDESKA__PLUGIN_DIR . 'class.UREDNIDESKA.php' );
require_once( UREDNIDESKA__PLUGIN_DIR . 'class.urednideska-widget.php' );


$wp_embed->register_handler(
    'urednideska',
    '#(https?://edeska\.olomucany\.cz/.*)#i',
    'urednideska_embed_handler_ud'
);
 

function urednideska_getdate($d) {
    if($d)
        return (new DateTime($d))->format("j. n. Y");
    return "";
}

add_action( 'wp_head', function () {
    ?> 
    <style type="text/css">
        .ud-badge-block {
            color: white; font-size: 10px; padding: 2px 5px; float:right; border-radius: 3px;
        }

        a.ud-file {
            border: 1px solid gray; padding: 2px 5px; color: #333; border-radius: 3px; text-decoration: none;    
            margin: 2px 2px;
            display: inline-block;
        }


        a.ud-file:hover {
            background: #333;
            color: white;
        }

        .ud-badge {
            padding: 2px 5px; color: white; border-radius: 3px;
        }
    </style>
    <?php
} );


function urednideska_embed_handler_ud( $matches, $attr, $url, $rawattr ) {



    $json = file_get_contents($url);
    $json_data = json_decode($json, true);

    $embed = '<ul class="site-archive-posts">';
    foreach($json_data as $d) {
        $d=(object)$d;
        $embed .= '
    <li class="site-archive-post post-nothumbnail post-32 post type-post status-publish format-standard hentry category-nezarazene" style="list-style-type: none;">

		<div class="site-column-widget-wrapper clearfix">
			<!-- ws fix
			--><div class="entry-preview">
				<div class="entry-preview-wrapper clearfix">
					<h2 class="entry-title"><a href="' . $d->link . '" title="">' . $d->name . '</a></h2>
                    <p class="entry-excerpt">' . $d->description . "<br>"; /* . '</p>';

        $embed .= '<p class="entry-excerpt">';*/
        foreach($d->files as $f) {
            $img = '<span class="dashicons dashicons-format-aside"></span> ';
            if($f["mime"] == "application/pdf")
                $img = '<span class="dashicons dashicons-pdf"></span>';
            $embed .= "<a href='{$f['link']}' target='_blank' class='ud-file'>$img {$f['name']}</a>";
        }
        $embed .= '</p>';
                                        
        $embed .=   '<p class="entry-descriptor">
                        <span class="entry-descriptor-span"><time class="entry-date published">' . urednideska_getdate($d->show) . '</time> - ' . urednideska_getdate($d->hide) . '</span>
                        <span class="pull-right ud-badge" style="background: ' . $d->color . ';">' . $d->category . '</span>    
                    </p>				</div><!-- .entry-preview-wrapper .clearfix -->
			</div><!-- .entry-preview -->
		</div><!-- .site-column-widget-wrapper .clearfix -->

    </li>
    ';
    }

    $embed .= "</ul>";
    if(!$json_data) {
        $embed = "Žádné záznamy k zobrazení";
    }
    return $embed;
}
