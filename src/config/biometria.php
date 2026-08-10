<?php

const HUELLA_TEMPLATE_BYTES = 1536;
const HUELLA_TEMPLATE_HEX_CHARS = HUELLA_TEMPLATE_BYTES * 2;

function es_template_huella_hex_valido(string $template): bool
{
    return strlen($template) === HUELLA_TEMPLATE_HEX_CHARS
        && preg_match('/\A[0-9a-fA-F]+\z/D', $template) === 1;
}
