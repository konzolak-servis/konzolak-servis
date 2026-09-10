<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\Login;
use App\Filament\Pages\Dashboard;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\HtmlString;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login(Login::class)
            ->profile()
            ->brandName('Konzolák Zlín')
            ->brandLogo(asset('images/konzolak-logo-print.png'))
            ->brandLogoHeight('5.75rem')
            ->favicon(asset('images/konzolak-icon.png'))
            ->colors([
                'primary' => Color::hex('#C8992E'),
                'gray' => Color::Slate,
            ])
            ->font('Inter')
            ->navigationGroups([
                'Servis',
                'Sklad',
                'Finance',
                'Pošta',
                'Nastavení',
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([])
            ->renderHook(
                PanelsRenderHook::HEAD_END,
                fn (): HtmlString => new HtmlString(<<<'HTML'
                    <link rel="manifest" href="/manifest.json">
                    <meta name="theme-color" content="#0F2038">
                    <meta name="mobile-web-app-capable" content="yes">
                    <meta name="apple-mobile-web-app-capable" content="yes">
                    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
                    <meta name="apple-mobile-web-app-title" content="Konzolák">
                    <link rel="apple-touch-icon" href="/icons/icon-192.png">
                    <script src="/js/ks-passkey.js" defer></script>
                    <script>
                        if ('serviceWorker' in navigator) {
                            window.addEventListener('load', function () {
                                navigator.serviceWorker.register('/sw.js').catch(function () {});
                            });
                        }
                    </script>
                    <style>
                        :root{ --ks-navy:#0F2038; --ks-gold:#C8992E; }

                        /* ---- Nadpis stránky: nelámat číslo zakázky po slabikách ---- */
                        .fi-header-heading{ word-break:normal; overflow-wrap:normal; hyphens:none; }
                        @media (min-width:1024px){
                            .fi-header{ flex-wrap:wrap; align-items:flex-start; }
                            .fi-header-heading{ white-space:nowrap; }
                        }

                        /* ---- Logo v horní liště: velké + vyšší lišta, ať se nic neořízne ---- */
                        .fi-topbar{ min-height:7.5rem; }
                        .fi-topbar > nav, .fi-topbar > div{ min-height:7.5rem; align-items:center; }
                        .fi-logo{ height:5.75rem !important; width:auto; }
                        .fi-sidebar-header{ height:auto !important; min-height:0 !important; padding-top:1rem; padding-bottom:1rem; overflow:visible !important; }
                        .fi-sidebar-header .fi-logo{ max-width:100%; object-fit:contain; }

                        /* ---- Mobil: menší logo, normální výška lišty ---- */
                        @media (max-width:1024px){
                            .fi-topbar, .fi-topbar > nav, .fi-topbar > div{ min-height:0 !important; }
                            .fi-logo{ height:2.75rem !important; }
                            .fi-sidebar-header{ padding-top:.75rem; padding-bottom:.75rem; }
                        }

                        /* ---- Přihlašovací obrazovka: logo hodně velké a s odstupem ---- */
                        .fi-simple-header{ text-align:center; }
                        .fi-simple-header .fi-logo{ height:10rem !important; margin:0 auto 1.5rem; }
                        .fi-simple-header-heading{ margin-top:.25rem; }
                        .fi-simple-layout{
                            background:radial-gradient(120% 120% at 50% 0%, #1B3A5B 0%, #0F2038 70%) !important;
                        }
                        .fi-simple-main{
                            border:1px solid rgba(200,153,46,.28);
                            box-shadow:0 24px 60px -20px rgba(0,0,0,.55);
                        }

                        /* ================= SVĚTLÝ MOTIV – perleťově béžový, lehce do modré ================= */
                        html:not(.dark) body,
                        html:not(.dark) .fi-body{
                            background:linear-gradient(165deg, #F5F1E7 0%, #EFEFF1 45%, #E9EDF3 100%) !important;
                        }
                        html:not(.dark) .fi-main{
                            background:transparent !important;
                            background-image:
                                radial-gradient(52rem 40rem at 108% -12%, rgba(200,153,46,.10), transparent 60%),
                                radial-gradient(46rem 40rem at -10% 8%, rgba(27,58,91,.06), transparent 55%) !important;
                        }
                        html:not(.dark) .fi-sidebar,
                        html:not(.dark) .fi-topbar,
                        html:not(.dark) .fi-topbar > *{
                            background:rgba(251,249,242,.88) !important;
                            backdrop-filter:blur(6px);
                        }
                        html:not(.dark) .fi-sidebar{ border-right:1px solid rgba(163,143,102,.22) !important; }
                        html:not(.dark) .fi-section,
                        html:not(.dark) .fi-ta-ctn,
                        html:not(.dark) .fi-wi-stats-overview-stat,
                        html:not(.dark) .fi-fo-field-wrp .fi-input-wrp{
                            background:#FCFAF4 !important;
                            border:1px solid rgba(163,143,102,.22) !important;
                        }
                        html:not(.dark) .fi-ta-row:hover{ background:rgba(200,153,46,.06) !important; }

                        /* ---- společné jemné oživení ---- */
                        .fi-sidebar-item-active .fi-sidebar-item-button{
                            background:rgba(200,153,46,.16) !important;
                            box-shadow:inset 3px 0 0 var(--ks-gold);
                        }
                        .fi-section,
                        .fi-wi-stats-overview-stat,
                        .fi-ta-ctn{
                            border-radius:.9rem !important;
                            box-shadow:0 1px 2px rgba(15,32,56,.04), 0 10px 28px -18px rgba(15,32,56,.20) !important;
                        }
                        :is(.dark) .fi-section{ border:1px solid rgba(255,255,255,.06); }
                        .fi-wi-stats-overview-stat{ border-top:2px solid rgba(200,153,46,.55) !important; }
                        .fi-ta-header-cell{ background:rgba(200,153,46,.06); }
                        :is(.dark) .fi-ta-header-cell{ background:rgba(200,153,46,.10); }
                        a, .fi-link{ text-underline-offset:2px; }

                        /* Pryč s posuvníky (spinnery) u číselných polí v celé aplikaci */
                        input[type=number]::-webkit-inner-spin-button,
                        input[type=number]::-webkit-outer-spin-button{ -webkit-appearance:none; margin:0; }
                        input[type=number]{ -moz-appearance:textfield; appearance:textfield; }

                        /* Číselná / peněžní pole ať mají rozumnou minimální šířku */
                        .fi-input-wrp:has(input[inputmode="numeric"]),
                        .fi-input-wrp:has(input[type="number"]){ min-width:7rem; }

                        /* ============ TABULKY: vejít se bez vodorovného posouvání ============ */
                        .fi-ta-table{ table-layout:auto; width:100%; }
                        .fi-ta-header-cell, .fi-ta-cell{ padding-left:.5rem !important; padding-right:.5rem !important; }
                        .fi-ta-row > .fi-ta-cell:first-child, .fi-ta-header-row > .fi-ta-header-cell:first-child{ padding-left:.75rem !important; }
                        .fi-ta-row > .fi-ta-cell:last-child,  .fi-ta-header-row > .fi-ta-header-cell:last-child{ padding-right:.75rem !important; }
                        .fi-ta-cell .fi-ta-text, .fi-ta-text-item-label{ white-space:normal !important; overflow-wrap:anywhere; }
                        .fi-ta-header-cell-label{ white-space:normal; line-height:1.15; font-size:.72rem; letter-spacing:.02em; }
                        .fi-ta-text-item-label{ font-size:.82rem; }
                        .fi-ta-actions{ gap:.125rem; flex-wrap:nowrap; }
                        .fi-ta-actions .fi-btn{ padding-left:.4rem; padding-right:.4rem; }
                        .fi-ta-cell .fi-badge{ font-size:.72rem; }
                    </style>
                HTML)
            )
            ->renderHook(
                PanelsRenderHook::AUTH_LOGIN_FORM_AFTER,
                fn (): HtmlString => new HtmlString(<<<'HTML'
                    <div id="ks-passkey-wrap" style="margin-top:1rem; text-align:center; display:none;">
                        <div style="display:flex; align-items:center; gap:.75rem; color:#9aa3b2; font-size:.8rem; margin:.25rem 0 .75rem;">
                            <span style="flex:1; height:1px; background:currentColor; opacity:.3;"></span>nebo<span style="flex:1; height:1px; background:currentColor; opacity:.3;"></span>
                        </div>
                        <button type="button" id="ks-passkey-btn"
                            style="width:100%; padding:.65rem 1rem; border-radius:.6rem; border:1px solid rgba(200,153,46,.5);
                                   background:transparent; color:#E8C77C; font-weight:600; cursor:pointer; display:flex;
                                   align-items:center; justify-content:center; gap:.5rem;">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 11c0-3 2-4 2-4"/><path d="M7 8a5 5 0 0 1 10 0v3"/><path d="M7 12v3a5 5 0 0 0 .5 2"/><path d="M12 12v4"/><path d="M17 14v1a7 7 0 0 1-1 3.5"/></svg>
                            Přihlásit se otiskem (passkey)
                        </button>
                        <p id="ks-passkey-msg" style="font-size:.8rem; color:#f87171; margin:.5rem 0 0; min-height:1rem;"></p>
                    </div>
                    <script>
                        window.addEventListener('load', function () {
                            var wrap = document.getElementById('ks-passkey-wrap');
                            var btn = document.getElementById('ks-passkey-btn');
                            var msg = document.getElementById('ks-passkey-msg');
                            if (!wrap || !btn) return;
                            if (typeof PublicKeyCredential === 'undefined' || !window.isSecureContext) return;
                            wrap.style.display = 'block';
                            btn.addEventListener('click', async function () {
                                if (!window.KsPasskey) { msg.textContent = 'Načítám…'; return; }
                                msg.textContent = ''; btn.disabled = true; btn.style.opacity = '.6';
                                try {
                                    var r = await window.KsPasskey.login();
                                    if (r) { window.location.href = (typeof r === 'string') ? r : '/admin'; return; }
                                    msg.textContent = 'Přihlášení se nezdařilo.';
                                } catch (e) {
                                    msg.textContent = (e && e.name === 'NotAllowedError')
                                        ? 'Zrušeno nebo vypršel čas.'
                                        : ('Passkey se nepodařilo použít: ' + (e && e.message ? e.message : e));
                                }
                                btn.disabled = false; btn.style.opacity = '1';
                            });
                        });
                    </script>
                HTML)
            )
            ->renderHook(
                PanelsRenderHook::GLOBAL_SEARCH_AFTER,
                function (): HtmlString {
                    $host = request()->getHost();
                    $web = str_starts_with($host, 'servis.')
                        ? request()->getScheme().'://'.substr($host, 7)
                        : url('/');

                    return new HtmlString(<<<HTML
                        <a href="{$web}" target="_blank" rel="noopener"
                           title="Otevřít veřejný web konzolák.com"
                           style="display:inline-flex; align-items:center; gap:.4rem; white-space:nowrap;
                                  padding:.4rem .75rem; border-radius:.5rem; border:1px solid rgba(200,153,46,.5);
                                  color:inherit; font-size:.8rem; font-weight:600; text-decoration:none;">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                 stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="9"/><path d="M3 12h18"/>
                                <path d="M12 3a15 15 0 0 1 0 18 15 15 0 0 1 0-18z"/>
                            </svg>
                            <span>Web</span>
                        </a>
                    HTML);
                }
            )
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
