<?php
// backend-php/core/Algorithm.php

class Algorithm
{

    public static function calculate($type, $total_words, $start_date, $end_date, $rules = [])
    {
        $start = new DateTime($start_date);
        $end = new DateTime($end_date);
        $interval = $start->diff($end);
        $days = $interval->days + 1;

        if ($days <= 0)
            return [];

        $daily_targets = [];
        $intensity = isset($rules['intensity']) ? $rules['intensity'] : 'average'; // gentle, low, average, medium, hard_core

        // Intensity Multiplier (affects slope or variance)
        $intensity_factor = 1.0;
        switch ($intensity) {
            case 'gentle':
                $intensity_factor = 0.5;
                break;
            case 'low':
                $intensity_factor = 0.75;
                break;
            case 'average':
                $intensity_factor = 1.0;
                break;
            case 'medium':
                $intensity_factor = 1.25;
                break;
            case 'hard_core':
                $intensity_factor = 1.5;
                break;
        }

        switch ($type) {
            case 'steady':
                $daily = ceil($total_words / $days);
                for ($i = 0; $i < $days; $i++)
                    $daily_targets[] = $daily;
                break;

            case 'rising':
                // Linear increase
                // x + (x+d) + ...
                // Use intensity to determine steepness. 
                // Average intensity -> start at 50% of mean, end at 150%
                $mean = $total_words / $days;
                $start_val = $mean * (1 - (0.5 * $intensity_factor));
                if ($start_val < 0)
                    $start_val = 0;

                // Calculate d
                // Total = n*start + d*n(n-1)/2
                if ($days > 1) {
                    $d = ($total_words - ($days * $start_val)) / ($days * ($days - 1) / 2);
                } else {
                    $d = 0;
                    $start_val = $total_words;
                }

                for ($i = 0; $i < $days; $i++) {
                    $daily_targets[] = round($start_val + ($i * $d));
                }
                break;

            case 'biting_bullet':
                // Reverse of rising
                $mean = $total_words / $days;
                $start_val = $mean * (1 - (0.5 * $intensity_factor));
                if ($start_val < 0)
                    $start_val = 0;

                if ($days > 1) {
                    $d = ($total_words - ($days * $start_val)) / ($days * ($days - 1) / 2);
                } else {
                    $d = 0;
                    $start_val = $total_words;
                }

                for ($i = 0; $i < $days; $i++) {
                    $daily_targets[] = round($start_val + ($i * $d));
                }
                $daily_targets = array_reverse($daily_targets);
                break;

            case 'mountain_hike':
                // Low -> High -> Low
                // Triangle distribution
                // Peak at middle.
                // We can split into two 'rising' and 'biting_bullet' halves
                $mid = floor($days / 2);
                $first_half_days = $mid;
                $second_half_days = $days - $mid;

                // We need to split total words roughly in half too? 
                // Or just use a function f(x) = -a(x-h)^2 + k (parabola)
                // Let's use a simple triangle approach.
                // Peak height depends on intensity.
                // Area of triangle = 0.5 * base * height = Total
                // height = 2 * Total / days.
                // With intensity, we can make it sharper or flatter (but must sum to Total).
                // Actually, "Mountain Hike" usually means start low, go high, end low.

                // Let's use a simple heuristic:
                // Generate a normalized triangle array, then scale to Total.
                $ratios = [];
                for ($i = 0; $i < $days; $i++) {
                    // 0 to 1 to 0
                    $pos = $i / ($days - 1);
                    if ($pos <= 0.5) {
                        $val = $pos * 2; // 0 -> 1
                    } else {
                        $val = (1 - $pos) * 2; // 1 -> 0
                    }
                    // Apply intensity: power function
                    // If intensity is high, peak is sharper (power > 1)
                    // If intensity is low, flatter (power < 1)
                    // gentle=0.5 (sqrt, fat), average=1 (linear), hard=2 (sharp)
                    $pow = $intensity_factor;
                    $ratios[] = pow($val, $pow) + 0.1; // +0.1 base so not 0
                }

                $sum_r = array_sum($ratios);
                foreach ($ratios as $r) {
                    $daily_targets[] = round(($r / $sum_r) * $total_words);
                }
                break;

            case 'valley':
                // High -> Low -> High
                // Inverted triangle
                $ratios = [];
                for ($i = 0; $i < $days; $i++) {
                    $pos = $i / ($days - 1);
                    if ($pos <= 0.5) {
                        $val = 1 - ($pos * 2); // 1 -> 0
                    } else {
                        $val = ($pos - 0.5) * 2; // 0 -> 1
                    }
                    $pow = $intensity_factor;
                    $ratios[] = pow($val, $pow) + 0.1;
                }

                $sum_r = array_sum($ratios);
                foreach ($ratios as $r) {
                    $daily_targets[] = round(($r / $sum_r) * $total_words);
                }
                break;

            case 'oscillating':
                // High, Low, High, Low
                // Sine wave
                $ratios = [];
                $frequency = $days / 7; // Weekly oscillation roughly?
                // Let's make it oscillate every few days.
                for ($i = 0; $i < $days; $i++) {
                    $val = sin($i * 0.5 * $intensity_factor) + 1.1; // 0.1 to 2.1
                    $ratios[] = $val;
                }
                $sum_r = array_sum($ratios);
                foreach ($ratios as $r) {
                    $daily_targets[] = round(($r / $sum_r) * $total_words);
                }
                break;

            case 'random':
                $rands = [];
                $sum_r = 0;
                for ($i = 0; $i < $days; $i++) {
                    $r = rand(1, 100 * $intensity_factor);
                    $rands[] = $r;
                    $sum_r += $r;
                }
                foreach ($rands as $r) {
                    $daily_targets[] = round(($r / $sum_r) * $total_words);
                }
                break;

            default: // Steady
                $daily = ceil($total_words / $days);
                for ($i = 0; $i < $days; $i++)
                    $daily_targets[] = $daily;
                break;
        }

        // Adjust sum
        $current_sum = array_sum($daily_targets);
        $diff = $total_words - $current_sum;
        for ($i = 0; $i < abs($diff); $i++) {
            $idx = $i % $days;
            if ($diff > 0)
                $daily_targets[$idx]++;
            else if ($daily_targets[$idx] > 0)
                $daily_targets[$idx]--;
        }

        // Generate Date Map
        $current = clone $start;
        $result = [];
        for ($i = 0; $i < $days; $i++) {
            $result[] = [
                'date' => $current->format('Y-m-d'),
                'target' => $daily_targets[$i]
            ];
            $current->modify('+1 day');
        }

        return $result;
    }
}
?>