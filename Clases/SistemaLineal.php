<?php declare(strict_types=1);

namespace Clases;

abstract class SistemaLineal implements OperacionSistema
{
    protected float $a1;
    protected float $a2;
    protected float $b1;
    protected float $b2;
    protected float $c1;
    protected float $c2;

    public function __construct(float $a1, float $a2, float $b1, float $b2, float $c1, float $c2)
    {
        $this->a1 = $a1;
        $this->a2 = $a2;
        $this->b1 = $b1;
        $this->b2 = $b2;
        $this->c1 = $c1;
        $this->c2 = $c2;
    }

}