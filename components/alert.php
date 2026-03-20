<?php
/**
 * Vision HR - Alert Component
 * Usage: renderAlert(['type' => 'success', 'title' => 'Done!', 'message' => 'Saved']);
 */

function renderAlert($options = []) {
    $type = $options['type'] ?? 'info'; // success|warning|danger|info
    $title = $options['title'] ?? '';
    $message = $options['message'] ?? '';
    $dismissible = $options['dismissible'] ?? false;
    
    $icons = [
        'success' => 'fas fa-check-circle',
        'warning' => 'fas fa-exclamation-triangle',
        'danger' => 'fas fa-times-circle',
        'info' => 'fas fa-info-circle'
    ];
    $icon = $icons[$type] ?? $icons['info'];
    
    echo "<div class=\"vhr-alert vhr-alert-{$type}\" x-data=\"{ show: true }\" x-show=\"show\" x-transition>";
    echo "<i class=\"{$icon} vhr-alert-icon\"></i>";
    echo "<div class=\"vhr-alert-content\">";
    if ($title) echo "<div class=\"vhr-alert-title\">{$title}</div>";
    echo "<div>{$message}</div>";
    echo "</div>";
    if ($dismissible) {
        echo "<button type=\"button\" @click=\"show = false\" class=\"vhr-btn-ghost vhr-btn-icon sm\"><i class=\"fas fa-times\"></i></button>";
    }
    echo "</div>";
}
