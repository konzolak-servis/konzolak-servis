<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use App\Filament\Pages\Dashboard;
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
            ->login(\App\Filament\Pages\Auth\Login::class)
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
                    </style>
                HTML)
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
