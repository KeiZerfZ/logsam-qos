<?php

namespace App\Services;

class FuzzyQosService
{
    // ... (Fungsi fuzzifyDelay, fuzzifyJitter, fuzzifyLoss tidak berubah) ...
    private function fuzzifyDelay(float $delay): array
    {
        return [
            'Rendah' => $this->trapezoid($delay, -1, 0, 20, 40),
            'Sedang' => $this->triangle($delay, 30, 60, 90),
            'Tinggi' => $this->trapezoid($delay, 70, 100, 999, 9999),
        ];
    }

    private function fuzzifyJitter(float $jitter): array
    {
        return [
            'Rendah' => $this->trapezoid($jitter, -1, 0, 3, 7),
            'Sedang' => $this->triangle($jitter, 5, 12.5, 20),
            'Tinggi' => $this->trapezoid($jitter, 15, 25, 999, 9999),
        ];
    }

    private function fuzzifyLoss(float $loss): array
    {
        return [
            'Rendah' => $this->trapezoid($loss, -1, 0, 0.5, 1.5),
            'Sedang' => $this->triangle($loss, 1, 3, 5),
            'Tinggi' => $this->trapezoid($loss, 4, 6, 99, 101),
        ];
    }

    // ... (Fungsi applyRules dan defuzzify tidak berubah) ...
    private function applyRules(array $delay, array $jitter, array $loss): array
    {
        $qos = ['Buruk' => 0, 'Cukup' => 0, 'Baik' => 0, 'Sangat Baik' => 0];

        // Aturan BARU 8: Jika Jitter TINGGI, maka QoS BURUK (ini jadi prioritas)
        $rule8 = $jitter['Tinggi'];
        $qos['Buruk'] = max($qos['Buruk'], $rule8);

        // Aturan 1: Jika Delay TINGGI ATAU Loss TINGGI, maka QoS BURUK.
        $rule1 = max($delay['Tinggi'], $loss['Tinggi']);
        $qos['Buruk'] = max($qos['Buruk'], $rule1);
        $rule2 = min($delay['Sedang'], $jitter['Tinggi']);
        $qos['Buruk'] = max($qos['Buruk'], $rule2);
        $rule3 = min($delay['Sedang'], $jitter['Sedang'], $loss['Sedang']);
        $qos['Cukup'] = max($qos['Cukup'], $rule3);
        $rule4 = min($delay['Rendah'], $jitter['Sedang']);
        $qos['Baik'] = max($qos['Baik'], $rule4);
        $rule5 = min($delay['Rendah'], $jitter['Rendah'], $loss['Rendah']);
        $qos['Sangat Baik'] = max($qos['Sangat Baik'], $rule5);
        $rule6 = $loss['Sedang'];
        $qos['Cukup'] = max($qos['Cukup'], $rule6);
        $rule7 = min($delay['Rendah'], $jitter['Rendah'], $loss['Sedang']);
        $qos['Baik'] = max($qos['Baik'], $rule7);
        return $qos;
    }

    private function defuzzify(array $qos): float
    {
        $sets = [
            'Buruk' => [0, 0, 20, 40], 'Cukup' => [30, 45, 60],
            'Baik' => [50, 65, 80], 'Sangat Baik' => [70, 85, 100, 100],
        ];
        $step = 1; $samples = range(0, 100, $step);
        $numerator = 0; $denominator = 0;
        foreach ($samples as $sample) {
            $membershipValues = [
                $this->trapezoid($sample, ...$sets['Buruk']),
                $this->triangle($sample, ...$sets['Cukup']),
                $this->triangle($sample, ...$sets['Baik']),
                $this->trapezoid($sample, ...$sets['Sangat Baik']),
            ];
            $clippedValues = [
                min($qos['Buruk'], $membershipValues[0]), min($qos['Cukup'], $membershipValues[1]),
                min($qos['Baik'], $membershipValues[2]), min($qos['Sangat Baik'], $membershipValues[3]),
            ];
            $aggregatedValue = max($clippedValues);
            $numerator += $sample * $aggregatedValue;
            $denominator += $aggregatedValue;
        }
        return $denominator === 0.0 ? 0.0 : $numerator / $denominator;
    }

    public function calculate(array $metrics): array
    {
        $fuzzifiedDelay = $this->fuzzifyDelay($metrics['delay']);
        $fuzzifiedJitter = $this->fuzzifyJitter($metrics['jitter']);
        $fuzzifiedLoss = $this->fuzzifyLoss($metrics['loss']);
        $inferredQos = $this->applyRules($fuzzifiedDelay, $fuzzifiedJitter, $fuzzifiedLoss);
        $crispScore = $this->defuzzify($inferredQos);
        $category = $this->categorizeScore($crispScore);
        return ['score_qos' => round($crispScore, 2), 'category_qos' => $category];
    }
    
    private function categorizeScore(float $score): string
    {
        if ($score >= 80) return 'Sangat Baik';
        if ($score >= 60) return 'Baik';
        if ($score >= 40) return 'Cukup';
        return 'Buruk';
    }

    // --- FUNGSI HELPER YANG SUDAH DIPERBAIKI TOTAL ---

    /**
     * Fungsi Keanggotaan Segitiga yang sudah diperkuat.
     */
    private function triangle(float $x, float $a, float $b, float $c): float
    {
        // Jika x di luar jangkauan segitiga, langsung kembalikan 0.
        if ($x <= $a || $x >= $c) {
            return 0;
        }
        // Pencegahan pembagian dengan nol.
        if ($b - $a == 0 || $c - $b == 0) return 0;
        
        return max(0, min(($x - $a) / ($b - $a), ($c - $x) / ($c - $b)));
    }

    /**
     * Fungsi Keanggotaan Trapesium yang sudah diperkuat.
     */
    private function trapezoid(float $x, float $a, float $b, float $c, float $d): float
    {
        // Jika x di luar jangkauan trapesium, langsung kembalikan 0 atau 1.
        if ($x <= $a || $x >= $d) return 0;
        if ($x >= $b && $x <= $c) return 1.0;

        // Pencegahan pembagian dengan nol.
        $val1 = 1.0;
        if ($b - $a > 0) { $val1 = ($x - $a) / ($b - $a); } 
        
        $val2 = 1.0;
        if ($d - $c > 0) { $val2 = ($d - $x) / ($d - $c); } 
        
        return max(0, min($val1, 1, $val2));
    }
}

