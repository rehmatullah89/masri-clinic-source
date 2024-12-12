<?php

# Make sure we at least know about the home page.
if (!($home_page = page::home())) {
	return;
}

# Retrieve an instance of the current page.
$current_page = page::current();

if ($current_page) {
	if (site::is_editing()) {
		# We're editing the site, so try to save the current page properties.
		$current_page->save_properties_editor();
	} else {
		# We're not editing the site. Is the current page visible?
		if (!$current_page->get_is_publicly_accessible()) {
			# Redirect to the home page (you can change this to a 403 or similar if need be).
			page::redirect($home_page);
		}
	}
} else {
	return;
}

# Load MooTools dependency.
site::require_dependency('mootools_core');

# Retrieve an instance of the current template.
$template = template::current();

# Load default styles relative to the template directory.
$template->require_template_style('style/main.css');
$template->require_template_style('style/content.css');

$template->require_template_script('scripts/common.js');

site::require_dependency('search_autocomplete');

# Google Font
$template->require_style('https://fonts.googleapis.com/css?family=Open+Sans:300,400,600');

# If we're editing the site, load the special "CMS" stylesheet and scripts.
if (site::is_editing()) {
	$template->require_template_style('style/editing.css');
	$template->require_script('/scripts/toggle_page_properties.js');
}


# For each page we'll need to remember the "page" parameter and the "cms_preview" parameters.
uri::remember_part('page');
uri::remember_part('cms_preview');

# Load the cookie permissions library.
site::require_library('cookie_permissions');

# Load the lightbox dependency.
site::require_dependency('lightbox');

$search_page = page::find_single_by_type('muraspec/search');
if ($search_page && !$search_page->get_is_publicly_accessible()) $search_page = null;
if ($search_page) $template->require_template_script('scripts/search.js');

$newsletter_registration_page = page::find_single_by_type('muraspec/newsletter_registration');
if ($newsletter_registration_page && $current_page && $newsletter_registration_page->id == $current_page->id) $newsletter_registration_page = null;
if ($newsletter_registration_page && !$newsletter_registration_page->get_is_publicly_accessible()) $newsletter_registration_page = null;
if ($newsletter_registration_page) $newsletter_registration_page->begin();

if (!(($current_domain = domain::current()) && ($language_id = $current_domain->get_language_id()) && preg_match('/^[a-z]{2}(-[A-Z]{2})?$/', $language_id))) {
	$language_id = 'en';
}
$language_id_2 = substr($language_id, 0, 2);

?><!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php echo html_encode($language_id); ?>" lang="<?php echo html_encode($language_id); ?>">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
		<?php $template->output_head(); ?>
		<meta name="viewport" content="width=device-width" />
	</head>
	<body<?php if (site::is_editing()) { echo ' class="editing"'; } ?>>
		
		<?php $template->output_header(); ?>
		
		<?php
		# Is there a cookie permissions form?
		if (cookie_permissions::will_output_form()) {
			echo '<div id="cookie_permissions_container">';
			cookie_permissions::output_form();
			echo '</div>';
		}
		?>
		
		<?php
		# If the site is open for editing and we have a page visible, display a property editor and toolbar.
		if (site::is_editing() && $current_page) {
			echo '<div id="cms_header_container">';
			$template->output_cms_header();
			echo '</div>';
		}
		
		if ($current_page && method_exists($current_page, 'output_header_content')) {
			ob_start();
			$current_page->output_header_content();
			$header_content = trim(ob_get_clean());
		} else {
			$header_content = '';
		}
		
		ob_start();
		static_page_element::output_html('blanket_message', $home_page);
		if (strlen($content = trim(ob_get_clean())) > 0) {
			echo '<div id="blanket_message" class="shaded pale horizontal-padding" style="padding-top:1em;padding-bottom:1em;"><div class="limited-width">';
			echo $content;
			echo '</div></div>';
		}
		
		?> 
		
		<div id="header_container" class="horizontal-padding">
			<div class="limited-width">
				<div id="header">
					<div id="logo_and_strapline_container" class="header-column">
						<div id="logo_and_strapline">
							<div id="logo"><a href="<?php echo $home_page->get_uri_html(); ?>"><img src="/<?php
								if (!file_exists($full_path = ($base_path = 'templates/muraspec/images/muraspec-logo') . ('-' . $language_id_2) . ($extension = '.png'))) {
									$full_path = $base_path . $extension;
								}
								echo html_encode($full_path);
							?>"  alt="<?php echo system_variables::get('long_company_name'); ?>"  /></a></div>
							<div id="strapline">
								<?php static_page_element::output_text('strapline', page::home(), array('default'=>'International Leaders in Commercial Wallcoverings')); ?>
							</div>
						</div>
					</div>
					
					<div id="header_language_and_social" class="header-column">
						<?php
						$domain_links = array();
						foreach (domain::get_domains() as $domain) {
							if (($language = $domain->get_language()) && $language->get_is_enabled() && ($language_id = $language->id) && preg_match('/^([a-z]{2})(-[A-Z]{2})?$/', $language_id, $matches)) {
								$is_current_domain = $current_domain && $domain->id == $current_domain->id;
								$caption = file_exists($flag = 'templates/muraspec/images/flags/' . strtolower($matches[1]) . '.png') ? ('<img src="/' . html_encode($flag) . '" alt="' .  strtoupper($matches[1]). '" />') : html_encode(strtoupper($matches[1]));
								$domain_links[]  = '<a href="' . html_encode('//' . $domain->get_domain()) . '/" title="' . $language->get_name() . '">' . ($is_current_domain ? '<strong>' : '') . $caption . ($is_current_domain ? '</strong>' : '') .  '</a>';
							}
						}
						if (count($domain_links) > 1) {
							echo '<span id="header_language">' . implode(' ', $domain_links) . '</span>';
						}
						?>
						<span id="header_social">
							<?php
							if (count($social_media_accounts = template_info::get_social_media_account_links()) > 0) {
								echo implode(' ', $social_media_accounts);
							}
							?>
						</span>
					</div>
				</div>
			</div>
		</div>
		
		
		<div id="contact_links_container" class="horizontal-padding">
			<div class="limited-width">
				<div id="contact_links">
					<div id="contact_links_contact" class="contact-links">
						<?php
						foreach (array('mailto:'=>'email', 'tel:'=>'telephone') as $scheme=>$field) {
							ob_start();
							common_content::output_text('header_contact_links_' . $field);
							$content = trim(ob_get_clean());
							if (strlen($content) > 0) {
								if (site::is_editing()) {
									$content = ' ' . $content . ' ';
								} else {
									$content = '<a href="' . $scheme . preg_replace('/\s/', '', $content) . '">' . $content . '</a>';
								}
							}
							echo $content;
						}
						?>
					</div>
					
				</div>
			</div>
		</div>
		<div id="main_menu_bar" class="horizontal-padding">
			<div class="limited-width">
				<div id="main_menu_bar_sections">
					<div id="main_menu_container" class="main-menu-bar-section"><?php menu::output_from_id('main_menu'); ?></div>
					<div id="fardis_logo" class="main-menu-bar-section"><a href="https://www.fardis.com/" rel="external"><img src="/templates/muraspec/images/fardis-logo.png" alt="Fardis Wallpapers &amp; Fabrics" /></a></div>
				</div>
			</div>
		</div>
		<div id="search_bar_background" class="horizontal-padding shaded-right <?php echo ((strlen($header_content) > 0) ? 'has-header-content' : 'no-header-content'); ?>">
			<div class="limited-width">
				<div id="search_bar_container">
					<div id="search_bar">
						<div id="search_spacer" class="search-bar vertical-padding">
						</div>
						<div id="search_container" class="search-bar vertical-padding">
							<?php
							if ($search_page) {
								$search_page->output_header_search_form();
							}
							?>
						</div>
					</div>
					<?php echo $header_content; ?>
				</div>
			</div>
		</div>
		
		<?php
		$page_container_classes = array();
		if ($current_page) {
			foreach (array('has_horizontal_padding'=>'horizontal-padding', 'has_vertical_padding'=>'vertical-padding') as $method=>$class) {
				if (!method_exists($current_page, $method) || $current_page->$method()) $page_container_classes[] = $class;
			}
		}
		
		echo '<div id="page_container"';
		if (count($page_container_classes) > 0) echo ' class="' . implode(' ', $page_container_classes) . '"';
		echo '>';
		if (in_array('horizontal-padding', $page_container_classes)) echo '<div class="limited-width">';
		{
			if ($current_page) {
				$current_page->output();
			}
		}
		if (in_array('horizontal-padding', $page_container_classes)) echo '</div>';
		echo '<div class="clear"></div></div>';
		?>
		
		<?php
		if ($newsletter_registration_page) {
			$was_read_only = site::set_read_only();
			site::set_read_only(true);
			echo '<div id="newsletter_registration_container" class="shaded horizontal-padding vertical-padding"><div class="limited-width">';
			$newsletter_registration_page->output_form();
			echo '</div></div>';
			site::set_read_only($was_read_only);
		}
		?>
		
		<div id="footer_container" class="horizontal-padding vertical-padding">
			<div class="limited-width">
				<div id="footer_expander">
					<div id="footer">
						<div class="footer-column">
							<?php menu::output_from_id('footer_menu_1'); ?>
						</div><!--
						--><div class="footer-column">
							<?php menu::output_from_id('footer_menu_2'); ?>
						</div><!--
						--><div id="footer_logo" class="footer-column">
							<p><a href="<?php echo $home_page->get_uri_html(); ?>"><img src="/<?php
							if (!file_exists($full_path = ($base_path = 'templates/muraspec/images/muraspec-logo-white') . ('-' . $language_id_2) . ($extension = '.png'))) {
								$full_path = $base_path . $extension;
							}
							echo html_encode($full_path);
							?>"  alt="<?php echo system_variables::get('long_company_name'); ?>"  /></a></p>
							<?php
							if (count($social_media_accounts = template_info::get_social_media_account_links('url', 'white')) > 0) {
								echo '<p>' . implode(' ', $social_media_accounts) . '</p>';
							}
							?>
							<?php menu::output_from_id('footer_menu_3'); ?>
						</div><!--
						--><div class="footer-column">
							<?php static_page_element::output_html('footer_contact_1', $home_page); ?>
						</div><!--
						--><div class="footer-column">
							<?php static_page_element::output_html('footer_contact_2', $home_page); ?>
						</div>
					</div>
				</div>
			</div>
		</div>
		
		<?php $template->output_footer(); ?>
	</body>
</html>