<?php
	require('vendor/autoload.php');
	require('src/PIDI.php');

	use Pidi\PIDI;

	$pidi = new PIDI('OPSP.pdf');
	//$fields = $pidi->getFormFields();

	$pages = $pidi->getPages();
	$fields = $pidi->getFormFields();
?>

<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Document</title>
	<style>
		.page {

			border: 1px solid red;
			position: relative;
		}

		.field {

			position: absolute;
			font-size: 11px;
			resize: none;
			border: 0;
			background: transparent;
		}

		.background {

			position: absolute;
			width: 100%;
			height: 100%;
			top: 0;
			left: 0;
		}

	</style>
</head>
<body>
	<?php foreach($pages as $k => $page): ?>

		<h2><?php echo $page->object; ?></h2>

		<div class="page" style="width: <?php echo $page->info[2]*2; ?>px; aspect-ratio: <?php echo $page->info[2] / $page->info[3]; ?>">
			<img class="background" src="<?php echo $k; ?>.png" alt="">
			<?php foreach($fields as $field): if($field->page != $page->object) continue; ?>
				<textarea placeholder="<?php echo $field->object; ?>" class="field" style="bottom: <?php echo $field->info['relative']['y']; ?>%; left: <?php echo $field->info['relative']['x']; ?>%; width: <?php echo $field->info['relative']['width']; ?>%; height: <?php echo $field->info['relative']['height']; ?>%;"></textarea>
			<?php endforeach; ?>
		</div>
	<?php endforeach; ?>

	<pre><?php print_r($fields); ?></pre>

</body>
</html>