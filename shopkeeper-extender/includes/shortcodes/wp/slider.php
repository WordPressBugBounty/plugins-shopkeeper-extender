<?php

// [slider]
function sk_slider_shortcode($params = array(), $content = null) {

	wp_enqueue_style( 'swiper' );
	wp_enqueue_script( 'swiper' );

	wp_enqueue_style(  'shopkeeper-slider-shortcode-styles' );
	wp_enqueue_script( 'shopkeeper-slider-shortcode-script' );

	extract(shortcode_atts(array(
		'full_height' 				=> 'yes',
		'custom_height'	 			=> '',
		'hide_arrows'				=> '',
		'hide_bullets'				=> '',
		'color_navigation_bullets' 	=> '#000000',
		'color_navigation_arrows'	=> '#000000',
		'custom_autoplay_speed'		=> 10
	), $params));

	if ($full_height == 'no' && !empty($custom_height))
	{
		$height = 'height:'.esc_attr($custom_height).';';
		$extra_class = '';
	}
	else
	{
		$height = '';
		$extra_class = 'full_height';
	}

	$bottom_line = '<style>.shortcode_getbowtied_slider .shortcode-slider-pagination .swiper-pagination-bullet.swiper-pagination-bullet-active:after{background-color: '. esc_attr($color_navigation_bullets) . ';}</style>';

	$unique = uniqid();

	$getbowtied_slider = $bottom_line . '

		<div class="shortcode_getbowtied_slider swiper-container swiper-'.esc_attr($unique).' '.$extra_class.'" style="'.esc_attr($height).' width: 100%" data-autoplay="'.esc_attr($custom_autoplay_speed).'" data-id="'.esc_attr($unique).'">
			<div class="swiper-wrapper">
			'.do_shortcode($content).'
			</div>';

	if (!$hide_arrows):
			$getbowtied_slider .= '
				<div style="color: '. esc_attr($color_navigation_arrows) .'" class="swiper-button-prev"><i class="spk-icon spk-icon-left-arrow-thin-large"></i></div>
    			<div style="color: '. esc_attr($color_navigation_arrows) .'" class="swiper-button-next"><i class="spk-icon spk-icon-right-arrow-thin-large"></i></div>';
    endif;

    if (!$hide_bullets):
    		$getbowtied_slider .= '
				<div style="color: '. $color_navigation_bullets .'" class="shortcode-slider-pagination"></div>';
    endif;

	$getbowtied_slider .=	'</div>';

	return $getbowtied_slider;
}

add_shortcode('slider', 'sk_slider_shortcode');

function sk_image_slide_shortcode($params = array(), $content = null) {
	extract(shortcode_atts(array(
		'title' 					=> '',
		'title_font_size'			=> '64px',
		'title_line_height'			=> '',
		'title_font_family'			=> 'primary_font',
		'description' 				=> '',
		'description_font_size' 	=> '21px',
		'description_line_height'	=> '',
		'description_font_family'	=> 'primary_font',
		'text_color'				=> '#000000',
		'button_text' 				=> '',
		'button_url'				=> '',
		'link_whole_slide'			=> '',
		'button_color'				=> '#000000',
		'button_text_color'			=>'#FFFFFF',
		'bg_color'					=> '#CCCCCC',
		'bg_image'					=> '',
		'text_align'				=> 'left'

	), $params));

	switch ($text_align)
	{
		case 'left':
			$class = 'left-align';
			break;
		case 'right':
			$class = 'right-align';
			break;
		case 'center':
			$class = 'center-align';
	}


	if (!empty($title))
	{
		$title_line_height = $title_line_height ? esc_attr($title_line_height) : esc_attr($title_font_size);
		$title = wp_kses_post('<h2 class="'.esc_attr($title_font_family).'" style="color:'.esc_attr($text_color).'; font-size:'.esc_attr($title_font_size).'; line-height: '.esc_attr($title_line_height).'">'.esc_html($title).'</h2>');
	} else {
		$title = "";
	}

	if (is_numeric($bg_image))
	{
		$bg_image = wp_get_attachment_url($bg_image);
	} else {
		$bg_image = "";
	}

	if (!empty($description))
	{
		$description_line_height = $description_line_height ? esc_attr($description_line_height) : esc_attr($description_font_size);
		$description = wp_kses_post('<p class="'.esc_attr($description_font_family).'" style="color:'.esc_attr($text_color).'; font-size:'.esc_attr($description_font_size).'; line-height: '.esc_attr($description_line_height).'">'.esc_html($description).'</p>');
	} else {
		$description = "";
	}

	if (!empty($button_text))
	{
		$button = wp_kses_post('<a class="button" style="color:'.esc_attr($button_text_color).' !important; background: '.esc_attr($button_color).' !important" href="'.esc_url($button_url).'">'.esc_html($button_text).'</a>');
	} else {
		$button = "";
	}

	if ($link_whole_slide && !empty($button_url))
	{
		$slide_link = '<a href="'.esc_url($button_url).'" class="fullslidelink"></a>';
	}
	else
	{
		$slide_link = '';
	}


	$getbowtied_image_slide = '
			<div class="swiper-slide '.esc_attr($class).'"
			style=	"background: '.esc_attr($bg_color).' url('.$bg_image.') center center no-repeat ;
					-webkit-background-size: cover;
					-moz-background-size: cover;
					-o-background-size: cover;
					background-size: cover;
					color: '.esc_attr($text_color).'">
				'.esc_url($slide_link).'
				<div class="slider-content" data-swiper-parallax="-50%">
					<div class="slider-content-wrapper">
						'.wp_kses_post($title).'
						'.wp_kses_post($description).'
						'.wp_kses_post($button).'
					</div>
				</div>
			</div>';

	return $getbowtied_image_slide;
}

add_shortcode('image_slide', 'sk_image_slide_shortcode');
