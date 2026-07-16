<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use App\Filament\Pages\Dashboard as CustomDashboard;
use App\Filament\Resources\AgentPackageResource;
use App\Filament\Resources\ApiConfigResource;
use App\Filament\Resources\DealerPackageResource;
use App\Filament\Resources\OrderResource;
use App\Filament\Resources\PaymentConfigResource;
use App\Filament\Resources\UserResource;
use App\Filament\Resources\BalanceHistoryResource;
use App\Filament\Resources\WalletTopupResource;
use Filament\Navigation\NavigationItem;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Support\Facades\Blade;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
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
            ->passwordReset(
                requestAction: \App\Filament\Pages\Auth\ForgotPassword::class,
            )
            ->revealablePasswords()
            ->authGuard('web')
            ->colors([
                'primary' => '#0F9F95',
                'gray' => '#758A8A',
            ])
            ->brandName('AirLinks')
            ->spa()
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->widgets([
                \App\Filament\Widgets\WalletTopupStats::class,
            ])
            ->pages([
                CustomDashboard::class,
            ])
            ->navigationItems([
                NavigationItem::make('Dashboard')
                    ->icon('heroicon-o-home')
                    ->isActiveWhen(fn () => request()->routeIs('filament.admin.pages.dashboard'))
                    ->url(fn () => '/admin')
                    ->sort(1),
                NavigationItem::make('Orders')
                    ->group('Orders')
                    ->icon('heroicon-o-clipboard-document-list')
                    ->url(fn () => OrderResource::getUrl('index'))
                    ->sort(1),
                NavigationItem::make('Databundle Workflow')
                    ->group('Orders')
                    ->icon('heroicon-o-cog-6-tooth')
                    ->url('/admin/databundle-workflow')
                    ->sort(2),
                NavigationItem::make('Agent Orders')
                    ->group('Orders')
                    ->icon('heroicon-o-users')
                    ->url('/admin/agent-orders')
                    ->sort(3),
                NavigationItem::make('Customer Orders')
                    ->group('Orders')
                    ->icon('heroicon-o-shopping-bag')
                    ->url('/admin/store-orders')
                    ->sort(4),
                NavigationItem::make('Status Tracking')
                    ->group('Orders')
                    ->icon('heroicon-o-arrow-path')
                    ->url(fn () => \App\Filament\Resources\OrderStatusHistoryResource::getUrl('index'))
                    ->sort(5),
                NavigationItem::make('Sales Analytics')
                    ->group('Reports')
                    ->icon('heroicon-o-chart-pie')
                    ->url(fn () => \App\Filament\Pages\SalesAnalytics::getUrl())
                    ->sort(1),
                NavigationItem::make('Profit Report')
                    ->group('Reports')
                    ->icon('heroicon-o-currency-dollar')
                    ->url(fn () => \App\Filament\Pages\ProfitReport::getUrl())
                    ->sort(2),
                NavigationItem::make('Daily Balance')
                    ->group('Reports')
                    ->icon('heroicon-o-calendar-days')
                    ->url(fn () => \App\Filament\Pages\DailyBalance::getUrl())
                    ->sort(3),
                NavigationItem::make('Agent Monthly Orders')
                    ->group('Reports')
                    ->icon('heroicon-o-chart-bar')
                    ->url(fn () => \App\Filament\Pages\AgentMonthlyOrders::getUrl())
                    ->sort(4),
                NavigationItem::make('Balance History')
                    ->group('Reports')
                    ->icon('heroicon-o-chart-bar')
                    ->url(fn () => BalanceHistoryResource::getUrl('index'))
                    ->sort(5),
                NavigationItem::make('Dealers/Agents')
                    ->group('Management')
                    ->icon('heroicon-o-user-group')
                    ->url(fn () => UserResource::getUrl('index'))
                    ->sort(1),
                NavigationItem::make('Wallet Top-ups')
                    ->group('Management')
                    ->icon('heroicon-o-banknotes')
                    ->url(fn () => WalletTopupResource::getUrl('index'))
                    ->sort(2),
                NavigationItem::make('Agent Stores')
                    ->group('Management')
                    ->icon('heroicon-o-building-storefront')
                    ->url(fn () => \App\Filament\Resources\AgentStoreSettingResource::getUrl('index'))
                    ->sort(3),
                NavigationItem::make('Notifications')
                    ->group('Management')
                    ->icon('heroicon-o-bell')
                    ->url(fn () => \App\Filament\Resources\NotificationResource::getUrl('index'))
                    ->sort(4),
                NavigationItem::make('Support Tickets')
                    ->group('Management')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->url(fn () => \App\Filament\Resources\SupportTicketResource::getUrl('index'))
                    ->sort(5),
                NavigationItem::make('Package Management')
                    ->group('Configuration')
                    ->icon('heroicon-o-cube')
                    ->url(fn () => AgentPackageResource::getUrl('index'))
                    ->sort(1),
                NavigationItem::make('Dealer Package Management')
                    ->group('Configuration')
                    ->icon('heroicon-o-cube')
                    ->url(fn () => DealerPackageResource::getUrl('index'))
                    ->sort(2),
                NavigationItem::make('Customer Packages')
                    ->group('Configuration')
                    ->icon('heroicon-o-cube')
                    ->url(fn () => \App\Filament\Resources\CustomerPackageResource::getUrl('index'))
                    ->sort(3),
                NavigationItem::make('MTN Mashup')
                    ->group('Configuration')
                    ->icon('heroicon-o-beaker')
                    ->url(fn () => \App\Filament\Resources\MtnMashupResource::getUrl('index'))
                    ->sort(4),
                NavigationItem::make('Payment Config')
                    ->group('Configuration')
                    ->icon('heroicon-o-credit-card')
                    ->url(fn () => PaymentConfigResource::getUrl('index'))
                    ->sort(4),
                NavigationItem::make('API Configuration')
                    ->group('Configuration')
                    ->icon('heroicon-o-server-stack')
                    ->url(fn () => ApiConfigResource::getUrl('index'))
                    ->sort(5),
                NavigationItem::make('API Transactions')
                    ->group('Configuration')
                    ->icon('heroicon-o-arrows-right-left')
                    ->url(fn () => \App\Filament\Resources\ApiLogResource::getUrl('index'))
                    ->sort(6),
                NavigationItem::make('Email Configuration')
                    ->group('Configuration')
                    ->icon('heroicon-o-envelope')
                    ->url(fn () => \App\Filament\Pages\SmtpSettings::getUrl())
                    ->sort(7),
                NavigationItem::make('Payment Settings')
                    ->group('Configuration')
                    ->icon('heroicon-o-credit-card')
                    ->url(fn () => \App\Filament\Pages\PaymentSettings::getUrl())
                    ->sort(8),
                NavigationItem::make('Data Provider')
                    ->group('Configuration')
                    ->icon('heroicon-o-arrows-right-left')
                    ->url(fn () => \App\Filament\Pages\DataProviderSettings::getUrl())
                    ->sort(9),

            ])
            ->renderHook(
                'panels::head.end',
                fn () => Blade::render('
                    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css" integrity="sha512-..." crossorigin="anonymous" referrerpolicy="no-referrer" />
                    <style>
                        .fi-dropdown-panel {
                            z-index: 60 !important;
                        }
                        .choices__dropdown,
                        .choices__list--dropdown {
                            z-index: 9999 !important;
                        }
                    </style>
                ')
            )
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                \App\Http\Middleware\AdminAuthenticate::class,
            ]);
    }
}
