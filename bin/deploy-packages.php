<?php
$packageConfig = include __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'deploy-config.php';

$packagePath = realpath($packageConfig['package-path']);

function info(string $text) {
    echo "\033[34m" . $text . "\033[0m" . \PHP_EOL;
}
function execShell(string $cmd) {
    echo "\033[32m" . 'Running: ' . "\033[33m". $cmd . "\033[0m" . \PHP_EOL;
    echo `$cmd` . \PHP_EOL;
}

$gitC = '-C ' . escapeshellarg($packagePath);

info('PRIVATE: Deleting existing tags remotely');
execShell("git ${gitC} tag -l | xargs -n 1 git ${gitC} push --delete " . escapeshellarg($packageConfig['private-remote']));
info('PRIVATE: Deleting existing tags locally');
execShell("git ${gitC} tag -l | xargs -n 1 git ${gitC} tag -d");

info('PRIVATE: Resetting to master');
execShell("git ${gitC} reset --hard master");

info('PRIVATE: Writing composer.json version ' . $packageConfig['private']);
$composer = json_decode(file_get_contents($packagePath . \DIRECTORY_SEPARATOR . 'composer.json'), true);
$composer['version'] = $packageConfig['private'];
file_put_contents($packagePath . \DIRECTORY_SEPARATOR . 'composer.json', json_encode($composer, \JSON_PRETTY_PRINT));

info('PRIVATE: Adding composer');
execShell("git ${gitC} add composer.json");
info('PRIVATE: Committing composer');
execShell("git ${gitC} commit -m " . escapeshellarg('Private ' . $packageConfig['private']));
info('PRIVATE: Pushing private');
execShell("git ${gitC} push " . escapeshellarg($packageConfig['private-remote']) . ' master --force');
info('PRIVATE: Tagging private');
execShell("git ${gitC} tag " . escapeshellarg($packageConfig['private']));
info('PRIVATE: Pushing private tag');
execShell("git ${gitC} push " . escapeshellarg($packageConfig['private-remote']) . ' ' . escapeshellarg($packageConfig['private']) . ' --force');

info('Building packages');
$satisPath = __DIR__ . \DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'satis.json';
$satisConfig = json_decode(file_get_contents($satisPath), true);
$satisConfig['require'][$packageConfig['package-name']] = $packageConfig['private'];
file_put_contents($satisPath, json_encode($satisConfig, \JSON_PRETTY_PRINT));

execShell("composer run-script build-satis");
$packageJsonPath = __DIR__ . \DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'build' . \DIRECTORY_SEPARATOR . 'packages.json';
$packageJson = json_decode(file_get_contents($packageJsonPath), true);
$packageJson['metadata-url'] = '/p2/%package%.json';
$packageJson['require'] = [];
$packageJson['require'][$packageConfig['package-name']] = $packageConfig['private'];
file_put_contents($packageJsonPath, json_encode($packageJson, \JSON_PRETTY_PRINT));



info('PUBLIC: Deleting existing tags remotely');
execShell("git ${gitC} tag -l | xargs -n 1 git ${gitC} push --delete " . escapeshellarg($packageConfig['public-remote']));

info('PUBLIC: Resetting to master');
execShell("git ${gitC} reset --hard master");

info('PUBLIC: Writing composer.json version ' . $packageConfig['public']);
$composer = json_decode(file_get_contents($packagePath . \DIRECTORY_SEPARATOR . 'composer.json'), true);
$composer['version'] = $packageConfig['public'];
file_put_contents($packagePath . \DIRECTORY_SEPARATOR . 'composer.json', json_encode($composer, \JSON_PRETTY_PRINT));

info('PUBLIC: Adding composer');
execShell("git ${gitC} add composer.json");
info('PUBLIC: Committing composer');
execShell("git ${gitC} commit -m " . escapeshellarg('Public ' . $packageConfig['public']));
info('PUBLIC: Pushing public');
execShell("git ${gitC} push " . escapeshellarg($packageConfig['public-remote']) . ' master --force');
info('PUBLIC: Tagging private');
execShell("git ${gitC} tag " . escapeshellarg($packageConfig['public']));
info('PUBLIC: Pushing private tag');
execShell("git ${gitC} push " . escapeshellarg($packageConfig['public-remote']) . ' ' . escapeshellarg($packageConfig['public']) . ' --force');


info("");
info("");
info("Commit and push this repo to github+netlify and run the current scenarios");
