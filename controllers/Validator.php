<?php

/** Small chainable validator: $v->required('name')->maxLen('name', 150); if ($v->fails()) ... */
final class Validator
{
    private array $errors = [];

    public function __construct(private array $data)
    {
    }

    public function get(string $field): string
    {
        return trim((string) ($this->data[$field] ?? ''));
    }

    public function required(string $field): self
    {
        if ($this->get($field) === '') {
            $this->fail($field, 'This field is required.');
        }
        return $this;
    }

    public function minLen(string $field, int $min): self
    {
        if ($this->get($field) !== '' && mb_strlen($this->get($field)) < $min) {
            $this->fail($field, "At least $min characters.");
        }
        return $this;
    }

    public function maxLen(string $field, int $max): self
    {
        if (mb_strlen($this->get($field)) > $max) {
            $this->fail($field, "At most $max characters.");
        }
        return $this;
    }

    public function email(string $field): self
    {
        if ($this->get($field) !== '' && !filter_var($this->get($field), FILTER_VALIDATE_EMAIL)) {
            $this->fail($field, 'Invalid email address.');
        }
        return $this;
    }

    public function int(string $field, ?int $min = null, ?int $max = null): self
    {
        $v = $this->get($field);
        if ($v !== '' && filter_var($v, FILTER_VALIDATE_INT) === false) {
            $this->fail($field, 'Must be a whole number.');
        } elseif ($v !== '' && (($min !== null && (int) $v < $min) || ($max !== null && (int) $v > $max))) {
            $this->fail($field, 'Number out of range.');
        }
        return $this;
    }

    public function decimal(string $field, ?float $min = null, ?float $max = null): self
    {
        $v = $this->get($field);
        if ($v !== '' && !is_numeric($v)) {
            $this->fail($field, 'Must be a number.');
        } elseif ($v !== '' && (($min !== null && (float) $v < $min) || ($max !== null && (float) $v > $max))) {
            $this->fail($field, 'Number out of range.');
        }
        return $this;
    }

    public function in(string $field, array $allowed): self
    {
        if ($this->get($field) !== '' && !in_array($this->get($field), $allowed, true)) {
            $this->fail($field, 'Invalid choice.');
        }
        return $this;
    }

    public function fails(): bool
    {
        return $this->errors !== [];
    }

    public function errors(): array
    {
        return $this->errors;
    }

    private function fail(string $field, string $message): void
    {
        $this->errors[$field] ??= $message; // keep the first error per field
    }
}
