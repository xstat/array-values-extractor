<?php

namespace Xstat;

/**
 * Extracts and validates date from an array.
 */
class ArrayValuesExtractor {

  const TYPE_ARRAY = 'array';
  const TYPE_OTHER = 'other';
  const TYPE_STRING = 'string';
  const TYPE_NUMERIC = 'numeric';

  protected $parentObject;
  protected $attrDefinitions;

  protected $defaults = [
    self::TYPE_ARRAY => [],
  ];

  /**
   * Constructor method.
   */
  public function __construct(Object $parentObject) {
    $this->parentObject = $parentObject;
  }

  /**
   * Adds a validation rule for a given attribute.
   */
  public function expect($attrName, $attrType, $defaultValue = NULL) {
    $this->attrDefinitions[$attrName] = [
      'type' => $attrType,
      'required' => FALSE,
      'defaultValue' => $defaultValue,
    ];
    return $this;
  }

  /**
   * Flags a given attribute as required.
   */
  public function required(Array $attrNames) {
    foreach ($attrNames as $attrName) {
      if (isset($this->attrDefinitions[$attrName])) {
        $this->attrDefinitions[$attrName]['required'] = TRUE;
      }
    }
    return $this;
  }

  /**
   * Validates and extracts the data from the input array.
   */
  public function extract(Array $input) {
    $values = [];

    foreach ($this->attrDefinitions as $attrName => $attrDefinition) {
      // Get value from input array.
      $attrValue = @$input[$attrName];

      if (is_null($attrValue)) {
        // If no value use provided default.
        $attrValue = $attrDefinition['defaultValue'];
      }

      // If still no value and the attribute is required stop.
      if (is_null($attrValue) && $attrDefinition['required']) {
        $this->throw($attrName, 'cannot be empty');
      }

      if (!is_null($attrValue)) {
        // If there is a value, validate for the specific type.
        $this->validate($attrName, $attrValue, $attrDefinition);
      }

      if (is_null($attrValue)) {
        // Provide a sensitive default as the last attempt.
        $attrValue = @$this->defaults[$attrDefinition['type']];
      }

      // Store the final value.
      $values[$attrName] = $attrValue;
    }

    return $values;
  }

  /**
   * Throws an exception when  attribute name.
   */
  protected function throw($attrName, $message) {
    $className = (new \ReflectionClass($this->parentObject))->getShortName();
    throw new \Exception(sprintf('%s "%s" field %s.', $className, $attrName, $message));
  }

  /**
   * Runs the type-specific validations on a given attribute.
   */
  protected function validate($attrName, $attrValue, $attrDefinition) {
    $validateMethod = 'validate' . ucfirst($attrDefinition['type']) . 'Value';

    // Call the type-specific validation method.
    if (method_exists($this, $validateMethod)) {
      return $this->$validateMethod($attrName, $attrValue, $attrDefinition);
    }

    return $attrName;
  }

  /**
   * Validates "array" type attributes.
   */
  protected function validateArrayValue($attrName, $attrValue, $attrDefinition) {
    !is_array($attrValue) && $this->throw($attrName, 'must be an array');
  }

  /**
   * Validates "string" type attributes.
   */
  protected function validateStringValue($attrName, $attrValue, $attrDefinition) {
    !is_string($attrValue) && $this->throw($attrName, 'must be a string');

    if ($attrDefinition['required'] && empty(trim($attrValue))) {
      $this->throw($attrName, 'cannot be empty');
    }
  }

  /**
   * Validates "numeric" type attributes.
   */
  protected function validateNumericValue($attrName, $attrValue, $attrDefinition) {
    !is_numeric($attrValue) && $this->throw($attrName, 'must be numeric');
  }

}
