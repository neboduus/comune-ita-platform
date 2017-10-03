´´´composer create-project fabpot/silex-skeleton . "~2.0"´´´

´´´COMPOSER_PROCESS_TIMEOUT=0 composer run´´´

Add to src/controllers.php file

´´´
$app->post('/{codiceMeccanografico}/Pratiche', function (Silex\Application $app, $codiceMeccanografico) {
    return new JsonResponse([$codiceMeccanografico]);
});

$app->get('/{codiceMeccanografico}/Pratiche({pratica}/cfabilitati', function (Silex\Application $app, $codiceMeccanografico, $pratica) {
    return new JsonResponse([
        'AAA',
        'BBB',
        'CCC',
    ]);
});
´´´
