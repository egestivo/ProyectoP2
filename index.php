<?php declare(strict_types=1);

use Clases\ResolverSistema;
use Clases\Polinomio;

// Iniciar sesión para almacenar polinomios entre peticiones
session_start();

// Asegurarse de que los includes de las clases de polinomios estén aquí
require_once __DIR__ . '/Clases/OperacionSistema.php';
require_once __DIR__ . '/Clases/SistemaLineal.php';
require_once __DIR__ . '/Clases/ResolverSistema.php';

require_once __DIR__ . '/Clases/OperacionPolinomio.php';
require_once __DIR__ . '/Clases/PolinomioAbstracto.php';
require_once __DIR__ . '/Clases/Polinomio.php';

$resultado_ecuaciones = null;
$resultado_polinomios = null;
$polinomios_ingresados = $_SESSION['polinomios_ingresados'] ?? []; // Array para almacenar los objetos Polinomio

// Determinar qué pestaña está activa (para mantenerla activa después de un POST)
$activeTab = 'ecuaciones'; // Por defecto, la pestaña de ecuaciones

//* Manejo de POST para Ecuaciones Lineales 2x2
if (isset($_POST['calcular_ecuaciones'])) {
    $activeTab = 'ecuaciones';
    try {
        $a1 = (float) $_POST['a1'];
        $a2 = (float) $_POST['a2'];
        $b1 = (float) $_POST['b1'];
        $b2 = (float) $_POST['b2'];
        $c1 = (float) $_POST['c1'];
        $c2 = (float) $_POST['c2'];

        $resolver = new ResolverSistema($a1, $a2, $b1, $b2, $c1, $c2);
        $resultado_ecuaciones = $resolver->calcular();
    } catch (Exception $e) {
        $resultado_ecuaciones = ['error' => $e->getMessage()];
    }
}

//* Manejo de POST para Calculadora de Polinomios
if (isset($_POST['operacion_polinomio'])) {
    $activeTab = 'polinomios';
    $operacion = $_POST['operacion_polinomio'];

    // Limpiar resultados y polinomios si se pide iniciar nuevo cálculo
    if ($operacion === 'limpiar_polinomios') {
        $_SESSION['polinomios_ingresados'] = [];
        $polinomios_ingresados = [];
        $resultado_polinomios = null;
    } else {
        // Procesar la entrada de polinomios
        $num_polinomios = (int) $_POST['num_polinomios_inputs'] ?? 0;
        $polinomios_actuales = [];

        for ($i = 1; $i <= $num_polinomios; $i++) {
            $coeficientes_raw = $_POST["polinomio_{$i}_coeficientes"] ?? '';
            if (!empty($coeficientes_raw)) {
                $coefs_array = json_decode($coeficientes_raw, true); // Decodificar JSON de coeficientes
                if (is_array($coefs_array)) {
                    $polinomios_actuales[] = Polinomio::armarDesdeCoeficientes($coefs_array);
                }
            }
        }
        $_SESSION['polinomios_ingresados'] = $polinomios_actuales; // Guardar en sesión
        $polinomios_ingresados = $polinomios_actuales; // Actualizar variable local

        if ($operacion === 'derivar') {
            if (count($polinomios_ingresados) >= 1) {
                $polinomio_base = $polinomios_ingresados[0]; // Derivamos el primer polinomio ingresado
                $resultado_polinomios = ['derivada' => $polinomio_base->derivar()];
            } else {
                $resultado_polinomios = ['error' => 'Debe ingresar al menos un polinomio para derivar.'];
            }
        } elseif ($operacion === 'evaluar') {
            if (count($polinomios_ingresados) >= 1) {
                $polinomio_base = $polinomios_ingresados[0]; // Evaluamos el primer polinomio ingresado
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
        } elseif ($operacion === 'agregar_polinomio_input') {
            // No hay operación de cálculo, solo se prepara la interfaz para un nuevo input
            // El front-end se encargará de agregar el nuevo div de input.
            // Para mantener la consistencia en el back-end, no hacemos nada aquí,
            // ya que el número de inputs se maneja en el JS y la re-renderización del form.
        }
    }
}
// Esto es para que si se usa "usar resultado para nuevo cálculo", el resultado persista
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
                    <div>
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
                                    <td scope="col">
                                        <input type="number" name="a1" class="form-control" step="any" required value="<?= isset($_POST['a1']) ? htmlspecialchars($_POST['a1']) : '' ?>">
                                    </td>
                                    <td scope="col">
                                        <input type="number" name="b1" class="form-control" step="any" required value="<?= isset($_POST['b1']) ? htmlspecialchars($_POST['b1']) : '' ?>">
                                    </td>
                                    <td scope="col">
                                        <input type="number" name="c1" class="form-control" step="any" required value="<?= isset($_POST['c1']) ? htmlspecialchars($_POST['c1']) : '' ?>">
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="col" class="text-center">Ecuación 2</th>
                                    <td scope="col">
                                        <input type="number" name="a2" class="form-control" step="any" required value="<?= isset($_POST['a2']) ? htmlspecialchars($_POST['a2']) : '' ?>">
                                    </td>
                                    <td scope="col">
                                        <input type="number" name="b2" class="form-control" step="any" required value="<?= isset($_POST['b2']) ? htmlspecialchars($_POST['b2']) : '' ?>">
                                    </td>
                                    <td scope="col">
                                        <input type="number" name="c2" class="form-control" step="any" required value="<?= isset($_POST['c2']) ? htmlspecialchars($_POST['c2']) : '' ?>">
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3">
                        <button type="submit" name="calcular_ecuaciones" class="btn btn-primary me-2">Calcular</button>
                        <button type="reset" class="btn btn-danger">Limpiar campos</button>
                    </div>
                    <?php if(!is_null($resultado_ecuaciones)): ?>
                        <h5 class="mt-4">Resultado:</h5>
                        <?php if (isset($resultado_ecuaciones["error"])): ?>
                            <div class="alert alert-danger" role="alert">
                                <?=htmlspecialchars($resultado_ecuaciones["error"])?>
                            </div>
                            <?php else: ?>
                                <div class="alert alert-success" role="alert">
                                    <p class="mb-0">
                                        <strong>
                                        x= <?=$resultado_ecuaciones["x"]?>
                                        </strong>
                                    </p>
                                    <p class="mb-0">
                                        <strong>
                                        y= <?=$resultado_ecuaciones["y"]?>
                                        </strong>
                                    </p>
                                </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </form>
            </div>

            <div class="tab-pane fade <?= ($activeTab === 'polinomios') ? 'show active' : '' ?>" id="polinomios" role="tabpanel" aria-labelledby="polinomios-tab">
                <h2 class="mt-4">Calculadora de Polinomios</h2>
                <form method="POST" id="polinomioForm">
                    <input type="hidden" name="operacion_polinomio" id="operacionPolinomioInput" value="">
                    <input type="hidden" name="num_polinomios_inputs" id="numPolinomiosInputs" value="<?= count($polinomios_ingresados) > 0 ? count($polinomios_ingresados) : 1 ?>">

                    <div id="polinomiosContainer">
                        <?php if (empty($polinomios_ingresados)): ?>
                            <div class="polinomio-input-group mb-3 p-3 border rounded bg-white" data-polinomio-index="1">
                                <h5>Polinomio 1</h5>
                                <div class="mb-3">
                                    <label for="degreeSlider1" class="form-label">Grado del Polinomio 1: <span id="degreeValue1">0</span></label>
                                    <input type="range" class="form-range degree-slider" id="degreeSlider1" min="-30" max="30" value="0" data-target-polinomio="1">
                                </div>
                                <div class="row coefficients-inputs" id="coefficientsContainer1">
                                    </div>
                                <input type="hidden" name="polinomio_1_coeficientes" id="polinomio_1_coeficientes" value="">
                            </div>
                        <?php else: ?>
                            <?php foreach ($polinomios_ingresados as $index => $polinomioObj):
                                $pIndex = $index + 1;
                                $terminos = $polinomioObj->getTerminos();

                                // Determinar el valor inicial del slider para precargar
                                $initialSliderValue = 0; // Por defecto
                                if (!empty($terminos)) {
                                    $maxExistingExp = max(array_keys($terminos));
                                    $minExistingExp = min(array_keys($terminos));

                                    if ($maxExistingExp >= 0) { // Si hay algún exponente positivo o cero
                                        $initialSliderValue = $maxExistingExp;
                                    } else { // Si todos los exponentes son negativos
                                        $initialSliderValue = $minExistingExp;
                                    }
                                }

                                // Preparar los coeficientes para JS
                                $jsCoefs = [];
                                foreach ($terminos as $exp => $coef) {
                                    $jsCoefs[$exp] = $coef;
                                }
                            ?>
                                <div class="polinomio-input-group mb-3 p-3 border rounded bg-white" data-polinomio-index="<?= $pIndex ?>">
                                    <h5>Polinomio <?= $pIndex ?></h5>
                                    <div class="mb-3">
                                        <label for="degreeSlider<?= $pIndex ?>" class="form-label">Grado del Polinomio <?= $pIndex ?>: <span id="degreeValue<?= $pIndex ?>"><?= $initialSliderValue ?></span></label>
                                        <input type="range" class="form-range degree-slider" id="degreeSlider<?= $pIndex ?>" min="-30" max="30" value="<?= $initialSliderValue ?>" data-target-polinomio="<?= $pIndex ?>">
                                    </div>
                                    <div class="row coefficients-inputs" id="coefficientsContainer<?= $pIndex ?>">
                                        </div>
                                    <input type="hidden" name="polinomio_<?= $pIndex ?>_coeficientes" id="polinomio_<?= $pIndex ?>_coeficientes" value="<?= htmlspecialchars(json_encode($jsCoefs)) ?>">
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <div class="mt-3">
                        <button type="button" class="btn btn-info me-2" id="addPolinomioBtn">Agregar otro Polinomio</button>
                        <button type="button" class="btn btn-warning me-2" id="clearPolinomiosBtn">Borrar Polinomios</button>
                    </div>

                    <div class="mt-4">
                        <h4>Operaciones:</h4>
                        <div class="d-flex flex-wrap gap-2 mb-3">
                            <button type="submit" class="btn btn-success" data-operation="sumar">Sumar</button>
                            <button type="submit" class="btn btn-primary" data-operation="derivar">Derivar</button>
                            <div class="input-group" style="width: auto;">
                                <button type="submit" class="btn btn-secondary" data-operation="evaluar">Evaluar en x=</button>
                                <input type="number" step="any" class="form-control" name="valor_x" id="valorXInput" style="max-width: 100px;" value="<?= isset($_POST['valor_x']) ? htmlspecialchars($_POST['valor_x']) : '0' ?>">
                            </div>
                        </div>
                    </div>

                    <?php if(!is_null($resultado_polinomios)): ?>
                        <h5 class="mt-4">Resultado Polinomios:</h5>
                        <?php if (isset($resultado_polinomios["error"])): ?>
                            <div class="alert alert-danger" role="alert">
                                <?=htmlspecialchars($resultado_polinomios["error"])?>
                            </div>
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

    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-ndDqU0Gzau9qJ1lfW4pNLlhNTkCfHzAVBReH9diLvGRem5+R9g2FzA8ZGN954O5Q"
        crossorigin="anonymous"></script>

    <script>
        $(document).ready(function() {
            let nextPolinomioIndex = <?= count($polinomios_ingresados) > 0 ? count($polinomios_ingresados) + 1 : 2 ?>;

            // Función para generar los inputs de coeficientes para un polinomio dado un grado
            // `chosenDegree` es el valor del slider (puede ser positivo, cero o negativo)
            function generateCoefficientInputs(polinomioIndex, chosenDegree, initialCoefs = {}) {
                const $container = $(`#coefficientsContainer${polinomioIndex}`);
                $container.empty(); // Limpiar inputs existentes

                let startExp, endExp;

                if (chosenDegree >= 0) {
                    // Si el grado es positivo o cero (ej. 3, 0), generamos de chosenDegree hasta 0
                    startExp = chosenDegree;
                    endExp = 0;
                } else {
                    // Si el grado es negativo (ej. -2), generamos de 0 hasta chosenDegree
                    startExp = 0;
                    endExp = chosenDegree;
                }

                const inputsToAppend = [];

                // Generar los inputs en el rango definido
                if (startExp >= endExp) { // Iterar de mayor a menor exponente
                    for (let i = startExp; i >= endExp; i--) {
                        const value = initialCoefs[i] !== undefined ? initialCoefs[i] : '0'; // Por defecto 0
                        const inputHtml = `
                            <div class="col-md-3 mb-2">
                                <div class="input-group">
                                    <input type="number" step="any" class="form-control coef-input"
                                        data-exponent="${i}"
                                        data-polinomio="${polinomioIndex}"
                                        value="${value}">
                                    <span class="input-group-text">x<sup>${i}</sup></span>
                                </div>
                            </div>
                        `;
                        inputsToAppend.push(inputHtml);
                    }
                } else { // Iterar de menor a mayor exponente (cuando startExp es 0 y endExp es negativo)
                    for (let i = startExp; i >= endExp; i--) {
                        const value = initialCoefs[i] !== undefined ? initialCoefs[i] : '0'; // Por defecto 0
                        const inputHtml = `
                            <div class="col-md-3 mb-2">
                                <div class="input-group">
                                    <input type="number" step="any" class="form-control coef-input"
                                        data-exponent="${i}"
                                        data-polinomio="${polinomioIndex}"
                                        value="${value}">
                                    <span class="input-group-text">x<sup>${i}</sup></span>
                                </div>
                            </div>
                        `;
                        inputsToAppend.push(inputHtml);
                    }
                }
                $container.append(inputsToAppend.join('')); // Añadir todos los inputs de una vez

                // Ordenar los inputs para asegurar que los exponentes más altos estén primero
                const inputs = $container.children().get();
                inputs.sort((a, b) => {
                    const expA = parseInt($(a).find('.coef-input').data('exponent'));
                    const expB = parseInt($(b).find('.coef-input').data('exponent'));
                    return expB - expA; // Orden descendente por exponente
                });
                $container.empty().append(inputs);

                updateHiddenPolinomioInput(polinomioIndex); // Actualizar el hidden input al generar
            }

            // Función para actualizar el hidden input con los coeficientes
            function updateHiddenPolinomioInput(polinomioIndex) {
                const coefs = {};
                $(`#coefficientsContainer${polinomioIndex} .coef-input`).each(function() {
                    const exponent = $(this).data('exponent');
                    const value = parseFloat($(this).val());
                    if (!isNaN(value)) {
                        coefs[exponent] = value;
                    }
                });
                $(`#polinomio_${polinomioIndex}_coeficientes`).val(JSON.stringify(coefs));
            }

            // Manejar cambio en el slider de grado
            $(document).on('input', '.degree-slider', function() {
                const polinomioIndex = $(this).data('target-polinomio');
                const degreeValue = $(this).val(); // Este es el grado elegido por el usuario
                $(`#degreeValue${polinomioIndex}`).text(degreeValue);

                // Llamar a la función de generación con el grado elegido
                generateCoefficientInputs(polinomioIndex, parseInt(degreeValue));
            });

            // Manejar cambio en los inputs de coeficientes para actualizar el hidden input
            $(document).on('input', '.coef-input', function() {
                const polinomioIndex = $(this).data('polinomio');
                updateHiddenPolinomioInput(polinomioIndex);
            });

            // Función para agregar un nuevo grupo de inputs de polinomio
            $('#addPolinomioBtn').on('click', function() {
                const newPolinomioHtml = `
                    <div class="polinomio-input-group mb-3 p-3 border rounded bg-white" data-polinomio-index="${nextPolinomioIndex}">
                        <h5>Polinomio ${nextPolinomioIndex}</h5>
                        <div class="mb-3">
                            <label for="degreeSlider${nextPolinomioIndex}" class="form-label">Grado del Polinomio ${nextPolinomioIndex}: <span id="degreeValue${nextPolinomioIndex}">0</span></label>
                            <input type="range" class="form-range degree-slider" id="degreeSlider${nextPolinomioIndex}" min="-30" max="30" value="0" data-target-polinomio="${nextPolinomioIndex}">
                        </div>
                        <div class="row coefficients-inputs" id="coefficientsContainer${nextPolinomioIndex}">
                            </div>
                        <input type="hidden" name="polinomio_${nextPolinomioIndex}_coeficientes" id="polinomio_${nextPolinomioIndex}_coeficientes" value="">
                    </div>
                `;
                $('#polinomiosContainer').append(newPolinomioHtml);
                $(`#numPolinomiosInputs`).val(nextPolinomioIndex); // Actualizar el contador de polinomios
                generateCoefficientInputs(nextPolinomioIndex, 0); // Generar inputs iniciales para el nuevo polinomio (grado 0)
                nextPolinomioIndex++;
            });

            // Borrar polinomios y restablecer la interfaz
            $('#clearPolinomiosBtn').on('click', function() {
                // Enviar formulario para limpiar en sesión también antes de vaciar localmente
                $('#operacionPolinomioInput').val('limpiar_polinomios');
                $('#polinomioForm').submit(); // Esto recargará la página y la dejará limpia
            });


            // Asignar el valor de la operación al hidden input antes de enviar el formulario
            $('#polinomioForm button[type="submit"]').on('click', function() {
                const operation = $(this).data('operation');
                if (operation) { // Asegurarse de que solo los botones de operación lo establezcan
                    $('#operacionPolinomioInput').val(operation);
                }
            });

            // Inicializar inputs de coeficientes al cargar la página (para polinomios existentes o el primero)
            <?php if (!empty($polinomios_ingresados)): ?>
                <?php foreach ($polinomios_ingresados as $index => $polinomioObj):
                    $pIndex = $index + 1;
                    $terminos = $polinomioObj->getTerminos();

                    $initialSliderValue = 0; // Default if no terms
                    if (!empty($terminos)) {
                        $maxExistingExp = max(array_keys($terminos));
                        $minExistingExp = min(array_keys($terminos));

                        if ($maxExistingExp >= 0) {
                            $initialSliderValue = $maxExistingExp;
                        } else { // All exponents are negative
                            $initialSliderValue = $minExistingExp;
                        }
                    }

                    $jsCoefs = [];
                    foreach ($terminos as $exp => $coef) {
                        $jsCoefs[$exp] = $coef;
                    }
                ?>
                    // Asegurar que el slider refleje el grado máximo o el mínimo si es negativo
                    $(`#degreeSlider<?= $pIndex ?>`).val(`<?= $initialSliderValue ?>`);
                    $(`#degreeValue<?= $pIndex ?>`).text(`<?= $initialSliderValue ?>`);
                    // Cuando inicializamos, pasamos el valor del slider como el grado elegido
                    generateCoefficientInputs(<?= $pIndex ?>, <?= $initialSliderValue ?>, <?= json_encode($jsCoefs) ?>);
                <?php endforeach; ?>
            <?php else: ?>
                generateCoefficientInputs(1, 0); // Generar inputs iniciales para el primer polinomio (grado 0 por defecto)
            <?php endif; ?>

            // Activar la pestaña correcta al cargar la página o después de un POST
            new bootstrap.Tab($(`#<?= $activeTab ?>-tab`)[0]).show();

        });
    </script>
</body>
</html>