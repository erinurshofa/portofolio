<?php
/**
 * Server-Side Schema Validator (Mirroring Zod Frontend Schema)
 * Strict validation, type enforcement & sanitization
 * Eri Nur Sofa Portfolio
 */

declare(strict_types=1);

final class SchemaValidator
{
    public static function validateLead(array $input): array
    {
        $errors = [];

        // Honeypot check
        if (!empty($input['website_hp'])) {
            return [
                'isValid' => false,
                'errors' => ['general' => 'Spam verification failed.']
            ];
        }

        // Name: required, 2-100 chars
        $name = trim((string)($input['name'] ?? ''));
        if ($name === '') {
            $errors['name'] = 'Nama lengkap wajib diisi.';
        } elseif (mb_strlen($name, 'UTF-8') < 2) {
            $errors['name'] = 'Nama terlalu pendek (minimal 2 karakter).';
        } elseif (mb_strlen($name, 'UTF-8') > 100) {
            $errors['name'] = 'Nama terlalu panjang (maksimal 100 karakter).';
        }

        // WhatsApp / Phone: required, Indonesian format pattern (clean spaces, dashes, dots)
        $wa = trim((string)($input['whatsapp'] ?? ''));
        $cleanWa = preg_replace('/[^0-9+]/', '', $wa) ?? '';
        if ($cleanWa === '') {
            $errors['whatsapp'] = 'Nomor WhatsApp aktif wajib diisi.';
        } elseif (!preg_match('/^(\+62|62|08|8)[0-9]{8,13}$/', $cleanWa)) {
            $errors['whatsapp'] = 'Format nomor WhatsApp tidak valid (contoh: 085641280960 atau +62812345678).';
        }

        // Email: optional, but must be valid if supplied
        $email = trim((string)($input['email'] ?? ''));
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Format email tidak valid.';
        }

        // Category: required
        $category = trim((string)($input['project_category'] ?? ''));
        if ($category === '') {
            $errors['project_category'] = 'Pilih kategori aplikasi yang ingin dibuat.';
        }

        // Budget range
        $budget = trim((string)($input['budget_range'] ?? ''));
        if ($budget === '') {
            $errors['budget_range'] = 'Pilih perkiraan budget Anda.';
        }

        // Notes / Project description: mandatory so Mas Eri understands the exact needs
        $notes = trim((string)($input['notes'] ?? ''));
        if ($notes === '') {
            $errors['notes'] = 'Gambaran detail kebutuhan wajib diisi agar Mas Eri lebih paham proyek Anda.';
        } elseif (mb_strlen($notes, 'UTF-8') < 10) {
            $errors['notes'] = 'Mohon jelaskan sedikit lebih detail (minimal 10 karakter).';
        } elseif (mb_strlen($notes, 'UTF-8') > 1000) {
            $errors['notes'] = 'Gambaran kebutuhan terlalu panjang (maksimal 1000 karakter).';
        }

        return [
            'isValid' => empty($errors),
            'errors' => $errors
        ];
    }

    public static function validateChat(array $input): array
    {
        $errors = [];
        $message = trim((string)($input['message'] ?? ''));

        if ($message === '') {
            $errors['message'] = 'Pesan chat tidak boleh kosong.';
        } elseif (mb_strlen($message, 'UTF-8') > 1500) {
            $errors['message'] = 'Pesan terlalu panjang (maksimal 1500 karakter).';
        }

        return [
            'isValid' => empty($errors),
            'errors' => $errors
        ];
    }
}
