<?php
// Set site
$site = site();

// Set language
$site->visit('', $lang);

// Page URI sent via POST
$p = page(get('uri'));

// Load dompdf
require(kirby()->roots()->plugins().'/dompdf/autoload.inc.php');

// reference the Dompdf namespace
use Dompdf\Dompdf;

// instantiate the dompdf class
$dompdf = new Dompdf();

// Build the HTML
$html = '<style>body{font-family: sans-serif;}</style>';
$html .= '<h1>'.$site->title().'</h1>';

$contact = page('contact');
if ($address = $contact->location()->toStructure()->address() and $address != '') {
  $html .= $address->kirbytext();
} else if ($contact->location()->isNotEmpty()) {
  $html .= $contact->location()->kirbytext();
}
if ($phone = $contact->phone() and $phone != '') {
  $html .='<p>'.$phone.'</p>';
}
if ($email = $contact->email() and $email != '') {
  $html .='<p>'.$email.'</p>';
}

$html .= '<hr>';

$html .= '<p>'._t('invoice').' No. <strong>'.$p->txn_id()->value.'</strong> ('._t($p->status()->value).')</p>';
$html .= '<p><em>'.date('F j, Y H:i',$p->txn_date()->value).'</em></p>';
$html .= '<p><strong>'._t('bill-to').'</strong><br>';
if ($p->payer_name() != '') $html .= $p->payer_name()->value.'<br>';
$html .= $p->payer_email()->value.'</p>';
$html .= $p->payer_address()->kirbytext();

$html .= '<hr>';

if (strpos($p->products(),'uri:')) {
  // Show product overview
  foreach ($p->products()->toStructure() as $product) {
      $html .= '<p>'.$product->name().'<br><small>'.$product->variant();
      $html .= $product->option()->isNotEmpty() ? ' / '.$product->option() : '';
      $html .= ' / '._t('qty').$product->quantity();
      $html .= '</small>';

      // Include license keys
      if ($product->{'license-keys'}->value and in_array($p->status(), ['paid', 'shipped'])) {
        $html .= "<br><small>"._t('license-keys').': ';
        foreach ($product->{'license-keys'} as $key => $license_key) {
          $html .= $license_key;
          if (count($product->{'license-keys'}) - 1 !== $key) {
            $html .= ' | ';
          }
        }
        $html .= '</small>';
      }

      $html .= '</p>';
  }
} else {
  // Old transaction files from Shopkit 1.0.5 and earlier
  $html .= $p->products()->kirbytext()->bidi();
}

$html .= '<hr>';
$html .= '<p>'._t('subtotal').': '.formatPrice($p->subtotal()->value).'</p>';
$html .= '<p>'._t('discount').': '.formatPrice($p->discount()->value).'</p>';
$html .= '<p>'._t('shipping').': '.formatPrice($p->shipping()->value).'</p>';

if ($p->taxes()->value) {
  // List each tax rate separately
  foreach ($p->taxes()->toStructure() as $key => $value) {
    if ($key === 'total') {
      if ($p->taxes()->toStructure()->count() > 1) {
        continue;
      } else {
        $html .= '<p>'._t('tax').': '.formatPrice($value->value).'</p>';
      }
    }
    $html .= '<p>'._t('tax').' '.($key * 100).'%: '.formatPrice($value->value).'</p>';
  }
} else {
  // Fallback for old tax structure (single total only)
  $html .= '<p>'._t('tax').': '.formatPrice($p->tax()->value).'</p>';
}

$html .= '<p><strong>'._t('total').': '.formatPrice($p->subtotal()->value+$p->shipping()->value+$p->tax()->value-$p->discount()->value).'</strong></p>';
$html .= '<p><strong>'._t('gift-certificate').': &ndash; '.formatPrice($p->giftcertificate()->value).'</strong></p>';

// Load the html
$dompdf->loadHtml($html);

// (Optional) Setup the paper size and orientation
$dompdf->setPaper('A4', 'portrait');

// Render the HTML as PDF
$dompdf->render();

// Output the generated PDF to Browser
$dompdf->stream($p->txn_id()->value);