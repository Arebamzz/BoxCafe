<?php

/** Environment variables override private config/local.php settings. */
function box_config(string $key): mixed
{
    static $settings = null;

    if ($settings === null) {
        $settings = [
            'db_host' => 'localhost',
            'db_port' => 3306,
            'db_name' => 'boxkl',
            'db_user' => 'root',
            'db_password' => '',
            'stripe_secret_key' => '',
            'smtp_user' => 'b0xkl2022@gmail.com',
            'smtp_password' => '',
            'notify_email' => 'b0xkl2022@gmail.com',
        ];
        $local = __DIR__ . '/local.php';
        if (is_file($local)) {
            $settings = array_replace($settings, require $local);
        }
    }

    $value = getenv('BOXCAFE_' . strtoupper($key));
    return $value !== false ? $value : ($settings[$key] ?? null);
}