<?php

require_once('vendor/autoload.php');

use Xstat\ArrayValuesExtractor;

class Customer {}

class Invoice {

  protected $data;

  public function __construct(Array $values) {
    $extractor = new ArrayValuesExtractor($this);

    $extractor
      ->expect('id', $extractor::TYPE_NUMERIC)
      ->expect('date', $extractor::TYPE_STRING)
      ->expect('items', $extractor::TYPE_ARRAY)
      ->expect('customer', $extractor::TYPE_OTHER)
      ->required(['customer']);

    $this->data = $extractor->extract($values);

    // Add further validation on extracted data.
    if (!$this->data['customer'] instanceOf Customer) {
      throw new Exception('Invoice customer must be an instance of Customer class');
    }
  }

  public function getCustomer() {
    return $this->data['customer'];
  }

  public function toArray() {
    return $this->data;
  }

}

try {
  $invoice = new Invoice([
    'name' => 'TEST INVOICE',
  ]);
}
catch (Exception $e) {
  var_dump($e->getMessage());
}

try {
  $invoice = new Invoice([
    'name' => 123,
    'customer' => new Customer(),
  ]);

  var_dump($invoice->toArray());
}
catch (Exception $e) {
  var_dump($e->getMessage());
}


