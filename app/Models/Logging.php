<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Logging extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'delay_median_ms',
        'delay_p95_ms',
        'jitter_avg_ms',
        'loss_pct',
        'bitrate_sent_mbps',
        'ssid',
        'bssid',
        'band',
        'channel',
        'rssi',
        'score_qos',
        'category_qos',
    ];
}