<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contas extends Model
{
    use HasFactory;

    protected $fillable = [
        'Nome',
        'CpfCnpj',
        'Telefone',
        'DataNascimento',
        'Senha',
        'Email',
        'Logradouro',
        'Complemento',
        'Bairro',
        'Numero',
        'Selfie',
        'FotoDocumento',
        'CEP',
        'Cidade',
        'RendaMensal',
        'Saldo'
    ];


}
