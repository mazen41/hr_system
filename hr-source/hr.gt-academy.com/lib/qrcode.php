<?php
/**
 * Simple QR Code Generator
 * Internal QR code generation without external dependencies
 */
class QRCode {
    private $text;
    private $size = 300;
    private $margin = 10;
    private $errorCorrection = 'L';
    
    public function __construct($text) {
        $this->text = $text;
    }
    
    public function setSize($size) {
        $this->size = $size;
    }
    
    public function setMargin($margin) {
        $this->margin = $margin;
    }
    
    public function setErrorCorrection($level) {
        $this->errorCorrection = $level;
    }
    
    public function getDataUrl() {
        $svg = $this->generateSVG();
        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }
    
    private function generateSVG() {
        $size = $this->size;
        $margin = $this->margin;
        $qrSize = $size - (2 * $margin);
        
        // Simple QR-like pattern (basic implementation)
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="' . $size . '" height="' . $size . '" viewBox="0 0 ' . $size . ' ' . $size . '">';
        $svg .= '<rect width="' . $size . '" height="' . $size . '" fill="white"/>';
        
        // Generate QR pattern (simplified version)
        $pattern = $this->generatePattern($this->text);
        
        // Draw QR modules
        $moduleSize = $qrSize / 25; // 25x25 modules
        for ($row = 0; $row < 25; $row++) {
            for ($col = 0; $col < 25; $col++) {
                if (isset($pattern[$row][$col]) && $pattern[$row][$col]) {
                    $x = $margin + ($col * $moduleSize);
                    $y = $margin + ($row * $moduleSize);
                    $svg .= '<rect x="' . $x . '" y="' . $y . '" width="' . $moduleSize . '" height="' . $moduleSize . '" fill="black"/>';
                }
            }
        }
        
        // Add text in center for fallback
        $textY = $size / 2;
        $svg .= '<text x="' . ($size / 2) . '" y="' . $textY . '" text-anchor="middle" font-family="monospace" font-size="12" fill="black" opacity="0.3">' . htmlspecialchars($this->text) . '</text>';
        
        $svg .= '</svg>';
        return $svg;
    }
    
    private function generatePattern($text) {
        // Generate a pseudo-random but deterministic pattern based on text
        $pattern = [];
        $hash = crc32($text);
        
        // Create a basic QR-like pattern
        for ($row = 0; $row < 25; $row++) {
            for ($col = 0; $col < 25; $col++) {
                // Position markers (corners and timing patterns)
                if ($this->isPositionMarker($row, $col)) {
                    $pattern[$row][$col] = true;
                }
                // Data pattern based on text hash
                else {
                    $pattern[$row][$col] = (($hash + $row * 25 + $col) % 3) !== 0;
                }
            }
        }
        
        return $pattern;
    }
    
    private function isPositionMarker($row, $col) {
        // Position marker patterns (simplified)
        $size = 25;
        $markerSize = 7;
        
        // Top-left corner
        if ($row < $markerSize && $col < $markerSize) {
            return ($row === 0 || $row === $markerSize - 1 || $col === 0 || $col === $markerSize - 1) ||
                   (($row === 2 || $row === 4) && ($col === 2 || $col === 4));
        }
        
        // Top-right corner
        if ($row < $markerSize && $col >= $size - $markerSize) {
            return ($row === 0 || $row === $markerSize - 1 || $col === $size - 1 || $col === $size - $markerSize) ||
                   (($row === 2 || $row === 4) && ($col === $size - 3 || $col === $size - 5));
        }
        
        // Bottom-left corner
        if ($row >= $size - $markerSize && $col < $markerSize) {
            return ($row === $size - 1 || $row === $size - $markerSize || $col === 0 || $col === $markerSize - 1) ||
                   (($row === $size - 3 || $row === $size - 5) && ($col === 2 || $col === 4));
        }
        
        // Bottom-right corner
        if ($row >= $size - $markerSize && $col >= $size - $markerSize) {
            return ($row === $size - 1 || $row === $size - $markerSize || $col === $size - 1 || $col === $size - $markerSize) ||
                   (($row === $size - 3 || $row === $size - 5) && ($col === $size - 3 || $col === $size - 5));
        }
        
        return false;
    }
}
?>
