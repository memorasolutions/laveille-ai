<?php

/**
 * @author  MEMORA solutions <info@memora.ca> (https://memora.solutions)
 *
 * @project memora/laravel-saas-boilerplate
 */

declare(strict_types=1);

namespace Modules\Backoffice\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Nwidart\Modules\Facades\Module;

class PluginController
{
    /** @var list<string> */
    private const PROTECTED_MODULES = ['Core', 'Auth', 'Backoffice', 'RolesPermissions'];

    public function index(): View
    {

        /** @var array<string, array<string, mixed>> $registry */
        $registry = app('plugin.registry');

        $modules = [];
        foreach (Module::all() as $module) {
            $name = $module->getName();
            $config = $registry[$name] ?? [];

            $modules[] = [
                'name' => $name,
                'description' => $config['description'] ?? '',
                'version' => $config['version'] ?? '1.0.0',
                'type' => $config['type'] ?? 'module',
                'dependencies' => $config['dependencies'] ?? [],
                'priority' => $config['priority'] ?? 99,
                'enabled' => Module::isEnabled($name),
                'protected' => in_array($name, self::PROTECTED_MODULES, true),
            ];
        }

        usort($modules, fn (array $a, array $b) => $a['priority'] <=> $b['priority']);

        $dependencyMap = $this->buildDependencyMap($modules);

        return view('backoffice::plugins.index', compact('modules', 'dependencyMap'));
    }

    public function toggle(string $name): RedirectResponse
    {

        if (in_array($name, self::PROTECTED_MODULES, true)) {
            return back()->with('error', "Le module {$name} est protégé et ne peut pas être désactivé.");
        }

        if (! Module::has($name)) {
            return back()->with('error', "Le module {$name} n'existe pas.");
        }

        if (Module::isEnabled($name)) {
            $dependents = $this->getEnabledDependents($name);
            if ($dependents !== []) {
                return back()->with('error', "Impossible de désactiver {$name} : les modules suivants en dépendent : ".implode(', ', $dependents).'.');
            }
            Module::disable($name);

            return back()->with('success', "Module {$name} désactivé.");
        }

        $missing = $this->getMissingDependencies($name);
        if ($missing !== []) {
            return back()->with('error', "Impossible d'activer {$name} : les dépendances suivantes sont inactives : ".implode(', ', $missing).'.');
        }
        Module::enable($name);

        return back()->with('success', "Module {$name} activé.");
    }

    /**
     * @param  list<array<string, mixed>>  $modules
     * @return array<string, list<string>>
     */
    private function buildDependencyMap(array $modules): array
    {
        $map = [];
        foreach ($modules as $module) {
            foreach ($module['dependencies'] as $dep) {
                $map[$dep][] = $module['name'];
            }
        }

        return $map;
    }

    /** @return list<string> */
    private function getEnabledDependents(string $name): array
    {
        /** @var array<string, array<string, mixed>> $registry */
        $registry = app('plugin.registry');
        $dependents = [];

        foreach ($registry as $moduleName => $config) {
            $deps = $config['dependencies'] ?? [];
            if (in_array($name, $deps, true) && Module::isEnabled($moduleName)) {
                $dependents[] = $moduleName;
            }
        }

        return $dependents;
    }

    /** @return list<string> */
    private function getMissingDependencies(string $name): array
    {
        /** @var array<string, array<string, mixed>> $registry */
        $registry = app('plugin.registry');
        $config = $registry[$name] ?? [];
        $dependencies = $config['dependencies'] ?? [];
        $missing = [];

        foreach ($dependencies as $dep) {
            if (! Module::has($dep) || ! Module::isEnabled($dep)) {
                $missing[] = $dep;
            }
        }

        return $missing;
    }
}
