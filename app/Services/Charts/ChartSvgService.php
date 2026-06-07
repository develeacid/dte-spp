<?php

namespace App\Services\Charts;

class ChartSvgService
{
    // Color constants matching the plan's palette
    private const SEMAFORO = [
        'verde' => '#22C55E',
        'amarillo' => '#EAB308',
        'rojo' => '#EF4444',
        'rojo_alto' => '#A855F7',
        'gris' => '#9CA3AF',
    ];

    private const SEMAFORO_LIGHT = [
        'verde' => '#BBF7D0',
        'amarillo' => '#FEF08A',
        'rojo' => '#FECACA',
        'rojo_alto' => '#E9D5FF',
        'gris' => '#E5E7EB',
    ];

    private const SERIES = [
        '#3B82F6', // azul
        '#F59E0B', // ambar
        '#14B8A6', // teal
        '#8B5CF6', // violeta
        '#EC4899', // rosa
    ];

    /**
     * Donut chart SVG.
     *
     * @param  array  $data  [['label' => 'Verde', 'value' => 10], ...]
     * @param  array  $colors  hex colors (falls back to SERIES)
     * @return string SVG markup
     */
    public function donut(array $data, array $colors = [], ?string $centerText = null): string
    {
        $size = 200;
        $cx = $size / 2;
        $cy = $size / 2;
        $outerR = 80;
        $innerR = 52;

        $total = array_sum(array_column($data, 'value'));
        if ($total <= 0) {
            return $this->emptySvg($size, $size, 'Sin datos');
        }

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="'.$size.'" height="'.$size.'" viewBox="0 0 '.$size.' '.$size.'">';

        $currentAngle = -90; // start at top

        foreach ($data as $i => $item) {
            $value = (float) $item['value'];
            if ($value <= 0) {
                continue;
            }

            $sliceAngle = ($value / $total) * 360;
            $endAngle = $currentAngle + $sliceAngle;
            $color = $colors[$i] ?? self::SERIES[$i % count(self::SERIES)];

            // For a full circle (or nearly), we need two arcs
            if ($sliceAngle >= 359.99) {
                $svg .= $this->donutSlice($cx, $cy, $outerR, $innerR, $currentAngle, $currentAngle + 179.99, $color);
                $svg .= $this->donutSlice($cx, $cy, $outerR, $innerR, $currentAngle + 179.99, $endAngle, $color);
            } else {
                $svg .= $this->donutSlice($cx, $cy, $outerR, $innerR, $currentAngle, $endAngle, $color);
            }

            $currentAngle = $endAngle;
        }

        // Center text
        if ($centerText !== null) {
            $svg .= '<text x="'.$cx.'" y="'.($cy + 5).'" text-anchor="middle" font-size="16" font-weight="bold" fill="#1F2937">'
                .$this->escSvg($centerText).'</text>';
        }

        // Legend below (compact)
        $legendY = $size;
        $legendHeight = count($data) * 18 + 8;
        $svg = str_replace(
            'viewBox="0 0 '.$size.' '.$size.'"',
            'viewBox="0 0 '.$size.' '.($size + $legendHeight).'"',
            $svg
        );
        $svg = str_replace(
            'height="'.$size.'"',
            'height="'.($size + $legendHeight).'"',
            $svg
        );

        foreach ($data as $i => $item) {
            $color = $colors[$i] ?? self::SERIES[$i % count(self::SERIES)];
            $ly = $legendY + 14 + ($i * 18);
            $svg .= '<rect x="10" y="'.($ly - 9).'" width="10" height="10" fill="'.$color.'" />';
            $svg .= '<text x="25" y="'.$ly.'" font-size="11" fill="#374151">'
                .$this->escSvg($item['label'] ?? '').' ('.$item['value'].')</text>';
        }

        $svg .= '</svg>';

        return $svg;
    }

    /**
     * Horizontal bar chart SVG.
     *
     * @param  array  $categories  labels
     * @param  array  $series  [['name' => 'Real', 'data' => [75, 50]], ...]
     * @return string SVG markup
     */
    public function barHorizontal(array $categories, array $series, ?float $referenceLine = null): string
    {
        $width = 500;
        $labelWidth = 120;
        $barAreaWidth = $width - $labelWidth - 60; // 60 for right margin + value labels
        $barHeight = 20;
        $groupGap = 12;
        $barGap = 4;
        $seriesCount = count($series);
        $groupHeight = ($barHeight * $seriesCount) + ($barGap * ($seriesCount - 1));
        $topPadding = 30;
        $height = $topPadding + (count($categories) * ($groupHeight + $groupGap)) + 20;

        // Find max value across all series
        $maxVal = 0;
        foreach ($series as $s) {
            foreach ($s['data'] as $v) {
                $maxVal = max($maxVal, abs((float) $v));
            }
        }
        if ($maxVal <= 0) {
            $maxVal = 100;
        }

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="'.$width.'" height="'.$height.'" viewBox="0 0 '.$width.' '.$height.'">';

        // Legend row at top
        foreach ($series as $si => $s) {
            $color = self::SERIES[$si % count(self::SERIES)];
            $lx = $labelWidth + ($si * 120);
            $svg .= '<rect x="'.$lx.'" y="5" width="10" height="10" fill="'.$color.'" />';
            $svg .= '<text x="'.($lx + 14).'" y="14" font-size="10" fill="#374151">'.$this->escSvg($s['name'] ?? '').'</text>';
        }

        foreach ($categories as $ci => $cat) {
            $groupY = $topPadding + $ci * ($groupHeight + $groupGap);

            // Category label (truncate if too long)
            $displayLabel = mb_strlen($cat) > 18 ? mb_substr($cat, 0, 17).'...' : $cat;
            $labelY = $groupY + ($groupHeight / 2) + 4;
            $svg .= '<text x="'.($labelWidth - 5).'" y="'.$labelY.'" text-anchor="end" font-size="11" fill="#374151">'
                .$this->escSvg($displayLabel).'</text>';

            foreach ($series as $si => $s) {
                $val = (float) ($s['data'][$ci] ?? 0);
                $barW = ($val / $maxVal) * $barAreaWidth;
                $barW = max($barW, 1); // minimum visible
                $barY = $groupY + $si * ($barHeight + $barGap);
                $color = self::SERIES[$si % count(self::SERIES)];

                $svg .= '<rect x="'.$labelWidth.'" y="'.$barY.'" width="'.round($barW, 1)
                    .'" height="'.$barHeight.'" fill="'.$color.'" rx="2" />';

                // Value label
                $svg .= '<text x="'.($labelWidth + $barW + 4).'" y="'.($barY + $barHeight - 5)
                    .'" font-size="10" fill="#6B7280">'.round($val, 1).'</text>';
            }
        }

        // Reference line
        if ($referenceLine !== null && $maxVal > 0) {
            $lineX = $labelWidth + ($referenceLine / $maxVal) * $barAreaWidth;
            $svg .= '<line x1="'.round($lineX, 1).'" y1="'.$topPadding
                .'" x2="'.round($lineX, 1).'" y2="'.($height - 10)
                .'" stroke="#EF4444" stroke-width="1.5" stroke-dasharray="4,3" />';
            $svg .= '<text x="'.round($lineX, 1).'" y="'.($topPadding - 3)
                .'" text-anchor="middle" font-size="9" fill="#EF4444">'.round($referenceLine, 1).'</text>';
        }

        $svg .= '</svg>';

        return $svg;
    }

    /**
     * Gauge (semicircle) SVG.
     *
     * @param  float  $value  current value
     * @param  float  $max  maximum value
     * @param  array  $ranges  [['min' => 0, 'max' => 60, 'color' => '#EF4444'], ...]
     * @return string SVG markup
     */
    public function gauge(float $value, float $max = 100, array $ranges = [], string $label = ''): string
    {
        $width = 200;
        $height = 130;
        $cx = 100;
        $cy = 100;
        $radius = 75;
        $strokeWidth = 18;

        // Default ranges if none provided
        if (empty($ranges)) {
            $ranges = [
                ['min' => 0, 'max' => $max * 0.4, 'color' => self::SEMAFORO['rojo']],
                ['min' => $max * 0.4, 'max' => $max * 0.7, 'color' => self::SEMAFORO['amarillo']],
                ['min' => $max * 0.7, 'max' => $max, 'color' => self::SEMAFORO['verde']],
            ];
        }

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="'.$width.'" height="'.$height.'" viewBox="0 0 '.$width.' '.$height.'">';

        // Draw range arcs (semicircle goes from 180 to 360 degrees)
        foreach ($ranges as $range) {
            $startFraction = ($range['min'] / $max);
            $endFraction = ($range['max'] / $max);
            // Map to 180..360 degrees
            $startAngle = 180 + ($startFraction * 180);
            $endAngle = 180 + ($endFraction * 180);

            $svg .= $this->arcPath($cx, $cy, $radius, $startAngle, $endAngle, $range['color'], $strokeWidth);
        }

        // Needle
        $clampedValue = max(0, min($value, $max));
        $needleAngle = 180 + ($clampedValue / $max) * 180;
        $needleRad = deg2rad($needleAngle);
        $needleLen = $radius - $strokeWidth / 2 - 5;
        $nx = $cx + $needleLen * cos($needleRad);
        $ny = $cy + $needleLen * sin($needleRad);

        $svg .= '<line x1="'.$cx.'" y1="'.$cy.'" x2="'.round($nx, 2).'" y2="'.round($ny, 2)
            .'" stroke="#1F2937" stroke-width="2.5" stroke-linecap="round" />';
        // Needle center dot
        $svg .= '<circle cx="'.$cx.'" cy="'.$cy.'" r="4" fill="#1F2937" />';

        // Value text
        $svg .= '<text x="'.$cx.'" y="'.($cy + 20).'" text-anchor="middle" font-size="18" font-weight="bold" fill="#1F2937">'
            .round($clampedValue, 1).'</text>';

        // Label
        if ($label !== '') {
            $svg .= '<text x="'.$cx.'" y="'.($height - 2).'" text-anchor="middle" font-size="10" fill="#6B7280">'
                .$this->escSvg($label).'</text>';
        }

        // Min and max labels
        $svg .= '<text x="'.($cx - $radius - 5).'" y="'.($cy + 14).'" text-anchor="middle" font-size="9" fill="#9CA3AF">0</text>';
        $svg .= '<text x="'.($cx + $radius + 5).'" y="'.($cy + 14).'" text-anchor="middle" font-size="9" fill="#9CA3AF">'.round($max).'</text>';

        $svg .= '</svg>';

        return $svg;
    }

    /**
     * Bullet chart SVG for multiple indicators.
     *
     * @param  array  $indicadores  [['nombre' => '...', 'resultado' => 78, 'meta' => 85, 'rango_verde_min' => ..., 'rango_amarillo_min' => ...], ...]
     * @return string SVG markup
     */
    public function bullet(array $indicadores): string
    {
        $width = 500;
        $labelWidth = 140;
        $barAreaWidth = $width - $labelWidth - 50;
        $rowHeight = 48;
        $barHeight = 22;
        $height = count($indicadores) * $rowHeight + 10;

        if (empty($indicadores)) {
            return $this->emptySvg($width, 60, 'Sin indicadores');
        }

        // Find max value for scaling
        $maxVal = 0;
        foreach ($indicadores as $ind) {
            $maxVal = max($maxVal, (float) ($ind['resultado'] ?? 0), (float) ($ind['meta'] ?? 0), 100);
        }

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="'.$width.'" height="'.$height.'" viewBox="0 0 '.$width.' '.$height.'">';

        foreach ($indicadores as $i => $ind) {
            $y = $i * $rowHeight + 5;
            $barY = $y + 14;
            $resultado = (float) ($ind['resultado'] ?? 0);
            $meta = (float) ($ind['meta'] ?? 0);

            // Label (truncated)
            $nombre = $ind['nombre'] ?? 'Indicador '.($i + 1);
            $displayName = mb_strlen($nombre) > 22 ? mb_substr($nombre, 0, 21).'...' : $nombre;
            $svg .= '<text x="'.($labelWidth - 5).'" y="'.($barY + $barHeight / 2 + 4)
                .'" text-anchor="end" font-size="10" fill="#374151">'.$this->escSvg($displayName).'</text>';

            // Background range bands
            $rangeVerdeMin = (float) ($ind['rango_verde_min'] ?? 80);
            $rangeAmarilloMin = (float) ($ind['rango_amarillo_min'] ?? 60);

            // Rojo band (0 to amarillo min)
            $rojoW = ($rangeAmarilloMin / $maxVal) * $barAreaWidth;
            $svg .= '<rect x="'.$labelWidth.'" y="'.$barY.'" width="'.round($rojoW, 1)
                .'" height="'.$barHeight.'" fill="'.self::SEMAFORO_LIGHT['rojo'].'" />';

            // Amarillo band
            $amarilloStart = $rojoW;
            $amarilloW = (($rangeVerdeMin - $rangeAmarilloMin) / $maxVal) * $barAreaWidth;
            $svg .= '<rect x="'.($labelWidth + round($amarilloStart, 1)).'" y="'.$barY
                .'" width="'.round($amarilloW, 1).'" height="'.$barHeight
                .'" fill="'.self::SEMAFORO_LIGHT['amarillo'].'" />';

            // Verde band
            $verdeStart = $amarilloStart + $amarilloW;
            $verdeW = $barAreaWidth - $verdeStart + $labelWidth;
            // Recalculate properly
            $verdeW = (($maxVal - $rangeVerdeMin) / $maxVal) * $barAreaWidth;
            $svg .= '<rect x="'.($labelWidth + round($rojoW + $amarilloW, 1)).'" y="'.$barY
                .'" width="'.round($verdeW, 1).'" height="'.$barHeight
                .'" fill="'.self::SEMAFORO_LIGHT['verde'].'" />';

            // Resultado bar (thinner, darker)
            $resW = ($resultado / $maxVal) * $barAreaWidth;
            $resBarHeight = 10;
            $resBarY = $barY + ($barHeight - $resBarHeight) / 2;
            $svg .= '<rect x="'.$labelWidth.'" y="'.round($resBarY, 1).'" width="'.round($resW, 1)
                .'" height="'.$resBarHeight.'" fill="#1F2937" rx="1" />';

            // Meta marker (vertical line)
            $metaX = $labelWidth + ($meta / $maxVal) * $barAreaWidth;
            $svg .= '<line x1="'.round($metaX, 1).'" y1="'.$barY
                .'" x2="'.round($metaX, 1).'" y2="'.($barY + $barHeight)
                .'" stroke="#DC2626" stroke-width="2" />';

            // Value label to the right
            $svg .= '<text x="'.($labelWidth + $barAreaWidth + 5).'" y="'.($barY + $barHeight / 2 + 4)
                .'" font-size="10" font-weight="bold" fill="#1F2937">'.round($resultado, 1).'</text>';
        }

        $svg .= '</svg>';

        return $svg;
    }

    /**
     * Heatmap as HTML table (DomPDF handles tables better than SVG grids).
     *
     * @param  array  $rows  [['nombre' => 'Programa A'], ...]
     * @param  array  $columns  ['T1', 'T2', 'T3', 'T4']
     * @param  array  $values  [row_index][col_index] => ['valor' => 75, 'semaforo' => 'verde']
     * @return string HTML table markup
     */
    public function heatmap(array $rows, array $columns, array $values): string
    {
        $html = '<table style="border-collapse:collapse;width:100%;font-family:sans-serif;font-size:11px;">';

        // Header row
        $html .= '<tr>';
        $html .= '<th style="padding:6px 8px;text-align:left;border:1px solid #D1D5DB;background:#F3F4F6;">Programa</th>';
        foreach ($columns as $col) {
            $html .= '<th style="padding:6px 8px;text-align:center;border:1px solid #D1D5DB;background:#F3F4F6;">'
                .htmlspecialchars($col, ENT_QUOTES, 'UTF-8').'</th>';
        }
        $html .= '</tr>';

        // Data rows
        foreach ($rows as $ri => $row) {
            $html .= '<tr>';
            $html .= '<td style="padding:6px 8px;border:1px solid #D1D5DB;background:#FFFFFF;font-weight:600;">'
                .htmlspecialchars($row['nombre'] ?? '', ENT_QUOTES, 'UTF-8').'</td>';

            foreach ($columns as $ci => $col) {
                $cell = $values[$ri][$ci] ?? null;
                $valor = $cell['valor'] ?? '-';
                $semaforo = $cell['semaforo'] ?? 'gris';
                $bgColor = self::SEMAFORO_LIGHT[$semaforo] ?? self::SEMAFORO_LIGHT['gris'];
                $textColor = $semaforo === 'rojo' ? '#991B1B' : ($semaforo === 'verde' ? '#166534' : '#1F2937');

                $html .= '<td style="padding:6px 8px;text-align:center;border:1px solid #D1D5DB;background:'
                    .$bgColor.';color:'.$textColor.';font-weight:bold;">'
                    .htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8').'</td>';
            }

            $html .= '</tr>';
        }

        $html .= '</table>';

        return $html;
    }

    /**
     * Lollipop chart SVG showing deviations from zero.
     *
     * @param  array  $data  [['nombre' => '...', 'desviacion' => -23], ...]
     * @param  float  $threshold  alert threshold (negative value below which items are highlighted)
     * @return string SVG markup
     */
    public function lollipop(array $data, float $threshold = -20): string
    {
        if (empty($data)) {
            return $this->emptySvg(500, 60, 'Sin datos');
        }

        // Sort by absolute deviation descending
        usort($data, function ($a, $b) {
            return abs($b['desviacion'] ?? 0) <=> abs($a['desviacion'] ?? 0);
        });

        $width = 500;
        $labelWidth = 140;
        $rightMargin = 40;
        $chartWidth = $width - $labelWidth - $rightMargin;
        $rowHeight = 28;
        $topPadding = 20;
        $height = $topPadding + count($data) * $rowHeight + 10;

        // Find max absolute deviation for scaling
        $maxAbs = 0;
        foreach ($data as $item) {
            $maxAbs = max($maxAbs, abs((float) ($item['desviacion'] ?? 0)));
        }
        if ($maxAbs <= 0) {
            $maxAbs = 100;
        }

        $centerX = $labelWidth + $chartWidth / 2;

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="'.$width.'" height="'.$height.'" viewBox="0 0 '.$width.' '.$height.'">';

        // Zero center line
        $svg .= '<line x1="'.$centerX.'" y1="'.$topPadding.'" x2="'.$centerX.'" y2="'.($height - 5)
            .'" stroke="#D1D5DB" stroke-width="1" />';
        $svg .= '<text x="'.$centerX.'" y="'.($topPadding - 5).'" text-anchor="middle" font-size="9" fill="#9CA3AF">0%</text>';

        // Threshold lines (if within range)
        if (abs($threshold) <= $maxAbs) {
            $threshX = $centerX + ($threshold / $maxAbs) * ($chartWidth / 2);
            $svg .= '<line x1="'.round($threshX, 1).'" y1="'.$topPadding.'" x2="'.round($threshX, 1).'" y2="'.($height - 5)
                .'" stroke="#EF4444" stroke-width="1" stroke-dasharray="3,3" />';
        }

        foreach ($data as $i => $item) {
            $desv = (float) ($item['desviacion'] ?? 0);
            $y = $topPadding + $i * $rowHeight + $rowHeight / 2;

            // Label
            $nombre = $item['nombre'] ?? '';
            $displayName = mb_strlen($nombre) > 20 ? mb_substr($nombre, 0, 19).'...' : $nombre;
            $svg .= '<text x="'.($labelWidth - 5).'" y="'.($y + 4).'" text-anchor="end" font-size="10" fill="#374151">'
                .$this->escSvg($displayName).'</text>';

            // Line from center to value
            $endX = $centerX + ($desv / $maxAbs) * ($chartWidth / 2);
            $isAlert = $desv < $threshold;
            $color = $isAlert ? self::SEMAFORO['rojo'] : ($desv >= 0 ? self::SEMAFORO['verde'] : self::SEMAFORO['amarillo']);

            $svg .= '<line x1="'.$centerX.'" y1="'.$y.'" x2="'.round($endX, 1).'" y2="'.$y
                .'" stroke="'.$color.'" stroke-width="2" />';

            // Circle at end
            $svg .= '<circle cx="'.round($endX, 1).'" cy="'.$y.'" r="5" fill="'.$color.'" />';

            // Value label
            $valLabelX = $desv >= 0 ? $endX + 8 : $endX - 8;
            $anchor = $desv >= 0 ? 'start' : 'end';
            $svg .= '<text x="'.round($valLabelX, 1).'" y="'.($y + 3.5).'" text-anchor="'.$anchor
                .'" font-size="9" font-weight="bold" fill="'.$color.'">'.round($desv, 1).'%</text>';
        }

        $svg .= '</svg>';

        return $svg;
    }

    /**
     * Radar chart SVG (polygon).
     *
     * @param  array  $labels  axis labels
     * @param  array  $values  data values (0-100)
     * @param  array|null  $referenceValues  optional second series
     * @return string SVG markup
     */
    public function radar(array $labels, array $values, ?array $referenceValues = null): string
    {
        $size = 300;
        $cx = $size / 2;
        $cy = $size / 2;
        $radius = 110;
        $levels = 5;
        $n = count($labels);

        if ($n < 3) {
            return $this->emptySvg($size, $size, 'Se necesitan al menos 3 ejes');
        }

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="'.$size.'" height="'.$size.'" viewBox="0 0 '.$size.' '.$size.'">';

        // Background grid polygons
        for ($level = 1; $level <= $levels; $level++) {
            $r = ($level / $levels) * $radius;
            $points = [];
            for ($i = 0; $i < $n; $i++) {
                $angle = (2 * M_PI * $i / $n) - M_PI / 2; // start from top
                $px = $cx + $r * cos($angle);
                $py = $cy + $r * sin($angle);
                $points[] = round($px, 2).','.round($py, 2);
            }
            $svg .= '<polygon points="'.implode(' ', $points)
                .'" fill="none" stroke="#E5E7EB" stroke-width="0.8" />';
        }

        // Axis lines from center to each vertex
        for ($i = 0; $i < $n; $i++) {
            $angle = (2 * M_PI * $i / $n) - M_PI / 2;
            $px = $cx + $radius * cos($angle);
            $py = $cy + $radius * sin($angle);
            $svg .= '<line x1="'.$cx.'" y1="'.$cy.'" x2="'.round($px, 2).'" y2="'.round($py, 2)
                .'" stroke="#D1D5DB" stroke-width="0.8" />';
        }

        // Reference polygon (if provided)
        if ($referenceValues !== null && count($referenceValues) === $n) {
            $svg .= $this->radarPolygon($cx, $cy, $radius, $n, $referenceValues, '#9CA3AF', 'rgba(156,163,175,0.15)');
        }

        // Data polygon
        $svg .= $this->radarPolygon($cx, $cy, $radius, $n, $values, '#3B82F6', 'rgba(59,130,246,0.25)');

        // Labels
        $labelRadius = $radius + 18;
        for ($i = 0; $i < $n; $i++) {
            $angle = (2 * M_PI * $i / $n) - M_PI / 2;
            $lx = $cx + $labelRadius * cos($angle);
            $ly = $cy + $labelRadius * sin($angle);

            // Determine text-anchor based on position
            $anchor = 'middle';
            if ($lx < $cx - 5) {
                $anchor = 'end';
            } elseif ($lx > $cx + 5) {
                $anchor = 'start';
            }

            $label = $labels[$i] ?? '';
            $displayLabel = mb_strlen($label) > 15 ? mb_substr($label, 0, 14).'...' : $label;
            $svg .= '<text x="'.round($lx, 2).'" y="'.round($ly + 3, 2)
                .'" text-anchor="'.$anchor.'" font-size="9" fill="#4B5563">'
                .$this->escSvg($displayLabel).'</text>';
        }

        $svg .= '</svg>';

        return $svg;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * SVG arc path for stroked arcs (gauge).
     */
    private function arcPath(float $cx, float $cy, float $r, float $startAngle, float $endAngle, string $color, float $strokeWidth): string
    {
        $startRad = deg2rad($startAngle);
        $endRad = deg2rad($endAngle);

        $x1 = $cx + $r * cos($startRad);
        $y1 = $cy + $r * sin($startRad);
        $x2 = $cx + $r * cos($endRad);
        $y2 = $cy + $r * sin($endRad);

        $largeArc = ($endAngle - $startAngle) > 180 ? 1 : 0;

        $d = 'M '.round($x1, 2).' '.round($y1, 2)
            .' A '.$r.' '.$r.' 0 '.$largeArc.' 1 '.round($x2, 2).' '.round($y2, 2);

        return '<path d="'.$d.'" fill="none" stroke="'.$color.'" stroke-width="'.$strokeWidth.'" stroke-linecap="butt" />';
    }

    /**
     * Donut slice as a closed path between two arcs.
     */
    private function donutSlice(float $cx, float $cy, float $outerR, float $innerR, float $startAngle, float $endAngle, string $color): string
    {
        $startRad = deg2rad($startAngle);
        $endRad = deg2rad($endAngle);

        // Outer arc points
        $ox1 = $cx + $outerR * cos($startRad);
        $oy1 = $cy + $outerR * sin($startRad);
        $ox2 = $cx + $outerR * cos($endRad);
        $oy2 = $cy + $outerR * sin($endRad);

        // Inner arc points
        $ix1 = $cx + $innerR * cos($startRad);
        $iy1 = $cy + $innerR * sin($startRad);
        $ix2 = $cx + $innerR * cos($endRad);
        $iy2 = $cy + $innerR * sin($endRad);

        $angleDiff = $endAngle - $startAngle;
        $largeArc = $angleDiff > 180 ? 1 : 0;

        // Path: outer arc clockwise, line to inner, inner arc counter-clockwise, close
        $d = 'M '.round($ox1, 2).' '.round($oy1, 2)
            .' A '.$outerR.' '.$outerR.' 0 '.$largeArc.' 1 '.round($ox2, 2).' '.round($oy2, 2)
            .' L '.round($ix2, 2).' '.round($iy2, 2)
            .' A '.$innerR.' '.$innerR.' 0 '.$largeArc.' 0 '.round($ix1, 2).' '.round($iy1, 2)
            .' Z';

        return '<path d="'.$d.'" fill="'.$color.'" />';
    }

    /**
     * Generate SVG arc path descriptor (for external use).
     */
    private function describeArc(float $cx, float $cy, float $r, float $startAngle, float $endAngle): string
    {
        $startRad = deg2rad($startAngle);
        $endRad = deg2rad($endAngle);

        $x1 = $cx + $r * cos($startRad);
        $y1 = $cy + $r * sin($startRad);
        $x2 = $cx + $r * cos($endRad);
        $y2 = $cy + $r * sin($endRad);

        $largeArc = ($endAngle - $startAngle) > 180 ? 1 : 0;

        return 'M '.round($x1, 2).' '.round($y1, 2)
            .' A '.$r.' '.$r.' 0 '.$largeArc.' 1 '.round($x2, 2).' '.round($y2, 2);
    }

    /**
     * Draw a radar polygon (data or reference).
     */
    private function radarPolygon(float $cx, float $cy, float $radius, int $n, array $values, string $strokeColor, string $fillColor): string
    {
        $points = [];
        $svg = '';

        for ($i = 0; $i < $n; $i++) {
            $val = max(0, min(100, (float) ($values[$i] ?? 0)));
            $r = ($val / 100) * $radius;
            $angle = (2 * M_PI * $i / $n) - M_PI / 2;
            $px = $cx + $r * cos($angle);
            $py = $cy + $r * sin($angle);
            $points[] = round($px, 2).','.round($py, 2);
        }

        $svg .= '<polygon points="'.implode(' ', $points)
            .'" fill="'.$fillColor.'" stroke="'.$strokeColor.'" stroke-width="2" />';

        // Data points
        foreach (explode(' ', implode(' ', $points)) as $point) {
            [$px, $py] = explode(',', $point);
            $svg .= '<circle cx="'.$px.'" cy="'.$py.'" r="3" fill="'.$strokeColor.'" />';
        }

        return $svg;
    }

    /**
     * Escape text for SVG XML.
     */
    private function escSvg(string $text): string
    {
        return htmlspecialchars($text, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    /**
     * Format currency values with abbreviations.
     */
    private function formatCurrency(float $value): string
    {
        if ($value >= 1e6) {
            return '$'.number_format($value / 1e6, 1).'M';
        }
        if ($value >= 1e3) {
            return '$'.number_format($value / 1e3, 0).'K';
        }

        return '$'.number_format($value);
    }

    /**
     * Placeholder SVG for empty/error states.
     */
    private function emptySvg(int $width, int $height, string $message): string
    {
        return '<svg xmlns="http://www.w3.org/2000/svg" width="'.$width.'" height="'.$height.'" viewBox="0 0 '.$width.' '.$height.'">'
            .'<rect width="'.$width.'" height="'.$height.'" fill="#F9FAFB" rx="4" />'
            .'<text x="'.($width / 2).'" y="'.($height / 2 + 4).'" text-anchor="middle" font-size="12" fill="#9CA3AF">'
            .$this->escSvg($message).'</text></svg>';
    }
}
