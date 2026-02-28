<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';

use App\Services\EvolutionService;

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$service = new EvolutionService();

echo "Testando criação de grupo..." . PHP_EOL;

try {
    // A API Evolution exige ao menos 1 participante na criação
    $result = $service->createGroup("Teste API " . date('H:i:s'), ['+5511999999999']);

    if ($result) {
        echo "Sucesso! Grupo criado: " . json_encode($result, JSON_PRETTY_PRINT) . PHP_EOL;
    } else {
        echo "Falha na criação do grupo. Verifique o arquivo storage/logs/laravel.log para detalhes." . PHP_EOL;
    }
} catch (\Exception $e) {
    echo "EXCEÇÃO: " . $e->getMessage() . PHP_EOL;
}
