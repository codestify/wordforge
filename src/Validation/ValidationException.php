<?php

namespace WordForge\Validation;

/**
 * Validation Exception
 *
 * @package WordForge\Validation
 */
class ValidationException extends \Exception
{
    /**
     * The validator instance.
     *
     * @var Validator
     */
    protected $validator;

    /**
     * The recommended response status code.
     *
     * @var int
     */
    protected $status = 422;

    /**
     * Create a new validation exception instance.
     *
     * @param Validator $validator
     * @param string $message
     * @return void
     */
    public function __construct(Validator $validator, $message = 'The given data was invalid.')
    {
        parent::__construct($message);

        $this->validator = $validator;
    }

    /**
     * Get the validation errors.
     *
     * @return array
     */
    public function errors()
    {
        return $this->validator->errors();
    }

    /**
     * Set the recommended status code to be used for the response.
     *
     * @param int $status
     * @return $this
     */
    public function status(int $status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get the recommended status code to be used for the response.
     *
     * @return int
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Get the validator instance.
     *
     * @return Validator
     */
    public function validator()
    {
        return $this->validator;
    }
}
