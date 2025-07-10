<?php declare(strict_types=1);

namespace Clases;

require_once __DIR__ . '/PolinomioAbstracto.php';

class Polinomio extends PolinomioAbstracto
{
    /**
     * Constructor que llama al constructor de la clase abstracta.
     * @param array<int, float> $terminos Array asociativo de exponentes y coeficientes.
     */
    public function __construct(array $terminos)
    {
        parent::__construct($terminos);
    }

    public static function armarDesdeCoeficientes(array $coeficientesPorExponente): Polinomio
    {
        $terminosLimpios = [];
        foreach ($coeficientesPorExponente as $exponente => $coeficiente) {
            $exponente = (int)$exponente;
            $coeficiente = (float)$coeficiente;
            if (abs($coeficiente) >= PHP_FLOAT_EPSILON) { 
                $terminosLimpios[$exponente] = $coeficiente;
            }
        }
        return new self($terminosLimpios);
    }

    public function sumar(PolinomioAbstracto $otroPolinomio): PolinomioAbstracto
    {
        $nuevosTerminos = $this->terminos;
        foreach ($otroPolinomio->getTerminos() as $exponente => $coeficiente) {
            $nuevosTerminos[$exponente] = ($nuevosTerminos[$exponente] ?? 0.0) + $coeficiente;
        }
        return new Polinomio($nuevosTerminos);
    }

    /**
     * Deriva el polinomio.
     * @return Polinomio Un nuevo objeto Polinomio que representa la derivada.
     */
    public function derivar(): PolinomioAbstracto
    {
        $nuevosTerminos = [];
        foreach ($this->terminos as $exponente => $coeficiente) {
            if ($exponente === 0) { // La derivada de una constante es 0
                continue;
            }
            $nuevosTerminos[$exponente - 1] = $coeficiente * $exponente;
        }
        return new Polinomio($nuevosTerminos);
    }

    /**
     * Evalúa el polinomio en un valor dado de x.
     * @param float $x El valor en el que se evaluará el polinomio.
     * @return float El resultado de la evaluación.
     */
    public function evaluar(float $x): float
    {
        $resultado = 0.0;
        foreach ($this->terminos as $exponente => $coeficiente) {
            $resultado += $coeficiente * ($x ** $exponente);
        }
        return $resultado;
    }

    /**
     * Representación en cadena del polinomio para mostrarlo.
     * @return string
     */
    public function __toString(): string
    {
        if (empty($this->terminos)) {
            return "0";
        }

        $str = [];
        // Ordenar de mayor a menor exponente para una representación legible
        krsort($this->terminos);

        foreach ($this->terminos as $exponente => $coeficiente) {
            // Manejar el signo
            $sign = ($coeficiente >= 0) ? '+' : '-';
            $absCoef = abs($coeficiente);

            if ($sign === '+' && !empty($str)) {
                $str[] = $sign;
            } elseif ($sign === '-' && empty($str)) {
                $str[] = $sign;
            } elseif ($sign === '-' && !empty($str)) {
                $str[] = $sign;
            }


            // Manejar coeficientes
            if ($absCoef === 1.0 && $exponente !== 0) {
                // No mostrar '1' si es 1x^n (excepto para x^0)
                $coefStr = '';
            } else {
                $coefStr = (string)$absCoef;
            }

            // Manejar la 'x' y el exponente
            if ($exponente === 0) {
                $str[] = (string)$absCoef;
            } elseif ($exponente === 1) {
                $str[] = $coefStr . 'x';
            } else {
                $str[] = $coefStr . 'x^' . $exponente;
            }
        }

        return implode(' ', $str);
    }
}