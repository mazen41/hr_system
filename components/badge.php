<?php
/**
 * Vision HR - Badge Component
 * Usage: echo badge('نشط', 'success'); or badge('معلق', 'warning', true);
 */

function badge($text, $type = 'gray', $solid = false) {
    $class = $solid ? "vhr-badge-solid-{$type}" : "vhr-badge-{$type}";
    return "<span class=\"vhr-badge {$class}\">{$text}</span>";
}

function statusBadge($status) {
    $map = [
        0 => ['text' => 'معلق', 'type' => 'warning'],
        1 => ['text' => 'موافق', 'type' => 'success'],
        2 => ['text' => 'مرفوض', 'type' => 'danger'],
        'pending' => ['text' => 'معلق', 'type' => 'warning'],
        'approved' => ['text' => 'موافق', 'type' => 'success'],
        'rejected' => ['text' => 'مرفوض', 'type' => 'danger'],
        'active' => ['text' => 'نشط', 'type' => 'success'],
        'inactive' => ['text' => 'غير نشط', 'type' => 'gray'],
    ];
    
    $config = $map[$status] ?? ['text' => $status, 'type' => 'gray'];
    return badge($config['text'], $config['type']);
}
