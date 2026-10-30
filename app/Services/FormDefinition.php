<?php
declare(strict_types=1);

namespace App\Services;

/**
 * Custom form fields are edited as text, one per line: "Label | type | required(0/1) | option1, option2".
 * Stored as JSON; submissions are keyed by label so later edits never orphan old answers.
 */
final class FormDefinition
{
    public const TYPES = ['text' => 'Texte', 'email' => 'Email', 'phone' => 'Téléphone', 'textarea' => 'Zone de texte', 'select' => 'Liste', 'checkbox' => 'Case à cocher'];
    private const MAX_FIELDS = 20;

    /** @return array{0:list<array>,1:list<string>} fields, errors */
    public static function parse(string $text): array
    {
        $fields = [];
        $errors = [];
        foreach (preg_split('/\R/', trim($text)) as $n => $line) {
            if (trim($line) === '') {
                continue;
            }
            [$label, $type, $required, $options] = array_pad(array_map('trim', explode('|', $line, 4)), 4, '');
            $type = $type ?: 'text';
            if ($label === '' || mb_strlen($label) > 100) {
                $errors[] = 'Ligne ' . ($n + 1) . ' : libellé requis (100 caractères max).';
            } elseif (!isset(self::TYPES[$type])) {
                $errors[] = 'Ligne ' . ($n + 1) . " : type « $type » inconnu.";
            } elseif ($type === 'select' && $options === '') {
                $errors[] = 'Ligne ' . ($n + 1) . ' : une liste demande des options séparées par des virgules.';
            } else {
                $fields[] = ['label' => $label, 'type' => $type, 'required' => $required === '1', 'options' => $type === 'select' ? array_values(array_filter(array_map('trim', explode(',', $options)))) : []];
            }
        }
        if (!$fields && !$errors) {
            $errors[] = 'Ajoutez au moins un champ.';
        }
        if (count($fields) > self::MAX_FIELDS) {
            $errors[] = 'Maximum ' . self::MAX_FIELDS . ' champs.';
        }
        return [$fields, $errors];
    }

    public static function toText(array $fields): string
    {
        return implode("\n", array_map(fn($f) => "{$f['label']} | {$f['type']} | " . ($f['required'] ? '1' : '0') . ' | ' . implode(', ', $f['options']), $fields));
    }

    /** @return array{0:array<string,string>,1:array<int,string>} answers by label, errors by field index */
    public static function validate(array $fields, array $input): array
    {
        $out = [];
        $errors = [];
        foreach ($fields as $i => $f) {
            $v = $input["f$i"] ?? '';
            $v = is_string($v) ? trim($v) : '';
            if ($f['type'] === 'checkbox') {
                $v = $v === '1' ? 'Oui' : '';
            }
            if ($v === '') {
                $f['required'] && $errors[$i] = 'Champ requis.';
            } elseif (mb_strlen($v) > ($f['type'] === 'textarea' ? 3000 : 300)) {
                $errors[$i] = 'Texte trop long.';
            } elseif ($f['type'] === 'email' && !filter_var($v, FILTER_VALIDATE_EMAIL)) {
                $errors[$i] = 'Email invalide.';
            } elseif ($f['type'] === 'phone' && !preg_match('/^\+?[0-9 .()-]{8,20}$/', $v)) {
                $errors[$i] = 'Téléphone invalide.';
            } elseif ($f['type'] === 'select' && !in_array($v, $f['options'], true)) {
                $errors[$i] = 'Choix invalide.';
            }
            $out[$f['label']] = $v;
        }
        return [$out, $errors];
    }
}
