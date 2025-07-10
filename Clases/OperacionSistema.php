<?php

declare(strict_types=1);


// esto puedes considerarlo como un package de java xd, osea 
// tiene muchas clases con sus funciones incluso si se repiten sus nombres
namespace Clases;

interface OperacionSistema
{
    public function calcular(): array;
    public function imprimirResultadoHTML(): string;
}