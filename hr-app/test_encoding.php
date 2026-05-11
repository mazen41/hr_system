<?php
$str = "ØºÙŠØ± Ù…ØµØ±Ø­";
echo mb_convert_encoding($str, 'Windows-1252', 'UTF-8') . "\n";
