<?php
namespace moss\standard;

/**
 * Description of sanitizeValues
 *
 * @author mosspence
 * clean up user-entered data
 */
class sanitizeValues {
    public static final function sanitizeString($string)
    {
        $return_string = NULL;
        
        $sanitized_string = filter_var($string, FILTER_SANITIZE_STRING);
        if (filter_var($sanitized_string, FILTER_SANITIZE_STRING))
        {
            $return_string = $sanitized_string;
        }
        return $return_string;
    }
    public static final function sanitizeEmail($string)
    {
        $return_string = NULL;
        $sanitized_string = filter_var($string, FILTER_SANITIZE_EMAIL);
        if (filter_var($sanitized_string, FILTER_SANITIZE_EMAIL))
        {
            $return_string = $sanitized_string;
        }
        return $return_string;
    }
    public static final function sanitizeINT($value, $min=0, $max=50000)
    {
        $return_value = NULL;
        $options = NULL;
        if(
           (filter_var($min, FILTER_VALIDATE_INT)) &&
           (filter_var($max, FILTER_VALIDATE_INT)) &&
           ($min < $max)
          )
        {
         $options = array('min_range' => $min, 'max_range' => $max);
        }
        $sanitized_value = filter_var($value, FILTER_VALIDATE_INT, $options);
        if (filter_var($sanitized_value, FILTER_VALIDATE_INT))
        {
            $return_value = $sanitized_value;
        }
        return $return_value;
    }
}

?>
