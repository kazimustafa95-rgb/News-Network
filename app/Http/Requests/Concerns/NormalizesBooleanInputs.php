<?php

namespace App\Http\Requests\Concerns;

trait NormalizesBooleanInputs
{
    protected function prepareForValidation(): void
    {
        foreach ($this->booleanFields() as $field) {
            if (! $this->has($field)) {
                continue;
            }

            $normalized = filter_var($this->input($field), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

            if ($normalized !== null) {
                $this->merge([
                    $field => $normalized,
                ]);
            }
        }
    }

    /**
     * @return array<int, string>
     */
    abstract protected function booleanFields(): array;
}
