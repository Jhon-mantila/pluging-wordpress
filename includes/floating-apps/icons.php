<?php

if (!defined('ABSPATH')) {
    exit;
}

/*
|--------------------------------------------------------------------------
| Iconos SVG integrados (24x24, trazo)
|--------------------------------------------------------------------------
*/

function esquina_fa_icons()
{
    return [
        'apps' => [
            'label' => __('Apps (cuadrícula)', 'esquina-mis-funciones'),
            'color' => '#2563eb',
            'paths' => '<rect x="3" y="3" width="8" height="8" rx="2" fill="currentColor" stroke="none"/><rect x="13" y="3" width="8" height="8" rx="2" fill="currentColor" stroke="none"/><rect x="3" y="13" width="8" height="8" rx="2" fill="currentColor" stroke="none"/><rect x="13" y="13" width="8" height="8" rx="2" fill="currentColor" stroke="none"/>',
        ],
        'esquina' => [
            'label' => __('Esquina (destello)', 'esquina-mis-funciones'),
            'color' => '#2563eb',
            'paths' => '<path d="m12 3-1.912 5.813a2 2 0 0 1-1.275 1.275L3 12l5.813 1.912a2 2 0 0 1 1.275 1.275L12 21l1.912-5.813a2 2 0 0 1 1.275-1.275L21 12l-5.813-1.912a2 2 0 0 1-1.275-1.275L12 3Z"/>',
        ],
        'youtube' => [
            'label' => __('YouTube', 'esquina-mis-funciones'),
            'color' => '#ff0000',
            'paths' => '<path d="M2.5 17a24.12 24.12 0 0 1 0-10 2 2 0 0 1 1.4-1.4 49.56 49.56 0 0 1 16.2 0A2 2 0 0 1 21.5 7a24.12 24.12 0 0 1 0 10 2 2 0 0 1-1.4 1.4 49.55 49.55 0 0 1-16.2 0A2 2 0 0 1 2.5 17"/><path d="m10 15 5-3-5-3z"/>',
        ],
        'facebook' => [
            'label' => __('Facebook', 'esquina-mis-funciones'),
            'color' => '#1877f2',
            'paths' => '<path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/>',
        ],
        'tiktok' => [
            'label' => __('TikTok', 'esquina-mis-funciones'),
            'color' => '#111111',
            'paths' => '<path d="M9 12a4 4 0 1 0 4 4V2a5 5 0 0 0 5 5"/>',
        ],
        'instagram' => [
            'label' => __('Instagram', 'esquina-mis-funciones'),
            'color' => '#e1306c',
            'paths' => '<rect width="20" height="20" x="2" y="2" rx="5" ry="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" x2="17.51" y1="6.5" y2="6.5"/>',
        ],
        'x' => [
            'label' => __('X / Twitter', 'esquina-mis-funciones'),
            'color' => '#111111',
            'paths' => '<path d="M4 4l16 16"/><path d="M20 4 4 20"/>',
        ],
        'whatsapp' => [
            'label' => __('WhatsApp / Chat', 'esquina-mis-funciones'),
            'color' => '#25d366',
            'paths' => '<path d="M7.9 20A9 9 0 1 0 4 16.1L2 22Z"/>',
        ],
        'telegram' => [
            'label' => __('Telegram', 'esquina-mis-funciones'),
            'color' => '#229ed9',
            'paths' => '<path d="m22 2-7 20-4-9-9-4Z"/><path d="M22 2 11 13"/>',
        ],
        'linkedin' => [
            'label' => __('LinkedIn', 'esquina-mis-funciones'),
            'color' => '#0a66c2',
            'paths' => '<path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6z"/><rect width="4" height="12" x="2" y="9"/><circle cx="4" cy="4" r="2"/>',
        ],
        'github' => [
            'label' => __('GitHub', 'esquina-mis-funciones'),
            'color' => '#24292f',
            'paths' => '<path d="M15 22v-4a4.8 4.8 0 0 0-1-3.5c3 0 6-2 6-5.5.08-1.25-.27-2.48-1-3.5.28-1.15.28-2.35 0-3.5 0 0-1 0-3 1.5-2.64-.5-5.36-.5-8 0C6 2 5 2 5 2c-.3 1.15-.3 2.35 0 3.5A5.403 5.403 0 0 0 4 9c0 3.5 3 5.5 6 5.5-.39.49-.68 1.05-.85 1.65-.17.6-.22 1.23-.15 1.85v4"/><path d="M9 18c-4.51 2-5-2-7-2"/>',
        ],
        'web' => [
            'label' => __('Sitio web', 'esquina-mis-funciones'),
            'color' => '#0ea5e9',
            'paths' => '<circle cx="12" cy="12" r="10"/><path d="M12 2a14.5 14.5 0 0 0 0 20 14.5 14.5 0 0 0 0-20"/><path d="M2 12h20"/>',
        ],
        'blog' => [
            'label' => __('Blog', 'esquina-mis-funciones'),
            'color' => '#f59e0b',
            'paths' => '<path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/>',
        ],
        'docs' => [
            'label' => __('Documentación', 'esquina-mis-funciones'),
            'color' => '#6366f1',
            'paths' => '<path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/>',
        ],
        'games' => [
            'label' => __('Juegos', 'esquina-mis-funciones'),
            'color' => '#7c3aed',
            'paths' => '<line x1="6" x2="10" y1="11" y2="11"/><line x1="8" x2="8" y1="9" y2="13"/><line x1="15" x2="15.01" y1="12" y2="12"/><line x1="18" x2="18.01" y1="10" y2="10"/><path d="M17.32 5H6.68a4 4 0 0 0-3.978 3.59c-.006.052-.01.101-.017.152C2.604 9.416 2 14.456 2 16a3 3 0 0 0 3 3c1 0 1.5-.5 2-1l1.414-1.414A2 2 0 0 1 9.828 16h4.344a2 2 0 0 1 1.414.586L17 18c.5.5 1 1 2 1a3 3 0 0 0 3-3c0-1.545-.604-6.584-.685-7.258-.007-.05-.011-.1-.017-.151A4 4 0 0 0 17.32 5z"/>',
        ],
        'anime' => [
            'label' => __('Anime / TV', 'esquina-mis-funciones'),
            'color' => '#ec4899',
            'paths' => '<rect width="20" height="15" x="2" y="7" rx="2" ry="2"/><polyline points="17 2 12 7 7 2"/>',
        ],
        'music' => [
            'label' => __('Música', 'esquina-mis-funciones'),
            'color' => '#10b981',
            'paths' => '<path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/>',
        ],
        'shop' => [
            'label' => __('Tienda', 'esquina-mis-funciones'),
            'color' => '#ea580c',
            'paths' => '<path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/>',
        ],
        'mail' => [
            'label' => __('Correo', 'esquina-mis-funciones'),
            'color' => '#64748b',
            'paths' => '<rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>',
        ],
    ];
}

function esquina_fa_svg_markup($key, $size = 24)
{
    $icons = esquina_fa_icons();

    if (!isset($icons[$key])) {
        return '';
    }

    return sprintf(
        '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="%1$d" height="%1$d" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">%2$s</svg>',
        (int) $size,
        $icons[$key]['paths']
    );
}
