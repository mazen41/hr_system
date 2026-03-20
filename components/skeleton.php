<?php
/**
 * Vision HR - Skeleton Loader Components
 * Usage: renderSkeleton('card'); or renderSkeleton('stat', 4);
 */

function renderSkeleton($type = 'text', $count = 1) {
    for ($i = 0; $i < $count; $i++) {
        switch ($type) {
            case 'stat':
                echo <<<HTML
                <div class="vhr-stat-card" style="min-height: 100px;">
                    <div class="vhr-skeleton vhr-skeleton-circle" style="width: 56px; height: 56px;"></div>
                    <div style="flex: 1;">
                        <div class="vhr-skeleton vhr-skeleton-text lg" style="width: 60%;"></div>
                        <div class="vhr-skeleton vhr-skeleton-text sm" style="width: 80%; margin-top: 0.5rem;"></div>
                    </div>
                </div>
HTML;
                break;
                
            case 'card':
                echo <<<HTML
                <div class="vhr-card">
                    <div class="vhr-card-header">
                        <div class="vhr-skeleton vhr-skeleton-text" style="width: 40%;"></div>
                    </div>
                    <div class="vhr-card-body">
                        <div class="vhr-skeleton vhr-skeleton-text" style="width: 100%;"></div>
                        <div class="vhr-skeleton vhr-skeleton-text" style="width: 90%;"></div>
                        <div class="vhr-skeleton vhr-skeleton-text" style="width: 75%;"></div>
                    </div>
                </div>
HTML;
                break;
                
            case 'list-item':
                echo <<<HTML
                <div class="vhr-flex vhr-items-center vhr-gap-3 vhr-p-3" style="border-bottom: 1px solid var(--vhr-gray-100);">
                    <div class="vhr-skeleton vhr-skeleton-circle" style="width: 40px; height: 40px;"></div>
                    <div style="flex: 1;">
                        <div class="vhr-skeleton vhr-skeleton-text" style="width: 60%;"></div>
                        <div class="vhr-skeleton vhr-skeleton-text sm" style="width: 40%; margin-top: 0.25rem;"></div>
                    </div>
                    <div class="vhr-skeleton" style="width: 60px; height: 24px; border-radius: 9999px;"></div>
                </div>
HTML;
                break;
                
            case 'chart':
                echo <<<HTML
                <div class="vhr-skeleton" style="width: 100%; height: 200px; border-radius: var(--vhr-radius-lg);"></div>
HTML;
                break;
                
            case 'table-row':
                echo <<<HTML
                <tr>
                    <td><div class="vhr-skeleton vhr-skeleton-text" style="width: 80%;"></div></td>
                    <td><div class="vhr-skeleton vhr-skeleton-text" style="width: 60%;"></div></td>
                    <td><div class="vhr-skeleton vhr-skeleton-text" style="width: 70%;"></div></td>
                    <td><div class="vhr-skeleton" style="width: 60px; height: 24px; border-radius: 9999px;"></div></td>
                </tr>
HTML;
                break;
                
            default: // text
                echo '<div class="vhr-skeleton vhr-skeleton-text" style="width: 100%;"></div>';
        }
    }
}

/**
 * Render a full skeleton dashboard
 */
function renderDashboardSkeleton() {
    echo '<div class="vhr-grid vhr-grid-4 vhr-mb-6">';
    renderSkeleton('stat', 4);
    echo '</div>';
    
    echo '<div class="vhr-grid vhr-grid-3 vhr-mb-6">';
    renderSkeleton('card', 3);
    echo '</div>';
}
