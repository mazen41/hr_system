<?php
$content = file_get_contents('index.php');
$tokens = token_get_all($content);
$output = '';

$mojibake_pattern = '/(Ø|Ù|º|Š|±|µ|¬)/';

foreach ($tokens as $token) {
    if (is_array($token)) {
        if ($token[0] === T_CONSTANT_ENCAPSED_STRING) {
            $str = $token[1];
            // Check if it looks like it has mojibake characters
            if (preg_match($mojibake_pattern, $str)) {
                // Determine quote type
                $quote = $str[0];
                $inner = substr($str, 1, -1);
                
                // Decode
                $fixed_inner = mb_convert_encoding($inner, 'Windows-1252', 'UTF-8');
                
                // Re-encode inner content properly based on quote type
                $str = $quote . $fixed_inner . $quote;
            }
            $output .= $str;
        } else {
            $output .= $token[1];
        }
    } else {
        $output .= $token;
    }
}

file_put_contents('index_fixed.php', $output);
echo "Fixed mojibake and saved to index_fixed.php\n";
