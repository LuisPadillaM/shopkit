<?php
// Set variables
$site = site();
	/**
	 * Variables passed from process.php template
	 *
	 * $txn 		Transaction page object
	 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8" />
	<title><?= $site->title()->html() ?> | <?= page('shop/cart')->title() ?></title>
	<style>
		body { font-family: sans-serif; font-size: 2rem; text-align: center; }
		button { font-size: 1rem; padding: 1rem; }
	</style>
</head>
<body>
	<p><?= _t('redirecting') ?></p>

	<form method="post" action="<?= $site->paypalexpress_status() == 'sandbox' ? 'https://www.sandbox.paypal.com/cgi-bin/webscr' : 'https://www.paypal.com/cgi-bin/webscr'  ?>" name="paypalexpress">
		<!-- Setup fields -->
		<input type="hidden" name="cmd" value="_cart"> <!-- Identifies a shopping cart purchase -->
		<input type="hidden" name="upload" value="1">  <!-- Identifies a third-party cart -->
		<input type="hidden" name="return" value="<?= page('shop/confirm')->url().'/id:'.$txn->slug() ?>">
		<input type="hidden" name="rm" value="2"> <!-- Return method: POST, all variables passed -->
		<input type="hidden" name="cancel_return" value="<?= page('shop/cart')->url() ?>">
		<input type="hidden" name="notify_url" value="<?= page('shop/cart/callback')->url().'/gateway:paypalexpress/id:'.$txn->slug() ?>">
		<input type="hidden" name="business" value="<?= $site->paypalexpress_email() ?>">
		<input type="hidden" name="currency_code" value="<?= $site->currency_code() ?>">

		<!-- Cart items -->
		<?php foreach ($txn->products()->toStructure() as $i => $item) { ?>
		    <?php $i++ ?>
		    <input type="hidden" name="item_name_<?= $i ?>" value="<?= $item->name().' - '.$item->variant().' - '.$item->option() ?>">
		    
		    <?php $itemAmount = $item->{'sale-amount'}->value != '' ? $item->{'sale-amount'}->value : $item->amount()->value ?>
		    <input type="hidden" name="amount_<?= $i ?>" value="<?= number_format($itemAmount, decimalPlaces($site->currency_code()), '.', '') ?>">

		    <input type="hidden" name="quantity_<?= $i ?>" value="<?= $item->quantity() ?>">
		<?php } ?>

		<!-- Cart discount -->
		<input type="hidden" name="discount_amount_cart" value="<?= $txn->discount()->value + $txn->giftcertificate()->value ?>">

		<!-- Shipping -->
		<input type="hidden" name="shipping_1" value="<?= $txn->shipping() ?>">
	
		<!-- Tax -->
		<input type="hidden" name="tax_cart" value="<?= $txn->tax() ?>">

		<!-- Transaction ID (Callback for the success page to grab the right transaction page) -->
		<input type="hidden" name="custom" value="<?= $txn->slug() ?>">

		<button type="submit"><?= _t('continue-to-paypal') ?></button>
	</form>

	<script>
		// Automatically submit the form
		document.paypalexpress.submit();
	</script>
</body>
</html>