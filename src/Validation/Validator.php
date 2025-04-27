<?php

namespace WordForge\Validation;

/**
 * Validator class for validating data
 *
 * Provides Laravel-like validation functionality.
 *
 * @package WordForge\Validation
 */
class Validator
{
    /**
     * The data under validation.
     *
     * @var array
     */
    protected $data;

    /**
     * The validation rules.
     *
     * @var array
     */
    protected $rules;

    /**
     * The validation messages.
     *
     * @var array
     */
    protected $messages;

    /**
     * The custom attribute names.
     *
     * @var array
     */
    protected $attributes;

    /**
     * The validation errors.
     *
     * @var array
     */
    protected $errors = [];

    /**
     * Create a new Validator instance.
     *
     * @param array $data
     * @param array $rules
     * @param array $messages
     * @param array $attributes
     * @return void
     */
    public function __construct(array $data, array $rules, array $messages = [], array $attributes = [])
    {
        $this->data = $data;
        $this->rules = $rules;
        $this->messages = $messages;
        $this->attributes = $attributes;
    }

    /**
     * Run the validator.
     *
     * @return bool
     */
    public function passes()
    {
        $this->errors = [];

        foreach ($this->rules as $attribute => $rules) {
            foreach ($this->parseRules($rules) as $rule) {
                $this->validateAttribute($attribute, $rule);
            }
        }

        return empty($this->errors);
    }

    /**
     * Run the validator and return whether validation failed.
     *
     * @return bool
     */
    public function fails()
    {
        return !$this->passes();
    }

    public function diagnose($attribute, $rule)
    {
        $value = $this->getValue($attribute);
        [$ruleName, $parameters] = $this->parseRule($rule);

        // Use the same method name transformation as validateAttribute
        $methodName = 'validate' . str_replace(' ', '', ucwords(str_replace('_', ' ', $ruleName)));

        return [
            'attribute' => $attribute,
            'value' => $value,
            'rule' => $ruleName,
            'parameters' => $parameters,
            'would_pass' => method_exists($this, $methodName) ? $this->$methodName($attribute, $value, $parameters) : false
        ];
    }

    /**
     * Validate a given attribute against a rule.
     *
     * @param string $attribute
     * @param array $rule
     * @return void
     */
    protected function validateAttribute(string $attribute, array $rule)
    {
        $value = $this->getValue($attribute);
        list($rule, $parameters) = $rule;

        if ($rule !== 'required' && $this->isEmptyValue($value) && !$this->isImplicitRule($rule)) {
            return;
        }

        $methodName = 'validate' . str_replace(' ', '', ucwords(str_replace('_', ' ', $rule)));

        if (method_exists($this, $methodName)) {
            if (!$this->$methodName($attribute, $value, $parameters)) {
                $this->addError($attribute, $rule, $parameters);
            }
        }
    }

    /**
     * Get a value from the data array.
     *
     * @param string $attribute
     * @return mixed
     */
    protected function getValue(string $attribute)
    {
        return $this->data[$attribute] ?? null;
    }

    /**
     * Determine if a value is empty.
     *
     * @param mixed $value
     * @return bool
     */
    protected function isEmptyValue($value)
    {
        return is_null($value) || $value === '' || (is_array($value) && empty($value));
    }

    /**
     * Determine if a rule implies the attribute is required.
     *
     * @param string $rule
     * @return bool
     */
    protected function isImplicitRule(string $rule)
    {
        return in_array($rule, ['required', 'required_if', 'required_with']);
    }

    /**
     * Add an error message for an attribute.
     *
     * @param string $attribute
     * @param string $rule
     * @param array $parameters
     * @return void
     */
    protected function addError(string $attribute, string $rule, array $parameters = [])
    {
        $message = $this->getMessage($attribute, $rule);
        $message = $this->replaceParameters($message, $attribute, $rule, $parameters);

        $this->errors[$attribute][] = $message;
    }

    /**
     * Get the validation message for an attribute and rule.
     *
     * @param string $attribute
     * @param string $rule
     * @return string
     */
    protected function getMessage(string $attribute, string $rule)
    {
        // Check for custom message for specific attribute.rule
        if (isset($this->messages[$attribute . '.' . $rule])) {
            return $this->messages[$attribute . '.' . $rule];
        }

        // Check for custom message for the rule
        if (isset($this->messages[$rule])) {
            return $this->messages[$rule];
        }

        // Use default message
        return $this->getDefaultMessage($rule);
    }

    /**
     * Get the default validation message for a rule.
     *
     * @param string $rule
     * @return string
     */
    protected function getDefaultMessage(string $rule)
    {
        $messages = [
            'required' => 'The :attribute field is required.',
            'email' => 'The :attribute must be a valid email address.',
            'url' => 'The :attribute must be a valid URL.',
            'min' => 'The :attribute must be at least :min characters.',
            'max' => 'The :attribute may not be greater than :max characters.',
            'numeric' => 'The :attribute must be a number.',
            'integer' => 'The :attribute must be an integer.',
            'boolean' => 'The :attribute must be true or false.',
            'array' => 'The :attribute must be an array.',
            'in' => 'The selected :attribute is invalid.',
            'not_in' => 'The selected :attribute is invalid.',
            'date' => 'The :attribute is not a valid date.',
            'between' => 'The :attribute must be between :min and :max.',
            'regex' => 'The :attribute format is invalid.',
        ];

        return $messages[$rule] ?? "The :attribute is invalid.";
    }

    /**
     * Replace parameters in the message.
     *
     * @param string $message
     * @param string $attribute
     * @param string $rule
     * @param array $parameters
     * @return string
     */
    protected function replaceParameters(string $message, string $attribute, string $rule, array $parameters)
    {
        // Replace :attribute with the attribute name
        $attributeName = $this->attributes[$attribute] ?? str_replace('_', ' ', $attribute);
        $message = str_replace(':attribute', $attributeName, $message);

        // Replace other parameters
        switch ($rule) {
            case 'min':
            case 'max':
                $message = str_replace(':' . $rule, $parameters[0], $message);
                break;
            case 'between':
                $message = str_replace([':min', ':max'], $parameters, $message);
                break;
            case 'in':
            case 'not_in':
                $message = str_replace(':values', implode(', ', $parameters), $message);
                break;
        }

        return $message;
    }

    /**
     * Parse the rule string into an array of rules.
     *
     * @param string|array $rules
     * @return array
     */
    protected function parseRules($rules)
    {
        if (is_array($rules)) {
            return array_map([$this, 'parseRule'], $rules);
        }

        return array_map([$this, 'parseRule'], explode('|', $rules));
    }

    /**
     * Parse a rule string into a rule and parameters.
     *
     * @param string $rule
     * @return array
     */
    protected function parseRule(string $rule)
    {
        if (strpos($rule, ':') === false) {
            return [$rule, []];
        }

        list($ruleName, $parameterString) = explode(':', $rule, 2);
        $parameters = explode(',', $parameterString);

        return [$ruleName, $parameters];
    }

    /**
     * Get the validation errors.
     *
     * @return array
     */
    public function errors()
    {
        return $this->errors;
    }

    /**
     * Validate that an attribute is required.
     *
     * @param string $attribute
     * @param mixed $value
     * @param array $parameters
     * @return bool
     */
    protected function validateRequired(string $attribute, $value, array $parameters)
    {
        return !$this->isEmptyValue($value);
    }

    /**
     * Validate that an attribute is a valid email.
     *
     * @param string $attribute
     * @param mixed $value
     * @param array $parameters
     * @return bool
     */
    protected function validateEmail(string $attribute, $value, array $parameters)
    {
        return filter_var($value, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Validate that an attribute is a valid URL.
     *
     * @param string $attribute
     * @param mixed $value
     * @param array $parameters
     * @return bool
     */
    protected function validateUrl(string $attribute, $value, array $parameters)
    {
        return filter_var($value, FILTER_VALIDATE_URL) !== false;
    }

    /**
     * Validate that an attribute has a minimum value.
     *
     * @param string $attribute
     * @param mixed $value
     * @param array $parameters
     * @return bool
     */
    protected function validateMin(string $attribute, $value, array $parameters)
    {
        $min = (int) $parameters[0];

        if (is_numeric($value)) {
            return $value >= $min;
        }

        if (is_string($value)) {
            return mb_strlen($value) >= $min;
        }

        if (is_array($value)) {
            return count($value) >= $min;
        }

        return false;
    }

    /**
     * Validate that an attribute has a maximum value.
     *
     * @param string $attribute
     * @param mixed $value
     * @param array $parameters
     * @return bool
     */
    protected function validateMax(string $attribute, $value, array $parameters)
    {
        $max = (int) $parameters[0];

        if (is_numeric($value)) {
            return $value <= $max;
        }

        if (is_string($value)) {
            return mb_strlen($value) <= $max;
        }

        if (is_array($value)) {
            return count($value) <= $max;
        }

        return false;
    }

    /**
     * Validate that an attribute is numeric.
     *
     * @param string $attribute
     * @param mixed $value
     * @param array $parameters
     * @return bool
     */
    protected function validateNumeric(string $attribute, $value, array $parameters)
    {
        return is_numeric($value);
    }

    /**
     * Validate that an attribute is an integer.
     *
     * @param string $attribute
     * @param mixed $value
     * @param array $parameters
     * @return bool
     */
    protected function validateInteger(string $attribute, $value, array $parameters)
    {
        return filter_var($value, FILTER_VALIDATE_INT) !== false;
    }

    /**
     * Validate that an attribute is a boolean.
     *
     * @param string $attribute
     * @param mixed $value
     * @param array $parameters
     * @return bool
     */
    protected function validateBoolean(string $attribute, $value, array $parameters)
    {
        $acceptable = [true, false, 0, 1, '0', '1'];

        return in_array($value, $acceptable, true);
    }

    /**
     * Validate that an attribute is an array.
     *
     * @param string $attribute
     * @param mixed $value
     * @param array $parameters
     * @return bool
     */
    protected function validateArray(string $attribute, $value, array $parameters)
    {
        return is_array($value);
    }

    /**
     * Validate that an attribute is in a list of values.
     *
     * @param string $attribute
     * @param mixed $value
     * @param array $parameters
     * @return bool
     */
    protected function validateIn(string $attribute, $value, array $parameters)
    {
        return in_array($value, $parameters);
    }

    /**
     * Validate that an attribute is not in a list of values.
     *
     * @param string $attribute
     * @param mixed $value
     * @param array $parameters
     * @return bool
     */
    protected function validateNotIn(string $attribute, $value, array $parameters)
    {
        return !in_array($value, $parameters);
    }

    /**
     * Validate that an attribute is a valid date.
     *
     * @param string $attribute
     * @param mixed $value
     * @param array $parameters
     * @return bool
     */
    protected function validateDate(string $attribute, $value, array $parameters)
    {
        if ($value instanceof \DateTime) {
            return true;
        }

        if (!is_string($value) && !is_numeric($value)) {
            return false;
        }

        $date = date_parse($value);

        return $date['error_count'] === 0 && $date['warning_count'] === 0;
    }

    /**
     * Validate that an attribute is between a minimum and maximum.
     *
     * @param string $attribute
     * @param mixed $value
     * @param array $parameters
     * @return bool
     */
    protected function validateBetween(string $attribute, $value, array $parameters)
    {
        list($min, $max) = $parameters;

        if (is_numeric($value)) {
            return $value >= $min && $value <= $max;
        }

        if (is_string($value)) {
            $length = mb_strlen($value);
            return $length >= $min && $length <= $max;
        }

        if (is_array($value)) {
            $count = count($value);
            return $count >= $min && $count <= $max;
        }

        return false;
    }

    /**
     * Validate that an attribute matches a regular expression.
     *
     * @param string $attribute
     * @param mixed $value
     * @param array $parameters
     * @return bool
     */
    protected function validateRegex(string $attribute, $value, array $parameters)
    {
        if (!is_string($value) && !is_numeric($value)) {
            return false;
        }

        $pattern = $parameters[0];

        return preg_match($pattern, $value) > 0;
    }
}
