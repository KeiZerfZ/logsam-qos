<?php

namespace App\Services;

class FuzzyQosService
{
    // ========================================================================
    // TAHAP 1: FUZZIFIKASI 
    // ========================================================================

    private function fuzzifyDelay(float $delay): array
    {
        return [
            'Rendah' => $this->trapezoid($delay, -1, 0, 20, 40),
            'Sedang' => $this->triangle($delay, 30, 60, 90),
            'Tinggi' => $this->triangle($delay, 70, 100, 130),
            'Sangat Tinggi' => $this->trapezoid($delay, 110, 140, 999, 9999), 
        ];
    }

    private function fuzzifyJitter(float $jitter): array
    {
        return [
            'Rendah' => $this->trapezoid($jitter, -1, 0, 3, 7),
            'Sedang' => $this->triangle($jitter, 5, 12.5, 20),
            'Tinggi' => $this->triangle($jitter, 15, 25, 35),
            'Sangat Tinggi' => $this->trapezoid($jitter, 30, 40, 999, 9999),
        ];
    }

    private function fuzzifyLoss(float $loss): array
    {
        return [
            'Sangat Rendah' => $this->trapezoid($loss, -1, 0, 0.1, 0.3),    
            'Rendah' => $this->triangle($loss, 0.2, 0.6, 1.0),      
            'Sedang' => $this->triangle($loss, 0.8, 1.5, 2.5),     
            'Tinggi' => $this->triangle($loss, 2, 4, 6),          
            'Sangat Tinggi' => $this->trapezoid($loss, 5, 7, 99, 101),  
        ];
    }

    // ========================================================================
    // TAHAP 2: INFERENSI 
    // ========================================================================

    private function applyRules(array $delay, array $jitter, array $loss): array
    {
        $qos = ['Sangat Buruk' => 0.0, 'Buruk' => 0.0, 'Cukup' => 0.0, 'Baik' => 0.0, 'Sangat Baik' => 0.0];

        // Aturan 1: Loss ∈ {Sangat Tinggi, Tinggi} → QoS Sangat Buruk
        $rule1 = max($loss['Sangat Tinggi'], $loss['Tinggi']);
        $qos['Sangat Buruk'] = max($qos['Sangat Buruk'], $rule1);

        // Aturan 2: Loss Sedang ∧ Delay Tinggi → Buruk
        $rule2 = min($loss['Sedang'], $delay['Tinggi']);
        $qos['Buruk'] = max($qos['Buruk'], $rule2);
        
        // Aturan 3: Loss Sedang ∧ Jitter Tinggi → Buruk
        $rule3 = min($loss['Sedang'], $jitter['Tinggi']);
        $qos['Buruk'] = max($qos['Buruk'], $rule3);

        // Aturan 4: Loss Sedang ∧ Delay Sedang ∧ Jitter Sedang → Cukup
        $rule4 = min($loss['Sedang'], $delay['Sedang'], $jitter['Sedang']);
        $qos['Cukup'] = max($qos['Cukup'], $rule4);

        // Aturan 5: Loss Rendah ∧ Jitter Sedang → Cukup
        $rule5 = min($loss['Rendah'], $jitter['Sedang']);
        $qos['Cukup'] = max($qos['Cukup'], $rule5);

        // Aturan 6: Loss Rendah ∧ Delay Sedang → Cukup
        $rule6 = min($loss['Rendah'], $delay['Sedang']);
        $qos['Cukup'] = max($qos['Cukup'], $rule6);

        // Aturan 7: Loss Rendah ∧ Jitter Rendah ∧ Delay Sedang → Baik
        $rule7 = min($loss['Rendah'], $jitter['Rendah'], $delay['Sedang']);
        $qos['Baik'] = max($qos['Baik'], $rule7);

        // Aturan 8: Loss Rendah ∧ Jitter Rendah ∧ Delay Rendah → Baik
        $rule8 = min($loss['Rendah'], $jitter['Rendah'], $delay['Rendah']);
        $qos['Baik'] = max($qos['Baik'], $rule8);

        // Aturan 9: Loss Sangat Rendah ∧ Jitter Rendah ∧ Delay Rendah → Sangat Baik
        $rule9 = min($loss['Sangat Rendah'], $jitter['Rendah'], $delay['Rendah']);
        $qos['Sangat Baik'] = max($qos['Sangat Baik'], $rule9);

        // Aturan 10: Loss Sangat Rendah ∧ Jitter Sedang → Baik
        $rule10 = min($loss['Sangat Rendah'], $jitter['Sedang']);
        $qos['Baik'] = max($qos['Baik'], $rule10);
        
        // Aturan 11: Loss Sangat Rendah ∧ Delay Sedang → Baik
        $rule11 = min($loss['Sangat Rendah'], $delay['Sedang']);
        $qos['Baik'] = max($qos['Baik'], $rule11);

        // Aturan 12: (Delay Sangat Tinggi ∧ Loss Rendah) ∨ (Jitter Sangat Tinggi ∧ Loss Rendah) → Buruk
        $cond1 = min($delay['Sangat Tinggi'], $loss['Rendah']);
        $cond2 = min($jitter['Sangat Tinggi'], $loss['Rendah']);
        $rule12 = max($cond1, $cond2);
        $qos['Buruk'] = max($qos['Buruk'], $rule12);
        
        return $qos;
    }

    // ========================================================================
    // TAHAP 3: DEFUZZIFIKASI 
    // ========================================================================

    private function defuzzify(array $qos): float
    {
        $sets = [
            'Sangat Buruk' => [0, 0, 10, 20],  
            'Buruk' => [15, 30, 45],     
            'Cukup' => [40, 55, 70],    
            'Baik' => [65, 80, 95],     
            'Sangat Baik' => [90, 95, 100, 100], 
        ];
        
        $samples = range(0, 100, 1);
        $numerator = 0.0;
        $denominator = 0.0;

        foreach ($samples as $sample) {
            $membershipValues = [
                'Sangat Buruk' => $this->trapezoid($sample, ...$sets['Sangat Buruk']),
                'Buruk' => $this->triangle($sample, ...$sets['Buruk']),
                'Cukup' => $this->triangle($sample, ...$sets['Cukup']),
                'Baik' => $this->triangle($sample, ...$sets['Baik']),
                'Sangat Baik' => $this->trapezoid($sample, ...$sets['Sangat Baik']),
            ];

            $clippedValues = [
                min($qos['Sangat Buruk'], $membershipValues['Sangat Buruk']),
                min($qos['Buruk'], $membershipValues['Buruk']),
                min($qos['Cukup'], $membershipValues['Cukup']),
                min($qos['Baik'], $membershipValues['Baik']),
                min($qos['Sangat Baik'], $membershipValues['Sangat Baik']),
            ];
            
            $aggregatedValue = max($clippedValues);
            $numerator += $sample * $aggregatedValue;
            $denominator += $aggregatedValue;
        }

        return $denominator === 0.0 ? 0.0 : $numerator / $denominator;
    }

    // ========================================================================
    // FUNGSI UTAMA & PENDUKUNG 
    // ========================================================================
    
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
        if ($score >= 90) return 'Sangat Baik';
        if ($score >= 65) return 'Baik';
        if ($score >= 40) return 'Cukup';
        if ($score >= 20) return 'Buruk';
        return 'Sangat Buruk';
    }
        private function triangle(float $x, float $a, float $b, float $c): float
    {
        // Jika x di luar rentang, derajat keanggotaan pasti 0.
        if ($x <= $a || $x >= $c) {
            return 0.0;
        }
        // Hindari pembagian dengan nol jika puncak dan kaki sama.
        if ($b - $a == 0 || $c - $b == 0) return 0.0;
        
        // Kalkulasi lereng naik dan lereng turun.
        return max(0.0, min(($x - $a) / ($b - $a), ($c - $x) / ($c - $b)));
    }

    /**
     * Kalkulasi fungsi keanggotaan bentuk Trapesium.
     * @param float $x Nilai input.
     * @param float $a Kaki kiri.
     * @param float $b Bahu kiri (awal plateau).
     * @param float $c Bahu kanan (akhir plateau).
     * @param float $d Kaki kanan.
     * @return float Derajat keanggotaan (0-1).
     */
    private function trapezoid(float $x, float $a, float $b, float $c, float $d): float
    {
        // Jika x di luar rentang, derajat keanggotaan pasti 0.
        if ($x <= $a || $x >= $d) return 0.0;
        // Jika x berada di plateau (bagian datar), derajat keanggotaan pasti 1.
        if ($x >= $b && $x <= $c) return 1.0;

        $val1 = 1.0;
        if ($b - $a > 0) { $val1 = ($x - $a) / ($b - $a); } 
        
        $val2 = 1.0;
        if ($d - $c > 0) { $val2 = ($d - $x) / ($d - $c); } 
        
        return max(0.0, min($val1, 1.0, $val2));
    }
}
