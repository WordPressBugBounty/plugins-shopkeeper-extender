<!-- WordPress Wrapper -->
<div class="wrap">

	<h1></h1>

	<!-- WordPress Admin Header -->
	<hr class="wp-header-end">

	<!-- GBT Dashboard Scoped Content -->
	<div class="gbt-dashboard-scope">

		<?php
		// Include license status banner component
		$banner_path = dirname(__FILE__) . '/license-status-banner.php';
		if (file_exists($banner_path)) {
			include $banner_path;
		}
		?>