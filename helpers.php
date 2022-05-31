<?php

if (!function_exists('autocomplete_url')) {
    /**
     * Return a fully qualified autocomplete url
     * @param string $relative_path
     * @param bool $api
     * @return string
     */
    function autocomplete_url($relative_path='', $api=false) {
        $url = $api ? constant("AUTOCOMPLETE_URL_API") : constant('AUTOCOMPLETE_URL');
        $url = $url . '/' . ltrim($relative_path, '\/');
        if (str_contains($url, '#')) {
          $url = str_replace('#', constant('AUTOCOMPLETE_ATTRIBUTION') . '#', $url);
        } else {
          $url = $url . constant('AUTOCOMPLETE_ATTRIBUTION');
        }
        return $url;
    }
}

if (! function_exists('value')) {
    /**
     * Return the default value of the given value.
     *
     * @param  mixed  $value
     * @return mixed
     */
    function value($value)
    {
        return $value instanceof Closure ? $value() : $value;
    }
}

if (!function_exists('array_get')) {
    /**
     * Get an item from an array using "dot" notation.
     *
     * @param  array  $array
     * @param  string|int|null  $key
     * @param  mixed  $default
     * @return mixed
     */
    function array_get($array, $key, $default=null) {
        if (! is_array($array)) {
            return value($default);
        }

        if (is_null($key)) {
            return $array;
        }

        if (array_key_exists($key, $array)) {
            return $array[$key];
        }

        if (strpos($key, '.') === false) {
            return $array[$key] ?? value($default);
        }

        foreach (explode('.', $key) as $segment) {
            if (is_array($array) && array_key_exists($segment, $array)) {
                $array = $array[$segment];
            } else {
                return value($default);
            }
        }

        return $array;
    }
}

if (!function_exists('sanitize_input')) {
    /**
     * Sanitize the given input
     * @param $data
     * @return string
     */
    function sanitize_input($data) {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data);
        return $data;
    }
}

if (!function_exists('validate_data')) {
    /**
     * Validate the given error, failure will return messages
     * @param $data
     * @param $validators
     * @param array $messages
     * @return array
     */
    function validate_data($data, $validators, $messages=[]) {
        $errors = [];
        foreach($data as $name => $value) {
            if($validator = array_get($validators, $name)) {
                if (!is_callable($validator)) {
                    $errors[$name.'_validator'] = 'Validator is not callable.';
                }
                if (!$validator($value)) {
                    if (!$message = array_get($messages, $name)) {
                        $message = sprintf('Invalid Input: %s', $name);
                    }
                    $errors[$name] = $message;
                }
            }
        }
        return $errors;
    }
}
