<?php
declare(strict_types=1);
namespace Clases;
interface OperacionPolinomio {
    public function sumar(PolinomioAbstracto $polinomios): PolinomioAbstracto;
    public function derivar(): PolinomioAbstracto;
    public function evaluar(float $x): float;
}