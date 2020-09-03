<?php

use Elasticsearch\ClientBuilder;
use Jeto\Elasticize\Searcher\Searcher;
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Document</title>
</head>
<body>
<form method="get">
    <label>
        Index:
        <select name="index">
            <option value="employees">employees</option>
            <option value="person">person</option>
        </select>
    </label>

    <label>
        Query:
        <input id="inputSearch" type="search" name="q" value="<?= htmlspecialchars($_GET['q'] ?? '') ?>">
    </label>
</form>

<script>
    document.getElementById('inputSearch').focus();
</script>

<?php
if (isset($_GET['index'], $_GET['q'])) {
    require_once 'vendor/autoload.php';

    $elastic = ClientBuilder::create()->setHosts(['http://elasticsearch:9200'])->build();
    $searcher = new Searcher($elastic);

    $hits = $searcher->search($_GET['index'], $_GET['q']);

    echo '<pre>';
    /** @noinspection ForgottenDebugOutputInspection */
    var_dump($hits);
    echo '</pre>';
}
?>

</body>
</html>
