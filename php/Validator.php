<?php

/**
 * @author Binsar Dwi Jasuma <binsarjr121@gmail.com>
 */

class Validator
{
    private array $errors = [];
    private array $data = [];

    private array $messages = [
        'alpha'				=> ':attribute hanya boleh berisi huruf.',
        'alpha_dash'		=> ':attribute hanya boleh berisi huruf, angka, strip, dan garis bawah.',
        'alpha_num'			=> ':attribute hanya boleh berisi huruf dan angka.',
        'different'			=> ':attribute dan :other harus berbeda.',
        'digits'			=> ':attribute harus terdiri dari :digits angka.',
        'digits_between'	=> ':attribute harus terdiri dari :min sampai :max angka.',
        'email'				=> ':attribute harus berupa alamat surel yang valid.',
        'integer'			=> ':attribute harus berupa bilangan bulat.',
        'ip'				=> ':attribute harus berupa alamat IP yang valid.',
        'ipv4'				=> ':attribute harus berupa alamat IPv4 yang valid.',
        'ipv6'				=> ':attribute harus berupa alamat IPv6 yang valid.',
        'json'				=> ':attribute harus berupa JSON string yang valid.',
        'max'				=> ':attribute tidak boleh melebihi :max karakter.',
        'min'				=> ':attribute harus melebihi :min karakter.',
        'numeric' 			=> ':attribute harus berupa angka.',
        'present' 			=> ':attribute wajib ada.',
        'required'			=> ':attribute tidak boleh kosong.',
        'same'				=> ':attribute dan :other harus sama.',
        'string'			=> ':attribute harus berupa string.',
        'url'				=> ':attribute harus berupa URL yang valid.',
        'uuid'				=> ':attribute harus merupakan UUID yang valid.',
    ];

    private string $attribute = '';
    private string $max = '';
    private string $min = '';
    private string $digits = '';
    private string $other = '';

    protected function resolveMessage(string $message)
    {
        $message = preg_replace("#:attribute#i", $this->attribute, $message);
        $message = preg_replace("#:max#i", $this->max, $message);
        $message = preg_replace("#:min#i", $this->min, $message);
        $message = preg_replace("#:other#i", $this->other, $message);
        $message = preg_replace("#:digits#i", $this->digits, $message);

        return $message;
    }
    
    private static $instance;
    public static function getInstantce(): self
    {
        if (self::$instance == null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * @param   array   $data   data yang akan divalidasi
     * @param   array   $rules  aturan validasi required|integer|string'
     *                          $rules = ['tahun' => 'required|integer', 'urai' => 'required|string']
     */
    public static function make(array $data, array $rules): self
    {
        $self = self::getInstantce();
        $self->data = $data;
        foreach ($rules as $parameter => $rules) {
            $self->attribute = $parameter;

            if (!is_array($rules)) {
                $rules = explode('|', $rules);
            }

            foreach ($rules as $rule) {
                try {
                    if (strpos($rule, ':')) {
                        list($rule, $additional) = explode(':', $rule);
                        $additional = explode(',', $additional);

                        $rule = strtolower($rule);
                        $self->{"{$rule}Validation"}($self->data[$self->attribute], $additional);
                    } else {
                        $rule = strtolower($rule);
                        $self->{"{$rule}Validation"}($self->data[$self->attribute]);
                    }
                } catch (\Throwable $th) {
                    throw new Exception("Unknown Validation rule \"$rule\"");
                }
            }
        }
        return $self;
    }

    public function fails(): bool
    {
        return boolval(!empty($this->errors));
    }

    public function errors(): array
    {
        return $this->errors;
    }

    protected function addError(string $messageKey, string $attribute = null): void
    {
        $this->errors[$attribute ?? $this->attribute][] = $this->resolveMessage($this->messages[$messageKey]);
    }

    
    //  ============================

    
    public function requiredValidation($data): void
    {
        $data = !is_array($data) ? trim($data) : $data;
        if (empty($data)) {
            $this->addError('required');
        }
    }

    public function numericValidation($data): void
    {
        if (!is_numeric($data)) {
            $this->addError("numeric");
        }
    }

    public function integerValidation($data): void
    {
        if (!is_integer($data)) {
            $this->addError("integer");
        }
    }

    public function maxValidation($data, array $additional)
    {
        list($value) = $additional;
        $this->max = $value;
        if (strlen($data) > $value) {
            $this->addError('max');
        }
    }

    public function minValidation($data, array $additional)
    {
        list($value) = $additional;
        $this->min = $value;
        if (strlen($data) < $value) {
            $this->addError('min');
        }
    }

    public function uuidValidation($data)
    {
        if (!is_string($data) || (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/', $data) !== 1)) {
            $this->addError('uuid');
        }
    }

    public function sameValidation($data, array $additional)
    {
        list($other) = $additional;
        $this->other = $other;
        if ($data !== $this->data[$other]) {
            $this->addError('same');
        }
    }

    public function differentValidation($data, array $additional)
    {
        list($other) = $additional;
        $this->other = $other;
        
        if ($data === $this->data[$other]) {
            $this->addError('different');
        }
    }

    public function stringValidation($data)
    {
        if (!is_string($data)) {
            $this->addError('string');
        }
    }

    public function urlValidation($data)
    {
        $regularExpression  = "((https?|ftp)\:\/\/)?"; # SCHEME Check
        $regularExpression .= "([a-z0-9+!*(),;?&=\$_.-]+(\:[a-z0-9+!*(),;?&=\$_.-]+)?@)?"; # User and Pass Check
        $regularExpression .= "([a-z0-9-.]*)\.([a-z]{2,3})"; # Host or IP Check
        $regularExpression .= "(\:[0-9]{2,5})?"; # Port Check
        $regularExpression .= "(\/([a-z0-9+\$_-]\.?)+)*\/?"; # Path Check
        $regularExpression .= "(\?[a-z+&\$_.-][a-z0-9;:@&%=+\/\$_.-]*)?"; # GET Query String Check
        $regularExpression .= "(#[a-z_.-][a-z0-9+\$_.-]*)?"; # Anchor Check

        
        if (preg_match("/^$regularExpression$/i", $data) == false) {
            $this->addError('url');
        }
    }

    public function jsonValidation($data)
    {
        try {
            json_decode($data);
            if (json_last_error() != JSON_ERROR_NONE) {
                $this->addError('json');
            }
        } catch (\Throwable $th) {
            $this->addError('json');
        }
    }

    public function ipValidation($data)
    {
        if (!filter_var($data, FILTER_VALIDATE_IP)) {
            $this->addError('ip');
        }
    }

    public function ipv4Validation($data)
    {
        if (!filter_var($data, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $this->addError('ipv4');
        }
    }

    public function ipv6Validation($data)
    {
        if (!filter_var($data, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $this->addError('ipv6');
        }
    }

    public function alphaValidation($data)
    {
        if (preg_match("#^[a-zA-Z]+$#i", $data) == false) {
            $this->addError('alpha');
        }
    }

    public function alpha_dashValidation($data)
    {
        if (preg_match("#^[a-zA-Z0-9\-\_]+$#i", $data) == false) {
            $this->addError('alpha_dash');
        }
    }

    public function alpha_numValidation($data)
    {
        if (preg_match("#^[a-zA-Z0-9]+$#i", $data) == false) {
            $this->addError('alpha_num');
        }
    }

    public function digitsValidation($data, array $additional)
    {
        list($digits) = $additional;
        $this->digits = $digits;
        
        if (preg_match("#^\d{{$digits}}$#i", $data) == false) {
            $this->addError('digits');
        }
    }

    public function digits_betweenValidation($data, array $additional)
    {
        list($min, $max) = $additional;
        $this->min = $min;
        $this->max = $max;
        if (preg_match("#^\d{{$min},{$max}}$#i", $data) == false) {
            $this->addError('digits_between');
        }
    }

    public function emailValidation($data)
    {
        if (filter_var($data, FILTER_VALIDATE_EMAIL) == false) {
            $this->addError('email');
        }
    }

    public function presentValidation($data)
    {
        if (!isset($this->data[$this->attribute])) {
            $this->addError('present');
        }
    }
}