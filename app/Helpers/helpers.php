<?php

if (! function_exists('maskPhone')) {
    function maskPhone(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone);

        if (strlen($digits) === 11) {
            return preg_replace('/(\d{2})(\d{5})(\d{4})/', '($1) $2-$3', $digits);
        } elseif (strlen($digits) === 10) {
            return preg_replace('/(\d{2})(\d{4})(\d{4})/', '($1) $2-$3', $digits);
        }

        return $phone;
    }
}

if (! function_exists('maskCep')) {
    function maskCep(string $cep): string
    {
        $digits = preg_replace('/\D/', '', $cep);

        if (strlen($digits) === 8) {
            return preg_replace('/(\d{5})(\d{3})/', '$1-$2', $digits);
        }

        return $cep;
    }
}
