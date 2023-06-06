<?php
/**
 * Função para obter a URL base do projeto
 *
 * @return string URL base completa
 */
function urlBase() {
    $rootPath = str_replace('\\', '/', __DIR__);
    $publicPath = str_replace($_SERVER['DOCUMENT_ROOT'], '', $rootPath);
    $basePath = dirname($publicPath);
    return $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . $basePath;
}
