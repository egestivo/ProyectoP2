<?php declare(strict_types=1);

// Importar clases necesarias para evitar el uso de nombres de clase completamente calificados
use Clases\ResolverSistema;
use Clases\Polinomio;

// Iniciar sesión para mantener los datos de los polinomios entre recargas de página
session_start();

// Incluir los archivos de clases PHP necesarios
require_once __DIR__ . '/Clases/OperacionSistema.php';
require_once __DIR__ . '/Clases/SistemaLineal.php';
require_once __DIR__ . '/Clases/ResolverSistema.php';
require_once __DIR__ . '/Clases/OperacionPolinomio.php';
require_once __DIR__ . '/Clases/PolinomioAbstracto.php';
require_once __DIR__ . '/Clases/Polinomio.php';

// Variables para almacenar resultados y polinomios
$resultado_ecuaciones = null;
$resultado_polinomios = null;

// Recuperar polinomios de la sesión o inicializar un array vacío
// Cada elemento será un objeto Polinomio si se ha ingresado, o null si es nuevo/vacío
$polinomios_ingresados = $_SESSION['polinomios_ingresados'] ?? [];

// Determinar qué pestaña (Ecuaciones o Polinomios) debe estar activa al cargar la página
$activeTab = 'ecuaciones'; // Por defecto, iniciar en la pestaña de ecuaciones

// Manejo de la lógica cuando se envía el formulario (POST)

// Lógica para la calculadora de Ecuaciones Lineales 2x2
if (isset($_POST['calcular_ecuaciones'])) {
    $activeTab = 'ecuaciones'; // Asegura que esta pestaña permanezca activa
    try {
        // Obtener los coeficientes del formulario y convertirlos a flotantes
        $a1 = (float) $_POST['a1'];
        $a2 = (float) $_POST['a2'];
        $b1 = (float) $_POST['b1'];
        $b2 = (float) $_POST['b2'];
        $c1 = (float) $_POST['c1'];
        $c2 = (float) $_POST['c2'];

        // Crear una instancia del resolutor y calcular el sistema
        $resolver = new ResolverSistema($a1, $a2, $b1, $b2, $c1, $c2);
        $resultado_ecuaciones = $resolver->calcular();
    } catch (Exception $e) {
        // Capturar y mostrar errores en el cálculo
        $resultado_ecuaciones = ['error' => $e->getMessage()];
    }
}

// Lógica para la Calculadora de Polinomios
if (isset($_POST['operacion_polinomio']) || isset($_POST['add_polinomio']) || isset($_POST['update_degree_for_polinomio'])) {
    $activeTab = 'polinomios'; // Asegura que esta pestaña permanezca activa

    $operacion = $_POST['operacion_polinomio'] ?? ''; // Obtener la operación solicitada

    // Lógica para limpiar todos los polinomios
    if ($operacion === 'limpiar_polinomios') {
        $_SESSION['polinomios_ingresados'] = [];
        $polinomios_ingresados = []; 
        $resultado_polinomios = null; 
    } else {
        // Si se pide agregar un nuevo polinomio, incrementar el contador de inputs
        if (isset($_POST['add_polinomio'])) {
            $num_polinomios_a_mostrar = (int) $_POST['num_polinomios_inputs'] + 1;
        } else {
            // Si no se agrega, usar el número de inputs actual del formulario
            $num_polinomios_a_mostrar = (int) $_POST['num_polinomios_inputs'] ?? 1;
        }

        // Procesar los coeficientes enviados desde el formulario para cada polinomio
        $polinomios_actuales = [];
        for ($i = 1; $i <= $num_polinomios_a_mostrar; $i++) {
            // Cada input de coeficiente se llama "polinomio_X_coeficientes[exponente]"
            // PHP automáticamente crea un array asociativo por cada polinomio
            $coefs_array = $_POST["polinomio_{$i}_coeficientes"] ?? [];

            // Limpiar coeficientes con valor vacío o cero para no crear términos innecesarios
            $cleaned_coefs = [];
            foreach ($coefs_array as $exp => $coef) {
                if ($coef !== '' && $coef !== null) {
                    $cleaned_coefs[(int)$exp] = (float)$coef;
                }
            }
            // Crear un objeto Polinomio solo si hay coeficientes válidos
            if (!empty($cleaned_coefs)) {
                $polinomios_actuales[] = Polinomio::armarDesdeCoeficientes($cleaned_coefs);
            } else {
                $polinomios_actuales[] = null; // Marcar como vacío si no hay coeficientes
            }
        }
        // Filtrar los nulos si es que se crearon (e.g. un input de polinomio vacío)
        $polinomios_actuales = array_filter($polinomios_actuales, fn($p) => $p !== null);

        // Guardar los polinomios procesados en la sesión y actualizar la variable local
        $_SESSION['polinomios_ingresados'] = $polinomios_actuales;
        $polinomios_ingresados = $polinomios_actuales;

        // Ejecutar la operación de polinomio solicitada (sumar, derivar, evaluar)
        if ($operacion === 'derivar') {
            if (count($polinomios_ingresados) >= 1) {
                $polinomio_base = $polinomios_ingresados[0]; // Derivar el primer polinomio
                $resultado_polinomios = ['derivada' => $polinomio_base->derivar()];
            } else {
                $resultado_polinomios = ['error' => 'Debe ingresar al menos un polinomio para derivar.'];
            }
        } elseif ($operacion === 'evaluar') {
            if (count($polinomios_ingresados) >= 1) {
                $polinomio_base = $polinomios_ingresados[0]; // Evaluar el primer polinomio
                $valor_x = (float) ($_POST['valor_x'] ?? 0.0);
                $resultado_polinomios = ['evaluacion' => $polinomio_base->evaluar($valor_x), 'valor_x' => $valor_x];
            } else {
                $resultado_polinomios = ['error' => 'Debe ingresar al menos un polinomio para evaluar.'];
            }
        } elseif ($operacion === 'sumar') {
            if (count($polinomios_ingresados) >= 2) {
                $suma_total = $polinomios_ingresados[0];
                for ($i = 1; $i < count($polinomios_ingresados); $i++) {
                    $suma_total = $suma_total->sumar($polinomios_ingresados[$i]);
                }
                $resultado_polinomios = ['suma' => $suma_total];
            } else {
                $resultado_polinomios = ['error' => 'Debe ingresar al menos dos polinomios para sumar.'];
            }
        }
        // Si la operación fue solo 'update_degree_for_polinomio' o 'add_polinomio', no hay un resultado de cálculo específico
        // pero la página se recargará con los nuevos inputs o grados actualizados.
    }
}

// Lógica para usar un resultado de operación (suma o derivada) como un nuevo polinomio de entrada
if (isset($_POST['usar_resultado_suma_para_nuevo_calculo']) && isset($resultado_polinomios['suma'])) {
    $_SESSION['polinomios_ingresados'] = [$resultado_polinomios['suma']];
    $polinomios_ingresados = [$_SESSION['polinomios_ingresados'][0]]; // Forzar a ser un array de Polinomio
    $resultado_polinomios = null; // Limpiar resultado para nueva operación
    $activeTab = 'polinomios'; // Asegurar que la pestaña de polinomios esté activa
}
if (isset($_POST['usar_resultado_derivada_para_nuevo_calculo']) && isset($resultado_polinomios['derivada'])) {
    $_SESSION['polinomios_ingresados'] = [$resultado_polinomios['derivada']];
    $polinomios_ingresados = [$_SESSION['polinomios_ingresados'][0]]; // Forzar a ser un array de Polinomio
    $resultado_polinomios = null; // Limpiar resultado para nueva operación
    $activeTab = 'polinomios';
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Calculadoras Matemáticas</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-LN+7fdVzj6u52u30Kp6M/trliBMCMKTyK833zpbD+pXdCLuTusPj697FH4R/5mcr" crossorigin="anonymous">
</head>
<body class="bg-light">
    <div class="container py-5">
        <h1 class="mb-4">Calculadoras Matemáticas</h1>

        <ul class="nav nav-tabs" id="myTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link <?= ($activeTab === 'ecuaciones') ? 'active' : '' ?>" id="ecuaciones-tab" data-bs-toggle="tab" data-bs-target="#ecuaciones" type="button" role="tab" aria-controls="ecuaciones" aria-selected="<?= ($activeTab === 'ecuaciones') ? 'true' : 'false' ?>">
                    Ecuaciones Lineales 2x2
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link <?= ($activeTab === 'polinomios') ? 'active' : '' ?>" id="polinomios-tab" data-bs-toggle="tab" data-bs-target="#polinomios" type="button" role="tab" aria-controls="polinomios" aria-selected="<?= ($activeTab === 'polinomios') ? 'true' : 'false' ?>">
                    Calculadora de Polinomios
                </button>
            </li>
        </ul>

        <div class="tab-content" id="myTabContent">
            <div class="tab-pane fade <?= ($activeTab === 'ecuaciones') ? 'show active' : '' ?>" id="ecuaciones" role="tabpanel" aria-labelledby="ecuaciones-tab">
                <h2 class="mt-4">Resolución de sistemas de Ecuaciones Lineales de 2x2</h2>
                <form method="POST" class="needs-validation">
                    <table class="table table-striped table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th scope="col" class="text-center">Ecuacion</th>
                                <th scope="col" class="text-center">X</th>
                                <th scope="col" class="text-center">Y</th>
                                <th scope="col" class="text-center">Término Independiente</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <th scope="col" class="text-center">Ecuación 1</th>
                                <td><input type="number" name="a1" class="form-control" step="any" required value="<?= htmlspecialchars($_POST['a1'] ?? '') ?>"></td>
                                <td><input type="number" name="b1" class="form-control" step="any" required value="<?= htmlspecialchars($_POST['b1'] ?? '') ?>"></td>
                                <td><input type="number" name="c1" class="form-control" step="any" required value="<?= htmlspecialchars($_POST['c1'] ?? '') ?>"></td>
                            </tr>
                            <tr>
                                <th scope="col" class="text-center">Ecuación 2</th>
                                <td><input type="number" name="a2" class="form-control" step="any" required value="<?= htmlspecialchars($_POST['a2'] ?? '') ?>"></td>
                                <td><input type="number" name="b2" class="form-control" step="any" required value="<?= htmlspecialchars($_POST['b2'] ?? '') ?>"></td>
                                <td><input type="number" name="c2" class="form-control" step="any" required value="<?= htmlspecialchars($_POST['c2'] ?? '') ?>"></td>
                            </tr>
                        </tbody>
                    </table>
                    <div class="mt-3">
                        <button type="submit" name="calcular_ecuaciones" class="btn btn-primary me-2">Calcular</button>
                        <button type="reset" class="btn btn-danger">Limpiar campos</button>
                    </div>
                    <?php if(!is_null($resultado_ecuaciones)): // Mostrar resultado si existe ?>
                        <h5 class="mt-4">Resultado:</h5>
                        <?php if (isset($resultado_ecuaciones["error"])): ?>
                            <div class="alert alert-danger" role="alert"><?=htmlspecialchars($resultado_ecuaciones["error"])?></div>
                        <?php else: ?>
                            <div class="alert alert-success" role="alert">
                                <p class="mb-0"><strong>x = <?=$resultado_ecuaciones["x"]?></strong></p>
                                <p class="mb-0"><strong>y = <?=$resultado_ecuaciones["y"]?></strong></p>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </form>
            </div>

            <div class="tab-pane fade <?= ($activeTab === 'polinomios') ? 'show active' : '' ?>" id="polinomios" role="tabpanel" aria-labelledby="polinomios-tab">
                <h2 class="mt-4">Calculadora de Polinomios</h2>
                <form method="POST" id="polinomioForm">
                    <input type="hidden" name="operacion_polinomio" id="operacionPolinomioInput" value="">
                    <input type="hidden" name="num_polinomios_inputs" id="numPolinomiosInputs" value="<?= max(count($polinomios_ingresados), 1) ?>">

                    <div id="polinomiosContainer">
                        <?php
                        // Determinar cuántos bloques de polinomio se deben mostrar
                        // Si se acaba de presionar "Agregar otro Polinomio", se suma uno
                        $num_blocks_to_render = isset($_POST['add_polinomio']) ?
                                                    (int)($_POST['num_polinomios_inputs'] ?? 1) + 1 :
                                                    max(count($polinomios_ingresados), 1);

                        for ($pIndex = 1; $pIndex <= $num_blocks_to_render; $pIndex++):
                            $polinomio_obj = $polinomios_ingresados[$pIndex - 1] ?? null; // Obtener polinomio existente o nulo

                            // Determinar el grado inicial o actual del polinomio
                            $current_degree = 0; // Grado por defecto
                            $coefs_from_obj = []; // Coeficientes del objeto Polinomio para prellenar inputs

                            if ($polinomio_obj) {
                                // Si hay un objeto Polinomio, usar sus términos para establecer el grado y coeficientes
                                $terminos = $polinomio_obj->getTerminos();
                                if (!empty($terminos)) {
                                    $maxExistingExp = max(array_keys($terminos));
                                    $minExistingExp = min(array_keys($terminos));
                                    $current_degree = ($maxExistingExp >= 0) ? $maxExistingExp : $minExistingExp;
                                    $coefs_from_obj = $terminos;
                                }
                            }

                            // Si se envió una actualización de grado específica para este polinomio
                            // esto tiene prioridad sobre el grado del objeto existente.
                            if (isset($_POST['update_degree_for_polinomio']) && (int)$_POST['update_degree_for_polinomio'] === $pIndex) {
                                $current_degree = (int)($_POST["polinomio_{$pIndex}_selected_degree"] ?? $current_degree);
                            } else if (isset($_POST["polinomio_{$pIndex}_selected_degree"])) {
                                // Si hay un grado enviado por POST (de una recarga normal, no solo update_degree)
                                $current_degree = (int)$_POST["polinomio_{$pIndex}_selected_degree"];
                            }

                            // Asegurar que el grado esté dentro de un rango razonable para la interfaz
                            $current_degree = max(-30, min(30, $current_degree));
                        ?>
                            <div class="polinomio-input-group mb-3 p-3 border rounded bg-white" data-polinomio-index="<?= $pIndex ?>">
                                <h5>Polinomio <?= $pIndex ?></h5>
                                <div class="mb-3">
                                    <label for="degreeInput<?= $pIndex ?>" class="form-label">Grado del Polinomio <?= $pIndex ?>: <strong><?= $current_degree ?></strong></label>
                                    <div class="input-group">
                                        <input type="number" class="form-control" name="polinomio_<?= $pIndex ?>_selected_degree" id="degreeInput<?= $pIndex ?>" min="-30" max="30" value="<?= $current_degree ?>">
                                        <button type="submit" name="update_degree_for_polinomio" value="<?= $pIndex ?>" class="btn btn-outline-secondary">Actualizar Coeficientes</button>
                                    </div>
                                </div>
                                <div class="row coefficients-inputs">
                                    <?php
                                    // Determinar el rango de exponentes a generar
                                    $startExp = ($current_degree >= 0) ? $current_degree : 0;
                                    $endExp = ($current_degree >= 0) ? 0 : $current_degree;

                                    // Generar los inputs de coeficientes
                                    // Si el grado es positivo o cero (ej. 3, 0), va de $current_degree a 0.
                                    // Si el grado es negativo (ej. -2), va de 0 a $current_degree.
                                    $exponents = [];
                                    if ($startExp >= $endExp) {
                                        for ($i = $startExp; $i >= $endExp; $i--) {
                                            $exponents[] = $i;
                                        }
                                    } else { // Para grados negativos, ir de 0 hacia el negativo
                                        for ($i = $startExp; $i >= $endExp; $i--) {
                                            $exponents[] = $i;
                                        }
                                    }

                                    // Ordenar los exponentes de mayor a menor para la visualización
                                    rsort($exponents);

                                    foreach ($exponents as $exponent):
                                        // Obtener el valor del coeficiente. Prioridad: POST (si se envió un valor) -> Objeto Polinomio -> 0 (por defecto)
                                        $input_name = "polinomio_{$pIndex}_coeficientes[{$exponent}]";
                                        $value = $_POST[$input_name] ?? ($coefs_from_obj[$exponent] ?? 0);
                                    ?>
                                        <div class="col-md-3 mb-2">
                                            <div class="input-group">
                                                <input type="number" step="any" class="form-control"
                                                    name="<?= $input_name ?>"
                                                    value="<?= htmlspecialchars((string)$value) ?>">
                                                <span class="input-group-text">x<sup><?= $exponent ?></sup></span>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endfor; ?>
                    </div>

                    <div class="mt-3">
                        <button type="submit" name="add_polinomio" class="btn btn-info me-2">Agregar otro Polinomio</button>
                        <button type="submit" name="operacion_polinomio" value="limpiar_polinomios" class="btn btn-warning me-2">Borrar Polinomios</button>
                    </div>

                    <div class="mt-4">
                        <h4>Operaciones:</h4>
                        <div class="d-flex flex-wrap gap-2 mb-3">
                            <button type="submit" name="operacion_polinomio" value="sumar" class="btn btn-success">Sumar</button>
                            <button type="submit" name="operacion_polinomio" value="derivar" class="btn btn-primary">Derivar</button>
                            <div class="input-group" style="width: auto;">
                                <button type="submit" name="operacion_polinomio" value="evaluar" class="btn btn-secondary">Evaluar en x=</button>
                                <input type="number" step="any" class="form-control" name="valor_x" style="max-width: 100px;" value="<?= htmlspecialchars($_POST['valor_x'] ?? '0') ?>">
                            </div>
                        </div>
                    </div>

                    <?php if(!is_null($resultado_polinomios)): // Mostrar resultado de operaciones con polinomios si existe ?>
                        <h5 class="mt-4">Resultado Polinomios:</h5>
                        <?php if (isset($resultado_polinomios["error"])): ?>
                            <div class="alert alert-danger" role="alert"><?=htmlspecialchars($resultado_polinomios["error"])?></div>
                        <?php else: ?>
                            <div class="alert alert-success" role="alert">
                                <?php if (isset($resultado_polinomios["suma"])): ?>
                                    <p class="mb-0"><strong>Suma:</strong> <?= htmlspecialchars($resultado_polinomios["suma"]->__toString()) ?></p>
                                    <button type="submit" name="usar_resultado_suma_para_nuevo_calculo" class="btn btn-sm btn-outline-secondary mt-2">Usar resultado para nuevo cálculo</button>
                                <?php elseif (isset($resultado_polinomios["derivada"])): ?>
                                    <p class="mb-0"><strong>Derivada:</strong> <?= htmlspecialchars($resultado_polinomios["derivada"]->__toString()) ?></p>
                                    <button type="submit" name="usar_resultado_derivada_para_nuevo_calculo" class="btn btn-sm btn-outline-secondary mt-2">Usar resultado para nuevo cálculo</button>
                                <?php elseif (isset($resultado_polinomios["evaluacion"])): ?>
                                    <p class="mb-0"><strong>Evaluación en x=<?= htmlspecialchars((string)$resultado_polinomios['valor_x']) ?>:</strong> <?= htmlspecialchars((string)$resultado_polinomios["evaluacion"]) ?></p>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-ndDqU0Gzau9qJ1lfW4pNLlhNTkCfHzAVBReH9diLvGRem5+R9g2FzA8ZGN954O5Q"
        crossorigin="anonymous"></script>

    <script>
        // Este script es el mínimo necesario para que las pestañas de Bootstrap funcionen.
        // No hay lógica personalizada de JavaScript aquí.
        document.addEventListener('DOMContentLoaded', function () {
            var activeTab = document.querySelector('.nav-link.active[data-bs-toggle="tab"]');
            if (activeTab) {
                var tab = new bootstrap.Tab(activeTab);
                tab.show();
            }
        });
    </script>
</body>
</html>