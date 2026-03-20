<?php
/**
 * Vision HR - Card Component
 * Usage: 
 *   cardStart(['title' => 'Title', 'icon' => 'fas fa-users']);
 *   // card content here
 *   cardEnd();
 */

function cardStart($options = []) {
    $title = $options['title'] ?? '';
    $icon = $options['icon'] ?? '';
    $class = $options['class'] ?? '';
    $headerActions = $options['header_actions'] ?? '';
    
    echo "<div class=\"vhr-card {$class}\">";
    
    if ($title) {
        echo "<div class=\"vhr-card-header\">";
        echo "<span>";
        if ($icon) echo "<i class=\"{$icon}\" style=\"margin-left:0.5rem;\"></i>";
        echo $title;
        echo "</span>";
        if ($headerActions) echo "<div>{$headerActions}</div>";
        echo "</div>";
    }
    
    echo "<div class=\"vhr-card-body\">";
}

function cardEnd($footer = null) {
    echo "</div>"; // close body
    
    if ($footer) {
        echo "<div class=\"vhr-card-footer\">{$footer}</div>";
    }
    
    echo "</div>"; // close card
}
