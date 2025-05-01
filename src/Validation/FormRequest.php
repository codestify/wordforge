<?php

namespace WordForge\Validation;

use WordForge\Http\Request;

/**
 * Form Request class for request validation
 *
 * Provides Laravel-like form request validation.
 *
 * @package WordForge\Validation
 */
abstract class FormRequest extends Request
{
    /**
     * Create a new form request instance.
     *
     * @param  \WP_REST_Request  $wpRequest
     *
     * @return void
     */
    public function __construct(\WP_REST_Request $wpRequest)
    {
        parent::__construct($wpRequest);
    }

    /**
     * Validate the request.
     *
     * @param  array  $rules
     * @param  array  $messages
     * @param  array  $customAttributes
     *
     * @return bool
     * @throws \WordForge\Validation\ValidationException
     */
    public function validate(array $rules, array $messages = [], array $customAttributes = []): bool
    {
        if (! $this->authorize()) {
            throw new \RuntimeException('Unauthorized');
        }

        $data = $this->all();

        $validator = new Validator(
            $data,
            $rules,
            $messages ?: $this->messages(),
            $customAttributes ?: $this->attributes()
        );

        if ($validator->fails()) {
            throw new \WordForge\Validation\ValidationException($validator);
        }

        return true;
    }

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array
     */
    public function messages()
    {
        return [];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array
     */
    public function attributes()
    {
        return [];
    }

    /**
     * Get the validated data from the request.
     *
     * @param  array  $rules
     * @param  array  $messages
     * @param  array  $customAttributes  *
     *
     * @return array
     */
    public function validated(array $rules, array $messages = [], array $customAttributes = []): array
    {
        $rules = $this->rules();
        $data  = $this->all();

        $validated = [];

        foreach ($rules as $key => $rule) {
            if (isset($data[$key])) {
                $validated[$key] = $data[$key];
            }
        }

        return $validated;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    abstract public function rules();
}
