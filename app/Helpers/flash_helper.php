<?php

declare(strict_types=1);

if (! function_exists('flash_messages')) {
    /**
     * @return list<array{type: string, message: string}>
     */
    function flash_messages(): array
    {
        $messages = [];
        $session = session();

        foreach (['success', 'error', 'warning', 'info'] as $type) {
            $message = $session->getFlashdata($type);

            if (! is_string($message) || trim($message) === '') {
                continue;
            }

            $messages[] = [
                'type' => $type,
                'message' => $message,
            ];
        }

        return $messages;
    }
}

if (! function_exists('flash_message_class')) {
    function flash_message_class(string $type): string
    {
        return match ($type) {
            'success' => 'alert alert--success',
            'error' => 'alert alert--error',
            'warning' => 'alert alert--warning',
            default => 'alert alert--info',
        };
    }
}
