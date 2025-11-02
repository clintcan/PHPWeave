<!DOCTYPE html>
<html>
<head>
	<title><?php echo isset($title) ? safe($title) : 'PHPWeave Blog'; ?></title>
	<style>
		body {
			font-family: Arial, sans-serif;
			max-width: 800px;
			margin: 50px auto;
			padding: 20px;
			background-color: #f5f5f5;
		}
		.container {
			background: white;
			padding: 30px;
			border-radius: 8px;
			box-shadow: 0 2px 4px rgba(0,0,0,0.1);
		}
		h1 {
			color: #333;
			border-bottom: 3px solid #007bff;
			padding-bottom: 10px;
		}
		.message {
			color: #666;
			font-size: 1.1em;
			margin: 20px 0;
		}
		.result {
			background: #e9ecef;
			padding: 15px;
			border-radius: 4px;
			margin: 20px 0;
		}
		.result strong {
			color: #007bff;
		}
	</style>
</head>
<body>
	<div class="container">
		<?php if (isset($original_text) || isset($title) || isset($message) || isset($test)): ?>
			<!-- Display structured data from array -->
			<h1><?php echo safe($title ?? 'Blog'); ?></h1>

			<?php if (isset($message)): ?>
				<p class="message"><?php echo safe($message); ?></p>
			<?php endif; ?>

			<?php if (isset($test)): ?>
				<div class="result">
					<strong>Test Result:</strong> <?php echo safe($test); ?>
				</div>
			<?php endif; ?>

			<?php if (isset($original_text)): ?>
				<!-- Library Demo Output -->
				<div class="result">
					<h2 style="color: #007bff; margin-top: 0;">Original Text</h2>
					<p><code><?php echo safe($original_text); ?></code></p>
				</div>

				<?php if (isset($slug1) || isset($slug2) || isset($slug3)): ?>
					<div class="result">
						<h2 style="color: #007bff; margin-top: 0;">Slugify Results</h2>
						<?php if (isset($slug1)): ?>
							<p><strong>Method 1 ($PW->libraries):</strong> <code><?php echo safe($slug1); ?></code></p>
						<?php endif; ?>
						<?php if (isset($slug2)): ?>
							<p><strong>Method 2 (library() function):</strong> <code><?php echo safe($slug2); ?></code></p>
						<?php endif; ?>
						<?php if (isset($slug3)): ?>
							<p><strong>Method 3 ($libraries array):</strong> <code><?php echo safe($slug3); ?></code></p>
						<?php endif; ?>
					</div>
				<?php endif; ?>

				<?php if (isset($truncated)): ?>
					<div class="result">
						<h2 style="color: #007bff; margin-top: 0;">Truncated (20 chars)</h2>
						<p><code><?php echo safe($truncated); ?></code></p>
					</div>
				<?php endif; ?>

				<?php if (isset($title_cased)): ?>
					<div class="result">
						<h2 style="color: #007bff; margin-top: 0;">Title Case</h2>
						<p><code><?php echo safe($title_cased); ?></code></p>
					</div>
				<?php endif; ?>

				<?php if (isset($word_count)): ?>
					<div class="result">
						<h2 style="color: #007bff; margin-top: 0;">Word Count</h2>
						<p><strong><?php echo safe($word_count); ?></strong> words</p>
					</div>
				<?php endif; ?>

				<?php if (isset($reading_time)): ?>
					<div class="result">
						<h2 style="color: #007bff; margin-top: 0;">Estimated Reading Time</h2>
						<p><strong><?php echo safe($reading_time); ?></strong></p>
					</div>
				<?php endif; ?>

				<?php if (isset($random_token)): ?>
					<div class="result">
						<h2 style="color: #007bff; margin-top: 0;">Random Token (8 chars)</h2>
						<p><code><?php echo safe($random_token); ?></code></p>
					</div>
				<?php endif; ?>

				<div style="margin-top: 30px; padding: 20px; background: #d1ecf1; border-left: 4px solid #0c5460; border-radius: 4px;">
					<h3 style="margin-top: 0; color: #0c5460;">Try It Yourself!</h3>
					<p style="color: #0c5460; margin-bottom: 10px;">Change the text in the URL to see different results:</p>
					<p style="margin: 0;"><code style="background: white; padding: 8px; border-radius: 4px; display: inline-block;">/blog/slugify/Your-Text-Here</code></p>
				</div>
			<?php endif; ?>
		<?php elseif (isset($data) && is_string($data)): ?>
			<!-- Fallback for simple string data (legacy format) -->
			<h1>PHPWeave Blog</h1>
			<p class="message"><?php echo safe($data); ?></p>
		<?php else: ?>
			<!-- Default content when no data passed -->
			<h1>PHPWeave Blog</h1>
			<p class="message">Welcome to the PHPWeave blog!</p>
		<?php endif; ?>
	</div>
</body>
</html>