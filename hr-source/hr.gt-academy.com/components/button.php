<?php
/**
 * Vision HR - Button Component
 * Usage: echo btn('حفظ', 'primary', ['icon' => 'fas fa-save', 'type' => 'submit']);
 */

function btn($text, $variant = 'primary', $options = []) {
    $type = $options['type'] ?? 'button';
    $icon = $options['icon'] ?? '';
    $size = $options['size'] ?? ''; // sm, lg
    $class = $options['class'] ?? '';
    $href = $options['href'] ?? '';
    $disabled = $options['disabled'] ?? false;
    $loading = $options['loading'] ?? false;
    $id = $options['id'] ?? '';
    $onclick = $options['onclick'] ?? '';
    $alpine = $options['alpine'] ?? '';
    
    $sizeClass = $size ? "vhr-btn-$size" : '';
    $loadingClass = $loading ? 'vhr-loading' : '';
    $disabledAttr = $disabled ? 'disabled' : '';
    $idAttr = $id ? "id=\"$id\"" : '';
    $onclickAttr = $onclick ? "onclick=\"$onclick\"" : '';
    
    $iconHtml = $icon ? "<i class=\"$icon\"></i>" : '';
    
    $classes = "vhr-btn vhr-btn-$variant $sizeClass $loadingClass $class";
    
    if ($href) {
        return "<a href=\"$href\" class=\"$classes\" $alpine>$iconHtml $text</a>";
    }
    
    return "<button type=\"$type\" class=\"$classes\" $idAttr $onclickAttr $disabledAttr $alpine>$iconHtml $text</button>";
}

/**
 * Icon-only button
 */
function btnIcon($icon, $variant = 'ghost', $options = []) {
    $size = $options['size'] ?? '';
    $class = $options['class'] ?? '';
    $href = $options['href'] ?? '';
    $title = $options['title'] ?? '';
    $onclick = $options['onclick'] ?? '';
    $alpine = $options['alpine'] ?? '';
    
    $sizeClass = $size === 'sm' ? 'sm' : '';
    $titleAttr = $title ? "title=\"$title\"" : '';
    $onclickAttr = $onclick ? "onclick=\"$onclick\"" : '';
    
    $classes = "vhr-btn-icon vhr-btn-$variant $sizeClass $class";
    
    if ($href) {
        return "<a href=\"$href\" class=\"$classes\" $titleAttr $alpine><i class=\"$icon\"></i></a>";
    }
    
    return "<button type=\"button\" class=\"$classes\" $titleAttr $onclickAttr $alpine><i class=\"$icon\"></i></button>";
}

/**
 * Button group
 */
function btnGroupStart($class = '') {
    echo "<div class=\"vhr-flex vhr-gap-2 $class\">";
}

function btnGroupEnd() {
    echo "</div>";
}
