<?php
/**
 * Core helper functions for Vision HR
 */

/**
 * Sanitize input data
 */
function sanitizingData($data) {
    if (is_array($data)) {
        return array_map('sanitizingData', $data);
    }
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Format number with commas
 */
function formatNumber($number, $decimals = 2) {
    return number_format((float)$number, $decimals, '.', ',');
}

/**
 * Upload file helper
 */
function uploadFile($file, $destination, $allowed_types = ['jpg','jpeg','png','pdf','doc','docx','xls','xlsx']) {
    if (empty($file['name'])) return false;
    
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed_types)) {
        return ['error' => 'نوع الملف غير مسموح'];
    }
    
    $max_size = 10 * 1024 * 1024; // 10MB
    if ($file['size'] > $max_size) {
        return ['error' => 'حجم الملف كبير جداً'];
    }
    
    if (!is_dir($destination)) {
        mkdir($destination, 0755, true);
    }
    
    $filename = uniqid() . '_' . time() . '.' . $ext;
    $filepath = $destination . '/' . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return ['success' => true, 'filename' => $filename];
    }
    
    return ['error' => 'فشل رفع الملف'];
}
