<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Boletos extends Model
{
    use HasFactory;
    protected $fillable =[
        'CodBar',
        'Valor',
        'Descricao',
        'contaDestino',
        'dataPagamento',
        'dataCriacao',
        'status',
    ];
}
