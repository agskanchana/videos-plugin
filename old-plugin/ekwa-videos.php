<?php
/**
* Plugin Name: Ekwa Videos Plugin
* Plugin URI: https://docs.google.com/document/d/1hcpZ7p3z7EvMtEzyTqr_0fuDedF44BVQhCrJeepxkNM/
* Description: This is the Plugin for add service/dmtv videos for ekwa client websites with enhanced GA4 tracking
* Version: 2.1
* Author: Webmaster Team of Ekwa
* Author URI: https://www.ekwa.com
* Last Update: 05/08/2025
**/

/* generate custom fields */
function ekwa_acf_add_local_field_groups() {

acf_add_local_field_group(array(
	'key' => 'group_5da9485bad169',
	'title' => 'Video Section',
	'fields' => array(
		array(
			'key' => 'field_5dbbde1138e14',
			'label' => 'Service Video Short Code',
			'name' => '',
			'type' => 'message',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => array(
				'width' => '',
				'class' => '',
				'id' => '',
			),
			'message' => 'Use below short code to display the value
<pre id=\'ekv_shortcode\' style=\'background:yellow;width:600px;font-size:24px;\'>
[service_video video_id="VIDEO_ID_FIELD_VALUE"]
</pre>',
			'new_lines' => 'wpautop',
			'esc_html' => 0,
		),

		array(
			'key' => 'field_5daf1f1c6c70f',
			'label' => 'Video Id',
			'name' => 'video_id',
			'type' => 'text',
			'instructions' => 'Please Enter Video or Youtube Video Id
For Example: "392644036" from https://vimeo.com/392644036',
			'required' => 1,
			'conditional_logic' => 0,
			'wrapper' => array(
				'width' => '',
				'class' => '',
				'id' => '',
			),
			'default_value' => '',
			'placeholder' => '',
			'prepend' => '',
			'append' => '',
			'maxlength' => '',
		),
		array(
			'key' => 'field_5e8161d25005e',
			'label' => 'Video Display Type',
			'name' => 'video_display_type',
			'type' => 'radio',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => array(
				'width' => '',
				'class' => '',
				'id' => '',
			),
			'choices' => array(
				'embedded' => 'Embeded Video',
				'onClick' => 'On Click Load',
			),
			'allow_null' => 0,
			'other_choice' => 0,
			'default_value' => 'embedded',
			'layout' => 'horizontal',
			'return_format' => 'value',
			'save_other_choice' => 0,
		),
		array(
			'key' => 'field_5daf1e786c70e',
			'label' => 'Video Type',
			'name' => 'video_type',
			'type' => 'radio',
			'instructions' => '',
			'required' => 1,
			'conditional_logic' => 0,
			'wrapper' => array(
				'width' => '',
				'class' => '',
				'id' => '',
			),
			'choices' => array(
				'vimeo' => 'Vimeo',
				'youtube' => 'Youtube',
			),
			'allow_null' => 0,
			'other_choice' => 0,
			'default_value' => 'vimeo',
			'layout' => 'horizontal',
			'return_format' => 'value',
			'save_other_choice' => 0,
		),
		array(
			'key' => 'field_5daf20b02e628',
			'label' => 'Add to Video Gallery?',
			'name' => 'add_to_video_gallery',
			'type' => 'radio',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => array(
				'width' => '',
				'class' => '',
				'id' => '',
			),
			'choices' => array(
				'yes' => 'Yes',
				'no' => 'No',
			),
			'allow_null' => 0,
			'other_choice' => 0,
			'default_value' => 'no',
			'layout' => 'horizontal',
			'return_format' => 'value',
			'save_other_choice' => 0,
		),
		array(
			'key' => 'field_5e81610e5005d',
			'label' => 'Video Thumbnail Image',
			'name' => 'video_thumbnail_image',
			'type' => 'image',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => array(
				'width' => '',
				'class' => '',
				'id' => '',
			),
			'return_format' => 'array',
			'preview_size' => 'medium',
			'library' => 'all',
			'min_width' => '',
			'min_height' => '',
			'min_size' => '',
			'max_width' => '',
			'max_height' => '',
			'max_size' => '',
			'mime_types' => '',
		),
		array(
			'key' => 'field_5daf1f326c710',
			'label' => 'Embed Url',
			'name' => 'embed_url',
			'type' => 'text',
			'instructions' => '',
			'required' => 1,
			'conditional_logic' => 0,
			'wrapper' => array(
				'width' => '',
				'class' => '',
				'id' => '',
			),
			'default_value' => '',
			'placeholder' => '',
			'prepend' => '',
			'append' => '',
			'maxlength' => '',
		),
		array(
			'key' => 'field_5da94861d8dcf',
			'label' => 'Video Title',
			'name' => 'video_title',
			'type' => 'text',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => array(
				'width' => '',
				'class' => '',
				'id' => '',
			),
			'default_value' => '',
			'placeholder' => '',
			'prepend' => '',
			'append' => '',
			'maxlength' => '',
		),
		array(
			'key' => 'field_5daf1f836c711',
			'label' => 'Video Title Visible?',
			'name' => 'video_title_display',
			'type' => 'radio',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => array(
				'width' => '',
				'class' => '',
				'id' => '',
			),
			'choices' => array(
				'yes' => 'Yes',
				'no' => 'No',
			),
			'allow_null' => 0,
			'other_choice' => 0,
			'default_value' => 'yes',
			'layout' => 'horizontal',
			'return_format' => 'value',
			'save_other_choice' => 0,
		),
		array(
			'key' => 'field_5da9487bd8dd0',
			'label' => 'Video Description',
			'name' => 'video_description',
			'type' => 'textarea',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => array(
				'width' => '',
				'class' => '',
				'id' => '',
			),
			'default_value' => '',
			'placeholder' => '',
			'maxlength' => '',
			'rows' => '',
			'new_lines' => '',
		),
		array(
			'key' => 'field_5daf1fe26c712',
			'label' => 'Video Description Visible?',
			'name' => 'video_description_visible',
			'type' => 'radio',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => array(
				'width' => '',
				'class' => '',
				'id' => '',
			),
			'choices' => array(
				'yes' => 'Yes',
				'no' => 'No',
			),
			'allow_null' => 0,
			'other_choice' => 0,
			'default_value' => 'no',
			'layout' => 'horizontal',
			'return_format' => 'value',
			'save_other_choice' => 0,
		),
		array(
			'key' => 'field_5da949ccd8dd5',
			'label' => 'Video Transcript',
			'name' => 'video_transcript',
			'type' => 'wysiwyg',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => array(
				'width' => '',
				'class' => '',
				'id' => '',
			),
			'default_value' => '',
			'tabs' => 'all',
			'toolbar' => 'full',
			'media_upload' => 1,
			'delay' => 0,
		),
		array(
			'key' => 'field_5da94893d8dd1',
			'label' => 'Video Duration',
			'name' => 'video_duration',
			'type' => 'text',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => array(
				'width' => '',
				'class' => '',
				'id' => '',
			),
			'default_value' => '',
			'placeholder' => 'T01M15S',
			'prepend' => '',
			'append' => '',
			'maxlength' => '',
		),
		array(
			'key' => 'field_5da948bbd8dd2',
			'label' => 'Update Date',
			'name' => 'update_date',
			'type' => 'text',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => array(
				'width' => '',
				'class' => '',
				'id' => '',
			),
			'default_value' => '',
			'placeholder' => '2019-08-19T08:00:00+08:00',
			'prepend' => '',
			'append' => '',
			'maxlength' => '',
		),
		array(
			'key' => 'field_5da948f7d8dd3',
			'label' => 'Thumbnail Url',
			'name' => 'thumbnail_url',
			'type' => 'text',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => array(
				'width' => '',
				'class' => '',
				'id' => '',
			),
			'default_value' => '',
			'placeholder' => '',
			'prepend' => '',
			'append' => '',
			'maxlength' => '',
		),
		array(
			'key' => 'field_5dce16708f707',
			'label' => 'Main Wrapper Class',
			'name' => 'main_wrapper_class',
			'type' => 'text',
			'instructions' => 'Specific class form the main wrapper of Ekwa Video section. (To make easy the styling)',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => array(
				'width' => '',
				'class' => '',
				'id' => '',
			),
			'default_value' => '',
			'placeholder' => '',
			'prepend' => '',
			'append' => '',
			'maxlength' => '',
		),
		array(
			'key' => 'field_5dce16ab8f708',
			'label' => 'Transcript Wrapper Class',
			'name' => 'transcript_wrapper_class',
			'type' => 'text',
			'instructions' => 'Specific class for video transcript section (To make easy the styling)',
			'required' => 0,
			'conditional_logic' => 0,
			'wrapper' => array(
				'width' => '',
				'class' => '',
				'id' => '',
			),
			'default_value' => '',
			'placeholder' => '',
			'prepend' => '',
			'append' => '',
			'maxlength' => '',
		),
	),
	'location' => array(
		array(
			array(
				'param' => 'post_type',
				'operator' => '==',
				'value' => 'ekwa_service_video',
			),
		),
	),
	'menu_order' => 0,
	'position' => 'normal',
	'style' => 'default',
	'label_placement' => 'top',
	'instruction_placement' => 'label',
	'hide_on_screen' => '',
	'active' => true,
	'description' => '',
));

}

add_action('acf/init', 'ekwa_acf_add_local_field_groups');
 /* End custom fields */


 /**
  * Adds JavaScript code to be added to the admin page to generate auto video code
  */
 function ekv_admin_js() {
 	
 ?>
 <script type="text/javascript">
 	var ekv_shortcode = document.getElementById("acf-field_5daf1f1c6c70f").value;
 	ekv_shortcode = '[service_video video_id="'+ekv_shortcode+'"]';
 	document.getElementById("ekv_shortcode").textContent = ekv_shortcode;
 </script>
 <?php
 		
 }
 add_action('acf/input/admin_footer', 'ekv_admin_js');


/* create custom post type */
function ekwa_service_video_post_type() {

  $labels = array(
    'name' => 'Ekwa Videos',
    'singular_name' => 'Video',
    'add_new' => 'Add New Video'
  );

  $args = array(
    'labels'      => $labels,
    'supports'      => array( 'title', 'thumbnail', ),
    'hierarchical'    => false,
    'public'      => false,
    'show_ui'     => true,
    'show_in_menu'    => true,
    'menu_position'   => 7,
    'menu_icon'     => 'dashicons-format-video',
    'show_in_admin_bar' => true,
    'show_in_nav_menus' => false,
    'can_export'    => true,
    'has_archive'   => false,                
    'exclude_from_search' => true,
    'publicly_queryable'  => false,
    'query_var'     => false,
    'capability_type' => 'post'
  ); 

  register_post_type( 'ekwa_service_video', $args );

}
add_action( 'init', 'ekwa_service_video_post_type');

/* dmtv short code */
add_shortcode('service_video',function($atts){
ob_start();

$video_id=$atts["video_id"];

$args = array(
    'post_type' => 'ekwa_service_video',
	'numberposts'	=> -1,
	'meta_key'		=> 'video_id',
	'meta_value'	=> $video_id
);

$get_videos = get_posts($args);	


/* Video Settings Callback */
$ekwa_video_settings = (array) get_option( 'ekvideo-plugin-settings' );

// Lazyload Class
$field_lazyload = "ekv_field_1_1";
$lazyload_class = esc_attr( $ekwa_video_settings[$field_lazyload] );

// Lazyload Prefix
$prefix_lazyload = "ekv_field_1_2";
$lazyload_prefix = esc_attr( $ekwa_video_settings[$prefix_lazyload] );

// Transcript Icon
$ekv_transcript_ico = "ekv_field_1_3";
$transcript_ico = esc_attr( $ekwa_video_settings[$ekv_transcript_ico] );

// Video Main Wrapper Class
$ekv_wrapper = "ekv_field_2_1";
$ekv_css_main = esc_attr( $ekwa_video_settings[$ekv_wrapper] );


// frond-end codes
if($get_videos) {  
    // Remove video structured data for AMP
    if ( function_exists( 'is_amp_endpoint' ) && is_amp_endpoint() ) {
        $video_str_data = '';
    } else {
        $video_str_data = ' itemprop="video" itemscope="" itemtype="http://schema.org/VideoObject"';
    }
    ?>
	<?php foreach ($get_videos as $get_video){ 

        	if ($lazyload_class == "lazyload") {
        	?>
        		<div <?=$video_str_data;?> class="ekv-wrapper <?php echo $ekv_css_main; ?> <?php echo get_field('main_wrapper_class', $get_video->ID); ?> lazyload" data-script="<?php echo plugin_dir_url(__FILE__); ?>ekwa-video-script.js" >
        <?php
        	} else {
        ?>
		<!--Video Markup -->
			<div <?= $video_str_data; ?> class="ekv-wrapper <?php echo $ekv_css_main; ?> <?php echo get_field('main_wrapper_class', $get_video->ID); ?>">
		<?php } ?>
			<!-- If Video add to gallery -->
			<?php if(get_field('video_title_display', $get_video->ID)=="yes"){ ?>
				<h2><span itemprop="name"><?php echo get_field('video_title', $get_video->ID); ?></span></h2>
			<?php }else{ ?>
				<meta itemprop="name" content="<?php echo get_field('video_title', $get_video->ID); ?>">
			<?php } ?>
			<!-- / -->

			<meta itemprop="duration" content="<?php echo get_field('video_duration', $get_video->ID); ?>">
			<meta itemprop="uploadDate" content="<?php echo get_field('update_date', $get_video->ID); ?>">	
			<meta itemprop="thumbnailURL" content="<?php echo get_field('thumbnail_url', $get_video->ID); ?>">
			<meta itemprop="interactionCount" content="1" />
			<meta itemprop="embedURL" content="<?php echo get_field('embed_url', $get_video->ID); ?>">
			
			
			<!-- If Vimeo or Youtube Video -->
            <?php if(get_field('video_type', $get_video->ID)=="vimeo"){ ?>

        	<?php
        	// for AMP Pages
        	if (function_exists( 'is_amp_endpoint' ) && is_amp_endpoint()) {
        		$get_vimeo_id = getVimeoVideoIdFromUrl(get_field('embed_url', $get_video->ID));        		
        		echo $amp_video_string = '<amp-vimeo data-videoid="'.$get_vimeo_id.'" layout="responsive" width="500" height="281"></amp-vimeo>';
        	} else {
	      	// for NON AMP Pages					
					 if(get_field('video_display_type', $get_video->ID)=="onClick"){ ?>

                    <?php $service_video_id=str_replace("https://player.vimeo.com/video/","",get_field('embed_url', $get_video->ID)); ?>
                    <?php $service_video_id=str_replace("?title=0&byline=0&portrait=0","",$service_video_id); ?>

                    <div class="player-wrap plugin-responsive">
                        <div class="player" data-id="<?php echo $service_video_id; ?>" data-provider="vimeo"> 
                            <?php $video_thumbnail_image = get_field('video_thumbnail_image', $get_video->ID); ?>
                            <?php if( !empty( $video_thumbnail_image ) ){  ?>                   
                                <img class="<?php echo $lazyload_class; ?> image-responsive" <?php echo $lazyload_prefix; ?>src="<?php echo esc_url($video_thumbnail_image['url']); ?>" alt="<?php echo esc_attr($video_thumbnail_image['alt']); ?>">
                                <span class="playicon"></span>
                            <?php }  ?>
                        </div>

                    </div>
                    <script src="https://player.vimeo.com/api/player.js"></script>
                    <script>
                        // GA4 Vimeo Video Tracking for onClick videos
                        (function() {
                            var videoData = {
                                title: "<?php echo esc_js(get_field('video_title', $get_video->ID)); ?>",
                                id: "<?php echo esc_js($service_video_id); ?>",
                                provider: 'vimeo'
                            };
                            
                            // GA4 Event Sender
                            function sendGA4VideoEvent(action, params) {
                                params = params || {};
                                
                                if (typeof gtag !== 'undefined') {
                                    gtag('event', action, {
                                        video_title: params.video_title || videoData.title,
                                        video_provider: videoData.provider,
                                        video_id: videoData.id,
                                        video_url: window.location.href,
                                        video_current_time: params.video_current_time || 0,
                                        video_duration: params.video_duration || 0,
                                        video_percent: params.video_percent || 0
                                    });
                                    console.log('GA4 Video Event:', action, params);
                                }
                            }
                            
                            // Track clicks on video player element to load and start video
                            var playerElement = document.querySelector('.player[data-provider="vimeo"][data-id="' + videoData.id + '"]');
                            if (playerElement) {
                                playerElement.addEventListener('click', function() {
                                    console.log('Vimeo onClick video clicked:', videoData.title);
                                    
                                    // Send initial click event
                                    sendGA4VideoEvent('video_start', {
                                        video_title: videoData.title
                                    });
                                    
                                    // Create and insert iframe for Vimeo
                                    var iframe = document.createElement('iframe');
                                    iframe.width = '100%';
                                    iframe.height = '100%';
                                    iframe.src = 'https://player.vimeo.com/video/' + videoData.id + '?autoplay=1&api=1';
                                    iframe.frameBorder = '0';
                                    iframe.allowFullscreen = true;
                                    iframe.allow = 'autoplay; fullscreen';
                                    
                                    // Replace the thumbnail with iframe
                                    playerElement.innerHTML = '';
                                    playerElement.appendChild(iframe);
                                    
                                    // Setup tracking for the new iframe after it loads
                                    setTimeout(function() {
                                        try {
                                            var vimeoPlayer = new Vimeo.Player(iframe);
                                            var milestones = [25, 50, 75];
                                            var triggered = {};
                                            
                                            console.log('Vimeo onClick player initialized for:', videoData.title);
                                            
                                            // Video progress tracking
                                            vimeoPlayer.on('timeupdate', function(data) {
                                                var percent = Math.round(data.percent * 100);
                                                
                                                milestones.forEach(function(milestone) {
                                                    if (percent >= milestone && !triggered[milestone]) {
                                                        triggered[milestone] = true;
                                                        console.log('Vimeo onClick video progress:', milestone + '%', videoData.title);
                                                        sendGA4VideoEvent('video_progress', {
                                                            video_title: videoData.title,
                                                            video_percent: milestone,
                                                            video_current_time: Math.round(data.seconds || 0),
                                                            video_duration: Math.round(data.duration || 0)
                                                        });
                                                    }
                                                });
                                            });
                                            
                                            // Video completion
                                            vimeoPlayer.on('ended', function() {
                                                console.log('Vimeo onClick video completed:', videoData.title);
                                                sendGA4VideoEvent('video_complete', {
                                                    video_title: videoData.title,
                                                    video_percent: 100
                                                });
                                            });
                                            
                                            // Video pause
                                            vimeoPlayer.on('pause', function() {
                                                console.log('Vimeo onClick video paused:', videoData.title);
                                                sendGA4VideoEvent('video_pause', {
                                                    video_title: videoData.title
                                                });
                                            });
                                            
                                        } catch (error) {
                                            console.error('Vimeo onClick tracking setup error:', error);
                                        }
                                    }, 1000);
                                });
                            }
                        })();
                    </script>
           
                <?php } else { ?>
                    <div id="schema-videoobject" class="player-wrap plugin-responsive">
                    	<?php
                    		$base_url = get_field('embed_url', $get_video->ID);

							// Check if the URL already has a query string
							$separator = (strpos($base_url, '?') !== false) ? '&' : '?';

							$final_url = $base_url . $separator . 'api=1&player_id=vimeo-embedded-' . $get_video->ID;

                    	?>
                        <iframe id="vimeo-embedded-<?php echo $get_video->ID; ?>" width="853" height="480" class="<?php echo $lazyload_class; ?>" <?php echo $lazyload_prefix; ?>src="<?php echo esc_url($final_url); ?>" frameborder="0" allowfullscreen allow="autoplay"></iframe>
                        <script src="https://player.vimeo.com/api/player.js"></script>
                        <script>
                            // GA4 Vimeo Video Tracking for embedded videos
                            (function() {
                                var videoData = {
                                    title: "<?php echo esc_js(get_field('video_title', $get_video->ID)); ?>",
                                    id: "<?php echo esc_js(getVimeoVideoIdFromUrl(get_field('embed_url', $get_video->ID))); ?>",
                                    provider: 'vimeo',
                                    iframeId: 'vimeo-embedded-<?php echo $get_video->ID; ?>'
                                };
                                
                                // GA4 Event Sender
                                function sendGA4VideoEvent(action, params) {
                                    params = params || {};
                                    
                                    if (typeof gtag !== 'undefined') {
                                        gtag('event', action, {
                                            video_title: params.video_title || videoData.title,
                                            video_provider: videoData.provider,
                                            video_id: videoData.id,
                                            video_url: window.location.href,
                                            video_current_time: params.video_current_time || 0,
                                            video_duration: params.video_duration || 0,
                                            video_percent: params.video_percent || 0
                                        });
                                        console.log('GA4 Video Event:', action, params);
                                    }
                                }
                                
                                // Initialize Vimeo tracking
                                function initVimeoTracking() {
                                    var iframe = document.getElementById(videoData.iframeId);
                                    if (iframe && typeof Vimeo !== 'undefined') {
                                        try {
                                            var player = new Vimeo.Player(iframe);
                                            var milestones = [25, 50, 75];
                                            var triggered = {}; // Moved outside event handlers
                                            
                                            console.log('Vimeo player initialized for:', videoData.title);
                                            
                                            // Video start
                                            player.on('play', function() {
                                                console.log('Vimeo video started:', videoData.title);
                                                sendGA4VideoEvent('video_start', {
                                                    video_title: videoData.title
                                                });
                                            });
                                            
                                            // Video progress
                                            player.on('timeupdate', function(data) {
                                                var percent = Math.round(data.percent * 100);
                                                
                                                // Check milestones
                                                milestones.forEach(function(milestone) {
                                                    if (percent >= milestone && !triggered[milestone]) {
                                                        triggered[milestone] = true;
                                                        console.log('Vimeo video progress:', milestone + '%', videoData.title);
                                                        sendGA4VideoEvent('video_progress', {
                                                            video_title: videoData.title,
                                                            video_percent: milestone,
                                                            video_current_time: Math.round(data.seconds || 0),
                                                            video_duration: Math.round(data.duration || 0)
                                                        });
                                                    }
                                                });
                                            });
                                            
                                            // Video complete
                                            player.on('ended', function() {
                                                console.log('Vimeo video completed:', videoData.title);
                                                sendGA4VideoEvent('video_complete', {
                                                    video_title: videoData.title,
                                                    video_percent: 100
                                                });
                                            });
                                            
                                            // Video pause
                                            player.on('pause', function() {
                                                console.log('Vimeo video paused:', videoData.title);
                                                sendGA4VideoEvent('video_pause', {
                                                    video_title: videoData.title
                                                });
                                            });
                                            
                                        } catch (error) {
                                            console.error('Vimeo tracking error:', error);
                                        }
                                    }
                                }
                                
                                // Initialize after iframe loads
                                setTimeout(initVimeoTracking, 1000);
                                
                                // Try again if first attempt fails
                                setTimeout(initVimeoTracking, 3000);
                            })();
                        </script>
                    </div>
                <?php 
            	} 
            } // end of non amp
                } else { 

                // for AMP Pages
                if (function_exists( 'is_amp_endpoint' ) && is_amp_endpoint()) {
                	$get_youtube_id = getYouTubeVideoIdFromUrl(get_field('embed_url', $get_video->ID));  
                	echo $amp_video_string = '<amp-youtube data-videoid="'.$get_youtube_id.'" layout="responsive" width="480" height="270"></amp-youtube>';
                } else {
            	     // For NON AMP Pages
            	    if(get_field('video_display_type', $get_video->ID)=="onClick") { ?>

                    <?php $service_video_id=str_replace("https://www.youtube.com/embed/","",get_field('embed_url', $get_video->ID)); ?>

                    <div class="player-wrap plugin-responsive">
                        <div class="player" data-id="<?php echo $service_video_id; ?>" data-provider="youtube"> 
                            <?php $video_thumbnail_image = get_field('video_thumbnail_image', $get_video->ID); ?>
                            <?php if( !empty( $video_thumbnail_image ) ){  ?>                   
                                <img class="<?php echo $lazyload_class; ?> image-responsive" <?php echo $lazyload_prefix; ?>src="<?php echo esc_url($video_thumbnail_image['url']); ?>" alt="<?php echo esc_attr($video_thumbnail_image['alt']); ?>">
                                <span class="playicon"></span>
                            <?php }  ?>                            
                        </div>                        
                    </div>
                    <script>
                        // GA4 YouTube Video Tracking for onClick videos
                        (function() {
                            var videoData = {
                                title: "<?php echo esc_js(get_field('video_title', $get_video->ID)); ?>",
                                id: "<?php echo esc_js($service_video_id); ?>",
                                provider: 'youtube'
                            };
                            
                            // GA4 Event Sender
                            function sendGA4VideoEvent(action, params) {
                                params = params || {};
                                
                                if (typeof gtag !== 'undefined') {
                                    gtag('event', action, {
                                        video_title: params.video_title || videoData.title,
                                        video_provider: videoData.provider,
                                        video_id: videoData.id,
                                        video_url: window.location.href,
                                        video_current_time: params.video_current_time || 0,
                                        video_duration: params.video_duration || 0,
                                        video_percent: params.video_percent || 0
                                    });
                                    console.log('GA4 Video Event:', action, params);
                                }
                            }
                            
                            // Track clicks on video player element to load and start video
                            var playerElement = document.querySelector('.player[data-provider="youtube"][data-id="' + videoData.id + '"]');
                            if (playerElement) {
                                playerElement.addEventListener('click', function() {
                                    console.log('YouTube onClick video clicked:', videoData.title);
                                    
                                    // Send initial click event
                                    sendGA4VideoEvent('video_start', {
                                        video_title: videoData.title
                                    });
                                    
                                    // Create and insert iframe for YouTube
                                    var iframe = document.createElement('iframe');
                                    iframe.width = '100%';
                                    iframe.height = '100%';
                                    iframe.src = 'https://www.youtube.com/embed/' + videoData.id + '?autoplay=1&enablejsapi=1&origin=' + encodeURIComponent(window.location.origin);
                                    iframe.frameBorder = '0';
                                    iframe.allowFullscreen = true;
                                    iframe.allow = 'autoplay; fullscreen';
                                    iframe.id = 'youtube-onclick-' + videoData.id;
                                    
                                    // Replace the thumbnail with iframe
                                    playerElement.innerHTML = '';
                                    playerElement.appendChild(iframe);
                                    
                                    // Load YouTube API if not already loaded
                                    if (typeof YT === 'undefined' || typeof YT.Player === 'undefined') {
                                        var tag = document.createElement('script');
                                        tag.src = 'https://www.youtube.com/iframe_api';
                                        var firstScriptTag = document.getElementsByTagName('script')[0];
                                        firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);
                                    }
                                    
                                    // Setup tracking for the new iframe after YouTube API loads
                                    function setupYouTubeOnClickTracking() {
                                        if (typeof YT !== 'undefined' && YT.Player) {
                                            try {
                                                var milestones = [25, 50, 75];
                                                var triggered = {};
                                                
                                                var player = new YT.Player(iframe, {
                                                    events: {
                                                        'onReady': function(event) {
                                                            console.log('YouTube onClick player ready for:', videoData.title);
                                                        },
                                                        'onStateChange': function(event) {
                                                            if (event.data === YT.PlayerState.PLAYING) {
                                                                // Progress tracking
                                                                var progressInterval = setInterval(function() {
                                                                    try {
                                                                        if (player.getPlayerState() === YT.PlayerState.PLAYING) {
                                                                            var currentTime = player.getCurrentTime();
                                                                            var duration = player.getDuration();
                                                                            if (duration > 0) {
                                                                                var percent = Math.round((currentTime / duration) * 100);
                                                                                
                                                                                milestones.forEach(function(milestone) {
                                                                                    if (percent >= milestone && !triggered[milestone]) {
                                                                                        triggered[milestone] = true;
                                                                                        console.log('YouTube onClick video progress:', milestone + '%', videoData.title);
                                                                                        sendGA4VideoEvent('video_progress', {
                                                                                            video_title: videoData.title,
                                                                                            video_percent: milestone,
                                                                                            video_current_time: Math.round(currentTime),
                                                                                            video_duration: Math.round(duration)
                                                                                        });
                                                                                    }
                                                                                });
                                                                            }
                                                                        } else {
                                                                            clearInterval(progressInterval);
                                                                        }
                                                                    } catch (err) {
                                                                        console.error('YouTube onClick progress tracking error:', err);
                                                                        clearInterval(progressInterval);
                                                                    }
                                                                }, 1000);
                                                            }
                                                            
                                                            if (event.data === YT.PlayerState.PAUSED) {
                                                                console.log('YouTube onClick video paused:', videoData.title);
                                                                sendGA4VideoEvent('video_pause', {
                                                                    video_title: videoData.title
                                                                });
                                                            }
                                                            
                                                            if (event.data === YT.PlayerState.ENDED) {
                                                                console.log('YouTube onClick video completed:', videoData.title);
                                                                sendGA4VideoEvent('video_complete', {
                                                                    video_title: videoData.title,
                                                                    video_percent: 100
                                                                });
                                                            }
                                                        }
                                                    }
                                                });
                                            } catch (error) {
                                                console.error('YouTube onClick tracking setup error:', error);
                                            }
                                        } else {
                                            // Retry if API not ready
                                            setTimeout(setupYouTubeOnClickTracking, 1000);
                                        }
                                    }
                                    
                                    // Initialize tracking
                                    setTimeout(setupYouTubeOnClickTracking, 2000);
                                });
                            }
                        })();
                    </script>                   

                <?php } else { ?>
                        <div id="schema-videoobject" class="player-wrap plugin-responsive">
                        	<?php
                        		$base_url = get_field('embed_url', $get_video->ID);
                        		$youtube_video_id = getYouTubeVideoIdFromUrl($base_url);

								// Check if the URL already has a query string
								$separator = (strpos($base_url, '?') !== false) ? '&' : '?';

								$final_url = $base_url . $separator . 'rel=0&enablejsapi=1&origin=' . urlencode(home_url());

                        	?>
                            <iframe id="youtube-player-<?php echo $get_video->ID; ?>" width="853" height="480" class="<?php echo $lazyload_class; ?>" <?php echo $lazyload_prefix; ?>src="<?php echo esc_url($final_url); ?>" frameborder="0" allowfullscreen allow="autoplay"></iframe>
                            <script>
                                // GA4 YouTube Video Tracking for embedded videos
                                (function() {
                                    var videoData = {
                                        title: "<?php echo esc_js(get_field('video_title', $get_video->ID)); ?>",
                                        id: "<?php echo esc_js($youtube_video_id); ?>",
                                        provider: 'youtube',
                                        iframeId: 'youtube-player-<?php echo $get_video->ID; ?>'
                                    };
                                    
                                    // GA4 Event Sender
                                    function sendGA4VideoEvent(action, params) {
                                        params = params || {};
                                        
                                        if (typeof gtag !== 'undefined') {
                                            gtag('event', action, {
                                                video_title: params.video_title || videoData.title,
                                                video_provider: videoData.provider,
                                                video_id: videoData.id,
                                                video_url: window.location.href,
                                                video_current_time: params.video_current_time || 0,
                                                video_duration: params.video_duration || 0,
                                                video_percent: params.video_percent || 0
                                            });
                                            console.log('GA4 Video Event:', action, params);
                                        }
                                    }
                                    
                                    // Load YouTube API if not already loaded
                                    if (typeof YT === 'undefined' || typeof YT.Player === 'undefined') {
                                        var tag = document.createElement('script');
                                        tag.src = 'https://www.youtube.com/iframe_api';
                                        var firstScriptTag = document.getElementsByTagName('script')[0];
                                        firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);
                                    }
                                    
                                    // Initialize YouTube tracking
                                    function initYouTubeTracking() {
                                        var iframe = document.getElementById(videoData.iframeId);
                                        if (iframe && typeof YT !== 'undefined' && YT.Player) {
                                            try {
                                                var milestones = [25, 50, 75];
                                                var triggered = {}; // Moved outside event handlers
                                                
                                                var player = new YT.Player(iframe, {
                                                    events: {
                                                        'onReady': function(event) {
                                                            console.log('YouTube player ready for:', videoData.title);
                                                        },
                                                        'onStateChange': function(event) {
                                                            if (event.data === YT.PlayerState.PLAYING) {
                                                                console.log('YouTube video started:', videoData.title);
                                                                sendGA4VideoEvent('video_start', {
                                                                    video_title: videoData.title
                                                                });
                                                                
                                                                // Progress tracking
                                                                var progressInterval = setInterval(function() {
                                                                    try {
                                                                        if (player.getPlayerState() === YT.PlayerState.PLAYING) {
                                                                            var currentTime = player.getCurrentTime();
                                                                            var duration = player.getDuration();
                                                                            if (duration > 0) {
                                                                                var percent = Math.round((currentTime / duration) * 100);
                                                                                
                                                                                // Check milestones
                                                                                milestones.forEach(function(milestone) {
                                                                                    if (percent >= milestone && !triggered[milestone]) {
                                                                                        triggered[milestone] = true;
                                                                                        console.log('YouTube video progress:', milestone + '%', videoData.title);
                                                                                        sendGA4VideoEvent('video_progress', {
                                                                                            video_title: videoData.title,
                                                                                            video_percent: milestone,
                                                                                            video_current_time: Math.round(currentTime),
                                                                                            video_duration: Math.round(duration)
                                                                                        });
                                                                                    }
                                                                                });
                                                                            }
                                                                        } else {
                                                                            clearInterval(progressInterval);
                                                                        }
                                                                    } catch (err) {
                                                                        console.error('YouTube progress tracking error:', err);
                                                                        clearInterval(progressInterval);
                                                                    }
                                                                }, 1000);
                                                            }
                                                            
                                                            if (event.data === YT.PlayerState.PAUSED) {
                                                                console.log('YouTube video paused:', videoData.title);
                                                                sendGA4VideoEvent('video_pause', {
                                                                    video_title: videoData.title
                                                                });
                                                            }
                                                            
                                                            if (event.data === YT.PlayerState.ENDED) {
                                                                console.log('YouTube video completed:', videoData.title);
                                                                sendGA4VideoEvent('video_complete', {
                                                                    video_title: videoData.title,
                                                                    video_percent: 100
                                                                });
                                                            }
                                                        }
                                                    }
                                                });
                                            } catch (error) {
                                                console.error('YouTube tracking error:', error);
                                            }
                                        }
                                    }
                                    
                                    // Initialize when API is ready
                                    if (typeof YT !== 'undefined' && YT.Player) {
                                        setTimeout(initYouTubeTracking, 1000);
                                    } else {
                                        window.onYouTubeIframeAPIReady = function() {
                                            setTimeout(initYouTubeTracking, 1000);
                                        };
                                    }
                                })();
                            </script>
                        </div>
                <?php 
            		}                            
             	} // non amp
             }
             	?> 
            <!-- / -->


			<?php if(get_field('video_description_visible', $get_video->ID)=="yes"){ ?>	
				<div itemprop="description" class="transcript-container ekv-description">
					<?php echo get_field('video_description', $get_video->ID); ?>
				</div>
			<?php }else{ ?>
				<meta itemprop="description" content="<?php echo get_field('video_description', $get_video->ID); ?>">
			<?php } ?>
			<!--/ -->

			<!-- If Transcript is not empty -->
			<?php if(get_field('video_transcript', $get_video->ID)!=""){ ?>
			   <div class="video_transcript_btn">
			     <a data-target="#<?php echo $video_id; ?>" class="btn-standard btn-vdo-trans btn-transcript ekv-button" href="javascript:void(0);">Video Transcript 
			     	<span class="trans-icon"><?= html_entity_decode($transcript_ico); ?></span></a>
			   </div>

			     <div id="<?php echo $video_id; ?>" class="transcript-wrapper-del transcript">
			         <div class="transcript-box">
			             <div class="transcript-container ekv-transcript">
			                       <?php echo get_field('video_transcript', $get_video->ID); ?>
			             </div>
			         </div>
			     </div>
			 <?php } ?>
			<!--/ -->

		</div>
		<br />		
		<!--Video Markup -->


		<?php } ?>

	<?php } ?>
    
    <!-- GA4 Video Tracking Script -->
    <script>
        // Global GA4 Video Event Tracker
        window.EkwaGA4VideoTracker = window.EkwaGA4VideoTracker || {
            // Send GA4 video events
            sendEvent: function(action, params) {
                params = params || {};
                
                if (typeof gtag !== 'undefined') {
                    gtag('event', action, {
                        event_category: 'Video',
                        video_title: params.video_title || 'Unknown Video',
                        video_provider: params.video_provider || 'unknown',
                        video_id: params.video_id || '',
                        video_url: params.video_url || window.location.href,
                        video_current_time: params.video_current_time || 0,
                        video_duration: params.video_duration || 0,
                        video_percent: params.video_percent || 0
                    });
                    console.log('GA4 Video Event Sent:', action, params);
                } else {
                    console.warn('GA4 (gtag) not available. Event not sent:', action, params);
                }
            },
            
            // Test function to verify GA4 is working
            test: function() {
                this.sendEvent('video_test', {
                    video_title: 'Test Video',
                    video_provider: 'test',
                    video_id: 'test123'
                });
                console.log('GA4 Video Test Event Sent');
            }
        };
        
        // Log tracker availability
        console.log('Ekwa GA4 Video Tracker initialized');
        
        // Check if GA4 is loaded
        setTimeout(function() {
            if (typeof gtag !== 'undefined') {
                console.log('✅ GA4 (gtag) is available');
            } else {
                console.warn('⚠️ GA4 (gtag) not detected');
            }
        }, 2000);
    </script>

<?php
wp_reset_query();
return ob_get_clean();
});

/* Hook YT related video hidden code part to header */
add_action('wp_head', 'yt_related_hide_css');
function yt_related_hide_css(){

	// Video Extra CSS code
	$ek_video_settings = (array) get_option( 'ekvideo-plugin-settings' );
	$ekv_css = "ekv_field_2_2";
	$ekv_css_style = esc_attr( $ek_video_settings[$ekv_css] );
?>
<!-- Youtube Video Configs for Header -->
<style>
	.player-wrap {position: relative;}.player-wrap.paused:before, .player-wrap.ended:before {top: 18%;width: 100%;height: 70%;content: '';z-index: +99;background: #000;position: absolute;}.player-wrap.paused:after {content: '\25B6';position: absolute;top: 50%;color: #ffff;left: 50%;font-size: 60px;height: 72px;width: 72px;text-align: center;line-height: 72px;margin: -36px;z-index: +100;}.player-wrap.ended:after {content: '\21BB';position: absolute;top: 50%;color: #ffff;left: 50%;font-size: 60px;height: 72px;width: 72px;text-align: center;line-height: 72px;margin: -36px;z-index: +100;}.player-wrap .player{cursor: pointer;}.player-wrap .player .playicon:before {content: "\f01d";position: absolute;margin: auto;top: 0;left: 0;bottom: 0;right: 0;opacity: 0.55;width: 80px;height: 80px;color: #fff;display: inline-block;font-size: 90px;font-family: FontAwesome;line-height: 80px;cursor: pointer;}
	.plugin-responsive { position: relative;padding-bottom: 56.25%;height: 0;}
	.image-responsive {display: block;max-width: 100%;height: auto;}
	.plugin-responsive iframe { position: absolute;top: 0;left: 0; width: 100%; height: 100%;}
    .btn-transcript .trans-icon {display:inline-block;transition:.3s transform ease-in-out;}
    .btn-transcript[aria-expanded="true"] .trans-icon {transform: rotate(90deg);}  
    .transcript-wrapper-del {display: none;}
    .transcript-wrapper-in {display: block}
    <?php echo $ekv_css_style; ?>  
</style>
<!-- Youtube Video Configs -->
<?php
}

/* Hook YT related video hidden code part to footer */
$ek_video_settings = (array) get_option( 'ekvideo-plugin-settings' );
$field_lazyload = "ekv_field_1_1";
$lazyload_class = esc_attr( $ek_video_settings[$field_lazyload] );

if ($lazyload_class != "lazyload") {
	 add_action('wp_footer', 'yt_related_hide_js');
}

function yt_related_hide_js(){
	wp_enqueue_script(
	    'ekwa_video_script', // lowercase name of script
	    plugin_dir_url(__FILE__) . 'ekwa-video-script.js', // url to script
	    array( 'jquery' ), // libraries to use
	    false, // version of script (false is WP version)
	    true // load in footer (true) or head (false)? 
	);

}

// vimeo video ID extract
function getVimeoVideoIdFromUrl($url = '') {    
        $regs = array();    
        $id = '';    
        if (preg_match('%^https?:\/\/(?:www\.|player\.)?vimeo.com\/(?:channels\/(?:\w+\/)?|groups\/([^\/]*)\/videos\/|album\/(\d+)\/video\/|video\/|)(\d+)(?:$|\/|\?)(?:[?]?.*)$%im', $url, $regs)) {
            $id = $regs[3];
        }    
        return $id;    
    }

// youtube video ID extract
    function getYouTubeVideoIdFromUrl($url = '') {    
            $regs = array();    
            $id = '';    
            if (preg_match("/^(?:http(?:s)?:\/\/)?(?:www\.)?(?:m\.)?(?:youtu\.be\/|youtube\.com\/(?:(?:watch)?\?(?:.*&)?v(?:i)?=|(?:embed|v|vi|user)\/))([^\?&\"'>]+)/", $url, $regs)) {
                $id = $regs[1];
            }    
            return $id;    
        }


/* PLUGIN SETTINGS PAGE */
    add_action('admin_menu', 'add_custom_link_into_appearnace_menu');
    function add_custom_link_into_appearnace_menu() {
       add_submenu_page('edit.php?post_type=ekwa_service_video', 'Video Plugin Settings', 'Video Settings', 'manage_options', 'ekvideo-settings', 'ekvideo_options_page');
    }

    /*
    * INPUT VALIDATION:
    * */
    // function ekvideo_settings_validate_and_sanitize( $input ) {

    // 	$settings = (array) get_option( 'ekvideo-plugin-settings' );
    	
    // 	if ( $some_condition == $input['ekv_field_1_1'] ) {
    // 		$output['ekv_field_1_1'] = $input['ekv_field_1_1'];
    // 	} else {
    // 		add_settings_error( 'ekvideo-plugin-settings', 'invalid-ekv_field_1_1', 'You have entered an invalid value' );
    // 	}
    	
    // 	if ( $some_condition == $input['ekv_field_1_2'] ) {
    // 		$output['ekv_field_1_2'] = $input['ekv_field_1_2'];
    // 	} else {
    // 		add_settings_error( 'ekvideo-plugin-settings', 'invalid-ekv_field_1_2', 'You have entered an invalid value' );
    // 	}
    	
    // 	return $output;
    // }


    add_action( 'admin_init', 'ekvideo_admin_init' );
    function ekvideo_admin_init() {
      
      /* 
    	 * http://codex.wordpress.org/Function_Reference/register_setting
    	 * register_setting( $option_group, $option_name, $sanitize_callback );
    	 * The second argument ($option_name) is the option name. It’s the one we use with functions like get_option() and update_option()
    	 * */
      	# With input validation:
      	# register_setting( 'ekvideo-settings-group', 'ekvideo-plugin-settings', 'ekvideo_settings_validate_and_sanitize' );    
      	register_setting( 'ekvideo-settings-group', 'ekvideo-plugin-settings' );
    	
      	/* 
    	 * http://codex.wordpress.org/Function_Reference/add_settings_section
    	 * add_settings_section( $id, $title, $callback, $page ); 
    	 * */	 
      	add_settings_section( 'section-1', __( 'Lazyload Settings', 'textdomain' ), 'section_1_callback', 'ekvideo-plugin' );
    	add_settings_section( 'section-2', __( 'Common Settings', 'textdomain' ), 'section_2_callback', 'ekvideo-plugin' );
    	add_settings_section( 'section-3', __( 'Pre Defined Classes', 'textdomain' ), 'section_3_callback', 'ekvideo-plugin' );
    	
    	/* 
    	 * http://codex.wordpress.org/Function_Reference/add_settings_field
    	 * add_settings_field( $id, $title, $callback, $page, $section, $args );
    	 * */
      	add_settings_field( 'field-1-1', __( 'Lazyload Class <br><small><b>(lozad/ lazyload)</b></small>', 'textdomain' ), 'ekv_field_1_1_callback', 'ekvideo-plugin', 'section-1' );
    	add_settings_field( 'field-1-2', __( 'Prefix of SRC attribute <br><small><b>(data-src/ src)</b></small>', 'textdomain' ), 'ekv_field_1_2_callback', 'ekvideo-plugin', 'section-1' );
    	add_settings_field( 'field-1-3', __( 'Transcript Icon<br><small>(&#x3C;i class=&#x22;fa fa-caret-right&#x22;&#x3E;&#x3C;/i&#x3E;)</small>', 'textdomain' ), 'ekv_field_1_3_callback', 'ekvideo-plugin', 'section-1' );
    	
    	add_settings_field( 'field-2-1', __( 'Wrapper Class', 'textdomain' ), 'ekv_field_2_1_callback', 'ekvideo-plugin', 'section-2' );
    	add_settings_field( 'field-2-2', __( 'CSS Styles', 'textdomain' ), 'ekv_field_2_2_callback', 'ekvideo-plugin', 'section-2' );

    	add_settings_field( 'field-3-0', __( 'CSS Styles', 'textdomain' ), 'ekv_field_3_0_callback', 'ekvideo-plugin', 'section-3' );    	
    }
    /* 
     * THE ACTUAL PAGE 
     * */
    function ekvideo_options_page() {
    ?>
      <div class="wrap">
          <h2><?php _e('Ekwa Video Plugin Options', 'textdomain'); ?></h2>

          <?php if ( isset( $_GET['settings-updated'] ) ) {
              echo "<div class='updated'><p>Settings updated successfully.</p></div>";
          } ?>

          <form action="options.php" method="POST">
            <?php settings_fields('ekvideo-settings-group'); ?>
            <?php do_settings_sections('ekvideo-plugin'); ?>
            <?php submit_button(); ?>
          </form>
      </div>
    <?php }
    /*
    * THE SECTIONS
    * */
    function section_1_callback() {
    	_e( 'Select the correct Lazyload method installed on website', 'textdomain' );
    }
    function section_2_callback() {
    	_e( 'Some common styles goes here.', 'textdomain' );
    }
    /*
    * THE FIELDS
    * */
    function ekv_field_1_1_callback() {
    	
    	$settings = (array) get_option( 'ekvideo-plugin-settings' );
    	$field = "ekv_field_1_1";
    	$value = esc_attr( $settings[$field] );
    	
    	echo "<input type='text' name='ekvideo-plugin-settings[$field]' value='$value' />";
    }
    function ekv_field_1_2_callback() {
    	
    	$settings = (array) get_option( 'ekvideo-plugin-settings' );
    	$field = "ekv_field_1_2";
    	$value = esc_attr( $settings[$field] );
    	
    	echo "<input type='text' name='ekvideo-plugin-settings[$field]' value='$value' />";
    }
    function ekv_field_1_3_callback() {
    	
    	$settings = (array) get_option( 'ekvideo-plugin-settings' );
    	$field = "ekv_field_1_3";
    	$value = esc_attr( $settings[$field] );
    	
    	echo "<input type='text' name='ekvideo-plugin-settings[$field]' value='$value' />";
    }
    function ekv_field_2_1_callback() {
    	
    	$settings = (array) get_option( 'ekvideo-plugin-settings' );
    	$field = "ekv_field_2_1";
    	$value = esc_attr( $settings[$field] );
    	
    	echo "<input type='text' name='ekvideo-plugin-settings[$field]' value='$value' />";
    }
    function ekv_field_2_2_callback() {
    	
    	$settings = (array) get_option( 'ekvideo-plugin-settings' );
    	$field = "ekv_field_2_2";
    	$value = esc_attr( $settings[$field] );
    	
    	// echo "<input type='text' name='ekvideo-plugin-settings[$field]' value='$value' />";
    	echo '<textarea cols="150" rows="10" name="ekvideo-plugin-settings['.$field.']">'.$value.'</textarea>';
    }

    function ekv_field_3_0_callback() {
    ?>
    	<ul>
    		<li>.ekv-wrapper => main wrapper class of video/ margine</li>
    		<li>.ekv-description => video description</li>
    		<li>.ekv-transcript => video transcript</li>
    		<li>.ekv-button => transcript toggle button</li>
    	</ul>
    
    <?php }
