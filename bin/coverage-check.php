<?php

/**
 * Vérifie que la couverture des lignes atteint un seuil minimal.
 *
 * Usage : php bin/coverage-check.php <rapport clover.xml> <seuil en %>
 * Code de sortie : 0 si la couverture atteint le seuil, 1 sinon (fait échouer la CI).
 */

declare(strict_types=1);

$file = $argv[1] ?? '';
$threshold = $argv[2] ?? '';

if (!is_file($file) || !is_numeric($threshold)) {
    fwrite(STDERR, "Usage : php bin/coverage-check.php <clover.xml> <seuil en %>\n");

    exit(1);
}

// Totaux du projet dans le rapport Clover : statements = lignes exécutables
$metrics = simplexml_load_file($file)->project->metrics ?? null;

if (null === $metrics) {
    fwrite(STDERR, "Rapport Clover illisible : {$file}\n");

    exit(1);
}

$statements = (int) $metrics['statements'];
$covered = (int) $metrics['coveredstatements'];
$coverage = 0 === $statements ? 0.0 : $covered / $statements * 100;

$message = sprintf('Couverture des lignes : %.2f %% (%d/%d), seuil : %s %%', $coverage, $covered, $statements, $threshold);

if ($coverage < (float) $threshold) {
    fwrite(STDERR, "❌ {$message}\n");

    exit(1);
}

echo "✅ {$message}\n";
