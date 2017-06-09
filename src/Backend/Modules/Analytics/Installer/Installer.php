<?php

namespace Backend\Modules\Analytics\Installer;

use Backend\Core\Installer\ModuleInstaller;

/**
 * Installer for the analytics module
 */
class Installer extends ModuleInstaller
{
    public function install(): void
    {
        $this->addModule('Analytics');
        $this->importLocale(__DIR__ . '/Data/locale.xml');
        $this->configureRights();
        $this->configureBackendNavigation();
        $this->configureBackendWidgets();
    }

    private function configureBackendNavigation(): void
    {
        // Navigation for "Modules"
        $navigationMarketingId = $this->setNavigation(null, 'Marketing', 'analytics/index', null, 4);
        $this->setNavigation($navigationMarketingId, 'Analytics', 'analytics/index');

        // Navigation for "Settings"
        $navigationSettingsId = $this->setNavigation(null, 'Settings');
        $navigationModulesId = $this->setNavigation($navigationSettingsId, 'Modules');
        $this->setNavigation($navigationModulesId, $this->getModule(), 'analytics/settings');
    }

    private function configureBackendWidgets(): void
    {
        $this->insertDashboardWidget('Analytics', 'RecentVisits');
        $this->insertDashboardWidget('Analytics', 'TraficSources');
    }

    private function configureRights(): void
    {
        $this->setModuleRights(1, $this->getModule());

        $this->setActionRights(1, $this->getModule(), 'Index');
        $this->setActionRights(1, $this->getModule(), 'Settings');
        $this->setActionRights(1, $this->getModule(), 'Reset');
    }
}
