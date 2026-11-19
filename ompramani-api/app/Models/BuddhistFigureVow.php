<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BuddhistFigureVow extends Model
{
    use HasFactory;

    protected $table = 'buddhist_figure_vows';

    protected $fillable = [
        'buddhist_figure_id',
        'title',
        'content',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    public function figure(): BelongsTo
    {
        return $this->belongsTo(BuddhistFigure::class, 'buddhist_figure_id');
    }
}
