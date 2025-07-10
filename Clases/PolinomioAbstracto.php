<?php
declare(strict_types=1);
namespace Clases;
require_once __DIR__ . '/OperacionPolinomio.php';
abstract class PolinomioAbstracto implements OperacionPolinomio {
    protected array $terminos;

    public function __construct(array $terminos) {
        $this->terminos = $terminos;
    }

    public function getTerminos(): array {
        return $this->terminos;
    }
    
    public function __toString(): string {
        $resultado = '';
        foreach ($this->terminos as $exponente => $coeficiente) {
            if ($coeficiente === 0.0) {
                continue; // Ignorar términos con coeficiente cero
            }
            if ($resultado !== '') {
                $resultado .= ' + ';
            }
            if ($exponente === 0) {
                $resultado .= (string)$coeficiente;
            } elseif ($exponente === 1) {
                $resultado .= "{$coeficiente}x";
            } else {
                $resultado .= "{$coeficiente}x^{$exponente}";
            }
        }
        return $resultado ?: '0'; // Si no hay términos, retornar '0'
    }
}