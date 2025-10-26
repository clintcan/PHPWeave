<!DOCTYPE html>
<html>
<head>
	<title><?php echo isset($title) ? htmlspecialchars($title, ENT_QUOTES, 'UTF-8') : 'PHPWeave Blog'; ?></title>
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
		<?php if (is_array($data)): ?>
			<!-- Display structured data from array -->
			<h1><?php echo htmlspecialchars($title ?? 'Blog', ENT_QUOTES, 'UTF-8'); ?></h1>

			<?php if (isset($message)): ?>
				<p class="message"><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></p>
			<?php endif; ?>

			<?php if (isset($test)): ?>
				<div class="result">
					<strong>Test Result:</strong> <?php echo htmlspecialchars($test, ENT_QUOTES, 'UTF-8'); ?>
				</div>
			<?php endif; ?>
		<?php else: ?>
			<!-- Fallback for simple string data -->
			<p><?php echo htmlspecialchars($data, ENT_QUOTES, 'UTF-8'); ?></p>
		<?php endif; ?>
	</div>
</body>
</html>