<?php
/**
 * Vision HR - Stat Card Component
 * Usage: <?php include 'components/stat-card.php'; renderStatCard([...options]); ?>
 * 
 * Options:
 * - value: The main number/value
 * - label: Description text
 * - icon: Font Awesome icon class (e.g., 'fas fa-users')
 * - color: brand|success|warning|danger|info
 * - trend: Optional trend value (e.g., '+12%')
 * - trend_dir: up|down
 * - link: Optional link URL
 */

function renderStatCard($options = []) {
    $value = $options['value'] ?? '0';
    $label = $options['label'] ?? '';
    $icon = $options['icon'] ?? 'fas fa-chart-bar';
    $color = $options['color'] ?? 'brand';
    $trend = $options['trend'] ?? null;
    $trend_dir = $options['trend_dir'] ?? 'up';
    $link = $options['link'] ?? null;
    
    $tag = $link ? 'a' : 'div';
    $href = $link ? "href=\"$link\"" : '';
    
    echo <<<HTML
    <{$tag} {$href} class="vhr-stat-card vhr-fade-in" style="--card-accent: var(--vhr-{$color});">
        <div class="vhr-stat-icon {$color}">
            <i class="{$icon}"></i>
        </div>
        <div class="vhr-stat-content">
            <div class="vhr-stat-value">{$value}</div>
            <div class="vhr-stat-label">{$label}</div>
HTML;
    
    if ($trend) {
        echo "<span class=\"vhr-stat-trend {$trend_dir}\"><i class=\"fas fa-arrow-" . ($trend_dir === 'up' ? 'up' : 'down') . "\"></i> {$trend}</span>";
    }
    
    echo <<<HTML
        </div>
    </{$tag}>
HTML;
}
