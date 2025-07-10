<?php declare(strict_types=1);

namespace Clases;

class ResolverSistema extends SistemaLineal
{
    public function calcular(): array
    {
        //* Esto es cramer, prácticamente xd
        $det = ($this->a1 * $this->b2) - ($this->a2 * $this->b1);
        $detX = ($this->c1 * $this->b2) - ($this->c2 * $this->b1);
        $detY = ($this->a1 * $this->c2) - ($this->a2 * $this->c1);

        if($det == 0) {
            return ['error' => "El sistema no tiene solución real (Determinante = 0)"];
        }
        //* Si no es 0 el determinante
        $x = $detX / $det;
        $y = $detY / $det;
        //* Devolvemos el asociativo con las soluciones yesyesyes 👍
        return ["x" => round($x, 2), "y" => round($y, 2)];
    }

    public function imprimirResultadoHTML(): string
    {
        $resultado = $this->calcular();
        if (isset($resultado["error"])) {
            return "<p>{$resultado["error"]}</p>";
        }
        echo "<p>La solución del sistema es:</p>";
        echo "<ul>";
        echo "<li>x = {$resultado["x"]}</li>";
        echo "<li>y = {$resultado["y"]}</li>";
        echo "</ul>";

        return "<p>La solución del sistema es: x = {$resultado["x"]}, y = {$resultado["y"]}</p>";
    }
}