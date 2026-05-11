<?php
/**
 * Vision HR - Request Validator
 * Validates API request input with common rules
 */

class Validator
{
    private array $errors = [];
    private array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Create validator from JSON request body
     */
    public static function fromRequest(): self
    {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);

        if (!is_array($data)) {
            $data = $_POST;
        }

        return new self($data);
    }

    /**
     * Validate required fields
     */
    public function required(string $field, string $label = ''): self
    {
        $label = $label ?: $field;
        if (!isset($this->data[$field]) || trim((string) $this->data[$field]) === '') {
            $this->errors[$field] = "حقل {$label} مطلوب";
        }
        return $this;
    }

    /**
     * Validate email format
     */
    public function email(string $field, string $label = ''): self
    {
        $label = $label ?: $field;
        if (isset($this->data[$field]) && $this->data[$field] !== '') {
            if (!filter_var($this->data[$field], FILTER_VALIDATE_EMAIL)) {
                $this->errors[$field] = "حقل {$label} يجب أن يكون بريدًا إلكترونيًا صالحًا";
            }
        }
        return $this;
    }

    /**
     * Validate minimum string length
     */
    public function minLength(string $field, int $min, string $label = ''): self
    {
        $label = $label ?: $field;
        if (isset($this->data[$field]) && mb_strlen((string) $this->data[$field]) < $min) {
            $this->errors[$field] = "حقل {$label} يجب أن يحتوي على {$min} أحرف على الأقل";
        }
        return $this;
    }

    /**
     * Validate maximum string length
     */
    public function maxLength(string $field, int $max, string $label = ''): self
    {
        $label = $label ?: $field;
        if (isset($this->data[$field]) && mb_strlen((string) $this->data[$field]) > $max) {
            $this->errors[$field] = "حقل {$label} يجب ألا يتجاوز {$max} حرفًا";
        }
        return $this;
    }

    /**
     * Validate numeric value
     */
    public function numeric(string $field, string $label = ''): self
    {
        $label = $label ?: $field;
        if (isset($this->data[$field]) && $this->data[$field] !== '') {
            if (!is_numeric($this->data[$field])) {
                $this->errors[$field] = "حقل {$label} يجب أن يكون رقمًا";
            }
        }
        return $this;
    }

    /**
     * Validate integer value
     */
    public function integer(string $field, string $label = ''): self
    {
        $label = $label ?: $field;
        if (isset($this->data[$field]) && $this->data[$field] !== '') {
            if (filter_var($this->data[$field], FILTER_VALIDATE_INT) === false) {
                $this->errors[$field] = "حقل {$label} يجب أن يكون عددًا صحيحًا";
            }
        }
        return $this;
    }

    /**
     * Validate date format
     */
    public function date(string $field, string $format = 'Y-m-d', string $label = ''): self
    {
        $label = $label ?: $field;
        if (isset($this->data[$field]) && $this->data[$field] !== '') {
            $d = DateTime::createFromFormat($format, $this->data[$field]);
            if (!$d || $d->format($format) !== $this->data[$field]) {
                $this->errors[$field] = "حقل {$label} يجب أن يكون تاريخًا بالصيغة {$format}";
            }
        }
        return $this;
    }

    /**
     * Validate value is in a set of allowed values
     */
    public function in(string $field, array $allowed, string $label = ''): self
    {
        $label = $label ?: $field;
        if (isset($this->data[$field]) && !in_array($this->data[$field], $allowed, true)) {
            $this->errors[$field] = "حقل {$label} يجب أن يكون إحدى القيم التالية: " . implode('، ', $allowed);
        }
        return $this;
    }

    /**
     * Validate latitude
     */
    public function latitude(string $field, string $label = ''): self
    {
        $label = $label ?: $field;
        if (isset($this->data[$field]) && $this->data[$field] !== '') {
            $val = (float) $this->data[$field];
            if ($val < -90 || $val > 90) {
                $this->errors[$field] = "حقل {$label} يجب أن يكون خط عرض صالحًا بين -90 و90";
            }
        }
        return $this;
    }

    /**
     * Validate longitude
     */
    public function longitude(string $field, string $label = ''): self
    {
        $label = $label ?: $field;
        if (isset($this->data[$field]) && $this->data[$field] !== '') {
            $val = (float) $this->data[$field];
            if ($val < -180 || $val > 180) {
                $this->errors[$field] = "حقل {$label} يجب أن يكون خط طول صالحًا بين -180 و180";
            }
        }
        return $this;
    }

    /**
     * Validate minimum numeric value
     */
    public function min(string $field, float $min, string $label = ''): self
    {
        $label = $label ?: $field;
        if (isset($this->data[$field]) && is_numeric($this->data[$field])) {
            if ((float) $this->data[$field] < $min) {
                $this->errors[$field] = "حقل {$label} يجب أن يكون {$min} على الأقل";
            }
        }
        return $this;
    }

    /**
     * Check if validation passed
     */
    public function passes(): bool
    {
        return empty($this->errors);
    }

    /**
     * Check if validation failed
     */
    public function fails(): bool
    {
        return !empty($this->errors);
    }

    /**
     * Get validation errors
     */
    public function errors(): array
    {
        return $this->errors;
    }

    /**
     * Get a validated/sanitized value
     */
    public function get(string $field, $default = null)
    {
        return $this->data[$field] ?? $default;
    }

    /**
     * Get all input data
     */
    public function all(): array
    {
        return $this->data;
    }

    /**
     * Get only specified fields
     */
    public function only(array $fields): array
    {
        return array_intersect_key($this->data, array_flip($fields));
    }

    /**
     * Get pagination parameters from query string
     */
    public static function pagination(int $defaultPerPage = 20, int $maxPerPage = 100): array
    {
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $perPage = min($maxPerPage, max(1, (int) ($_GET['per_page'] ?? $defaultPerPage)));
        $offset = ($page - 1) * $perPage;

        return [
            'page'     => $page,
            'per_page' => $perPage,
            'offset'   => $offset,
        ];
    }
}
