<?php

if (!defined('ABSPATH')) {
    exit;
}

function esquinaweb_admin_menu()
{
    add_menu_page(
        'EsquinaWeb',
        'EsquinaWeb',
        'manage_options',
        'esquinaweb-dashboard',
        'esquinaweb_dashboard_page',
        'dashicons-superhero',
        30
    );
}

add_action('admin_menu', 'esquinaweb_admin_menu');

function esquinaweb_dashboard_page()
{
    ?>
    <div class="wrap esquina-dashboard">
        <h1>🚀 EsquinaWeb</h1>

        <p class="esquina-subtitle">
            Plugin gratuito desarrollado por <strong>Jhon Edinson Mantilla Ruiz</strong><br>
            Si te es útil, apóyame siguiéndome en mis redes o dejando un saludo 💙
        </p>

        <div class="esquina-grid">

            <!-- SITIOS WEB -->
            <div class="esquina-card">
                <h2>🌐 Sitios Web</h2>
                <a href="https://esquinaweb.com/" target="_blank">EsquinaWeb</a>
                <a href="https://esquinagamers.com/" target="_blank">EsquinaGamers</a>
                <a href="https://esquinaanime.com/" target="_blank">EsquinaAnime</a>
            </div>

            <!-- FACEBOOK -->
            <div class="esquina-card">
                <h2>📘 Facebook</h2>
                <a href="https://www.facebook.com/profile.php?id=61580211039248" target="_blank">EsquinaGamers</a>
                <a href="https://www.facebook.com/profile.php?id=61575869177785" target="_blank">EsquinaWeb</a>
            </div>

            <!-- YOUTUBE -->
            <div class="esquina-card">
                <h2>▶️ YouTube</h2>
                <a href="https://www.youtube.com/@EsquinaStudio" target="_blank">EsquinaStudio</a>
            </div>

            <!-- TIKTOK -->
            <div class="esquina-card">
                <h2>▶️ TikTok</h2>
                <a href="https://www.tiktok.com/@esquina.studio2" target="_blank">EsquinaStudio</a>
            </div>

            <!-- PROFESIONAL -->
            <div class="esquina-card">
                <h2>💼 Profesional</h2>
                <a href="https://www.linkedin.com/in/jhon-edinson-mantilla-ruiz-b2038b207/" target="_blank">LinkedIn</a>
                <a href="https://github.com/Jhon-mantila" target="_blank">GitHub Perfil</a>
            </div>

            <!-- DOCUMENTACION -->
            <div class="esquina-card">
                <h2>📘 Documentación</h2>
                <a href="https://esquinaweb.com/base-de-conocimiento/" target="_blank">Base de Conocimiento</a>
                <a href="https://esquinaweb.com/base-de-conocimiento/documentacion-plugin-wordpress-shortcodes-youtube-facebook-y-categorias/" target="_blank">Documentación</a>
            </div>

            <!-- PLUGIN -->
            <div class="esquina-card">
                <h2>📦 Plugin WordPress</h2>
                <a href="https://github.com/Jhon-mantila/pluging-wordpress" target="_blank">Repositorio</a>
                <a href="https://github.com/Jhon-mantila/pluging-wordpress/releases/" target="_blank">Releases</a>
            </div>

        </div>

        <div class="esquina-footer">
            💡 Hecho con ❤️ por Jhon Mantilla — Esquina Web
        </div>
    </div>

    <style>
        .esquina-dashboard {
            max-width: 1100px;
        }

        .esquina-subtitle {
            font-size: 15px;
            color: #555;
            margin-bottom: 25px;
        }

        .esquina-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
            gap: 20px;
        }

        .esquina-card {
            background: #fff;
            border-radius: 12px;
            padding: 18px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            transition: transform 0.2s ease;
        }

        .esquina-card:hover {
            transform: translateY(-3px);
        }

        .esquina-card h2 {
            font-size: 16px;
            margin-bottom: 12px;
            color: #1d2327;
        }

        .esquina-card a {
            display: block;
            padding: 8px 10px;
            margin: 6px 0;
            background: #f0f0f1;
            border-radius: 8px;
            text-decoration: none;
            color: #2271b1;
            font-weight: 500;
            transition: 0.2s;
        }

        .esquina-card a:hover {
            background: #2271b1;
            color: #fff;
        }

        .esquina-footer {
            margin-top: 30px;
            text-align: center;
            color: #777;
            font-size: 13px;
        }
    </style>
    <?php
}